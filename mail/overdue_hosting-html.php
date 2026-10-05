<?php
use yii\helpers\Html;

// Mensaje informativo sobre retención
$warningText = "Nota: Recuerda que los servicios suspendidos se mantienen respaldados temporalmente. Te sugerimos realizar tu renovación antes de 30 días para conservar tu información.";
$clientAreaLink = Yii::$app->urlManager->createAbsoluteUrl(['customer-services']);
?>
<h2>Hola, <?=$business_name?></h2>
<?php 
if (!isset($servicesData) && isset($domain)) {
    $servicesData = [
        (object)['domain' => $domain, 'next_due_date' => $due_date]
    ];
}
$multiple = count($servicesData) > 1;
?>
<p>Te informamos que <?=$multiple ? 'los siguientes servicios han sido suspendidos' : 'tu servicio para el dominio <strong>'.$servicesData[0]->domain.'</strong> ha sido suspendido'?> por falta de pago.</p>

<?php if ($multiple): ?>
    <ul>
    <?php foreach ($servicesData as $s): ?>
        <li><strong><?=$s->domain?></strong> (Vencimiento: <?=Yii::$app->formatter->asDate($s->next_due_date, 'long')?>)</li>
    <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Fecha de vencimiento: <?=Yii::$app->formatter->asDate($servicesData[0]->next_due_date, 'long')?></p>
<?php endif; ?>

<div style='background-color: #f8fafc; border-left: 4px solid #cbd5e1; color: #475569; padding: 15px; margin: 20px 0;'>
    <?=$warningText?>
</div>

<p>Para reactivar tu servicio inmediatamente, por favor realiza el pago en tu área de cliente.</p>
<p style="text-align: center; margin: 30px 0;">
    <a href='<?=$clientAreaLink?>' style="background-color: #134C42; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Ir a Mis servicios</a>
</p>
