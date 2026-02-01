<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\CustomerServices $model */

$this->title = 'Editar Servicio: ' . ($model->domain ?? $model->product->name);
?>
<div class="customer-services-update">

    <h1 class="text-2xl font-bold mb-6"><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'customers' => $customers,
        'products' => $products,
    ]) ?>

</div>