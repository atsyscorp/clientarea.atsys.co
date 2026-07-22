<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
/* @var $form yii\widgets\ActiveForm */
/* @var $customersList array */

?>

<div class="card bg-base-100 shadow-xl border border-base-200">
    <div class="card-body">

        <?php $form = ActiveForm::begin([
            'options' => ['enctype' => 'multipart/form-data'],
        ]); ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Código de Contrato -->
            <div class="form-control w-full">
                <?php if ($model->isNewRecord): ?>
                    <?= $form->field($model, 'code')->textInput([
                        'maxlength' => true,
                        'class' => 'input input-bordered font-mono font-bold text-primary w-full',
                        'placeholder' => 'ej: ATC-2026-001'
                    ])->label('Código del Contrato (Sugerido / Modificable)') ?>
                <?php else: ?>
                    <?= $form->field($model, 'code')->textInput([
                        'disabled' => true,
                        'class' => 'input input-bordered font-mono font-bold w-full bg-base-200 opacity-80 cursor-not-allowed',
                    ])->label('Código del Contrato (Inhabilitado para edición)') ?>
                <?php endif; ?>
            </div>

            <!-- Cliente -->
            <div class="form-control w-full">
                <?= $form->field($model, 'customer_id')->dropDownList(
                    $customersList,
                    ['prompt' => '-- Seleccione un Cliente --', 'class' => 'select select-bordered w-full']
                ) ?>
            </div>

            <!-- Título / Objeto -->
            <div class="form-control w-full">
                <?= $form->field($model, 'title')->textInput([
                    'maxlength' => true,
                    'placeholder' => 'ej: Contrato de Desarrollo Web y Mantenimiento Anual',
                    'class' => 'input input-bordered w-full'
                ]) ?>
            </div>

            <!-- Monto Total -->
            <div class="form-control w-full">
                <?= $form->field($model, 'total_amount')->textInput([
                    'type' => 'number',
                    'step' => '0.01',
                    'class' => 'input input-bordered w-full'
                ]) ?>
            </div>

            <!-- Moneda -->
            <div class="form-control w-full">
                <?= $form->field($model, 'currency')->dropDownList([
                    'COP' => 'COP (Pesos Colombianos)',
                    'USD' => 'USD (Dólares)',
                    'EUR' => 'EUR (Euros)',
                ], ['class' => 'select select-bordered w-full']) ?>
            </div>

            <!-- Fecha Inicio -->
            <div class="form-control w-full">
                <?= $form->field($model, 'start_date')->input('date', ['class' => 'input input-bordered w-full']) ?>
            </div>

            <!-- Fecha Vencimiento -->
            <div class="form-control w-full">
                <?= $form->field($model, 'end_date')->input('date', ['class' => 'input input-bordered w-full']) ?>
            </div>

            <!-- Estado -->
            <div class="form-control w-full">
                <?= $form->field($model, 'status')->dropDownList(
                    \app\models\Contracts::getStatusOptions(),
                    ['class' => 'select select-bordered w-full']
                ) ?>
            </div>

            <!-- Modo de Avance -->
            <div class="form-control w-full">
                <?= $form->field($model, 'progress_mode')->dropDownList([
                    0 => 'Automático (Calculado por OTs / Tareas)',
                    1 => 'Manual (Fijado por el Administrador)',
                ], ['class' => 'select select-bordered w-full', 'id' => 'progress-mode-select']) ?>
            </div>

            <!-- % Avance (Si modo es manual) -->
            <div class="form-control w-full">
                <?= $form->field($model, 'progress_percentage')->textInput([
                    'type' => 'number',
                    'step' => '0.1',
                    'min' => '0',
                    'max' => '100',
                    'class' => 'input input-bordered w-full',
                    'placeholder' => '0 - 100%'
                ])->label('% de Avance Manual') ?>
            </div>

            <!-- Archivo Adjunto PDF del Contrato -->
            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text font-bold">Documento del Contrato (PDF)</span>
                </label>
                <?= $form->field($model, 'attachmentFile')->fileInput([
                    'class' => 'file-input file-input-bordered file-input-primary w-full',
                    'accept' => '.pdf,.doc,.docx,.zip,.rar'
                ])->label(false) ?>

                <?php if ($model->contract_file): ?>
                    <p class="text-xs text-success mt-1">
                        Archivo actual: <a href="<?= Html::encode($model->contract_file) ?>" target="_blank" class="underline font-bold">Ver / Descargar PDF</a>
                    </p>
                <?php endif; ?>
            </div>

        </div>

        <!-- Descripción / Detalles -->
        <div class="form-control w-full mt-4">
            <?= $form->field($model, 'description')->textarea([
                'rows' => 4,
                'placeholder' => 'Escriba aquí el alcance, entregables clave o cláusulas importantes del contrato...',
                'class' => 'textarea textarea-bordered w-full'
            ]) ?>
        </div>

        <div class="card-actions justify-end mt-6 border-t pt-4">
            <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-ghost']) ?>
            <?= Html::submitButton($model->isNewRecord ? 'Registrar Contrato' : 'Guardar Cambios', ['class' => 'btn btn-primary text-white font-bold px-8']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>
