<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\WorkOrders;
use app\models\Notifications;
use yii\helpers\Html;

class CleanupController extends Controller
{
    public function init()
    {
        parent::init();
        try {
            if (Yii::$app->db->getTableSchema('system_settings', true) !== null) {
                $settings = \app\models\SystemSettings::find()->all();
                foreach ($settings as $setting) {
                    Yii::$app->params[$setting->key] = $setting->value;
                }
            }
        } catch (\Exception $e) {
            Yii::error("CleanupController failed to load settings from DB: " . $e->getMessage());
        }
    }

    /**
     * Elimina órdenes de trabajo en estado 'pending' que han superado los días de vigencia configurados.
     * Uso: php yii cleanup/prune-work-orders
     */
    public function actionPruneWorkOrders()
    {
        $expirationDays = WorkOrders::getExpirationDaysLimit();
        echo "Iniciando limpieza de Órdenes de Trabajo (Vigencia: {$expirationDays} días)...\n";
        
        // Calculamos la fecha límite según los días configurados
        $limitDate = date('Y-m-d H:i:s', strtotime("-{$expirationDays} days"));
        
        // Buscamos: Estado 'pending' Y creadas antes del límite Y sin contrato de servicio
        $oldOrders = WorkOrders::find()
            ->where([
                'status' => WorkOrders::STATUS_PENDING,
                'is_request' => 0
            ])
            ->andWhere(['or', ['has_service_contract' => 0], ['is', 'has_service_contract', null]])
            ->andWhere(['<', 'created_at', $limitDate])
            ->all();

        $count = 0;
        $prunedList = [];

        foreach ($oldOrders as $order) {
            echo "Procesando Orden #{$order->code} (Creada: {$order->created_at})...\n";

            // 1. Notificar al cliente por email e intra-portal antes de eliminar
            $this->notifyCustomerExpiredOrder($order, $expirationDays);

            $orderCode = $order->code;
            $orderTitle = $order->title;
            $orderId = $order->id;

            if ($order->delete()) {
                // Disparar alerta push a dispositivos de administradores
                $this->triggerN8nNotification(
                    "⚠️ NOTIFICACIÓN: Orden Expirada",
                    "La orden de trabajo #{$orderCode} ha sido eliminada por superar los {$expirationDays} días de vigencia.",
                    $orderId
                );
                $count++;
                $prunedList[] = "#{$orderCode} - {$orderTitle}";
            }
        }

        $summaryBody = "<p>Se ejecutó el proceso de limpieza y expiración de órdenes de trabajo.</p>";
        $summaryBody .= "<p><strong>Días de vigencia aplicados:</strong> {$expirationDays} días.</p>";
        $summaryBody .= "<p><strong>Órdenes expiradas y eliminadas:</strong> {$count}</p>";
        if (!empty($prunedList)) {
            $summaryBody .= "<ul>";
            foreach ($prunedList as $item) {
                $summaryBody .= "<li>" . Html::encode($item) . "</li>";
            }
            $summaryBody .= "</ul>";
        }

        try {
            Yii::$app->mailer->compose()
                ->setHtmlBody($summaryBody)
                ->setTo(Yii::$app->params['adminEmail'])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setSubject("Cron Cleanup: {$count} órdenes de trabajo expiradas")
                ->send();
        } catch (\Throwable $e) {
            Yii::error("Error enviando correo resumen de cleanup al admin: " . $e->getMessage());
        }

        echo "Proceso finalizado. Se expiraron y eliminaron {$count} órdenes antiguas.\n";
    }

    /**
     * Notifica al cliente por correo y notificación interna en plataforma que su orden ha expirado.
     *
     * @param WorkOrders $order
     * @param int $expirationDays
     */
    protected function notifyCustomerExpiredOrder($order, $expirationDays)
    {
        $customer = $order->customer;
        if (!$customer) {
            return;
        }

        // 1. Notificación interna en el portal (campana de notificaciones)
        try {
            Notifications::notifyCustomer(
                $order->customer_id,
                "Orden Expirada: #{$order->code}",
                "La orden de trabajo #{$order->code} ({$order->title}) ha expirado tras superar el período de vigencia de {$expirationDays} días.",
                ['work-orders/index'],
                Notifications::TYPE_WARNING
            );
        } catch (\Throwable $ne) {
            Yii::error("Error creando notificación interna de orden expirada para cliente {$order->customer_id}: " . $ne->getMessage());
        }

        // 2. Notificación por correo electrónico al cliente
        $customerEmail = !empty($customer->email) ? trim($customer->email) : null;
        if ($customerEmail) {
            try {
                $subject = "Vigencia expirada: Orden de Trabajo #{$order->code} - {$order->title}";

                Yii::$app->mailer->compose(['html' => 'workOrderExpired-html'], [
                    'model' => $order,
                    'orderCode' => $order->code,
                    'orderTitle' => $order->title,
                    'customerName' => $customer->business_name ?? 'Estimado Cliente',
                    'createdAt' => $order->created_at,
                    'expirationDays' => $expirationDays,
                    'totalCost' => $order->total_cost,
                    'currency' => $order->currency,
                    'totalCostUsd' => $order->total_cost_usd,
                ])
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                    ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                    ->setTo($customerEmail)
                    ->setSubject($subject)
                    ->send();

                echo " - Correo de expiración enviado al cliente: {$customerEmail}\n";
            } catch (\Throwable $me) {
                Yii::error("Error enviando correo de expiración de orden {$order->code} a {$customerEmail}: " . $me->getMessage());
                echo " - Error enviando correo de expiración al cliente: " . $me->getMessage() . "\n";
            }
        }

        // 3. Enviar copia directa al administrador como evidencia (no BCC)
        try {
            $adminEmail = Yii::$app->params['adminEmail'] ?? 'gerencia@atsys.co';
            $adminEmails = !empty($adminEmail)
                ? array_map('trim', explode(',', $adminEmail))
                : ['gerencia@atsys.co'];

            $adminEmailsToSend = array_filter($adminEmails, fn($e) => strcasecmp($e, $customerEmail ?? '') !== 0);

            if (!empty($adminEmailsToSend)) {
                $recipientTag = !empty($customerEmail) ? $customerEmail : 'Sin correo';
                $adminSubject = "[Copia Admin] Vigencia expirada: Orden de Trabajo #{$order->code} - {$order->title} ({$recipientTag})";

                Yii::$app->mailer->compose(['html' => 'workOrderExpired-html'], [
                    'model' => $order,
                    'orderCode' => $order->code,
                    'orderTitle' => $order->title,
                    'customerName' => $customer->business_name ?? 'Estimado Cliente',
                    'createdAt' => $order->created_at,
                    'expirationDays' => $expirationDays,
                    'totalCost' => $order->total_cost,
                    'currency' => $order->currency,
                    'totalCostUsd' => $order->total_cost_usd,
                ])
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                    ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                    ->setTo($adminEmailsToSend)
                    ->setSubject($adminSubject)
                    ->send();

                echo " - Copia de orden expirada enviada al administrador (" . implode(', ', $adminEmailsToSend) . ")\n";
            }
        } catch (\Throwable $adminMailEx) {
            Yii::error("Error enviando copia de orden expirada al administrador: " . $adminMailEx->getMessage());
            echo " - Error enviando copia al administrador: " . $adminMailEx->getMessage() . "\n";
        }
    }

    protected function triggerN8nNotification($title, $message, $ticketId)
    {

        $tokens = \app\models\AdminTokens::find()->select('token')->column();

        if (empty($tokens)) {
            return; // No hay nadie a quien notificar
        }

        // Define aquí la URL de tu webhook de N8N
        $webhookUrl = Yii::$app->params['n8n_admin_push_url'] ?? 'https://n8n-new.atsys.co/webhook/send-admin-push';

        $data = [
            'tokens' => $tokens,
            'title' => $title,
            'body' => $message,
            'link' => "https://clientarea.atsys.co/work-orders/",
            'image' => 'https://clientarea.atsys.co/images/atsys-clientarea-og.webp'
        ];

        try {
            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Evita que el cron se cuelgue si N8N no responde

            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            echo " - Error disparando webhook: " . $e->getMessage() . "\n";
        }
    }
}