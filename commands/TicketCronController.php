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
    public $hours = 48;
    /**
     * Cierra tickets con más de 72 horas de inactividad.
     */
    public function actionAutoClose()
    {
        $hours = $this->hours;
        echo "Iniciando proceso de cierre automático...\n";

        // 1. Definir el límite de tiempo (Hace $hours horas)
        $limitTime = date('Y-m-d H:i:s', strtotime('-'.$hours.' hours'));

        // 2. Buscar tickets:
        // - Estado: ABIERTO
        // - Última actualización (updated_at): Anterior al límite
        // IMPORTANTE: Asumimos que cuando respondes un ticket, se actualiza 'updated_at'.
        // Si no tienes updated_at, usa created_at, pero ojo con ignorar respuestas recientes.
        $tickets = Tickets::find()
            ->where(['<', 'updated_at', $limitTime]) // O created_at si no usas updated_at
            ->andWhere(['status' => [
                Tickets::STATUS_OPEN,
                Tickets::STATUS_ANSWERED,
                Tickets::STATUS_CUSTOMER_REPLY
            ]])
            ->all();

        $count = 0;

        foreach ($tickets as $ticket) {
            echo "Procesando Ticket #{$ticket->ticket_code}...\n";

            $ticket->status = Tickets::STATUS_CLOSED;
            
            // Guardamos sin validación estricta para asegurar el cierre
            if ($ticket->save(false)) {
                
                // Enviar correo de notificación
                $this->sendNotification($ticket);
                
                $count++;
                echo " - Cerrado y notificado.\n";
            } else {
                echo " - Error al cerrar.\n";
            }
        }

        echo "Proceso finalizado. Total cerrados: $count\n";

        return ExitCode::OK;
    }

    protected function sendNotification($ticket)
    {
        if (empty($ticket->email)) return;

        try {
            Yii::$app->mailer->compose(['html' => 'ticket_autoclose-html'], ['ticket' => $ticket, 'hours' => $this->hours])
                ->setFrom([Yii::$app->params['senderEmail'] => 'Soporte ATSYS'])
                ->setTo($ticket->email)
                ->setBcc(Yii::$app->params['adminEmail'])
                ->setSubject("Ticket Cerrado por Inactividad: {$ticket->ticket_code}")
                ->send();
        } catch (\Exception $e) {
            echo " - Error enviando email: " . $e->getMessage() . "\n";
        }
    }
}