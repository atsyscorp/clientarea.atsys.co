<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Tickets;

/**
 * Comando para tareas programadas de Tickets.
 * Uso: php yii ticket-cron/auto-close
 */
class TicketCronController extends Controller
{
    public $hoursToClose = 48; // Tiempo para cerrar tickets inactivos del cliente
    public $hoursSla = 24;     // Tiempo límite de respuesta para ATSYS

    public function init()
    {
        parent::init();
        try {
            if (Yii::$app->db->getTableSchema('system_settings', true) !== null) {
                $settings = \app\models\SystemSettings::find()->all();
                foreach ($settings as $setting) {
                    if ($setting->key === 'ticket_hours_to_close') {
                        $this->hoursToClose = (int)$setting->value;
                    } elseif ($setting->key === 'ticket_hours_sla') {
                        $this->hoursSla = (int)$setting->value;
                    }
                    Yii::$app->params[$setting->key] = $setting->value;
                }
            }
        } catch (\Exception $e) {
            Yii::error("TicketCronController failed to load settings from DB: " . $e->getMessage());
        }
    }

    /**
     * Acción principal a ejecutar en el Cron de Virtualmin
     */
    public function actionAutoClose()
    {
        echo "Iniciando proceso de revisión de tickets...\n";

        $this->processAutoClose();
        $this->processSlaAlerts();

        echo "Proceso finalizado.\n";
        return ExitCode::OK;
    }

    /**
     * 1. Auto-cierre estricto: Solo afecta tickets donde el cliente debe responder
     */
    protected function processAutoClose()
    {
        echo "1. Verificando tickets para auto-cierre...\n";
        $limitTime = date('Y-m-d H:i:s', strtotime('-' . $this->hoursToClose . ' hours'));

        // Solo buscar tickets donde ATSYS ya respondió (STATUS_ANSWERED)
        $tickets = Tickets::find()
            ->where(['<', 'updated_at', $limitTime])
            ->andWhere([
                'status' => [
                    Tickets::STATUS_ANSWERED
                ]
            ])
            ->all();

        $count = 0;
        foreach ($tickets as $ticket) {
            echo "Procesando cierre del Ticket #{$ticket->ticket_code}...\n";

            $ticket->status = Tickets::STATUS_CLOSED;

            // Guardamos sin validación estricta para asegurar el cierre
            if ($ticket->save(false)) {

                $this->sendEmailAlert($ticket);

                $count++;
                echo " - Cerrado y notificado.\n";
            } else {
                echo " - Error al cerrar.\n";
            }
        }

        echo "   Total cerrados: $count\n\n";
    }

    /**
     * 2. Alertas SLA: Avisa mediante Push si ATSYS está tardando en responder
     */
    protected function processSlaAlerts()
    {
        echo "2. Verificando alertas de SLA para soporte...\n";
        $slaLimitTime = date('Y-m-d H:i:s', strtotime('-' . $this->hoursSla . ' hours'));

        // Buscar tickets que la empresa no ha respondido a tiempo
        $ticketsEnRiesgo = Tickets::find()
            ->where(['<', 'updated_at', $slaLimitTime])
            ->andWhere([
                'status' => [
                    Tickets::STATUS_OPEN,
                    Tickets::STATUS_CUSTOMER_REPLY
                ]
            ])
            ->all();

        $countSla = 0;
        foreach ($ticketsEnRiesgo as $riesgo) {
            // Disparar alerta push a dispositivos
            $this->triggerN8nNotification(
                "⚠️ ALERTA SLA: Ticket {$riesgo->ticket_code} requiere atención",
                "El cliente lleva más de {$this->hoursSla} horas esperando una respuesta. Requiere revisión inmediata.",
                $riesgo->id
            );

            $countSla++;
            echo " - Alerta SLA enviada para el Ticket #{$riesgo->ticket_code}.\n";
        }

        echo "   Total alertas enviadas: $countSla\n\n";
    }

    protected function sendEmailAlert($ticket) {

        try {
            $sender = [Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']];
            $subject = 'Ticket Cerrado por Inactividad: ' . $ticket->ticket_code;

            // 1. Enviar al cliente
            $mail = Yii::$app->mailer->compose([
                'html' =>  'ticket_autoclose-html'
            ],[
                'ticket' => $ticket,
                'hours' => $this->hoursToClose,
            ])
            ->setTo($ticket->email)
            ->setFrom($sender)
            ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
            ->setSubject($subject);

            if(!$mail->send()) {
                Yii::error("No se pudo enviar el email al cliente: " . $ticket->email, __METHOD__);
            }

            // 2. Enviar copia al admin (en lugar de BCC para evitar bloqueos/filtros de SMTP)
            $adminEmail = Yii::$app->params['adminEmail'] ?? 'gerencia@atsys.co';
            $adminEmails = !empty($adminEmail) 
                ? array_map('trim', explode(',', $adminEmail)) 
                : ['gerencia@atsys.co'];

            $mailAdmin = Yii::$app->mailer->compose([
                'html' =>  'ticket_autoclose-html'
            ],[
                'ticket' => $ticket,
                'hours' => $this->hoursToClose,
            ])
            ->setTo($adminEmails)
            ->setFrom($sender)
            ->setSubject('[Copia Admin] ' . $subject);

            if(!$mailAdmin->send()) {
                Yii::error("No se pudo enviar la copia del email al administrador: " . implode(', ', $adminEmails), __METHOD__);
            }
        } catch(\Throwable $e) {
            Yii::error("No se pudo enviar el email.", __METHOD__ . " \n Error: " . $e->getMessage());
        }
        
    }

    /**
     * Canaliza todas las notificaciones hacia el flujo de automatización (Push)
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
            'link' => "https://clientarea.atsys.co/tickets/view?id=" . $ticketId,
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