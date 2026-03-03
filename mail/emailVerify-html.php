<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user app\models\User */

$verifyLink = Yii::$app->urlManager->createAbsoluteUrl(['site/verify-email', 'token' => $user->verification_token]);
?>
<div style="font-family: Arial, sans-serif; color: #333;">
    <h2 style="color: #134C42;">¡Bienvenido a ATSYS!</h2>
    <p>¡Hola!</p>
    <p>Gracias por registrarte en nuestra área de clientes. Para comenzar a gestionar tus tickets, por favor confirma tu correo electrónico haciendo clic en el botón de abajo:</p>
    
    <p style="text-align: center; margin: 30px 0;">
        <a href="<?= $verifyLink ?>" style="background-color: #134C42; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Confirmar Cuenta</a>
    </p>
    
    <p>
        Si no puedes hacer clic en el botón, copia y pega el siguiente enlace en tu navegador:
    </p>
    <p style="background-color: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace;">
        <?= $verifyLink ?>
    </p>
    
    <p style="font-size: 12px; color: #777;">Si no solicitaste este registro, puedes ignorar este correo.</p>
</div>