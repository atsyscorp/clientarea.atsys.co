<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\CustomerServices;
use app\models\Notifications;
use app\models\User;
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
        $groupedSuspensions = [];

        foreach ($overdueServices as $service) {
            echo "Procesando: {$service->domain}... ";

            if (!$service->customer) {
                echo "SALTADO (Sin cliente asignado).\n";
                continue;
            }

            // 2. Identificar Servidor y Tipo
            $server = $service->server;

            // Fallback: Si no tiene server_id directo, intentamos el del producto
            if (!$server && $service->product && $service->product->server_id) {
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

                // Agrupamos la suspensión para mandar 1 solo correo por cliente
                $customerId = $service->customer_id;
                if (!isset($groupedSuspensions[$customerId])) {
                    $groupedSuspensions[$customerId] = [
                        'customer' => $service->customer,
                        'services' => []
                    ];
                }
                $groupedSuspensions[$customerId]['services'][] = $service;

                // Notificación push a los admins individual por servicio
                $this->triggerN8nNotification(
                    "⚠️ Servicio Suspendido",
                    "El servicio {$service->domain} ha sido suspendido.",
                    $service->id
                );

                // Notificación en plataforma individual (para que se vea la lista)
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

        // Enviar un solo email agrupado de suspensión por cliente
        foreach ($groupedSuspensions as $customerId => $data) {
            $this->sendGroupedSuspensionEmail($data['customer'], $data['services']);
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
     * Envía el correo de advertencia usando la plantilla de Yii2 (agrupado)
     */
    private function sendGroupedSuspensionEmail($customer, $servicesData)
    {
        try {
            $multiple = count($servicesData) > 1;
            $subject = $multiple ? "⚠️ Servicios Suspendidos" : "⚠️ Servicio Suspendido: {$servicesData[0]->domain}";

            Yii::$app->mailer->compose(['html' => 'overdue_hosting-html'], [
                'business_name' => $customer->business_name,
                'servicesData' => $servicesData
            ])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                ->setTo($customer->email)
                ->setSubject($subject)
                ->send();

        } catch (\Throwable $e) {
            echo "Error enviando email: " . $e->getMessage() . "\n";
            Yii::error("Error enviando email suspensión agrupado: " . $e->getMessage());
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

        // 1. Recordatorios preventivos (Servicios Activos que venzan en los próximos 91 días)
        $services = CustomerServices::find()
            ->with(['customer'])
            ->where(['status' => 1])
            ->andWhere(['>=', 'next_due_date', date('Y-m-d')]) // Que no estén vencidos aún
            ->andWhere(['<=', 'next_due_date', date('Y-m-d', strtotime('+91 days'))])
            ->all();

        $count = 0;
        // Días gatillo para enviar recordatorio preventivo (desde 90 días antes)
        $triggerDays = [90, 60, 30, 20, 15, 10, 7, 5, 2, 1, 0];

        $groupedReminders = [];

        foreach ($services as $service) {
            if (!$service->customer) {
                continue;
            }

            // Calcular días faltantes
            $today = new \DateTime(date('Y-m-d'));
            $dueDate = new \DateTime(substr($service->next_due_date, 0, 10));
            $diff = $today->diff($dueDate);
            $daysLeft = $diff->days;

            // Verificamos si hoy coincide con uno de los días gatillo
            if ($diff->invert == 0 && in_array($daysLeft, $triggerDays)) {
                $customerId = $service->customer->id;
                if (!isset($groupedReminders[$customerId])) {
                    $groupedReminders[$customerId] = [
                        'customer' => $service->customer,
                        'services' => []
                    ];
                }
                $groupedReminders[$customerId]['services'][] = [
                    'model' => $service,
                    'daysLeft' => $daysLeft,
                    'date_long' => Yii::$app->formatter->asDate($service->next_due_date, 'long')
                ];

                // Notificación en plataforma (individual sigue siendo útil para que el cliente las vea separadas)
                $notifTitle = $daysLeft == 0 ? "🚨 Servicio vence HOY: {$service->domain}" : "📅 Servicio por vencer: {$service->domain}";
                $notifBody = $daysLeft == 0
                    ? "Tu servicio {$service->domain} vence el día de hoy (" . Yii::$app->formatter->asDate($service->next_due_date, 'long') . "). Evita interrupciones renovando de inmediato."
                    : "Tu servicio {$service->domain} vence en {$daysLeft} días (" . Yii::$app->formatter->asDate($service->next_due_date, 'long') . "). Evita interrupciones renovando hoy.";

                Notifications::notifyCustomer(
                    $service->customer_id,
                    $notifTitle,
                    $notifBody,
                    "/customer-services",
                    ($daysLeft <= 5) ? Notifications::TYPE_DANGER : (($daysLeft <= 15) ? Notifications::TYPE_WARNING : Notifications::TYPE_INFO)
                );
            }
        }

        // Enviar correos agrupados preventivos
        $bccEmail = Yii::$app->params['renewalAlertBccEmail'] ?? (Yii::$app->params['adminEmail'] ?? null);
        foreach ($groupedReminders as $customerId => $data) {
            $bccText = !empty($bccEmail) ? " (con copia BCC a {$bccEmail})" : "";
            echo "Enviando aviso preventivo agrupado a {$data['customer']->email}{$bccText} (" . count($data['services']) . " servicios)... ";
            $this->sendGroupedRenewalReminderEmail($data['customer'], $data['services']);
            $count += count($data['services']);
            echo "OK.\n";
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
            if (!$service->customer) {
                continue;
            }

            $today = new \DateTime(date('Y-m-d'));
            $dueDate = new \DateTime(substr($service->next_due_date, 0, 10));
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
     * Genera el correo de recordatorio agrupado con urgencia dinámica
     */
    private function sendGroupedRenewalReminderEmail($customer, $servicesData)
    {
        try {
            $minDaysLeft = 999;
            foreach ($servicesData as $data) {
                if ($data['daysLeft'] < $minDaysLeft) {
                    $minDaysLeft = $data['daysLeft'];
                }
            }

            $multiple = count($servicesData) > 1;

            if ($minDaysLeft == 0) {
                $subject = $multiple ? "🚨 HOY vencen " . count($servicesData) . " de tus servicios" : "🚨 HOY vence tu servicio: {$servicesData[0]['model']->domain}";
                $color = "#dc2626"; // Rojo
                $msgIntro = $multiple ? "Tienes servicios que vencen el día de hoy. Por favor renueva de inmediato para evitar la suspensión." : "Tu servicio vence el día de hoy. Por favor renueva de inmediato para evitar la suspensión.";
            } elseif ($minDaysLeft <= 5) {
                $subject = $multiple ? "🚨 ÚLTIMO AVISO: Tienes servicios por vencer en {$minDaysLeft} días" : "🚨 ÚLTIMO AVISO: Tu servicio vence en {$minDaysLeft} días";
                $color = "#dc2626"; // Rojo
                $msgIntro = $multiple ? "Es urgente que renueves para evitar la suspensión y desconexión de tus servicios." : "Es urgente que renueves para evitar la suspensión y desconexión de tu sitio.";
            } elseif ($minDaysLeft <= 15) {
                $subject = $multiple ? "⚠️ Recordatorio: Tienes servicios que vencen pronto" : "⚠️ Recordatorio: {$servicesData[0]['model']->domain} vence pronto";
                $color = "#d97706"; // Naranja
            } elseif ($minDaysLeft <= 30) {
                $subject = $multiple ? "📅 Próximo vencimiento de tus servicios ({$minDaysLeft} días)" : "📅 Próximo vencimiento: {$servicesData[0]['model']->domain} ({$minDaysLeft} días)";
                $color = "#2563eb"; // Azul
                $msgIntro = "Este es un aviso preventivo para programar tu renovación.";
            } else {
                $subject = $multiple ? "📅 Aviso Preventivo: Renovación en {$minDaysLeft} días" : "📅 Aviso Preventivo: {$servicesData[0]['model']->domain} vence en {$minDaysLeft} días";
                $color = "#0284c7"; // Azul celeste informativo
                $msgIntro = "Te enviamos este aviso con anticipación para que puedas planificar la renovación de tus servicios y agendarlos en tu calendario.";
            }

            $renewLink = "https://clientarea.atsys.co/customer-services/";
            $bccEmail = Yii::$app->params['renewalAlertBccEmail'] ?? (Yii::$app->params['adminEmail'] ?? null);

            $mail = Yii::$app->mailer->compose([
                'html' => 'renewal_alert-html'
            ], [
                'daysLeft' => $minDaysLeft,
                'business_name' => $customer->business_name,
                'msgIntro' => $msgIntro,
                'servicesData' => $servicesData,
                'renewLink' => $renewLink,
                'color' => $color
            ])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                ->setTo($customer->email);

            if (!empty($bccEmail)) {
                $mail->setBcc($bccEmail);
            }

            $mail->setSubject($subject)
                ->send();

        } catch (\Throwable $e) {
            Yii::error("Error enviando recordatorio agrupado: " . $e->getMessage());
        }
    }

    /**
     * Envía un correo electrónico resumen (digest) con las notificaciones pendientes sin leer
     * a cada usuario/cliente que tenga notificaciones no enviadas previamente por correo.
     * Ejecutar periódicamente (ej. 1 o 2 veces al día).
     */
    public function actionSendNotificationDigest()
    {
        echo "Iniciando envío de correo resumen de notificaciones...\n";

        // Buscar todas las notificaciones pendientes de leer (is_read = 0) y no enviadas por email (email_sent = 0)
        $unreadNotifications = Notifications::find()
            ->where(['is_read' => 0, 'email_sent' => 0])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        if (empty($unreadNotifications)) {
            echo "No hay notificaciones pendientes por procesar.\n";
            return ExitCode::OK;
        }

        // Agrupar notificaciones por user_id
        $groupedByUser = [];
        foreach ($unreadNotifications as $notif) {
            $groupedByUser[$notif->user_id][] = $notif;
        }

        $sentCount = 0;
        $userCount = 0;

        foreach ($groupedByUser as $userId => $userNotifications) {
            $user = User::findOne($userId);
            if (!$user || empty($user->email)) {
                echo "SALTADO (Usuario ID {$userId} no encontrado o sin email válido).\n";
                continue;
            }

            $countNotifs = count($userNotifications);
            echo "Enviando resumen a {$user->email} ({$countNotifs} notificaciones)... ";

            try {
                $subject = "🔔 Resumen: tienes {$countNotifs} " . ($countNotifs === 1 ? 'novedad pendiente' : 'novedades pendientes') . " en tu área de cliente";

                $mailer = Yii::$app->mailer->compose(['html' => 'notification_digest-html'], [
                    'user' => $user,
                    'notifications' => $userNotifications
                ])
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName'] ?? Yii::$app->name])
                    ->setReplyTo(Yii::$app->params['adminEmail'] ?? 'gerencia@atsys.co')
                    ->setTo($user->email)
                    ->setSubject($subject);

                if ($mailer->send()) {
                    // Marcar notificaciones como enviadas por email
                    $ids = array_map(fn($n) => $n->id, $userNotifications);
                    Notifications::updateAll(['email_sent' => 1], ['in', 'id', $ids]);

                    $sentCount += $countNotifs;
                    $userCount++;
                    echo "OK.\n";
                } else {
                    echo "ERROR AL ENVIAR MAIL.\n";
                }
            } catch (\Throwable $e) {
                Yii::error("Error enviando correo digest de notificaciones a user {$userId}: " . $e->getMessage());
                echo "EXCEPCIÓN: " . $e->getMessage() . "\n";
            }
        }

        echo "Proceso finalizado. Se enviaron {$sentCount} notificaciones en {$userCount} correos resumen.\n";
        return ExitCode::OK;
    }

    /**
     * Revisa el uso de disco de todas las cuentas y envía advertencias si exceden el límite.
     * Ejecutar diariamente.
     */
    public function actionCheckDiskUsage()
    {
        echo "Iniciando revisión de cuotas de disco...\n";

        // Obtener todos los servidores tipo virtualmin activos
        $servers = \app\models\Servers::find()
            ->where(['is_active' => 1, 'type' => 'virtualmin'])
            ->all();

        $count = 0;
        $notified = 0;

        foreach ($servers as $server) {
            echo "Consultando servidor: {$server->name}...\n";

            try {
                // Ejecutar list-domains remoto (puede demorar con muchas cuentas)
                $result = Yii::$app->virtualmin->sendCommandDynamic(
                    $server->username,
                    $server->auth_token,
                    $server->hostname,
                    'list-domains',
                    ['multiline' => ''],
                    300
                );

                if ($result['success'] && !empty($result['data'])) {
                    foreach ($result['data'] as $domainData) {
                        $domainName = $domainData['name'] ?? null;
                        if (!$domainName)
                            continue;

                        $values = $domainData['values'] ?? [];

                        // Diferentes versiones de Virtualmin devuelven distintas llaves (y formateadas en GiB, MiB, etc)
                        $quota = 0;
                        $used = 0;
                        if (!empty($values['server_byte_quota'][0])) {
                            $quota = $this->convertToBytes($values['server_byte_quota'][0]);
                            $used = $this->convertToBytes($values['server_byte_quota_used'][0] ?? '0');
                        } elseif (!empty($values['server_quota'][0])) {
                            $quota = $this->convertToBytes($values['server_quota'][0]);
                            $used = $this->convertToBytes($values['server_quota_used'][0] ?? '0');
                        } elseif (!empty($values['byte_quota'][0])) {
                            $quota = $this->convertToBytes($values['byte_quota'][0]);
                            $used = $this->convertToBytes($values['byte_quota_used'][0] ?? '0');
                        }

                        if ($quota > 0) {
                            $percentage = ($used / $quota) * 100;
                            echo " - $domainName: " . round($percentage) . "% usado\n";

                            if ($percentage >= 90) {
                                $this->processDiskWarning($domainName, $percentage);
                                $notified++;
                            }
                        } else {
                            echo " - $domainName: Cuota ilimitada o no detectada.\n";
                        }
                        $count++;
                    }
                } else {
                    echo "No se pudo obtener datos del servidor {$server->name}. Respuesta de API: \n";
                    print_r($result);
                }
            } catch (\Exception $e) {
                Yii::error("Cron Disk Usage Error [{$server->name}]: " . $e->getMessage());
                echo "ERROR CONEXIÓN con {$server->name}. Mensaje: " . $e->getMessage() . "\n";
            }
        }

        echo "Terminado. Dominios procesados: $count. Notificaciones enviadas: $notified.\n";
        return \yii\console\ExitCode::OK;
    }

    /**
     * Procesa la alerta de uso de disco para un dominio.
     */
    private function processDiskWarning($domainName, $percentage)
    {
        // Buscar el servicio activo correspondiente
        $service = \app\models\CustomerServices::find()
            ->where(['domain' => $domainName, 'status' => 1])
            ->one();

        if (!$service)
            return;

        $isFull = $percentage >= 100;
        $isCritical = $percentage >= 90;

        // Evitar notificaciones duplicadas usando Notifications
        $title = $isFull ? "⚠️ Alerta urgente: Espacio lleno en $domainName" :
            ($isCritical ? "⚠️ Aviso de espacio en $domainName próximo a llenarse" : "⚠️ Aviso de espacio en $domainName");
        $type = $isFull ? \app\models\Notifications::TYPE_DANGER :
            ($isCritical ? \app\models\Notifications::TYPE_WARNING : \app\models\Notifications::TYPE_INFO);

        $customer = $service->customer;
        if (!$customer)
            return;

        $userId = $customer->user_id;

        if ($userId) {
            $daysToWait = $isFull ? 3 : ($isCritical ? 7 : 14);

            $recentNotification = \app\models\Notifications::find()
                ->where(['user_id' => $userId, 'title' => $title])
                ->andWhere(['>=', 'created_at', date('Y-m-d H:i:s', strtotime("-$daysToWait days"))])
                ->exists();

            if ($recentNotification) {
                return; // Ya notificado recientemente
            }
        }

        // Enviar Correo
        try {
            Yii::$app->mailer->compose(['html' => 'quota_warning-html'], [
                'business_name' => $customer->business_name,
                'domain' => $domainName,
                'usagePercentage' => $percentage,
                'isCritical' => $isCritical
            ])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                //->setTo($customer->email)
                ->setTo(Yii::$app->params['adminEmail'])
                ->setSubject($title)
                ->send();
        } catch (\Exception $e) {
            Yii::error("Error enviando email de alerta de espacio: " . $e->getMessage());
        }

        // Crear notificación en sistema
        $body = $isFull ? "El espacio en disco para el dominio $domainName ha alcanzado el 100%. Recomendamos limpiar espacio o actualizar el plan."
            : "El espacio en disco para el dominio $domainName ha superado el " . round($percentage) . "%.";

        \app\models\Notifications::notifyCustomer(
            $customer->id,
            $title,
            $body,
            "/customer-services",
            $type
        );

        echo " -> Notificación enviada a {$domainName} (" . round($percentage) . "%)\n";
    }

    /**
     * Convierte strings de Virtualmin (ej. "3 GiB", "500 MiB") a bytes para calculo preciso.
     */
    private function convertToBytes($sizeString)
    {
        $sizeString = trim($sizeString);
        if (preg_match('/^([\d\.]+)\s*(GiB|MiB|KiB|TiB|GB|MB|KB|TB|bytes|B)?$/i', $sizeString, $matches)) {
            $value = (float) $matches[1];
            $unit = strtoupper($matches[2] ?? '');
            switch ($unit) {
                case 'TIB':
                case 'TB':
                    return $value * 1024 * 1024 * 1024 * 1024;
                case 'GIB':
                case 'GB':
                    return $value * 1024 * 1024 * 1024;
                case 'MIB':
                case 'MB':
                    return $value * 1024 * 1024;
                case 'KIB':
                case 'KB':
                    return $value * 1024;
                default:
                    return $value;
            }
        }
        return (float) $sizeString;
    }
}
