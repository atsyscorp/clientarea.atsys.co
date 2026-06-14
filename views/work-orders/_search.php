<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\Customers;
use app\models\WorkOrders;

// Preparamos las listas para los dropdowns
$clientesList = ArrayHelper::map(Customers::find()->orderBy('business_name')->all(), 'id', 'business_name');
$estadosList = [
    WorkOrders::STATUS_DRAFT => 'Borrador',
    WorkOrders::STATUS_PENDING => 'Pendiente',
    WorkOrders::STATUS_APPROVED => 'Aprobada',
    WorkOrders::STATUS_REJECTED => 'Rechazada',
    WorkOrders::STATUS_COMPLETED => 'Finalizada',
    WorkOrders::STATUS_NOT_COMPLETED => 'No Finalizada',
    WorkOrders::STATUS_PARTIAL => 'Parcialmente Finalizada',
];
?>

<div class="work-order-search bg-base-200 py-6 rounded-xl mb-6 shadow-sm">
    <?php $form = ActiveForm::begin([
        'action' => ['index'], // Envia los datos a la misma acción index
        'method' => 'get',     // DEBE ser GET para que la URL guarde los filtros
    ]); ?>

    <div class="w-full bg-base-100 shadow-xl rounded-box border border-base-200 mb-3 flex md:flex-row flex-col gap-4 p-3">
        
        <?php if ($isAdmin): ?>
        <div class="form-control">
            <?= $form->field($model, 'customer_id', [
                'template' => '{input}'
            ])->dropDownList($clientesList, [
                'class' => 'select select-bordered w-full',
                'prompt' => 'Todos los clientes'
            ])->label(false) ?>
        </div>
        <?php endif; ?>

        <div class="form-control">
            <?= $form->field($model, 'status', [
                'template' => '{input}'
            ])->dropDownList($estadosList, [
                'class' => 'select select-bordered w-full',
                'prompt' => 'Cualquier estado'
            ])->label(false) ?>
        </div>

        <div class="flex gap-2">
            <?= Html::submitButton('Buscar', ['class' => 'btn btn-primary flex-1']) ?>
            <?= Html::a('Limpiar', ['index'], ['class' => 'btn btn-outline flex-1']) ?>
        </div>

    </div>

    <?php ActiveForm::end(); ?>
</div>
