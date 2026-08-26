<?php
use yii\helpers\Html;

/* @var $model app\models\Contracts */

$link = Yii::$app->urlManager->createAbsoluteUrl(['contracts/view', 'id' => $model->id]);
$percent = number_format(floatval($model->progress_percentage), 1);
?>
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1f2937; line-height: 1.6;">
    <div style="background-color: #4F46E5; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h1 style="color: #ffffff; margin: 0; font-size: 22px;">Nuevo Contrato Activo</h1>
    </div>

    <div style="background-color: #ffffff; padding: 25px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <p>Estimado(a) <strong><?= Html::encode($model->customer->business_name) ?></strong>,</p>

        <p>Le informamos que se ha registrado y activado el contrato <strong><?= Html::encode($model->code) ?></strong> correspondiente a sus servicios contratados con nuestra compañía.</p>

        <div style="background-color: #f9fafb; border-left: 4px solid #4F46E5; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <h3 style="margin-top: 0; color: #4F46E5; font-size: 16px;">Detalles del Contrato</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr>
                    <td style="padding: 4px 0; color: #6b7280; width: 40%;"><strong>Código:</strong></td>
                    <td style="padding: 4px 0; font-family: monospace; font-weight: bold;"><?= Html::encode($model->code) ?></td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; color: #6b7280;"><strong>Objeto:</strong></td>
                    <td style="padding: 4px 0; font-weight: bold;"><?= Html::encode($model->title) ?></td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; color: #6b7280;"><strong>Monto Total:</strong></td>
                    <td style="padding: 4px 0; font-weight: bold; color: #059669;"><?= $model->currency ?> $<?= number_format($model->total_amount, 2) ?></td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; color: #6b7280;"><strong>Fecha de Inicio:</strong></td>
                    <td style="padding: 4px 0;"><?= $model->start_date ? date('d/m/Y', strtotime($model->start_date)) : '-' ?></td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; color: #6b7280;"><strong>Vencimiento:</strong></td>
                    <td style="padding: 4px 0; font-weight: bold; color: #4F46E5;"><?= $model->end_date ? date('d/m/Y', strtotime($model->end_date)) : 'Indefinido (Sin fecha de finalización)' ?></td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; color: #6b7280;"><strong>% Avance Inicial:</strong></td>
                    <td style="padding: 4px 0; font-weight: bold; color: #2563eb;"><?= $percent ?>%</td>
                </tr>
            </table>
        </div>

        <?php if ($model->contract_file): ?>
            <p style="background-color: #ecfdf5; border: 1px solid #10b981; color: #065f46; padding: 12px; border-radius: 6px; font-size: 13px;">
                📄 <strong>Documento Adjunto:</strong> Hemos adjuntado a este correo la copia digital del contrato para su constancia y archivo.
            </p>
        <?php endif; ?>

        <p>Para consultar el avance del contrato, hitos y órdenes de trabajo asociadas en tiempo real, puede ingresar a su portal de cliente:</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="<?= $link ?>" style="background-color: #4F46E5; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                Ver Contrato en Área de Cliente
            </a>
        </div>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;" />
        <p style="font-size: 12px; color: #9ca3af; text-align: center; margin: 0;">
            Este es un correo automático enviado por el sistema de clientes de ATSYS. Por favor no responda directamente a esta dirección.
        </p>
    </div>
</div>
