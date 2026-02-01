<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Customers $model */

$this->title = 'Nuevo servicio';
$this->params['breadcrumbs'][] = ['label' => 'Clientes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="customers-create">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'products' => $products,
        'customers' => $customers,
        'lockedCustomer' => $lockedCustomer,
    ]) ?>

</div>
