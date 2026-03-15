<?php
use yii\helpers\Html;
/* @var $model app\models\WorkOrders */

$link = Yii::$app->urlManager->createAbsoluteUrl(['work-orders/view', 'id' => $id]);
?>
<div style="font-family: Arial, sans-serif;">
    <h2 style="color: #4F46E5;">Nueva Solicitud de Órden de Trabajo</h2>
    <p>Hola!</p>
    <p>El cliente <strong><?= Html::encode($customer->business_name) ?></strong> ha solicitado que se genere una órden de trabajo con la siguiente información correspondiente al proyecto: <strong><?= Html::encode($title) ?></strong>.</p>
    <p>Código: <strong><?= $code ?></strong></p>
    <p>Título: <strong><?=$title?></strong></p>
    <p>Requerimientos:</p>
    <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #ccc;">
        <?=$requirements?>
    </div>
    
    <p style="text-align: center; margin: 30px 0;">
        <a href="<?= $link ?>" style="background-color: #4F46E5; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;">
            Ver detalles de la Orden
        </a>
    </p>
</div>