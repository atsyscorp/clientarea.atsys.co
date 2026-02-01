<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user app\models\User */
/* @var $link string */
?>
<div style="font-family: Arial, sans-serif;">
    <h2 style="color: #134C42;">Recuperación de Contraseña</h2>
    
    <p>Hola <?= Html::encode($user->username) ?>,</p>

    <p>Hemos recibido una solicitud para restablecer tu contraseña en el <strong>Area de clientes de ATSYS</strong>.</p>
    
    <p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p>

    <p style="margin: 30px 0;">
        <a href="<?= $link ?>" style="background-color: #134C42; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Restablecer Contraseña</a>
    </p>
    
    <p style="font-size: 12px; color: #777;">Este enlace expirará en 1 hora. Si no solicitaste esto, ignora este mensaje.</p>
</div>