<?php
use yii\helpers\Html;

/* @var $ticket app\models\Tickets */

$rateUrl = Yii::$app->urlManager->createAbsoluteUrl(['feedback/rate', 'ticket_id' => $ticket->ticket_code]);
$ticketUrl = Yii::$app->urlManager->createAbsoluteUrl(['tickets/view', 'id' => $ticket->id]);
?>
<div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    
    <div style="text-align: center; margin-bottom: 24px;">
        <h2 style="color: #134C42; margin: 0; font-size: 22px; font-weight: bold;">Tu Ticket ha sido Cerrado</h2>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Ticket #<?= Html::encode($ticket->ticket_code) ?> - <?= Html::encode($ticket->subject) ?></p>
    </div>

    <p style="font-size: 14px; color: #334155;">Hola,</p>
    <p style="font-size: 14px; color: #334155;">Te informamos que tu solicitud de soporte ha sido marcada como **Cerrada / Resuelta**.</p>

    <!-- Caja Destacada para la Encuesta -->
    <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: 10px; margin: 24px 0; text-align: center;">
        <h3 style="margin: 0 0 8px 0; font-size: 16px; color: #166534;">¿Cómo evalúas nuestro servicio?</h3>
        <p style="margin: 0 0 16px 0; font-size: 13px; color: #15803d;">Tu opinión es fundamental para seguir mejorando la atención que te brindamos.</p>
        <a href="<?= $rateUrl ?>" style="display: inline-block; background-color: #134C42; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            ⭐⭐⭐⭐⭐ Calificar Atención Recibida
        </a>
    </div>

    <p style="font-size: 13px; color: #64748b; text-align: center; margin-top: 20px;">
        Si consideras que tu inquietud aún no ha sido solucionada, puedes 
        <a href="<?= $ticketUrl ?>" style="color: #134C42; font-weight: bold; text-decoration: underline;">ver el ticket en el portal</a> para responder y reabrirlo automáticamente.
    </p>

    <div style="border-top: 1px solid #f1f5f9; margin-top: 24px; pt: 16px; text-align: center; font-size: 12px; color: #94a3b8;">
        &copy; <?= date('Y') ?> <?= Html::encode(Yii::$app->name) ?> - Todos los derechos reservados.
    </div>
</div>
