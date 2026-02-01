<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\WorkOrders */

$this->title = 'Actualizar Orden de Trabajo ' . $model->code;
?>
<div class="work-orders-create max-w-4xl mx-auto mt-6">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-ghost']) ?>
    </div>

    <?= $this->render('_form', [
        'model' => $model
    ]) ?>

</div>