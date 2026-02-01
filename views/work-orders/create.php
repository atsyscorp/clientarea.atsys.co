<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\WorkOrders */

$this->title = 'Nueva Orden de Trabajo';
?>
<div class="work-orders-create max-w-4xl mx-auto mt-6">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-ghost']) ?>
    </div>

    <div class="alert alert-info shadow-lg mb-6 text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span>Al guardar esta orden, se generará el PDF y se enviará automáticamente al correo del cliente seleccionado.</span>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'customers' => $customers,
    ]) ?>

</div>