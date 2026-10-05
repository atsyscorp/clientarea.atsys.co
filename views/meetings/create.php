<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Meetings $model */
/** @var array $customers */
/** @var int $durationMinutes */

$this->title = 'Agendar Reunión Google Meet';
$this->params['breadcrumbs'][] = ['label' => 'Reuniones', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="meetings-create max-w-4xl mx-auto space-y-6">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-base-content/60 mb-1">
                <a href="<?= Url::to(['meetings/index']) ?>" class="hover:text-primary transition-colors flex items-center gap-1.5 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    Volver a Reuniones
                </a>
            </div>
            <h1 class="text-3xl font-bold text-base-content/85 flex items-center gap-3">
                <span class="p-2.5 bg-primary/10 text-primary rounded-xl inline-flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5m-9 3.75h.008v.008H12v-.008Z" /></svg>
                </span>
                Agendar Nueva Reunión
            </h1>
            <p class="text-sm opacity-60 mt-1">Sincroniza automáticamente con Google Calendar, genera la sala de Google Meet y notifica por email al cliente.</p>
        </div>
    </div>

    <!-- Alerta Informativa del Flujo -->
    <div class="alert alert-info shadow-sm border border-info/20 text-sm">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mt-0.5 text-info flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
            <div>
                <span class="font-bold">Automatización con n8n activa:</span>
                Al hacer clic en <b>"Agendar Reunión"</b>, tu servidor invocará a n8n para crear la cita en Google Calendar con Google Meet y enviar la invitación oficial con el archivo de calendario (<code class="text-xs bg-base-100 px-1 py-0.5 rounded">.ics</code>) al correo del asistente.
            </div>
        </div>
    </div>

    <!-- Formulario Principal -->
    <div class="card bg-base-100 shadow-md border border-base-200">
        <div class="card-body p-6">
            <?php $form = ActiveForm::begin([
                'id' => 'meeting-form',
                'enableClientScript' => false,
                'enableClientValidation' => false,
                'options' => ['class' => 'space-y-6'],
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'label-text font-semibold text-sm mb-1 block'],
                    'inputOptions' => ['class' => 'input input-bordered w-full bg-base-200/40 focus:bg-base-100 transition-colors'],
                    'errorOptions' => ['class' => 'text-error text-xs mt-1 block'],
                ],
            ]); ?>

            <!-- Bloque 1: Cliente y Asistente -->
            <div class="border-b border-base-200 pb-6">
                <h3 class="text-base font-bold text-base-content/80 flex items-center gap-2 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                    Participante
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Selector de Cliente Registrado -->
                    <div class="form-control">
                        <label class="label-text font-semibold text-sm mb-1 block">
                            Cliente Registrado en Plataforma
                        </label>
                        <select id="customer-select" name="Meetings[customer_id]" class="select select-bordered w-full bg-base-200/40 focus:bg-base-100">
                            <option value="">-- Cliente Externo / No Registrado --</option>
                            <?php foreach ($customers as $c): ?>
                                <?php 
                                    $isSelected = ($model->customer_id == $c['id']) ? 'selected' : '';
                                    $displayContact = !empty($c['contact_name']) ? $c['contact_name'] : $c['business_name'];
                                ?>
                                <option value="<?= $c['id'] ?>" 
                                        data-name="<?= Html::encode($displayContact) ?>" 
                                        data-email="<?= Html::encode($c['email']) ?>"
                                        <?= $isSelected ?>>
                                    <?= Html::encode($c['business_name']) ?> (<?= Html::encode($c['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="text-xs opacity-50 mt-1">Si es un prospecto nuevo, déjalo en blanco y escribe su correo abajo.</span>
                    </div>

                    <!-- Nombre del Asistente -->
                    <div class="form-control">
                        <?= $form->field($model, 'client_name')->textInput([
                            'id' => 'client-name-input',
                            'placeholder' => 'Ej: Carlos Pérez',
                            'required' => true,
                        ])->label('Nombre del Asistente <span class="text-error">*</span>') ?>
                    </div>

                    <!-- Correo Electrónico del Asistente -->
                    <div class="form-control">
                        <?= $form->field($model, 'client_email')->input('email', [
                            'id' => 'client-email-input',
                            'placeholder' => 'carlos@empresa.com',
                            'required' => true,
                        ])->label('Correo Electrónico de Invitación <span class="text-error">*</span>') ?>
                    </div>
                </div>
            </div>

            <!-- Bloque 2: Fecha, Hora y Duración -->
            <div class="border-b border-base-200 pb-6">
                <h3 class="text-base font-bold text-base-content/80 flex items-center gap-2 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    Programación de Horario
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Fecha y Hora de Inicio -->
                    <div class="form-control">
                        <label class="label-text font-semibold text-sm mb-1 block">
                            Fecha y Hora de Inicio <span class="text-error">*</span>
                        </label>
                        <input type="datetime-local" 
                               name="Meetings[start_time]" 
                               id="start-time-input"
                               value="<?= date('Y-m-d\TH:i', strtotime($model->start_time ?: '+30 minutes')) ?>" 
                               class="input input-bordered w-full bg-base-200/40 focus:bg-base-100" 
                               required>
                        <span class="text-xs opacity-50 mt-1">Zona horaria: Bogotá / Colombia (UTC-5)</span>
                    </div>

                    <!-- Duración -->
                    <div class="form-control">
                        <label class="label-text font-semibold text-sm mb-1 block">
                            Duración Estimada <span class="text-error">*</span>
                        </label>
                        <select name="duration_minutes" class="select select-bordered w-full bg-base-200/40 focus:bg-base-100">
                            <option value="15" <?= $durationMinutes == 15 ? 'selected' : '' ?>>15 minutos (Rápida)</option>
                            <option value="30" <?= $durationMinutes == 30 ? 'selected' : '' ?>>30 minutos (Estándar)</option>
                            <option value="45" <?= $durationMinutes == 45 ? 'selected' : '' ?>>45 minutos</option>
                            <option value="60" <?= $durationMinutes == 60 ? 'selected' : '' ?>>60 minutos (1 hora)</option>
                            <option value="90" <?= $durationMinutes == 90 ? 'selected' : '' ?>>90 minutos (1 hora y media)</option>
                        </select>
                        <span class="text-xs opacity-50 mt-1">Se calculará automáticamente la hora de finalización del evento.</span>
                    </div>
                </div>
            </div>

            <!-- Bloque 3: Asunto y Agenda -->
            <div class="space-y-4">
                <h3 class="text-base font-bold text-base-content/80 flex items-center gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                    Detalle de la Sesión
                </h3>

                <div class="form-control">
                    <?= $form->field($model, 'title')->textInput([
                        'placeholder' => 'Ej: Sesión Técnica - Revisión de Servidores y Soporte',
                        'required' => true,
                    ])->label('Asunto / Título de la Reunión <span class="text-error">*</span>') ?>
                </div>

                <div class="form-control">
                    <?= $form->field($model, 'description')->textarea([
                        'rows' => 4,
                        'class' => 'textarea textarea-bordered w-full bg-base-200/40 focus:bg-base-100',
                        'placeholder' => 'Escribe aquí los temas a tratar o la agenda que verá el cliente en su invitación de Google Calendar...',
                    ])->label('Agenda / Notas para la Invitación (Opcional)') ?>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-base-200">
                <div class="text-xs opacity-60 flex items-center gap-1.5 text-center sm:text-left">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-success flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                    Se enviará una copia oculta (BCC) a <b>gerencia@atsys.co</b> para verificar el envío.
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <a href="<?= Url::to(['meetings/index']) ?>" class="btn btn-ghost btn-sm sm:btn-md">Cancelar</a>
                    <button type="submit" id="btn-submit-meeting" class="btn btn-primary gap-2 shadow-lg shadow-primary/20">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5m-9 3.75h.008v.008H12v-.008Z" /></svg>
                        Agendar y Enviar Invitación
                    </button>
                </div>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customer-select');
    const nameInput = document.getElementById('client-name-input');
    const emailInput = document.getElementById('client-email-input');
    const submitBtn = document.getElementById('btn-submit-meeting');
    const form = document.getElementById('meeting-form');

    // Auto-llenar campos al cambiar de cliente
    customerSelect.addEventListener('change', function() {
        const selectedOpt = customerSelect.options[customerSelect.selectedIndex];
        const dataName = selectedOpt.getAttribute('data-name');
        const dataEmail = selectedOpt.getAttribute('data-email');

        if (customerSelect.value && dataName && dataEmail) {
            nameInput.value = dataName;
            emailInput.value = dataEmail;
        } else if (!customerSelect.value) {
            // No forzar borrado si ya había escrito algo
        }
    });

    // Indicador visual de envío al presionar el botón
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> Conectando con n8n y Google...';
    });
});
</script>
