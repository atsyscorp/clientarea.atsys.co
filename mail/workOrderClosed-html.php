<?php
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\WorkOrders */

$orderLink = Url::to(['work-orders/view', 'id' => $model->id], true);
?>

<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333;">
    
    <p style="font-size: 16px; margin-bottom: 20px;">
        Hola, <strong><?= Html::encode($model->customer->business_name ?? 'Cliente Estimado') ?></strong>.
    </p>

    <p>Nos complace informarte que la orden de trabajo <strong>#<?= Html::encode($model->code) ?></strong> ha sido completada exitosamente.</p>

    <div style="border-left: 4px solid #28a745; background-color: #f0fff4; padding: 15px; margin: 25px 0; border-radius: 4px;">
        <p style="margin: 0; color: #155724; font-size: 15px;">
            ✅ <strong>Misión Cumplida:</strong> En ATSYS confirmamos que hemos <strong>cumplido con el trabajo solicitado dentro de los tiempos establecidos</strong> y bajo los estándares de calidad acordados.
        </p>
    </div>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; color: #777; width: 40%;">Referencia:</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;"><?= Html::encode($model->code) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; color: #777;">Servicio:</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;"><?= Html::encode($model->title) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; color: #777;">Fecha de Cierre:</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;"><?= Yii::$app->formatter->asDate(date('Y-m-d'), 'long') ?></td>
        </tr>
    </table>

    <p style="color: #666; margin-bottom: 25px;">
        Puedes revisar los detalles técnicos, descargar informes o documentos adjuntos ingresando directamente a tu panel de cliente.
    </p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="<?= $orderLink ?>" style="background-color: #28a745; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
            Ver Orden en ATSYS
        </a>
    </div>

    <p style="font-size: 12px; color: #999; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px;">
        Si tienes alguna duda sobre este trabajo, por favor no dudes en crear un ticket de soporte.
    </p>
</div>