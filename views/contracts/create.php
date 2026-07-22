<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
/* @var $customersList array */

$this->title = 'Registrar Nuevo Contrato';
?>

<div class="contracts-create">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary">
            <?= Html::encode($this->title) ?>
        </h1>
        <?= Html::a('← Volver a Contratos', ['index'], ['class' => 'btn btn-ghost btn-sm']) ?>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'customersList' => $customersList,
    ]) ?>

</div>
