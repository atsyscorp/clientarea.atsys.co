<?php

namespace app\commands;

use yii\console\Controller;
use app\models\WorkOrders;

class CleanupController extends Controller
{
    /**
     * Elimina órdenes de trabajo en estado 'pending' con más de 5 días de antigüedad.
     * Uso: php yii cleanup/prune-work-orders
     */
    public function actionPruneWorkOrders()
    {
        echo "Iniciando limpieza de Órdenes de Trabajo...\n";
        
        // Calculamos la fecha límite (hace 5 días)
        $limitDate = date('Y-m-d H:i:s', strtotime('-5 days'));
        
        // Buscamos: Estado 'pending' (o 1) Y creadas antes del límite
        $oldOrders = WorkOrders::find()
            ->where([
                'status' => 'pending',
                'is_request' => 0
            ]) // Asegúrate que este sea el estado de "Propuesta enviada"
            ->andWhere(['<', 'created_at', $limitDate])
            ->all();

        $count = 0;
        foreach ($oldOrders as $order) {
            echo "Eliminando Orden #{$order->code} (Creada: {$order->created_at})...\n";
            if ($order->delete()) {
                // Disparar alerta push a dispositivos
                $this->triggerN8nNotification(
                    "⚠️ NOTIFICACIÓN: Orden Eliminada",
                    "La orden de trabajo #{$order->code} ha sido eliminada por inactividad.",
                    $order->id
                );
                $count++;
            }
        }

        echo "Proceso finalizado. Se eliminaron {$count} órdenes antiguas.\n";
    }

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
}