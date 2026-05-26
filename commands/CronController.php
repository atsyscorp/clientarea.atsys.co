<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\CustomerServices;
use app\components\CyberPanel;
use yii\httpclient\Client; // Asegúrate de tener yii2-httpclient o usa curl nativo

class CronController extends Controller
{
    /**
     * Revisa servicios vencidos, los suspende y notifica.
     * Ejecutar diariamente (03:00 AM).
     */
    public function actionSuspendOverdue()
    {
        echo "Iniciando revisión de cuentas vencidas...\n";

        // 1. Buscar servicios ACTIVOS y VENCIDOS que tengan un servidor asignado
        // Usamos 'with' para traer el servidor y el cliente en una sola consulta
        $overdueServices = CustomerServices::find()
            ->with(['customer', 'product', 'server'])
            ->where(['status' => 1])
            ->andWhere(['<', 'next_due_date', date('Y-m-d')])
            ->all();

        $count = 0;

        foreach ($overdueServices as $service) {
            echo "Procesando: {$service->domain}... ";

            // 2. Identificar Servidor y Tipo
            $server = $service->server;

            // Fallback: Si no tiene server_id directo, intentamos el del producto
            if (!$server && $service->product->server_id) {
                $server = \app\models\Servers::findOne($service->product->server_id);
            }

            if (!$server) {
                echo "SALTADO (Sin servidor configurado).\n";
                continue;
            }

            $apiSuccess = false;

            // 3. Ejecutar Suspensión según el tipo de Panel
            try {
                if ($server->type === 'virtualmin') {
                    $result = Yii::$app->virtualmin->sendCommandDynamic(
                        $server->username,
                        $server->auth_token,
                        $server->hostname,
                        'disable-domain',
                        ['domain' => $service->domain]
                    );
                    $apiSuccess = $result['success'];
                } elseif ($server->type === 'cyberpanel') {
                    $apiSuccess = CyberPanel::suspendAccount($server->id, $service->domain);
                }
            } catch (\Exception $e) {
                Yii::error("Cron Suspend Error [{$service->domain}]: " . $e->getMessage());
                echo "ERROR CONEXIÓN.\n";
                continue;
            }

            // 4. Procesar resultado de la API
            if ($apiSuccess) {
                $service->status = 2; // Suspendido
                $service->save(false);

                // Notificaciones (Solo si no es modo silencioso, aunque en cron suele ser activo siempre)
                $this->sendSuspensionEmail($service);
                $this->triggerN8nNotification(
                    "⚠️ Servicio Suspendido",
                    "El servicio {$service->domain} ha sido suspendido.",
                    $service->id
                );

                echo "SUSPENDIDO Y NOTIFICADO.\n";
                $count++;
            } else {
                echo "ERROR API (Revisa logs).\n";
                Yii::error("Cron Job: Falló suspensión remota de {$service->domain} en servidor {$server->name}");
            }
        }

        echo "Terminado. Total procesados: $count\n";
        return ExitCode::OK;
    }

    /**
     * Envía el correo de advertencia usando la plantilla de Yii2
     */
    private function sendSuspensionEmail($service)
    {
        try {
            $customer = $service->customer;
            $subject = "⚠️ Servicio Suspendido: {$service->domain}";

            Yii::$app->mailer->compose(['html' => 'overdue_hosting-html'], [
                'business_name' => $customer->business_name,
                'domain' => $service->domain,
                'due_date' => $service->next_due_date
            ])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo($customer->email)
                ->setSubject($subject)
                ->setBcc(Yii::$app->params['adminEmail'])
                ->send();

        } catch (\Exception $e) {
            echo "Error enviando email: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Dispara el Webhook de N8N para enviar WhatsApp
     */
    protected function triggerN8nNotification($title, $message, $ticketId)
    {

        $tokens = \app\models\AdminTokens::find()->select('token')->column();

        if (empty($tokens)) {
            return; // No hay nadie a quien notificar
        }

        // Define aquí la URL de tu webhook de N8N
        $webhookUrl = 'https://n8n.atsys.co/webhook/send-admin-push';

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

    /**
     * Envía recordatorios de vencimiento (30, 15, 7, 5, 1 días antes).
     * Ejecutar diariamente.
     */
    public function actionSendReminders()
    {
        echo "Iniciando envío de recordatorios...\n";

        // Buscamos servicios ACTIVOS que venzan en los próximos 31 días
        // (No tiene sentido buscar más allá)
        $services = CustomerServices::find()
            ->with(['customer'])
            ->where(['status' => 1])
            ->andWhere(['>=', 'next_due_date', date('Y-m-d')]) // Que no estén vencidos aún
            ->andWhere(['<=', 'next_due_date', date('Y-m-d', strtotime('+31 days'))])
            ->all();

        $count = 0;
        // Días gatillo para enviar correo
        $triggerDays = [30, 20, 15, 10, 7, 5, 2, 1];

        foreach ($services as $service) {
            // Calcular días faltantes
            $today = new \DateTime(date('Y-m-d'));
            $dueDate = new \DateTime($service->next_due_date);
            $diff = $today->diff($dueDate);
            $daysLeft = $diff->days;

            // Verificamos si hoy coincide con uno de los días gatillo
            // (El diff->invert == 0 asegura que sea fecha futura)
            if ($diff->invert == 0 && in_array($daysLeft, $triggerDays)) {

                echo "Enviando aviso de {$daysLeft} días a {$service->domain}... ";
                $this->sendRenewalReminderEmail($service, $daysLeft);
                $count++;
                echo "OK.\n";

            }
        }

        echo "Terminado. Recordatorios enviados: $count\n";
        return ExitCode::OK;
    }

    /**
     * Genera el correo de recordatorio con urgencia dinámica
     */
    private function sendRenewalReminderEmail($service, $daysLeft)
    {
        try {
            $customer = $service->customer;

            // Personalización según urgencia
            if ($daysLeft <= 5) {
                $subject = "🚨 ÚLTIMO AVISO: Tu servicio vence en {$daysLeft} días";
                $color = "#dc2626"; // Rojo
                $msgIntro = "Es urgente que renueves para evitar la suspensión y desconexión de tu sitio.";
            } elseif ($daysLeft <= 15) {
                $subject = "⚠️ Recordatorio: {$service->domain} vence pronto";
                $color = "#d97706"; // Naranja
                $msgIntro = "Te recordamos que la fecha de renovación se acerca.";
            } else {
                $subject = "📅 Próximo vencimiento de servicios";
                $color = "#2563eb"; // Azul
                $msgIntro = "Este es un aviso preventivo para programar tu renovación.";
            }

            $renewLink = "https://clientarea.atsys.co/customer-services/"; // O link directo al pago si lo tienes

            Yii::$app->mailer->compose([
                'html' => 'renewal_alert-html'
            ], [
                'daysLeft' => $daysLeft,
                'business_name' => $customer->business_name,
                'msgIntro' => $msgIntro,
                'domain' => $service->domain,
                'date_long' => Yii::$app->formatter->asDate($service->next_due_date, 'long'),
                'renewLink' => $renewLink,
                'color' => $color
            ])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo($customer->email)
                ->setSubject($subject)
                ->setBcc(Yii::$app->params['adminEmail'])
                ->send();

        } catch (\Exception $e) {
            Yii::error("Error enviando recordatorio: " . $e->getMessage());
        }
    }
}