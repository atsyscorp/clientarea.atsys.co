<?php
/* @var $business_name string */
/* @var $itemsHtml string */
/* @var $order_total string */
/* @var $paymentLink string */
?>
<div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; color: #333;'>
    <h2 style='color: #2563eb;'>Hola, <?= $business_name ?></h2>
    <p>Se ha generado una nueva orden de pago en tu cuenta.</p>

    <div style='background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin: 20px 0;'>
        <p style='margin: 5px 0; font-weight: bold;'>Conceptos:</p>
        <ul style='margin-top: 5px; margin-bottom: 15px; padding-left: 20px;'>
            <?= $itemsHtml ?>
        </ul>
        <p style='margin: 5px 0; font-size: 18px;'><strong>Total a Pagar:</strong> <?= $order_total ?></p>
    </div>

    <p>Para realizar el pago de forma segura, por favor haz clic en el siguiente botón:</p>

    <div style='text-align: center; margin: 30px 0;'>
        <a href='<?= $paymentLink ?>'
            style='background-color: #059669; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;'>
            Pagar Ahora Online
        </a>
    </div>

    <p style='font-size: 12px; color: #666;'>Si tienes alguna duda sobre esta orden de pago, no dudes en contactarnos.</p>
</div>
