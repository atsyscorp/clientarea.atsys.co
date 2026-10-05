<?php
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $business_name string */
/* @var $domain string */
/* @var $usagePercentage float */
/* @var $isCritical bool */

?>
<div class="quota-warning">
    <p>Hola <strong><?= Html::encode($business_name) ?></strong>,</p>

    <?php if ($isCritical): ?>
        <p>Te informamos que el espacio en disco de tu servicio para el dominio <strong><?= Html::encode($domain) ?></strong> ha alcanzado el <strong>100%</strong> de su cuota asignada.</p>
        <p>Al tener el espacio lleno, es posible que comiences a presentar intermitencias en tu sitio web o en la recepción de correos electrónicos.</p>
        <p>Te invitamos a liberar espacio (eliminando correos antiguos o archivos innecesarios) o <strong>actualizar a un plan con mayor capacidad</strong> para asegurar el correcto funcionamiento.</p>
    <?php else: ?>
        <p>Te informamos que el espacio en disco de tu servicio para el dominio <strong><?= Html::encode($domain) ?></strong> ha superado el <strong><?= round($usagePercentage) ?>%</strong> de su cuota asignada.</p>
        <p>Te sugerimos revisar tu cuenta para limpiar archivos que ya no utilices, vaciar la papelera de correos, o bien considerar actualizarte a un siguiente plan para evitar futuras interrupciones del servicio.</p>
    <?php endif; ?>

    <p>Puedes gestionar tu cuenta directamente desde nuestra área de clientes.</p>

    <br>
    <p>Atentamente,</p>
    <p><strong>El equipo de <?= Html::encode(Yii::$app->name) ?></strong></p>
</div>
