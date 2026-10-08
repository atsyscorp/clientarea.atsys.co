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

<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2d3748; line-height: 1.6; max-width: 580px; margin: 0 auto;">

    <div style="text-align: center; margin-bottom: 25px;">
        <span style="background-color: #e0f2fe; color: #0369a1; font-weight: bold; font-size: 11px; padding: 5px 14px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;">
            Solicitud de Reunión Recibida
        </span>
        <h2 style="color: #134C42; margin-top: 12px; margin-bottom: 6px; font-size: 22px; font-weight: 700;">
            Hemos recibido tu solicitud
        </h2>
    </div>

    <p style="font-size: 15px; margin-bottom: 12px;">
        Estimado(a) <strong><?= Html::encode($model->client_name) ?></strong>,
    </p>

    <p style="font-size: 14px; color: #4a5568; margin-bottom: 20px;">
        Gracias por ponerte en contacto con <strong>ATSYS</strong>. Hemos recibido exitosamente tu solicitud para agendar una sesión virtual. A continuación te compartimos el resumen de la fecha y hora solicitadas:
    </p>

    <!-- Tarjeta de Detalles Solicitados -->
    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #134C42; border-radius: 6px; padding: 18px 20px; margin: 22px 0;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <tr>
                <td style="padding: 6px 0; color: #718096; width: 35%;"><strong>📅 Asunto:</strong></td>
                <td style="padding: 6px 0; font-weight: bold; color: #1a202c;"><?= Html::encode($model->title) ?></td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #718096;"><strong>🗓️ Fecha propuesta:</strong></td>
                <td style="padding: 6px 0; font-weight: bold; color: #1a202c;"><?= $fechaFormateada ?></td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #718096;"><strong>⏰ Horario:</strong></td>
                <td style="padding: 6px 0; font-weight: bold; color: #134C42;">
                    <?= $horaInicio ?> - <?= $horaFin ?> <span style="font-weight: normal; font-size: 12px; color: #718096;">(Hora Colombia, UTC-5)</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #718096;"><strong>⏱️ Duración:</strong></td>
                <td style="padding: 6px 0; color: #4a5568;"><?= $model->getDurationMinutes() ?> minutos</td>
            </tr>
            <?php if (!empty($model->description)): ?>
                <tr>
                    <td style="padding: 8px 0 4px 0; color: #718096; vertical-align: top;"><strong>📝 Temas a tratar:</strong></td>
                    <td style="padding: 8px 0 4px 0; color: #2d3748; white-space: pre-line;"><?= Html::encode($model->description) ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Estado de Validación en Agenda -->
    <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 14px 18px; margin: 20px 0; font-size: 13px; color: #166534; line-height: 1.5;">
        ⏳ <strong>Validación en curso:</strong> Nuestro equipo directivo y técnico está validando la disponibilidad de la agenda. Tan pronto como la reunión sea aprobada, recibirás un correo de confirmación con el enlace oficial de <strong>Google Meet</strong> y el archivo de calendario (<code style="background-color: #ffffff; padding: 2px 5px; border-radius: 3px;">invitacion.ics</code>) para tu agenda.
    </div>

    <!-- Aviso de Grabación y Calidad del Servicio -->
    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #134C42; border-radius: 4px; padding: 12px 16px; margin: 16px 0 24px 0; font-size: 12px; color: #4a5568; line-height: 1.5;">
        <strong style="color: #1a202c; display: block; margin-bottom: 4px; font-size: 12px;">
            📹 Aviso de Grabación y Calidad del Servicio:
        </strong>
        Con el propósito de asegurar los más altos estándares de calidad, registro fiel de los compromisos técnicos y mejora continua en la prestación de nuestros servicios, le informamos que nuestras sesiones virtuales son grabadas y documentadas. La información tratada durante la reunión se gestiona bajo estrictas normas de confidencialidad y nuestra <a href="https://atsys.co/politica-de-tratamiento-de-datos/" target="_blank" rel="noopener noreferrer" style="color: #134C42; font-weight: bold; text-decoration: underline;">Política de Tratamiento de Datos</a>.
    </div>

    <p style="font-size: 14px; color: #4a5568; margin-top: 25px;">
        Si requieres realizar algún ajuste previo en la fecha o el motivo de tu consulta, puedes responder directamente a este correo.
    </p>
</div>
