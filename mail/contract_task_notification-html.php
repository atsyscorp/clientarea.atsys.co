<?php
use yii\helpers\Html;

/* @var $task app\models\ContractTasks */
/* @var $contract app\models\Contracts */
/* @var $actionType string */
/* @var $statusLabel string */

$link = Yii::$app->urlManager->createAbsoluteUrl(['contracts/view', 'id' => $contract->id]);
$taskProgress = number_format(floatval($task->progress_percentage), 1);
$contractProgress = number_format(floatval($contract->progress_percentage), 1);

// Definir color de cabecera e ícono según el tipo de acción o estado
$headerBg = '#4F46E5'; // Indigo por defecto
$badgeBg = '#e0e7ff';
$badgeColor = '#3730a3';

if ($task->status == \app\models\ContractTasks::STATUS_COMPLETED || $actionType === 'completed') {
    $headerBg = '#059669'; // Emerald
    $headerTitle = 'Hito de Trabajo Completado';
    $badgeBg = '#d1fae5';
    $badgeColor = '#065f46';
} elseif ($actionType === 'created') {
    $headerTitle = 'Nuevo Hito Registrado';
} else {
    $headerTitle = 'Actualización de Hito de Trabajo';
}
?>
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1f2937; line-height: 1.6;">
    <div style="background-color: <?= $headerBg ?>; padding: 22px 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: bold;"><?= Html::encode($headerTitle) ?></h1>
        <p style="color: rgba(255,255,255,0.9); margin: 6px 0 0 0; font-size: 13px;">
            Contrato <strong><?= Html::encode($contract->code) ?></strong> &mdash; <?= Html::encode($contract->title) ?>
        </p>
    </div>

    <div style="background-color: #ffffff; padding: 25px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <?php if (!empty($isAdminCopy)): ?>
            <div style="background-color: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; line-height: 1.5;">
                <strong>📋 Copia de Respaldo para Administración / Reenvío</strong><br>
                Este correo fue enviado a: <strong><?= Html::encode($targetEmail ?? $contract->customer->email) ?></strong>.<br>
                <span style="font-size: 12px; color: #78350f;">Si el cliente no lo recibió o cayó en su bandeja de spam, puedes reenviarle directamente este mensaje desde tu correo corporativo.</span>
            </div>
        <?php endif; ?>

        <p style="margin-top: 0;">Estimado(a) <strong><?= Html::encode($contract->customer->business_name) ?></strong>,</p>

        <?php if (!empty($customMessage)): ?>
            <div style="background-color: #f8fafc; border-left: 4px solid <?= $headerBg ?>; padding: 12px 16px; margin: 16px 0; border-radius: 0 4px 4px 0;">
                <strong style="color: <?= $headerBg ?>; display: block; margin-bottom: 4px; font-size: 13px;">Mensaje de ATSYS:</strong>
                <p style="margin: 0; white-space: pre-line; color: #475569; font-size: 13px;"><?= Html::encode($customMessage) ?></p>
            </div>
        <?php endif; ?>

        <p>
            <?php if ($task->status == \app\models\ContractTasks::STATUS_COMPLETED): ?>
                Nos complace informarle que se ha completado satisfactoriamente el hito <strong><?= Html::encode($task->title) ?></strong> correspondiente a su contrato de servicios.
            <?php elseif ($actionType === 'created'): ?>
                Le notificamos que se ha incorporado un nuevo hito de trabajo programado en su contrato: <strong><?= Html::encode($task->title) ?></strong>.
            <?php else: ?>
                Le compartimos la actualización más reciente sobre el estado y avance del hito <strong><?= Html::encode($task->title) ?></strong> en su contrato.
            <?php endif; ?>
        </p>

        <!-- Tarjeta de Detalles del Hito -->
        <div style="background-color: #f9fafb; border-left: 4px solid <?= $headerBg ?>; padding: 18px 20px; margin: 20px 0; border-radius: 4px; border-top: 1px solid #f3f4f6; border-right: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">
                <span style="font-size: 16px; font-weight: bold; color: #111827;">
                    <?= Html::encode($task->title) ?>
                </span>
                <span style="background-color: <?= $badgeBg ?>; color: <?= $badgeColor ?>; font-size: 12px; font-weight: bold; padding: 3px 10px; border-radius: 12px; display: inline-block;">
                    <?= Html::encode($statusLabel) ?>
                </span>
            </div>

            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <tr>
                    <td style="padding: 5px 0; color: #6b7280; width: 45%;"><strong>Avance de este Hito:</strong></td>
                    <td style="padding: 5px 0; font-weight: bold; color: <?= $task->progress_percentage >= 100 ? '#059669' : '#2563eb' ?>;">
                        <?= $taskProgress ?>%
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; color: #6b7280;"><strong>Peso en el Contrato:</strong></td>
                    <td style="padding: 5px 0; font-weight: bold; color: #374151;"><?= number_format($task->weight_percentage, 1) ?>%</td>
                </tr>
                <?php if ($task->due_date): ?>
                <tr>
                    <td style="padding: 5px 0; color: #6b7280;"><strong>Fecha Límite Estimada:</strong></td>
                    <td style="padding: 5px 0; font-weight: bold; color: #4b5563;">
                        <?= date('d/m/Y', strtotime($task->due_date)) ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if ($task->workOrder): ?>
                <tr>
                    <td style="padding: 5px 0; color: #6b7280;"><strong>Orden de Trabajo:</strong></td>
                    <td style="padding: 5px 0; font-weight: bold; color: #4b5563;">
                        <?= Html::encode($task->workOrder->code) ?> - <?= Html::encode($task->workOrder->title) ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>

            <!-- Barra de Progreso del Hito -->
            <div style="margin-top: 14px;">
                <div style="display: flex; justify-content: space-between; font-size: 11px; color: #6b7280; margin-bottom: 4px;">
                    <span>Progreso del hito</span>
                    <span style="font-weight: bold;"><?= $taskProgress ?>%</span>
                </div>
                <div style="background-color: #e5e7eb; border-radius: 9999px; height: 8px; overflow: hidden; width: 100%;">
                    <div style="background-color: <?= $task->progress_percentage >= 100 ? '#10b981' : '#4F46E5' ?>; height: 100%; width: <?= min(100, max(0, floatval($task->progress_percentage))) ?>%; border-radius: 9999px;"></div>
                </div>
            </div>

            <?php if (!empty($task->description)): ?>
                <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #d1d5db;">
                    <strong style="font-size: 12px; color: #4b5563; display: block; margin-bottom: 4px;">Detalles y Entregables:</strong>
                    <p style="margin: 0; font-size: 13px; color: #374151; white-space: pre-line;"><?= Html::encode($task->description) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($task->files)): ?>
                <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #d1d5db;">
                    <strong style="font-size: 12px; color: #4b5563; display: block; margin-bottom: 8px;">📎 Archivos y Evidencias de Respaldo (<?= count($task->files) ?>):</strong>
                    <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                        <?php foreach ($task->files as $evidFile): 
                            $fileDownloadUrl = str_starts_with($evidFile->file_url, 'http') 
                                ? $evidFile->file_url 
                                : Yii::$app->urlManager->createAbsoluteUrl($evidFile->file_url);
                        ?>
                            <li style="margin-bottom: 6px;">
                                <a href="<?= Html::encode($fileDownloadUrl) ?>" target="_blank" style="color: <?= $headerBg ?>; font-weight: bold; text-decoration: underline;">
                                    <?= Html::encode($evidFile->title) ?>
                                </a>
                                <span style="color: #6b7280; font-size: 11px;">(<?= $evidFile->getFormattedSize() ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- Avance Global del Contrato -->
        <div style="background-color: #f3f4f6; border-radius: 6px; padding: 12px 16px; margin: 18px 0; font-size: 13px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="color: #4b5563;"><strong>Avance Global del Contrato (<?= Html::encode($contract->code) ?>):</strong></td>
                    <td style="text-align: right; font-weight: bold; color: #111827; font-size: 14px;"><?= $contractProgress ?>%</td>
                </tr>
            </table>
            <div style="background-color: #d1d5db; border-radius: 9999px; height: 6px; overflow: hidden; width: 100%; margin-top: 6px;">
                <div style="background-color: #2563eb; height: 100%; width: <?= min(100, max(0, floatval($contract->progress_percentage))) ?>%; border-radius: 9999px;"></div>
            </div>
        </div>

        <p style="margin-bottom: 5px;">Para consultar el cronograma completo de hitos y el detalle de actividades de su contrato en tiempo real, ingrese al portal de cliente:</p>

        <div style="text-align: center; margin: 26px 0;">
            <a href="<?= $link ?>" style="background-color: <?= $headerBg ?>; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 14px;">
                Ver Hitos y Contrato en Línea
            </a>
        </div>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;" />
        <p style="font-size: 11px; color: #9ca3af; text-align: center; margin: 0;">
            Este es un correo del sistema de clientes de ATSYS. Si tiene preguntas sobre este hito, puede responder a este mensaje directamente o comunicarse con su asesor comercial asignado.
        </p>
    </div>
</div>
