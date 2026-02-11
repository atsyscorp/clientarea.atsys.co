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
            ->where(['status' => 'pending']) // Asegúrate que este sea el estado de "Propuesta enviada"
            ->andWhere(['<', 'created_at', $limitDate])
            ->all();

        $count = 0;
        foreach ($oldOrders as $order) {
            echo "Eliminando Orden #{$order->code} (Creada: {$order->created_at})...\n";
            if ($order->delete()) {
                $count++;
            }
        }

        echo "Proceso finalizado. Se eliminaron {$count} órdenes antiguas.\n";
    }
}