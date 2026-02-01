<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user app\models\User */

$verifyLink = Yii::$app->urlManager->createAbsoluteUrl(['site/verify-email', 'token' => $user->verification_token]);
?>
¡Bienvenido a ATSYS!

Hola <?= Html::encode($user->username) ?>,

Gracias por registrarte en nuestra área de clientes. Para comenzar a gestionar tus tickets, por favor confirma tu correo electrónico haciendo clic en el botón de abajo:

<?= $verifyLink ?>

Si no solicitaste este registro, puedes ignorar este correo.