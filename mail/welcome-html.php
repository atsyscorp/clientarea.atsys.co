<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user app\models\User */

$loginLink = Yii::$app->urlManager->createAbsoluteUrl(['site/login']);
?>
<div style="font-family: Arial, sans-serif; color: #333;">
    <h2 style="color: #10B981;">¡Cuenta Activada Exitosamente!</h2>
    <p>Hola <?= Html::encode($user->username) ?>,</p>
    <p>Tu cuenta en el <strong>Área de clientes</strong> ha sido verificada. Ya tienes acceso completo a la plataforma de gestión.</p>
    
    <p>Puedes ingresar ahora mismo:</p>
    <p>
        <a href="<?= $loginLink ?>">Ir al Login</a>
    </p>
    
    <p>Atentamente,<br>El equipo de ATSYS.</p>
</div>