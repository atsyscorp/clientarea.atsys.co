<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
/* @var $customersList array */

$this->title = 'Editar Contrato: ' . $model->code;
?>

<div class="contracts-update">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary">
            <?= Html::encode($this->title) ?>
        </h1>
        <div class="flex gap-2">
            <?= Html::a('Ver Detalle', ['view', 'id' => $model->id], ['class' => 'btn btn-outline btn-sm']) ?>
            <?= Html::a('← Volver', ['index'], ['class' => 'btn btn-ghost btn-sm']) ?>
        </div>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'customersList' => $customersList,
    ]) ?>

</div>
