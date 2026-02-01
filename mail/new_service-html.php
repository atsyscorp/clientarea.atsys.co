<?php
use yii\helpers\Html;
?>
<div style="font-family: Arial, sans-serif; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
    <div style="background-color: #4F46E5; padding: 20px; color: white; text-align: center;">
        <h2 style="margin:0;">Nuevo Servicio Activo</h2>
    </div>
    
    <div style="padding: 20px; color: #374151;">
        <p>Hola <strong><?= Html::encode($service->customer->business_name) ?></strong>,</p>
        <p>Tu nuevo servicio ha sido provisionado y está listo para usar.</p>

        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 6px; margin: 20px 0;">
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 5px; font-weight: bold;">Servicio:</td>
                    <td><?= Html::encode($service->product->name) ?></td>
                </tr>
                <tr>
                    <td style="padding: 5px; font-weight: bold;">Dominio/Ref:</td>
                    <td><?= Html::encode($service->domain ?: $service->description_label) ?></td>
                </tr>
                <tr>
                    <td style="padding: 5px; font-weight: bold;">Vencimiento:</td>
                    <td><?= Yii::$app->formatter->asDate($service->next_due_date) ?></td>
                </tr>
                <?php if($service->username_service): ?>
                <tr>
                    <td style="padding: 5px; font-weight: bold;">Usuario:</td>
                    <td><code><?= Html::encode($service->username_service) ?></code></td>
                </tr>
                <tr>
                    <td style="padding: 5px; font-weight: bold;">Contraseña:</td>
                    <td><code><?= Html::encode($service->password_service) ?></code></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <p>Si necesitas soporte técnico, puedes abrir un ticket desde tu área de cliente.</p>
    </div>
</div>