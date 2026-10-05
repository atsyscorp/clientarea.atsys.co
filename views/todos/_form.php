<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use app\models\Todos;

/** @var yii\web\View $this */
/** @var app\models\Todos $model */
/** @var yii\widgets\ActiveForm $form */
/** @var array $customersList */
/** @var array $adminsList */
?>

<div class="todos-form">

    <?php 
    $form = ActiveForm::begin([
        'options' => ['class' => 'space-y-6'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'options' => ['class' => 'form-control w-full'],
            'labelOptions' => ['class' => 'label label-text font-semibold text-sm'],
            'inputOptions' => ['class' => 'input input-bordered w-full focus:input-primary'],
            'errorOptions' => ['class' => 'text-error text-xs mt-1'],
        ],
    ]); 
    ?>

    <!-- 1. Tarjeta Principal: Título y Detalles -->
    <div class="card w-full bg-base-100 shadow-xl border border-base-200">
        <div class="card-body p-6">
            <h2 class="card-title text-primary border-b border-base-200 pb-3 mb-4 text-lg">
                <i class="fas fa-tasks mr-2"></i> Información de la Tarea
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-12">
                    <?= $form->field($model, 'title')->textInput([
                        'placeholder' => 'Ej: Actualizar certificado SSL en servidor principal o auditar backups',
                        'class' => 'input input-bordered w-full input-lg font-medium focus:input-primary'
                    ]) ?>
                </div>

                <div class="md:col-span-12">
                    <?= $form->field($model, 'description')->textarea([
                        'rows' => 4,
                        'placeholder' => 'Describe los detalles, requerimientos, enlaces o pasos necesarios para esta tarea...',
                        'class' => 'textarea textarea-bordered w-full focus:textarea-primary text-sm'
                    ]) ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-2">
                <div>
                    <?= $form->field($model, 'priority')->dropDownList(
                        Todos::optsPriority(),
                        ['class' => 'select select-bordered w-full focus:select-primary']
                    ) ?>
                </div>

                <div>
                    <?= $form->field($model, 'status')->dropDownList(
                        Todos::optsStatus(),
                        ['class' => 'select select-bordered w-full focus:select-primary']
                    ) ?>
                </div>

                <div>
                    <?= $form->field($model, 'assigned_to')->dropDownList(
                        $adminsList,
                        ['class' => 'select select-bordered w-full focus:select-primary']
                    )->label('Administrador Responsable') ?>
                </div>

                <div>
                    <?= $form->field($model, 'estimated_minutes')->input('number', [
                        'placeholder' => 'Ej: 60',
                        'min' => 0,
                        'step' => 15,
                        'class' => 'input input-bordered w-full focus:input-primary'
                    ])->label('Estimado (Minutos)') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Tarjeta: Planificación, Recordatorios y Cliente -->
    <div class="card w-full bg-base-100 shadow-xl border border-base-200">
        <div class="card-body p-6">
            <h2 class="card-title text-primary border-b border-base-200 pb-3 mb-4 text-lg">
                <i class="fas fa-calendar-alt mr-2"></i> Planificación y Recordatorios
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-6">
                    <?= $form->field($model, 'customer_id')->dropDownList(
                        $customersList,
                        [
                            'prompt' => '-- Tarea interna / Sin cliente vinculado --',
                            'class' => 'select select-bordered w-full focus:select-primary'
                        ]
                    )->label('Cliente Asociado (Opcional)') ?>
                </div>

                <div class="md:col-span-3">
                    <?php 
                    $dueDateVal = !empty($model->due_date) ? date('Y-m-d\TH:i', strtotime($model->due_date)) : '';
                    ?>
                    <?= $form->field($model, 'due_date')->input('datetime-local', [
                        'value' => $dueDateVal,
                        'class' => 'input input-bordered w-full focus:input-primary'
                    ])->label('Fecha Límite (Entrega)') ?>
                </div>

                <div class="md:col-span-3">
                    <?php 
                    $reminderVal = !empty($model->reminder_at) ? date('Y-m-d\TH:i', strtotime($model->reminder_at)) : '';
                    ?>
                    <?= $form->field($model, 'reminder_at')->input('datetime-local', [
                        'value' => $reminderVal,
                        'id' => 'input-reminder-at',
                        'class' => 'input input-bordered w-full focus:input-primary'
                    ])->label('🔔 Recordar con Notificación') ?>
                    <span class="text-xs text-base-content/50 block mt-1">
                        Recibirás una alerta en el panel cuando llegue esta hora.
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($model->isNewRecord): ?>
        <!-- 3. Tarjeta: Subtareas Iniciales / Checklist Rápida -->
        <div class="card w-full bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-6">
                <h2 class="card-title text-primary border-b border-base-200 pb-3 mb-2 text-lg">
                    <i class="fas fa-list-check mr-2"></i> Subtareas Iniciales (Opcional)
                </h2>
                <p class="text-xs text-base-content/60 mb-3">
                    Escribe los pasos o entregables clave para esta tarea (un paso por línea). Podrás marcarlos interactivamente como completados luego.
                </p>

                <textarea name="initial_checklist" rows="3" aria-label="Subtareas iniciales (un paso por línea)" class="textarea textarea-bordered w-full focus:textarea-primary text-sm font-mono" placeholder="Paso 1: Respaldar base de datos&#10;Paso 2: Aplicar script de migración&#10;Paso 3: Probar inicio de sesión"></textarea>
            </div>
        </div>
    <?php endif; ?>

    <div class="flex justify-end gap-3 mt-6">
        <?= Html::a('<i class="fas fa-times mr-1"></i> Cancelar', ['index'], ['class' => 'btn btn-ghost']) ?>
        <?= Html::submitButton('<i class="fas fa-save mr-2"></i> ' . ($model->isNewRecord ? 'Crear Tarea' : 'Guardar Cambios'), [
            'class' => 'btn btn-primary text-white shadow-lg px-8'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
