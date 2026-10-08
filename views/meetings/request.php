<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use easedevs\yii2\turnstile\TurnstileInput;

/** @var yii\web\View $this */
/** @var app\models\MeetingRequestForm $model */
/** @var bool $isSuccess */
/** @var app\models\Meetings|null $createdMeeting */
/** @var array|null $schedule */
/** @var array|null $nextAvailable */

$isGuest = Yii::$app->user->isGuest;

if (!isset($schedule)) {
    $schedule = \app\models\SystemSettings::getMeetingSchedule();
}
if (!isset($nextAvailable)) {
    $nextAvailable = \app\models\SystemSettings::getNextAvailableBusinessDate();
}

$minDate = $nextAvailable['date'];
if (empty($model->requested_date) || $model->requested_date < $minDate) {
    $model->requested_date = $minDate;
}

$this->title = 'Solicitar Reunión Virtual - ATSYS';
if (!$isGuest) {
    $this->params['breadcrumbs'][] = ['label' => 'Reuniones Meet', 'url' => ['index']];
    $this->params['breadcrumbs'][] = 'Solicitar Cita';
}
?>

<?php if ($isGuest): ?>
<div class="min-h-screen bg-base-200/50 py-10 px-4 sm:px-6 lg:px-8 flex flex-col justify-center items-center">
    <!-- Tarjeta Contenedora Principal -->
    <div class="w-full max-w-3xl">

        <!-- Logo y Encabezado de Marca (Público) -->
        <div class="text-center mb-8">
            <a href="/" class="inline-block hover:opacity-90 transition-opacity">
                <img src="https://static.atsys.co/img/email/atsys-email-customer-tpl.png" alt="Logo ATSYS" class="h-10 sm:h-12 w-auto mx-auto" />
            </a>
            <h1 class="text-3xl font-extrabold text-base-content mt-6">Solicitar Reunión Virtual</h1>
            <p class="text-sm text-base-content/60 max-w-lg mx-auto mt-2">
                Coordina una sesión con nuestro equipo de ingeniería y soporte. Validaremos la disponibilidad en agenda y te enviaremos la confirmación con la sala de Google Meet.
            </p>
        </div>
<?php else: ?>
<div class="w-full max-w-3xl mx-auto space-y-6">
    <div>
        <!-- Encabezado en Portal de Clientes -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-base-content/85 flex items-center gap-3">
                    <span class="p-2.5 bg-primary/10 text-primary rounded-xl inline-flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" /></svg>
                    </span>
                    Solicitar Reunión Virtual
                </h1>
                <p class="text-sm opacity-60 mt-1">
                    Indica tu disponibilidad y el tema a tratar. Validaremos la agenda corporativa para enviarte la confirmación con Google Meet.
                </p>
            </div>
            <a href="<?= Url::to(['meetings/index']) ?>" class="btn btn-outline btn-sm gap-1.5 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                Mis Reuniones
            </a>
        </div>
<?php endif; ?>

        <?php if ($isSuccess && $createdMeeting): ?>
            <!-- Pantalla de Éxito / Confirmación -->
            <div class="card bg-base-100 shadow-xl border border-success/30 overflow-hidden">
                <div class="bg-success text-white py-6 px-8 text-center">
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-9 h-9 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    </div>
                    <h2 class="text-2xl font-bold">¡Solicitud Recibida con Éxito!</h2>
                    <p class="text-sm opacity-90 mt-1">Hemos registrado tu petición para agendar una reunión virtual.</p>
                </div>

                <div class="card-body p-6 sm:p-8 space-y-6">
                    <div class="bg-base-200/50 rounded-xl p-5 border border-base-200 space-y-3">
                        <div class="flex justify-between items-center text-sm border-b border-base-200 pb-2">
                            <span class="text-base-content/60">Asunto:</span>
                            <span class="font-bold text-base-content"><?= Html::encode($createdMeeting->title) ?></span>
                        </div>
                        <div class="flex justify-between items-center text-sm border-b border-base-200 pb-2">
                            <span class="text-base-content/60">Fecha y Hora Propuesta:</span>
                            <span class="font-bold text-primary"><?= date('d/m/Y h:i A', strtotime($createdMeeting->start_time)) ?> (Hora Colombia)</span>
                        </div>
                        <div class="flex justify-between items-center text-sm border-b border-base-200 pb-2">
                            <span class="text-base-content/60">Solicitante:</span>
                            <span class="font-semibold text-base-content"><?= Html::encode($createdMeeting->client_name) ?> (<?= Html::encode($createdMeeting->client_email) ?>)</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-base-content/60">Estado:</span>
                            <span class="badge badge-warning text-white font-semibold">Pendiente de Aprobación</span>
                        </div>
                    </div>

                    <div class="alert alert-info text-sm shadow-sm border border-info/20">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 flex-shrink-0 text-info"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                        <div>
                            Hemos enviado un acuse de recibo a <b><?= Html::encode($createdMeeting->client_email) ?></b>. Nuestro equipo revisará la agenda corporativa y te notificará la confirmación con el enlace de Google Meet.
                        </div>
                    </div>

                    <div class="text-center pt-2 flex items-center justify-center gap-3">
                        <?php if (!$isGuest): ?>
                            <a href="<?= Url::to(['meetings/index']) ?>" class="btn btn-primary btn-sm gap-1.5 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                Ver Mis Reuniones
                            </a>
                        <?php endif; ?>
                        <a href="/reuniones/solicitar" class="btn btn-outline btn-sm">
                            Solicitar otra reunión
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>

            <!-- Formulario de Solicitud -->
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-6 sm:p-8">

                    <!-- Insignias de garantía de servicio -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pb-6 border-b border-base-200">
                        <div class="flex items-center gap-2 text-xs font-semibold text-base-content/70">
                            <span class="p-1.5 bg-success/10 text-success rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                            </span>
                            Google Meet Oficial
                        </div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-base-content/70">
                            <span class="p-1.5 bg-primary/10 text-primary rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" /></svg>
                            </span>
                            Sincroniza con Calendar
                        </div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-base-content/70">
                            <span class="p-1.5 bg-info/10 text-info rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                            </span>
                            Validación Anti-Spam
                        </div>
                    </div>

                    <?php $form = ActiveForm::begin([
                        'id' => 'form-meeting-request',
                        'enableClientScript' => false,
                        'enableClientValidation' => false,
                        'options' => ['class' => 'space-y-6 mt-6'],
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'label-text font-semibold text-sm mb-1 block'],
                            'inputOptions' => ['class' => 'input input-bordered w-full bg-base-200/40 focus:bg-base-100 transition-colors'],
                            'errorOptions' => ['class' => 'text-error text-xs mt-1 block'],
                        ],
                    ]); ?>

                    <!-- Campo Honeypot Oculto (Anti-Bot Trampa) -->
                    <div style="display:none !important; position:absolute !important; left:-9999px !important;" aria-hidden="true">
                        <label for="honeypot-website">No llenar este campo:</label>
                        <input type="text" name="MeetingRequestForm[website]" id="honeypot-website" tabindex="-1" autocomplete="off">
                    </div>

                    <!-- Sección 1: Datos de Contacto -->
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-base-content/60 mb-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                            Tus Datos de Contacto
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-control">
                                <?= $form->field($model, 'name')->textInput([
                                    'placeholder' => 'Ej: Juan Pérez',
                                    'required' => true,
                                ])->label('Nombre Completo <span class="text-error">*</span>') ?>
                            </div>
                            <div class="form-control">
                                <?= $form->field($model, 'email')->input('email', [
                                    'placeholder' => 'juan@empresa.com',
                                    'required' => true,
                                ])->label('Correo Electrónico <span class="text-error">*</span>') ?>
                            </div>
                            <div class="form-control">
                                <?= $form->field($model, 'company')->textInput([
                                    'placeholder' => 'Ej: Mi Empresa S.A.S.',
                                ]) ?>
                            </div>
                            <div class="form-control">
                                <?= $form->field($model, 'phone')->textInput([
                                    'placeholder' => 'Ej: +57 300 123 4567',
                                ]) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 2: Programación de Fecha y Hora -->
                    <div class="pt-2 border-t border-base-200">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-base-content/60 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                Preferencia de Horario
                            </h3>
                            <span class="text-[11px] text-base-content/50 font-medium">Zona horaria: Colombia (UTC-5)</span>
                        </div>

                        <?php if (!empty($nextAvailable['isPastCutoff'])): ?>
                            <div class="alert alert-warning/15 border border-warning/30 text-xs py-2.5 px-3.5 rounded-xl shadow-sm flex items-start gap-2.5 mb-4 text-base-content/90">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-warning flex-shrink-0 mt-0.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                <div>
                                    <span class="font-bold">Horario de hoy concluido:</span> La hora límite de atención para hoy ha finalizado. La agenda se encuentra habilitada para el siguiente día hábil: <strong><?= date('d/m/Y', strtotime($minDate)) ?> (<?= Html::encode($nextAvailable['config']['name'] ?? 'Próximo Día Hábil') ?>)</strong>.
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-control">
                                <?= $form->field($model, 'requested_date')->input('date', [
                                    'min' => $minDate,
                                    'id' => 'input-requested-date',
                                    'required' => true,
                                    'class' => 'input input-bordered w-full bg-base-200/40 focus:bg-base-100',
                                ])->label('Fecha Sugerida <span class="text-error">*</span>') ?>
                                <div id="date-schedule-notice" class="text-error text-xs mt-1.5 hidden font-medium"></div>
                            </div>

                            <div class="form-control">
                                <label class="label-text font-semibold text-sm mb-1 block">
                                    Hora Sugerida <span class="text-error">*</span>
                                </label>
                                <?php
                                $initialDow = (string)date('w', strtotime($model->requested_date));
                                $initialConfig = $schedule[$initialDow] ?? null;
                                $initialSlots = [];
                                if ($initialConfig && !empty($initialConfig['enabled'])) {
                                    $startH = (int)substr($initialConfig['start'] ?? '08:00', 0, 2);
                                    $endH = (int)substr($initialConfig['end'] ?? '17:00', 0, 2);
                                    $todayStr = date('Y-m-d');
                                    $currentTime = date('H:i');
                                    for ($h = $startH; $h <= $endH; $h++) {
                                        $timeVal = sprintf('%02d:00', $h);
                                        if ($model->requested_date === $todayStr && $timeVal <= $currentTime) {
                                            continue;
                                        }
                                        $ampm = $h >= 12 ? 'PM' : 'AM';
                                        $h12 = $h % 12;
                                        if ($h12 === 0) $h12 = 12;
                                        $label = sprintf('%02d:00 %s', $h12, $ampm);
                                        $initialSlots[] = ['value' => $timeVal, 'label' => $label];
                                    }
                                }
                                ?>
                                <select name="MeetingRequestForm[requested_time]" id="select-requested-time" class="select select-bordered w-full bg-base-200/40 focus:bg-base-100" required>
                                    <option value="">-- Seleccionar Hora --</option>
                                    <?php foreach ($initialSlots as $slot): ?>
                                        <option value="<?= $slot['value'] ?>" <?= $model->requested_time === $slot['value'] ? 'selected' : '' ?>>
                                            <?= $slot['label'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="text-[11px] opacity-50 mt-1">Hora Colombia (UTC-5)</span>
                            </div>

                            <div class="form-control">
                                <label class="label-text font-semibold text-sm mb-1 block">
                                    Duración Estimada
                                </label>
                                <select name="MeetingRequestForm[duration_minutes]" class="select select-bordered w-full bg-base-200/40 focus:bg-base-100">
                                    <option value="15" <?= $model->duration_minutes == 15 ? 'selected' : '' ?>>15 minutos (Rápida)</option>
                                    <option value="30" <?= $model->duration_minutes == 30 ? 'selected' : '' ?>>30 minutos (Estándar)</option>
                                    <option value="45" <?= $model->duration_minutes == 45 ? 'selected' : '' ?>>45 minutos</option>
                                    <option value="60" <?= $model->duration_minutes == 60 ? 'selected' : '' ?>>60 minutos (1 hora)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 3: Asunto y Motivo -->
                    <div class="pt-2 border-t border-base-200">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-base-content/60 mb-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                            Detalle de tu Consulta
                        </h3>
                        <div class="space-y-4">
                            <div class="form-control">
                                <?= $form->field($model, 'title')->textInput([
                                    'placeholder' => 'Ej: Asesoría técnica sobre servidores VPS y hosting',
                                    'required' => true,
                                ])->label('Asunto Principal <span class="text-error">*</span>') ?>
                            </div>

                            <div class="form-control">
                                <?= $form->field($model, 'description')->textarea([
                                    'rows' => 3,
                                    'class' => 'textarea textarea-bordered w-full bg-base-200/40 focus:bg-base-100',
                                    'placeholder' => 'Cuéntanos brevemente los puntos que deseas tratar en la sesión para que nuestro equipo esté preparado...',
                                ]) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Verificación Cloudflare Turnstile -->
                    <div class="pt-2 border-t border-base-200">
                        <div class="flex flex-col items-center justify-center py-2">
                            <?= $form->field($model, 'captcha')->widget(TurnstileInput::class, [
                                'size' => TurnstileInput::SIZE_NORMAL,
                            ])->label(false) ?>
                        </div>
                    </div>

                    <!-- Botón de Envío -->
                    <div>
                        <button type="submit" id="btn-submit-request" class="btn btn-primary w-full text-white text-base shadow-lg shadow-primary/20 gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                            Enviar Solicitud de Reunión
                        </button>
                        
                        <!-- Aviso Legal de Tratamiento de Datos y Grabación -->
                        <div class="text-xs text-center text-base-content/60 mt-3.5 space-y-1.5 leading-relaxed">
                            <p>
                                Al enviar esta solicitud, autorizas a <strong>Arkitech Systems SAS (ATSYS)</strong> el tratamiento de tus datos personales conforme a nuestra 
                                <a href="https://atsys.co/politica-de-tratamiento-de-datos/" target="_blank" rel="noopener noreferrer" class="link link-primary font-semibold hover:underline">
                                    Política de Tratamiento de Datos
                                </a>.
                            </p>
                            <p class="text-base-content/40 text-[11px]">
                                Asimismo, aceptas que la sesión pueda ser grabada con fines de aseguramiento de calidad y mejoramiento en el servicio.
                            </p>
                        </div>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        <?php endif; ?>

<?php if ($isGuest): ?>
        <!-- Pie de Página (Solo Invitados) -->
        <div class="mt-8 text-center text-xs text-base-content/40 space-y-1">
            <div>&copy; <?= date('Y') ?> Arkitech Systems SAS (ATSYS). Todos los derechos reservados.</div>
            <div>
                <a href="https://atsys.co/politica-de-tratamiento-de-datos/" target="_blank" rel="noopener noreferrer" class="hover:text-base-content/70 hover:underline">
                    Política de Tratamiento de Datos Personales
                </a>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-meeting-request');
    const submitBtn = document.getElementById('btn-submit-request');
    const dateInput = document.getElementById('input-requested-date');
    const timeSelect = document.getElementById('select-requested-time');
    const noticeEl = document.getElementById('date-schedule-notice');

    const scheduleConfig = <?= json_encode($schedule, JSON_UNESCAPED_UNICODE) ?>;
    const serverToday = '<?= date('Y-m-d') ?>';
    const serverCurrentTime = '<?= date('H:i') ?>';
    const minAvailableDate = '<?= $minDate ?>';

    function formatTime12h(timeStr) {
        const parts = timeStr.split(':');
        let h = parseInt(parts[0], 10);
        const m = parts[1] || '00';
        const ampm = h >= 12 ? 'PM' : 'AM';
        let h12 = h % 12;
        if (h12 === 0) h12 = 12;
        return (h12 < 10 ? '0' + h12 : h12) + ':' + m + ' ' + ampm;
    }

    function updateAvailableSlots() {
        if (!dateInput || !timeSelect) return;
        const selectedDate = dateInput.value;
        if (!selectedDate) {
            timeSelect.innerHTML = '<option value="">-- Seleccionar Fecha Primero --</option>';
            return;
        }

        // Si es anterior a la fecha mínima permitida, restablecer
        if (selectedDate < minAvailableDate) {
            dateInput.value = minAvailableDate;
            updateAvailableSlots();
            return;
        }

        const dateParts = selectedDate.split('-');
        if (dateParts.length !== 3) return;
        const dObj = new Date(parseInt(dateParts[0], 10), parseInt(dateParts[1], 10) - 1, parseInt(dateParts[2], 10));
        const dow = String(dObj.getDay()); // 0=Domingo, 1=Lunes, ...
        const dayCfg = scheduleConfig[dow];

        if (!dayCfg || !dayCfg.enabled) {
            const dayName = (dayCfg && dayCfg.name) ? dayCfg.name : 'seleccionado';
            if (noticeEl) {
                noticeEl.textContent = 'Los días ' + dayName + ' no contamos con atención para citas. Por favor selecciona otro día hábil.';
                noticeEl.classList.remove('hidden');
            }
            timeSelect.innerHTML = '<option value="">-- Día cerrado para citas --</option>';
            timeSelect.disabled = true;
            if (submitBtn) submitBtn.disabled = true;
            return;
        }

        // Día habilitado
        if (noticeEl) {
            noticeEl.textContent = '';
            noticeEl.classList.add('hidden');
        }
        timeSelect.disabled = false;
        if (submitBtn) submitBtn.disabled = false;

        const startH = parseInt(dayCfg.start.split(':')[0], 10);
        const endH = parseInt(dayCfg.end.split(':')[0], 10);
        const isToday = (selectedDate === serverToday);

        let optionsHtml = '<option value="">-- Seleccionar Hora --</option>';
        let validSlotsCount = 0;

        for (let h = startH; h <= endH; h++) {
            const timeVal = (h < 10 ? '0' + h : '' + h) + ':00';
            if (isToday && timeVal <= serverCurrentTime) {
                continue; // Hora ya transcurrida hoy
            }
            const label = formatTime12h(timeVal);
            optionsHtml += '<option value="' + timeVal + '">' + label + '</option>';
            validSlotsCount++;
        }

        if (validSlotsCount === 0) {
            timeSelect.innerHTML = '<option value="">-- Sin turnos disponibles hoy --</option>';
            timeSelect.disabled = true;
            if (noticeEl) {
                noticeEl.textContent = 'La jornada de atención para hoy ya finalizó. Te sugerimos seleccionar el siguiente día hábil.';
                noticeEl.classList.remove('hidden');
            }
            if (submitBtn) submitBtn.disabled = true;
        } else {
            timeSelect.innerHTML = optionsHtml;
        }
    }

    if (dateInput) {
        dateInput.addEventListener('change', updateAvailableSlots);
        dateInput.addEventListener('input', updateAvailableSlots);
    }

    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> Procesando solicitud...';
        });
    }
});
</script>
