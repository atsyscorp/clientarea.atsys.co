<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $ticket app\models\Tickets */
/* @var $message string */
/* @var $user app\models\User */

$adminUrl = Yii::$app->urlManager->createAbsoluteUrl(['tickets/view', 'id' => $ticket->id]);
?>
<div style="font-family: Arial, sans-serif; color: #333;">
    <h3 style="color: #d97706;">🔔 Nuevo Ticket de Soporte</h3>
    
    <p>El cliente <strong><?= Html::encode($user->username) ?></strong> (<?= Html::encode($user->email) ?>) ha abierto un ticket.</p>
    
    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Código:</strong></td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;"><?= Html::encode($ticket->ticket_code) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Asunto:</strong></td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;"><?= Html::encode($ticket->subject) ?></td>
        </tr>
    </table>

    <div style="background: #fffbeb; padding: 15px; border: 1px solid #fcd34d; border-radius: 5px; margin-bottom: 20px;">
        <strong>Mensaje:</strong><br>
        <?= nl2br(Html::encode($message)) ?>
    </div>

    <p>
        <a href="<?= $adminUrl ?>" style="font-weight: bold;">Ir a responder &rarr;</a>
    </p>
</div>