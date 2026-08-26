<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\Customers;

/* @var $this yii\web\View */
/* @var $model app\models\OrdersSearch */
/* @var $form yii\widgets\ActiveForm */
/* @var $isAdmin bool */

$clientesList = ArrayHelper::map(Customers::find()->orderBy('business_name')->all(), 'id', 'business_name');
$estadosList = [
    0 => 'Pendiente',
    1 => 'Pagado',
    2 => 'Activo',
    3 => 'Cancelado',
];
?>

<div class="orders-search bg-base-200 py-6 rounded-xl mb-6 shadow-sm">
    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="w-full bg-base-100 shadow-xl rounded-box border border-base-200 mb-3 flex md:flex-row flex-col gap-4 p-3">
        
        <div class="form-control flex-1">
            <?= $form->field($model, 'code', [
                'template' => '{input}'
            ])->textInput([
                'class' => 'input input-bordered w-full',
                'placeholder' => 'Código de orden (Ej: ORD-12345)', 'aria-label' => 'Código de orden (Ej: ORD-12345)'
            ])->label(false) ?>
        </div>
        
        <?php if ($isAdmin): ?>
        <div class="form-control flex-1">
            <?= $form->field($model, 'customer_id', [
                'template' => '{input}'
            ])->dropDownList($clientesList, [
                'class' => 'select select-bordered w-full',
                'prompt' => 'Todos los clientes', 'aria-label' => 'Filtrar por cliente'
            ])->label(false) ?>
        </div>
        <?php endif; ?>

        <div class="form-control flex-1">
            <?= $form->field($model, 'status', [
                'template' => '{input}'
            ])->dropDownList($estadosList, [
                'class' => 'select select-bordered w-full',
                'prompt' => 'Cualquier estado', 'aria-label' => 'Filtrar por estado'
            ])->label(false) ?>
        </div>

        <div class="flex gap-2">
            <?= Html::submitButton('Buscar', ['class' => 'btn btn-primary flex-1']) ?>
            <?= Html::a('Limpiar', ['index'], ['class' => 'btn btn-outline flex-1']) ?>
        </div>

    </div>

    <?php ActiveForm::end(); ?>
</div>
