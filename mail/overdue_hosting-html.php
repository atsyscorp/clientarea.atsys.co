<?php
use yii\helpers\Html;

// Mensaje legal crítico
$warningText = "IMPORTANTE: En caso de no reactivarse, se eliminará cualquier archivo asociado y bases de datos en un tiempo de 15 a 30 días de forma irreversible.";
$clientAreaLink = Yii::$app->urlManager->createAbsoluteUrl(['customer-services']);
?>
<h2>Hola, <?=$business_name?></h2>
<p>Te informamos que tu servicio de hosting para el dominio <strong><?=$domain?></strong> ha sido suspendido por falta de pago.</p>
<p>Fecha de vencimiento: <?=$due_date?></p>

<div style='background-color: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 15px; border-radius: 5px; margin: 20px 0;'>
    <strong><?=$warningText?></strong>
</div>

<p>Para reactivar tu servicio inmediatamente, por favor realiza el pago en tu área de cliente.</p>
<p style="text-align: center; margin: 30px 0;">
    <a href='<?=$clientAreaLink?>' style="background-color: #134C42; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Ir a Mis servicios</a>
</p>
