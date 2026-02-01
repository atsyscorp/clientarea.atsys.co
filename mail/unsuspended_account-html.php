<?php
use yii\helpers\Html;

// Mensaje legal crítico
$clientAreaLink = Yii::$app->urlManager->createAbsoluteUrl(['customer-services']);
?>
<h2>Hola, <?=$business_name?></h2>
<p>Te informamos que tu servicio de hosting para el dominio <strong><?=$domain?></strong> ha sido reactivado y puedes verificar en cualquier momento.</p>

<p>Puedes también consultar desde el área de clientes su estado actual haciendo click en el siguiente link:</p>
<p style="text-align: center; margin: 30px 0;">
    <a href='<?=$clientAreaLink?>' style="background-color: #134C42; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Ir a Mis servicios</a>
</p>
