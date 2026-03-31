<?php
use yii\helpers\Html;
use yii\helpers\Url;

// $model contiene los datos del delegado
// $titular contiene los datos de la cuenta principal
// $plainPassword contiene la contraseña en texto plano
?>
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2>¡Hola, <?= Html::encode($model->contact_name ?? 'Usuario') ?>!</h2>
    
    <p>Has sido invitado por <strong><?= Html::encode($titular->contact_name ?? $titular->email) ?></strong> para gestionar las solicitudes de soporte en la plataforma de ATSYS.</p>
    
    <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0;"><strong>Tus credenciales de acceso:</strong></p>
        <p style="margin: 0 0 5px 0;"><strong>Usuario/Email:</strong> <?= Html::encode($model->email) ?></p>
        <p style="margin: 0;"><strong>Contraseña:</strong> <?= Html::encode($plainPassword) ?></p>
    </div>

    <p style="text-align: center; margin-top: 30px;">
        <a href="<?= Url::to(['/site/login'], true) ?>" style="background-color: #134C42; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Acceder al Portal</a>
    </p>

    <p style="font-size: 12px; color: #666; margin-top: 40px; border-top: 1px solid #ddd; padding-top: 10px;">
        Te recomendamos cambiar tu contraseña una vez hayas ingresado al sistema. Si no reconoces esta invitación, por favor ignora este correo.
    </p>
</div>