<?php
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Meetings */
/* @var $customer app\models\Customers|null */
/* @var $phone string|null */
/* @var $company string|null */

$viewUrl = Yii::$app->urlManager->createAbsoluteUrl(['meetings/view', 'id' => $model->id]);
$approveUrl = Yii::$app->urlManager->createAbsoluteUrl(['meetings/approve', 'id' => $model->id]);
$rejectUrl = Yii::$app->urlManager->createAbsoluteUrl(['meetings/reject', 'id' => $model->id]);
$customerUrl = $customer ? Yii::$app->urlManager->createAbsoluteUrl(['customers/view', 'id' => $customer->id]) : null;

$startTime = strtotime($model->start_time);
$endTime = strtotime($model->end_time);

// Formato amigable en español
$dias = ['Sunday' => 'Domingo', 'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado'];
$meses = ['January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio', 'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre', 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'];

$diaSemana = $dias[date('l', $startTime)] ?? date('l', $startTime);
$diaMes = date('d', $startTime);
$nombreMes = $meses[date('F', $startTime)] ?? date('F', $startTime);
$anio = date('Y', $startTime);

$fechaFormateada = "{$diaSemana}, {$diaMes} de {$nombreMes} de {$anio}";
$horaInicio = date('h:i A', $startTime);
$horaFin = date('h:i A', $endTime);
?>

<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2d3748; line-height: 1.6; max-width: 600px; margin: 0 auto;">

    <!-- Encabezado -->
    <div style="text-align: center; margin-bottom: 24px;">
        <span style="background-color: #fef3c7; color: #92400e; font-weight: bold; font-size: 11px; padding: 5px 14px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;">
            🔔 Nueva Solicitud de Reunión por Aprobar
        </span>
        <h2 style="color: #134C42; margin-top: 12px; margin-bottom: 4px; font-size: 22px; font-weight: 700;">
            <?= Html::encode($model->title) ?>
        </h2>
        <p style="font-size: 13px; color: #64748b; margin: 0;">Recibida a través del portal público (/reuniones/solicitar)</p>
    </div>

    <!-- Indicador de Identidad: Cliente Registrado vs Usuario Externo -->
    <?php if ($customer): ?>
        <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-left: 4px solid #059669; border-radius: 6px; padding: 14px 16px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 16px;">🏢</span>
                <div>
                    <strong style="color: #065f46; font-size: 14px;">CLIENTE REGISTRADO EN PLATAFORMA</strong>
                    <div style="font-size: 13px; color: #047857; margin-top: 2px;">
                        Empresa: <strong><?= Html::encode($customer->business_name) ?></strong>
                        <?php if ($customerUrl): ?>
                            &bull; <a href="<?= $customerUrl ?>" target="_blank" style="color: #065f46; font-weight: bold; text-decoration: underline;">Ver ficha en ClientArea &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; border-radius: 6px; padding: 14px 16px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 16px;">⚠️</span>
                <div>
                    <strong style="color: #92400e; font-size: 14px;">CONTACTO EXTERNO / PROSPECTO NO REGISTRADO</strong>
                    <div style="font-size: 13px; color: #b45309; margin-top: 2px;">
                        El correo <strong><?= Html::encode($model->client_email) ?></strong> no corresponde a ningún cliente con cuenta activa en ClientArea.
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tarjeta de Detalles del Solicitante -->
    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px 20px; margin-bottom: 20px;">
        <h4 style="margin: 0 0 10px 0; font-size: 13px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">
            👤 Datos del Solicitante:
        </h4>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <tr>
                <td style="padding: 4px 0; color: #64748b; width: 35%;"><strong>Nombre:</strong></td>
                <td style="padding: 4px 0; font-weight: bold; color: #1e293b;"><?= Html::encode($model->client_name) ?></td>
            </tr>
            <tr>
                <td style="padding: 4px 0; color: #64748b;"><strong>Email:</strong></td>
                <td style="padding: 4px 0;">
                    <a href="mailto:<?= Html::encode($model->client_email) ?>" style="color: #134C42; font-weight: 600; text-decoration: none;">
                        <?= Html::encode($model->client_email) ?>
                    </a>
                </td>
            </tr>
            <?php if (!empty($company)): ?>
                <tr>
                    <td style="padding: 4px 0; color: #64748b;"><strong>Empresa declarada:</strong></td>
                    <td style="padding: 4px 0; color: #1e293b;"><?= Html::encode($company) ?></td>
                </tr>
            <?php endif; ?>
            <?php if (!empty($phone)): ?>
                <tr>
                    <td style="padding: 4px 0; color: #64748b;"><strong>Teléfono / WhatsApp:</strong></td>
                    <td style="padding: 4px 0; color: #1e293b;"><?= Html::encode($phone) ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Tarjeta de Horario Propuesto -->
    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #134C42; border-radius: 6px; padding: 16px 20px; margin-bottom: 20px;">
        <h4 style="margin: 0 0 10px 0; font-size: 13px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">
            📅 Horario Solicitado:
        </h4>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <tr>
                <td style="padding: 4px 0; color: #64748b; width: 35%;"><strong>Fecha:</strong></td>
                <td style="padding: 4px 0; font-weight: bold; color: #1e293b;"><?= $fechaFormateada ?></td>
            </tr>
            <tr>
                <td style="padding: 4px 0; color: #64748b;"><strong>Hora:</strong></td>
                <td style="padding: 4px 0; font-weight: bold; color: #134C42;">
                    <?= $horaInicio ?> - <?= $horaFin ?> <span style="font-weight: normal; font-size: 12px; color: #64748b;">(Hora Colombia, UTC-5)</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 4px 0; color: #64748b;"><strong>Duración:</strong></td>
                <td style="padding: 4px 0; color: #334155;"><?= $model->getDurationMinutes() ?> minutos</td>
            </tr>
            <?php if (!empty($model->description)): ?>
                <tr>
                    <td style="padding: 8px 0 4px 0; color: #64748b; vertical-align: top;"><strong>Temas / Consulta:</strong></td>
                    <td style="padding: 8px 0 4px 0; color: #1e293b; white-space: pre-line;"><?= Html::encode($model->description) ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Botones de Acción para Gerencia -->
    <div style="background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 20px; text-align: center; margin: 25px 0;">
        <div style="font-size: 13px; color: #475569; margin-bottom: 15px; font-weight: 500;">
            ¿Deseas autorizar esta cita y generar la sala de Google Meet?
        </div>
        
        <div style="display: inline-block;">
            <!-- Botón Aprobar -->
            <a href="<?= $approveUrl ?>" target="_blank" 
               style="background-color: #134C42; color: #ffffff; padding: 12px 26px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block; margin: 4px; box-shadow: 0 4px 6px rgba(19, 76, 66, 0.2);">
                ✅ Aprobar y Generar Google Meet
            </a>

            <!-- Botón Ver en Panel -->
            <a href="<?= $viewUrl ?>" target="_blank" 
               style="background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block; margin: 4px;">
                👁️ Ver en ClientArea
            </a>
        </div>
        <p style="font-size: 11px; color: #94a3b8; margin: 12px 0 0 0;">
            Al hacer clic en <b>Aprobar</b>, el sistema invocará a n8n para crear la cita en Google Calendar y le enviará la invitación con el enlace Meet y el archivo .ics al solicitante.
        </p>
    </div>

</div>
