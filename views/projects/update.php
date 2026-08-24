<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Projects */

$this->title = 'Editar Proyecto: ' . $model->name;
?>

<div class="projects-update space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
            <p class="text-sm text-base-content/70 mt-1">Modifica los detalles del proyecto o empresa filial.</p>
        </div>
        <?= Html::a('Ver Proyecto', ['view', 'id' => $model->id], ['class' => 'btn btn-ghost']) ?>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
