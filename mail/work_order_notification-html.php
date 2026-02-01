<?php
use yii\helpers\Html;
/* @var $model app\models\WorkOrders */

$link = Yii::$app->urlManager->createAbsoluteUrl(['work-orders/view', 'id' => $model->id]);
?>
<div style="font-family: Arial, sans-serif;">
    <h2 style="color: #4F46E5;">Nueva Propuesta de Trabajo</h2>
    <p>Hola <strong><?= Html::encode($model->customer->business_name) ?></strong>,</p>
    
    <p>Hemos generado la Orden de Trabajo <strong><?= $model->code ?></strong> correspondiente al proyecto: <strong><?= Html::encode($model->title) ?></strong>.</p>
    
    <p>Adjunto a este correo encontrarás el documento PDF con el detalle de los requerimientos y la inversión.</p>
    
    <p>Para aprobarla e iniciar el desarrollo, por favor ingresa a tu área de cliente:</p>
    
    <p style="text-align: center; margin: 30px 0;">
        <a href="<?= $link ?>" style="background-color: #4F46E5; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;">
            Ver y Aprobar Orden
        </a>
    </p>

    <div style='background-color: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 15px; border-radius: 5px; margin: 20px 0;'>
        <strong>Importante:</strong> Esta órden tendrá una vigencia de cinco (5) días, trascurrido el plazo se eliminará y no hay manera de restaurarla. Si se requiere la misma orden, debe generarse una nueva.
    </div>
</div>