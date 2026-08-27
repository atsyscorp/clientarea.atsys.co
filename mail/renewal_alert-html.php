<?php
use yii\helpers\Html;

?>
<div style='font-family: sans-serif; max-width: 600px; margin: 0 auto;'>
    <div style='border-left: 5px solid <?=$color?>; padding-left: 15px; margin-bottom: 20px;'>
        <h2 style='color: <?=$color?>; margin: 0;'>Aviso de Renovación</h2>
        <?php if (isset($daysLeft) && $daysLeft == 0): ?>
            <p style='font-size: 18px; margin: 5px 0;'><strong>Vence HOY</strong></p>
        <?php elseif (isset($daysLeft)): ?>
            <p style='font-size: 18px; margin: 5px 0;'>Faltan <strong><?=$daysLeft?> días</strong></p>
        <?php endif; ?>
    </div>

    <p>Hola <strong><?=$business_name?></strong>,</p>
    <p><?=$msgIntro?></p>

    <?php 
    // Compatibilidad: si se pasa un solo servicio a la antigua
    if (!isset($servicesData) && isset($domain)) {
        $servicesData = [
            ['model' => (object)['domain' => $domain], 'date_long' => $date_long]
        ];
    }
    ?>

    <?php foreach ($servicesData as $data): 
        $serviceDomain = isset($data['model']) ? $data['model']->domain : $data['domain'];
        $serviceDate = isset($data['date_long']) ? $data['date_long'] : Yii::$app->formatter->asDate($data['model']->next_due_date, 'long');
    ?>
    <div style='background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin: 20px 0;'>
        <p style='margin: 5px 0;'><strong>Dominio:</strong> <?=$serviceDomain?></p>
        <p style='margin: 5px 0;'><strong>Vence el:</strong> <?=$serviceDate?></p>
        <p style='margin: 5px 0; color: <?=$color?>; font-weight: bold;'>Estado: Pendiente de Pago</p>
    </div>
    <?php endforeach; ?>

    <div style='text-align: center; margin: 30px 0;'>
        <a href='<?=$renewLink?>' style='background-color: <?=$color?>; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
            Renovar Ahora
        </a>
    </div>
    
    <p style='font-size: 12px; color: #999; text-align: center;'>
        Si ya realizaste el pago, por favor omite este mensaje.
    </p>
</div>