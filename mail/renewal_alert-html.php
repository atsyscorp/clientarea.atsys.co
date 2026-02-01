<?php
use yii\helpers\Html;

?>
<div style='font-family: sans-serif; max-width: 600px; margin: 0 auto;'>
    <div style='border-left: 5px solid <?=$color?>; padding-left: 15px; margin-bottom: 20px;'>
        <h2 style='color: <?=$color?>; margin: 0;'>Aviso de Renovación</h2>
        <p style='font-size: 18px; margin: 5px 0;'>Faltan <strong><?=$daysLeft?> días</strong></p>
    </div>

    <p>Hola <strong><?=$business_name?></strong>,</p>
    <p><?=$msgIntro?></p>

    <div style='background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin: 20px 0;'>
        <p style='margin: 5px 0;'><strong>Dominio:</strong> <?=$domain?></p>
        <p style='margin: 5px 0;'><strong>Vence el:</strong> <?=$date_long?></p>
        <p style='margin: 5px 0; color: <?=$color?>; font-weight: bold;'>Estado: Pendiente de Pago</p>
    </div>

    <div style='text-align: center; margin: 30px 0;'>
        <a href='<?=$renewLink?>' style='background-color: <?=$color?>; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
            Renovar Ahora
        </a>
    </div>
    
    <p style='font-size: 12px; color: #999; text-align: center;'>
        Si ya realizaste el pago, por favor omite este mensaje.
    </p>
</div>