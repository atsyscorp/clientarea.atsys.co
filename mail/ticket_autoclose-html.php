<?php
use yii\helpers\Html;

/* @var $ticket app\models\Tickets */

$ticketUrl = Yii::$app->urlManager->createAbsoluteUrl(['tickets/view', 'id' => $ticket->id]);
?>
<div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <h2 style="color: #64748b;">Ticket Cerrado Automáticamente</h2>
    
    <p>Hola,</p>
    
    <p>Te informamos que el ticket <strong><?= Html::encode($ticket->ticket_code) ?></strong> (<em><?= Html::encode($ticket->subject) ?></em>) ha sido cerrado automáticamente debido a que no ha tenido actividad en las últimas <?=$hours?> horas.</p>

    <div style="background: #f3f4f6; padding: 15px; border-left: 4px solid #9ca3af; margin: 20px 0; font-size: 0.9em;">
        Si tu problema persiste o necesitas más ayuda, por favor abre un nuevo ticket o responde a este correo para reabrir el caso.
    </div>

    <p style="text-align: center; margin-top: 30px;">
        <a href="<?= $ticketUrl ?>" style="background-color: #64748b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Ver Ticket en el Portal</a>
    </p>
</div>