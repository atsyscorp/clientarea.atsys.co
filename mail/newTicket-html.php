<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $ticket app\models\Tickets */
/* @var $message string */

$ticketUrl = Yii::$app->urlManager->createAbsoluteUrl(['tickets/view', 'id' => $ticket->id]);
$linkRegistro = Yii::$app->urlManager->createAbsoluteUrl(['signup']);
?>
<div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <h2 style="color: #4F46E5;">¡Hemos recibido tu solicitud!</h2>
    <p>Hola,</p>
    <p>Se ha creado un nuevo ticket de soporte con el siguiente código:</p>
    
    <div style="background: #f3f4f6; padding: 15px; border-left: 4px solid #4F46E5; margin: 20px 0;">
        <strong>Código:</strong> <?= Html::encode($ticket->ticket_code) ?><br>
        <strong>Asunto:</strong> <?= Html::encode($ticket->subject) ?>
    </div>

    <?php if($ticket->customer_id !== null) { ?>

    <p>Nuestro equipo ya está revisando tu caso. Puedes ver el estado y responder directamente en la plataforma:</p>

    <p style="margin: 30px 0;">
        <a href="<?= $ticketUrl ?>" style="background-color: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Ver Mi Ticket</a>
    </p>

    <?php } else { ?>
    <div style='background-color: #fff7ed; border-left: 4px solid #f97316; padding: 15px; margin: 20px 0;'>
        <h3 style='margin-top: 0; color: #c2410c; font-size: 16px;'>⚠️ Aviso: No tienes cuenta de cliente</h3>
        <p style='margin-bottom: 0; font-size: 14px;'>
            Notamos que generaste este ticket como invitado. Nuestro equipo te responderá a este correo electrónico, 
            <strong>pero no podrás consultar el historial ni ver las actualizaciones en nuestra plataforma</strong> a menos que te registres.
        </p>
    </div>

    <p>Para asociar este y futuros casos a tu perfil, te recomendamos crear tu cuenta ahora mismo:</p>

    <div style='text-align: center; margin: 30px 0;'>
        <a href='<?=$linkRegistro?>' style='background-color: #2563eb; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;'>
            Crear mi Cuenta de Cliente
        </a>
    </div>

    <hr style='border: 0; border-top: 1px solid #eee; margin: 30px 0;'>
    
    <p style='font-size: 12px; color: #999;'>
        Si prefieres no registrarte, no te preocupes. Te seguiremos atendiendo vía email, aunque el proceso podría ser un poco más lento.
    </p>
    <?php } ?>
    
    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #777;">Detalle de tu mensaje:<br><em><?= nl2br(Html::encode($message)) ?></em></p>
</div>