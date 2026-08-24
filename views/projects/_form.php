<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Customers;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\Projects */
/* @var $form yii\widgets\ActiveForm */

$customersList = ArrayHelper::map(Customers::find()->orderBy(['business_name' => SORT_ASC])->all(), 'id', 'business_name');
?>

<div class="projects-form card bg-base-100 shadow-xl border border-base-200 p-6">

    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'space-y-6']
    ]); ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <?= $form->field($model, 'customer_id')->dropDownList(
                $customersList,
                [
                    'prompt' => '-- Seleccionar Cliente --',
                    'class' => 'select select-bordered w-full',
                    'disabled' => !$model->isNewRecord,
                ]
            ) ?>
        </div>

        <div>
            <?= $form->field($model, 'name')->textInput([
                'maxlength' => true,
                'class' => 'input input-bordered w-full',
                'placeholder' => 'Ej: Empresa Filial A / Rediseño Portal Web'
            ]) ?>
        </div>
    </div>

    <div class="divider text-xs font-semibold text-base-content/60">DATOS EMPRESARIALES / FILIAL (OPCIONAL)</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <?= $form->field($model, 'business_name')->textInput([
                'maxlength' => true,
                'class' => 'input input-bordered w-full',
                'placeholder' => 'Razón social específica de la filial o empresa'
            ]) ?>
        </div>

        <div>
            <?= $form->field($model, 'document_number')->textInput([
                'maxlength' => true,
                'class' => 'input input-bordered w-full',
                'placeholder' => 'NIT o Documento fiscal de la filial'
            ]) ?>
        </div>
    </div>

    <div>
        <?= $form->field($model, 'address')->textarea([
            'rows' => 2,
            'class' => 'textarea textarea-bordered w-full',
            'placeholder' => 'Dirección física de la sede o filial'
        ]) ?>
    </div>

    <div class="divider text-xs font-semibold text-base-content/60">CONFIGURACIÓN Y ESTADO</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <?= $form->field($model, 'status')->dropDownList(
                [
                    \app\models\Projects::STATUS_ACTIVE => 'Activo',
                    \app\models\Projects::STATUS_INACTIVE => 'Inactivo/Archivado',
                ],
                ['class' => 'select select-bordered w-full']
            ) ?>
        </div>

        <div class="flex items-center pt-6">
            <?= $form->field($model, 'is_default')->checkbox([
                'class' => 'checkbox checkbox-primary mr-2',
                'disabled' => $model->is_default && !$model->isNewRecord,
            ])->label('Marcar como Proyecto Predeterminado del Cliente') ?>
        </div>
    </div>

    <div>
        <?= $form->field($model, 'notes')->textarea([
            'rows' => 3,
            'class' => 'textarea textarea-bordered w-full',
            'placeholder' => 'Notas internas sobre este proyecto o empresa'
        ]) ?>
    </div>

    <div class="flex justify-end gap-3 pt-4 border-t border-base-200">
        <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-ghost']) ?>
        <?= Html::submitButton($model->isNewRecord ? 'Crear Proyecto' : 'Guardar Cambios', [
            'class' => 'btn btn-primary text-white shadow-md'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
