<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Meetings */

$startTime = strtotime($model->start_time);
$endTime = strtotime($model->end_time);

// Formato de fecha amigable en español
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

<div
    style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2d3748; line-height: 1.6; max-width: 580px; margin: 0 auto;">

    <div style="text-align: center; margin-bottom: 25px;">
        <span
            style="background-color: #e6f4ea; color: #137333; font-weight: bold; font-size: 11px; padding: 5px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;">
            Invitación a Reunión Virtual
        </span>
        <h2 style="color: #134C42; margin-top: 12px; margin-bottom: 6px; font-size: 22px; font-weight: 700;">
            <?= Html::encode($model->title) ?>
        </h2>
    </div>

    <p style="font-size: 15px; margin-bottom: 12px;">Estimado(a)
        <strong><?= Html::encode($model->client_name) ?></strong>,</p>

    <p style="font-size: 14px; color: #4a5568; margin-bottom: 20px;">
        Te confirmamos que se ha programado una sesión virtual mediante <strong>Google Meet</strong> con el equipo de
        <strong>ATSYS</strong>. A continuación encontrarás todos los detalles para conectarte:
    </p>

    <!-- Tarjeta de Detalles de la Sesión -->
    <div
        style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #134C42; border-radius: 6px; padding: 18px 20px; margin: 22px 0;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <tr>
                <td style="padding: 6px 0; color: #718096; width: 35%;"><strong>📅 Fecha:</strong></td>
                <td style="padding: 6px 0; font-weight: bold; color: #1a202c;"><?= $fechaFormateada ?></td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #718096;"><strong>⏰ Horario:</strong></td>
                <td style="padding: 6px 0; font-weight: bold; color: #134C42;">
                    <?= $horaInicio ?> - <?= $horaFin ?> <span
                        style="font-weight: normal; font-size: 12px; color: #718096;">(Hora Colombia, UTC-5)</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #718096;"><strong>⏱️ Duración:</strong></td>
                <td style="padding: 6px 0; color: #4a5568;"><?= $model->getDurationMinutes() ?> minutos</td>
            </tr>
            <?php if (!empty($model->description)): ?>
                <tr>
                    <td style="padding: 8px 0 4px 0; color: #718096; vertical-align: top;"><strong>📝 Agenda /
                            Temas:</strong></td>
                    <td style="padding: 8px 0 4px 0; color: #2d3748; white-space: pre-line;">
                        <?= Html::encode($model->description) ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Botón Destacado para Unirse a Google Meet -->
    <?php if (!empty($model->meet_url)): ?>
        <div style="text-align: center; margin: 30px 0;">
            <a href="<?= Html::encode($model->meet_url) ?>" target="_blank"
                style="background-color: #134C42; color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px; display: inline-block; box-shadow: 0 4px 6px rgba(19, 76, 66, 0.25);">
                🎥 Unirse a Google Meet
            </a>
            <div style="margin-top: 14px; font-size: 12px; color: #718096;">
                O accede directamente copiando este enlace en tu navegador:<br>
                <a href="<?= Html::encode($model->meet_url) ?>"
                    style="color: #134C42; text-decoration: underline; word-break: break-all; font-weight: 500;">
                    <?= Html::encode($model->meet_url) ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Aviso de archivo de calendario adjunto -->
    <div
        style="background-color: #edf2f7; border-radius: 6px; padding: 12px 16px; margin: 24px 0 16px 0; font-size: 13px; color: #4a5568; line-height: 1.5;">
        📎 <strong>Adjunto en este correo:</strong> Hemos incluido el archivo de calendario
        (<code>invitacion.ics</code>) para que puedas añadir la reunión automáticamente a tu Google Calendar, Apple
        Calendar u Outlook.
    </div>

    <!-- Aviso de Grabación y Calidad del Servicio -->
    <div
        style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #134C42; border-radius: 4px; padding: 12px 16px; margin: 16px 0 24px 0; font-size: 12px; color: #4a5568; line-height: 1.5;">
        <strong style="color: #1a202c; display: block; margin-bottom: 4px; font-size: 12px;">
            📹 Aviso de Grabación y Calidad del Servicio:
        </strong>
        Con el propósito de asegurar los más altos estándares de calidad, registro fiel de los compromisos técnicos y
        mejora continua en la prestación de nuestros servicios, le informamos que esta sesión virtual será grabada y
        documentada. La información tratada durante la reunión se gestiona bajo estrictas normas de confidencialidad y
        protección de datos.
    </div>

    <p style="font-size: 14px; color: #4a5568; margin-top: 25px;">
        Si requieres reprogramar la sesión o tienes dudas previas, puedes responder con toda confianza a este correo.
    </p>
</div>