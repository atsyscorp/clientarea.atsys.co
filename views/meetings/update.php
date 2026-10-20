<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\models\Meetings;

/** @var yii\web\View $this */
/** @var app\models\Meetings $model */
/** @var array $customers */
/** @var int $durationMinutes */
/** @var bool $wasScheduled */

$this->title = 'Editar / Reprogramar Reunión: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Reuniones', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>

<div class="meetings-update max-w-4xl mx-auto space-y-6">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-base-content/60 mb-1">
                <a href="<?= Url::to(['meetings/view', 'id' => $model->id]) ?>" class="hover:text-primary transition-colors flex items-center gap-1.5 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    Volver a la Reunión
                </a>
            </div>
            <h1 class="text-3xl font-bold text-base-content/85 flex items-center gap-3">
                <span class="p-2.5 bg-primary/10 text-primary rounded-xl inline-flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                </span>
                Editar / Reprogramar Reunión
            </h1>
            <p class="text-sm opacity-60 mt-1">Ajusta el horario, modifica los participantes o actualiza la agenda de la sesión.</p>
        </div>
        <div class="flex items-center gap-2">
            <?= $model->getStatusBadge() ?>
        </div>
    </div>

    <!-- Alertas contextuales según el estado -->
    <?php if ($model->status === Meetings::STATUS_PENDING): ?>
        <div class="alert alert-warning/20 border border-warning/40 shadow-sm text-sm">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mt-0.5 text-warning flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                <div>
                    <span class="font-bold text-base-content">Solicitud Pendiente:</span>
                    Esta cita fue solicitada por el cliente. Si coordinaste una hora distinta (ej: 9:30 en lugar de 9:00), ajusta el horario aquí y guarda los cambios. Luego podrás pulsar <b>"Aprobar y Generar Meet"</b> para que n8n cree el evento en Google Calendar con la hora ajustada.
                </div>
            </div>
        </div>
    <?php elseif ($wasScheduled): ?>
        <div class="alert alert-info shadow-sm border border-info/20 text-sm">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mt-0.5 text-info flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                <div>
                    <span class="font-bold text-base-content">Reunión con Google Meet Activo:</span>
                    Esta reunión ya fue aprobada y cuenta con sala de Meet generada. Al reprogramar la hora, el sistema enviará la actualización oficial al cliente por email con el archivo de calendario (<code class="text-xs bg-base-100 px-1 py-0.5 rounded">.ics</code>) actualizado.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Formulario de Edición -->
    <div class="card bg-base-100 shadow-md border border-base-200">
        <div class="card-body p-6">
            <?php $form = ActiveForm::begin([
                'id' => 'meeting-update-form',
                'enableClientScript' => false,
                'enableClientValidation' => false,
                'options' => ['class' => 'space-y-6'],
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'label-text font-semibold text-sm mb-1 block'],
                    'inputOptions' => ['class' => 'input input-bordered w-full bg-base-200/40 focus:bg-base-100 transition-colors'],
                    'errorOptions' => ['text-error text-xs mt-1 block'],
                ],
            ]); ?>

            <!-- Bloque 1: Participante -->
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

            <!-- Bloque 2: Programación de Horario (Reprogramar) -->
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
                               value="<?= Html::encode($model->start_time) ?>" 
                               class="input input-bordered w-full bg-base-200/40 focus:bg-base-100 font-mono" 
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
                            <option value="120" <?= $durationMinutes == 120 ? 'selected' : '' ?>>120 minutos (2 horas)</option>
                        </select>
                        <span class="text-xs opacity-50 mt-1">Se recalculará automáticamente la hora de finalización del evento.</span>
                    </div>
                </div>

                <!-- Notificación por email si la reunión ya estaba agendada -->
                <?php if ($wasScheduled): ?>
                    <div class="mt-4 pt-3 border-t border-base-200/60">
                        <label class="label cursor-pointer justify-start gap-3">
                            <input type="checkbox" name="send_update_email" value="1" checked class="checkbox checkbox-primary checkbox-sm">
                            <span class="label-text text-xs font-semibold text-base-content/80">
                                Enviar correo de notificación de reprogramación con el archivo de calendario (<code class="bg-base-200 px-1 py-0.5 rounded">.ics</code>) actualizado a <?= Html::encode($model->client_email) ?>
                            </span>
                        </label>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bloque 3: Asunto, Estado y Detalles -->
            <div class="space-y-4">
                <h3 class="text-base font-bold text-base-content/80 flex items-center gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                    Detalles y Estado
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-control md:col-span-2">
                        <?= $form->field($model, 'title')->textInput([
                            'placeholder' => 'Ej: Sesión Técnica - Revisión de Servidores y Soporte',
                            'required' => true,
                        ])->label('Asunto / Título de la Reunión <span class="text-error">*</span>') ?>
                    </div>

                    <div class="form-control">
                        <label class="label-text font-semibold text-sm mb-1 block">Estado de la Cita</label>
                        <select name="Meetings[status]" class="select select-bordered w-full bg-base-200/40 focus:bg-base-100">
                            <?php foreach (Meetings::optsStatus() as $stKey => $stLabel): ?>
                                <option value="<?= $stKey ?>" <?= $model->status === $stKey ? 'selected' : '' ?>>
                                    <?= Html::encode($stLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-control">
                    <?= $form->field($model, 'description')->textarea([
                        'rows' => 3,
                        'class' => 'textarea textarea-bordered w-full bg-base-200/40 focus:bg-base-100',
                        'placeholder' => 'Temas a tratar o agenda visible en la invitación...',
                    ])->label('Agenda / Descripción (Visible en la Invitación)') ?>
                </div>

                <div class="form-control">
                    <?= $form->field($model, 'notes')->textarea([
                        'rows' => 3,
                        'class' => 'textarea textarea-bordered w-full bg-base-200/40 focus:bg-base-100 font-mono text-xs',
                        'placeholder' => 'Notas internas del equipo, minuta o acuerdos...',
                    ])->label('Notas Internas / Minuta del Equipo') ?>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-6 border-t border-base-200">
                <a href="<?= Url::to(['meetings/view', 'id' => $model->id]) ?>" class="btn btn-ghost btn-sm gap-1.5 w-full sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    Cancelar
                </a>

                <button type="submit" id="btn-submit-update" class="btn btn-primary text-white gap-2 w-full sm:w-auto px-6 shadow-md shadow-primary/20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    Guardar Cambios
                </button>
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

    if (customerSelect) {
        customerSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            if (this.value) {
                const name = selected.getAttribute('data-name');
                const email = selected.getAttribute('data-email');
                if (name && nameInput) nameInput.value = name;
                if (email && emailInput) emailInput.value = email;
            }
        });
    }

    const form = document.getElementById('meeting-update-form');
    const submitBtn = document.getElementById('btn-submit-update');
    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> Guardando cambios...';
        });
    }
});
</script>
