<?php

?>
<div style='font-family: sans-serif; color: #333;'>
    <h2 style='color: #059669;'>¡Gracias por tu pago!</h2>
    <p>Hola <strong><?=$business_name?></strong>,</p>
    <p>Hemos recibido tu pago correctamente. Aquí tienes el resumen de tu transacción:</p>
    
    <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;'>
        <p style='margin: 5px 0;'><strong>Referencia:</strong> <?=$order_code?></p>
        <p style='margin: 5px 0;'><strong>Fecha:</strong> <?=$payment_date?></p>
        <p style='margin: 5px 0;'><strong>Método:</strong> <?=$payment_method?></p>
    </div>

    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
        <thead>
            <tr style='background: #e5e7eb; text-align: left;'>
                <th style='padding: 8px;'>Servicio</th>
                <th style='padding: 8px; text-align: right;'>Valor</th>
            </tr>
        </thead>
        <tbody>
            <?=$itemsHtml?>
        </tbody>
        <tfoot>
            <tr>
                <td style='padding: 12px; font-weight: bold; text-align: right;'>TOTAL PAGADO</td>
                <td style='padding: 12px; font-weight: bold; text-align: right; color: #059669;'><?=$total?></td>
            </tr>
        </tfoot>
    </table>

    <p>Tus servicios han sido activados o renovados automáticamente.</p>
    <p style='font-size: 12px; color: #666; margin-top: 30px;'>Si necesitas soporte o tuviste problemas con el pago, abre un ticket desde el área de clientes.</p>
</div>