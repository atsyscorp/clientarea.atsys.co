<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\User $model */

$this->title = 'Editar Usuario: ' . $model->username;
$this->params['breadcrumbs'][] = ['label' => 'Usuarios', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Editar';
?>
<div class="users-update max-w-3xl mx-auto">

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Volver al listado', ['index'], ['class' => 'btn btn-ghost btn-sm']) ?>
    </div>

    <div class="card bg-base-100 shadow-xl border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-primary border-b border-base-200 pb-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Datos Generales del Usuario
            </h2>

            <?php
            $form = ActiveForm::begin([
                'options' => ['class' => 'space-y-6'],
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'options' => ['class' => 'form-control w-full'],
                    'labelOptions' => ['class' => 'label label-text font-semibold'],
                    'inputOptions' => ['class' => 'input input-bordered w-full focus:input-primary'],
                    'errorOptions' => ['class' => 'text-error text-sm mt-1'],
                ],
            ]);
            ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?= $form->field($model, 'username')->textInput(['placeholder' => 'Ej: jgomez']) ?>
                <?= $form->field($model, 'email')->input('email', ['placeholder' => 'correo@ejemplo.com']) ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?= $form->field($model, 'mobile')->textInput(['placeholder' => 'Ej: +573001234567']) ?>
                
                <?= $form->field($model, 'role')->dropDownList([
                    20 => 'Administrador',
                    10 => 'Cliente (Titular)',
                    11 => 'Subcuenta (Soporte)',
                    12 => 'Delegado Admin (Backup)',
                ], ['class' => 'select select-bordered w-full focus:select-primary']) ?>

                <?= $form->field($model, 'status')->dropDownList([
                    10 => 'Activo',
                    9 => 'Inactivo',
                    0 => 'Eliminado',
                ], ['class' => 'select select-bordered w-full focus:select-primary']) ?>
            </div>

            <!-- Campo contraseña opcional -->
            <div class="form-control w-full mt-4">
                <label class="label label-text font-semibold">Cambiar Contraseña (Opcional)</label>
                <input type="password" name="new_password" placeholder="Dejar en blanco para conservar la actual" class="input input-bordered w-full focus:input-primary" />
                <label class="label">
                    <span class="label-text-alt text-base-content/50">Mínimo 6 caracteres si deseas cambiarla.</span>
                </label>
            </div>

            <div class="card-actions justify-end border-t border-base-200 pt-4 mt-6">
                <?= Html::submitButton('Guardar Cambios', ['class' => 'btn btn-primary text-white px-6']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
