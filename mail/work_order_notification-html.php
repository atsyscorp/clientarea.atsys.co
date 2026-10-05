<?php
use yii\helpers\Html;
/* @var $model app\models\WorkOrders */
/* @var $customMessage string|null */
/* @var $isUserRegistered bool|null */

$link = Yii::$app->urlManager->createAbsoluteUrl(['work-orders/view', 'id' => $model->id]);
$hasRegisteredUser = isset($isUserRegistered) ? $isUserRegistered : (!empty($model->customer && $model->customer->user_id));
?>
<div style="font-family: Arial, sans-serif; color: #333333; line-height: 1.6;">
    <h2 style="color: #134C42;">Nueva Propuesta de Trabajo</h2>
    <p>Hola <strong><?= Html::encode($model->customer->business_name) ?></strong>,</p>
    
    <p>Hemos generado la Orden de Trabajo <strong><?= $model->code ?></strong> correspondiente al proyecto: <strong><?= Html::encode($model->title) ?></strong>.</p>
    
    <p>Adjunto a este correo encontrarás el documento PDF oficial con el detalle de los requerimientos y la inversión.</p>

    <?php if (!empty($customMessage)): ?>
        <div style="background-color: #f8fafc; border-left: 4px solid #134C42; padding: 14px 18px; margin: 20px 0; border-radius: 0 4px 4px 0;">
            <strong style="color: #134C42; display: block; margin-bottom: 5px;">Mensaje de ATSYS:</strong>
            <p style="margin: 0; white-space: pre-line; color: #475569;"><?= Html::encode($customMessage) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($hasRegisteredUser): ?>
        <p>Para aprobarla e iniciar el desarrollo, por favor ingresa a tu área de cliente:</p>
        <p style="text-align: center; margin: 25px 0;">
            <a href="<?= $link ?>" style="background-color: #134C42; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                Ver y Aprobar Orden en Línea
            </a>
        </p>
    <?php else: ?>
        <div style="background-color: #f0fdf4; border: 1px solid #86efac; color: #166534; padding: 14px 18px; border-radius: 6px; margin: 20px 0;">
            <strong>📋 Aprobación de la Orden:</strong><br>
            Puedes revisar el documento <strong>PDF adjunto</strong> a este correo y responder directamente a este mensaje confirmando tu aprobación o indicando cualquier observación.
        </div>
    <?php endif; ?>

    <?php if ($model->has_service_contract): ?>
        <div style="background-color: #f0fdf4; border: 1px solid #86efac; color: #166534; padding: 14px 18px; border-radius: 6px; margin: 20px 0; font-size: 13px;">
            <strong>Contrato de Servicio:</strong> Esta orden de trabajo está vinculada a un contrato de servicios activo y no está sujeta a fecha límite de vencimiento.
        </div>
    <?php else: ?>
        <?php
        $days = $model->getExpirationDays();
        $expDateStr = $model->getExpirationDate('long');
        ?>
        <div style="background-color: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 14px 18px; border-radius: 6px; margin: 20px 0; font-size: 13px; line-height: 1.5;">
            <strong>⏳ Recordatorio de Vigencia:</strong> Esta propuesta tiene una vigencia de <strong><?= $days ?> días calendario</strong><?= !empty($expDateStr) ? ' (válida hasta el <strong>' . Html::encode($expDateStr) . '</strong>)' : '' ?>. 
            Transcurrido dicho plazo sin recibir aprobación, la orden expirará automáticamente. Si requieres reactivarla con posterioridad, deberás solicitar una nueva cotización.
        </div>
    <?php endif; ?>
</div>