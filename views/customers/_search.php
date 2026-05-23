<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

// Preparamos la lista de estados
$estadosList = [
    'active' => 'Activo',
    'inactive' => 'Inactivo',
    'prospect' => 'Prospecto',
];
?>

<div class="customer-search bg-base-200 py-6 rounded-xl mb-6 shadow-sm no-print">
    <?php $form = ActiveForm::begin([
        'action' => ['index'], // Envía los datos a la misma acción index
        'method' => 'get',     // DEBE ser GET para que la URL guarde los filtros
    ]); ?>

    <div class="w-full bg-base-100 shadow-xl rounded-box border border-base-200 mb-3 flex md:flex-row flex-col gap-4 p-3">
        
        <div class="form-control flex-1">
            <?= $form->field($model, 'name', [
                'template' => '{input}' // Evita divs extras de Bootstrap
            ])->textInput([
                'class' => 'input input-bordered w-full',
                'placeholder' => 'Nombre o Razón Social'
            ])->label(false) ?>
        </div>
        
        <div class="form-control flex-1">
            <?= $form->field($model, 'email', [
                'template' => '{input}'
            ])->textInput([
                'class' => 'input input-bordered w-full',
                'placeholder' => 'Correo electrónico'
            ])->label(false) ?>
        </div>

        <div class="form-control flex-1">
            <?= $form->field($model, 'phone', [
                'template' => '{input}'
            ])->textInput([
                'class' => 'input input-bordered w-full',
                'placeholder' => 'Teléfono / Móvil'
            ])->label(false) ?>
        </div>

        <div class="form-control w-full md:w-48">
            <?= $form->field($model, 'status', [
                'template' => '{input}'
            ])->dropDownList($estadosList, [
                'class' => 'select select-bordered w-full',
                'prompt' => 'Cualquier estado'
            ])->label(false) ?>
        </div>

        <div class="flex gap-2">
            <?= Html::submitButton('Buscar', ['class' => 'btn btn-primary px-6']) ?>
            <?= Html::a('Limpiar', ['index'], ['class' => 'btn btn-outline']) ?>
        </div>

    </div>

    <?php ActiveForm::end(); ?>
</div>
