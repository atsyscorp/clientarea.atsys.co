<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\CustomerServices;
use app\models\Notifications;
use app\components\CyberPanel;
use yii\httpclient\Client; // Asegúrate de tener yii2-httpclient o usa curl nativo

class CronController extends Controller
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
            Yii::error("CronController failed to load settings from DB: " . $e->getMessage());
        }
    }

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

                // Notificación en plataforma
                Notifications::notifyCustomer(
                    $service->customer_id,
                    "⚠️ Servicio Suspendido: " . $service->domain,
                    "El servicio {$service->domain} ha sido suspendido por vencimiento. Por favor realiza el pago para reactivarlo.",
                    "/customer-services",
                    Notifications::TYPE_DANGER
                );

                echo "SUSPENDIDO Y NOTIFICADO.\n";
                $count++;
            } else {
                echo "ERROR API (Revisa logs).\n";
                Yii::error("Cron Job: Falló suspensión remota de {$service->domain} en servidor {$server->name}");
            }
        }

        echo "Terminado. Total procesados: $count\n";
        Yii::$app->mailer->compose()
        ->setHtmlBody('Cron completed for suspend overdue')
        ->setTo(Yii::$app->params['adminEmail'])
        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
        ->setSubject("Cron Suspended Overdue")
        ->send();
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

    /**
     * Envía recordatorios de vencimiento (30, 15, 7, 5, 1 días antes).
     * Ejecutar diariamente.
     */
    public function actionSendReminders()
    {
        echo "Iniciando envío de recordatorios...\n";

        // 1. Recordatorios preventivos (Servicios Activos que venzan en los próximos 31 días)
        $services = CustomerServices::find()
            ->with(['customer'])
            ->where(['status' => 1])
            ->andWhere(['>=', 'next_due_date', date('Y-m-d')]) // Que no estén vencidos aún
            ->andWhere(['<=', 'next_due_date', date('Y-m-d', strtotime('+31 days'))])
            ->all();

        $count = 0;
        // Días gatillo para enviar recordatorio preventivo
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

                echo "Enviando aviso preventivo de {$daysLeft} días a {$service->domain}... ";
                $this->sendRenewalReminderEmail($service, $daysLeft);

                // Notificación en plataforma
                Notifications::notifyCustomer(
                    $service->customer_id,
                    "📅 Servicio por vencer: {$service->domain}",
                    "Tu servicio {$service->domain} vence en {$daysLeft} días (" . Yii::$app->formatter->asDate($service->next_due_date, 'long') . "). Evita interrupciones renovando hoy.",
                    "/customer-services",
                    ($daysLeft <= 5) ? Notifications::TYPE_DANGER : (($daysLeft <= 15) ? Notifications::TYPE_WARNING : Notifications::TYPE_INFO)
                );

                $count++;
                echo "OK.\n";

            }
        }

        // 2. Alertas de Servicios Vencidos (Suspendidos hasta 30 días después)
        $expiredServices = CustomerServices::find()
            ->with(['customer'])
            ->where(['status' => 2]) // Suspendido
            ->andWhere(['<', 'next_due_date', date('Y-m-d')])
            ->andWhere(['>=', 'next_due_date', date('Y-m-d', strtotime('-30 days'))])
            ->all();

        // Días gatillo después de vencimiento para notificar
        $triggerExpiredDays = [1, 3, 7, 14, 21, 30];

        foreach ($expiredServices as $service) {
            $today = new \DateTime(date('Y-m-d'));
            $dueDate = new \DateTime($service->next_due_date);
            $diff = $today->diff($dueDate);
            $daysExpired = $diff->days;

            // Verificamos si hoy coincide con los días gatillo posteriores
            if ($diff->invert == 1 && in_array($daysExpired, $triggerExpiredDays)) {
                echo "Enviando aviso de vencimiento de {$daysExpired} días para {$service->domain}... ";

                // Notificación en plataforma
                Notifications::notifyCustomer(
                    $service->customer_id,
                    "🚨 Servicio vencido hace {$daysExpired} días: {$service->domain}",
                    "El servicio {$service->domain} se encuentra vencido desde el " . Yii::$app->formatter->asDate($service->next_due_date, 'long') . ". Tienes hasta 30 días después del vencimiento para renovarlo antes de su desconexión definitiva.",
                    "/customer-services",
                    Notifications::TYPE_DANGER
                );

                $count++;
                echo "OK.\n";
            }
        }

        echo "Terminado. Recordatorios y alertas enviadas: $count\n";
        Yii::$app->mailer->compose()
        ->setHtmlBody('Cron completed for send reminders')
        ->setTo(Yii::$app->params['adminEmail'])
        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
        ->setSubject("Cron Send Reminders")
        ->send();
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