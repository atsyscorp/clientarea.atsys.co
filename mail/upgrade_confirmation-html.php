<?php
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\CustomerServices $service */
/** @var string $oldProductName */
/** @var app\models\Products $newProduct */
/** @var app\models\Customers $customer */
?>
<div
    style="font-family: Arial, sans-serif; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #0284c7; padding: 20px; color: white; text-align: center;">
        <h2 style="margin:0;">¡Plan de Hosting Mejorado con Éxito!</h2>
    </div>

    <div style="padding: 24px; color: #374151; line-height: 1.6;">
        <p>Hola <strong><?= Html::encode($customer ? $customer->business_name : 'Cliente') ?></strong>,</p>
        <p>Te confirmamos que el plan de hosting para tu dominio <strong><?= Html::encode($service->domain) ?></strong>
            ha sido actualizado correctamente en nuestros servidores.</p>

        <div
            style="background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 15px; border-radius: 6px; margin: 20px 0;">
            <p style="margin: 0; color: #166534; font-weight: bold;">
                🚀 Tu nueva capacidad y recursos ya se encuentran disponibles de inmediato.
            </p>
        </div>

        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 6px; margin: 20px 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 6px; font-weight: bold; width: 40%;">Dominio:</td>
                    <td style="padding: 6px;"><?= Html::encode($service->domain) ?></td>
                </tr>
                <tr>
                    <td style="padding: 6px; font-weight: bold;">Plan Anterior:</td>
                    <td style="padding: 6px; color: #6b7280; text-decoration: line-through;">
                        <?= Html::encode($oldProductName) ?></td>
                </tr>
                <tr>
                    <td style="padding: 6px; font-weight: bold;">Nuevo Plan Activo:</td>
                    <td style="padding: 6px; color: #0284c7; font-weight: bold; font-size: 15px;">
                        <?= Html::encode($newProduct->name) ?></td>
                </tr>
                <tr>
                    <td style="padding: 6px; font-weight: bold;">Próximo Vencimiento:</td>
                    <td style="padding: 6px;"><?= Yii::$app->formatter->asDate($service->next_due_date, 'long') ?></td>
                </tr>
            </table>
        </div>

        <p style="font-size: 13px; color: #6b7280;">
            No es necesario realizar ningún cambio técnico en tu web ni en tus correos. Las credenciales de acceso a tu
            panel de control siguen siendo exactamente las mismas.
        </p>

        <div style="text-align: center; margin-top: 25px;">
            <a href="https://clientarea.atsys.co/customer-services/view?id=<?= $service->id ?>"
                style="background-color: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                Ver Detalle en Área de Clientes
            </a>
        </div>
    </div>
</div>