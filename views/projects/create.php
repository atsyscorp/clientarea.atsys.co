<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Projects */

$this->title = 'Nuevo Proyecto / Filial';
?>

<div class="projects-create space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
            <p class="text-sm text-base-content/70 mt-1">Registra un nuevo proyecto o empresa filial para un cliente.</p>
        </div>
        <?= Html::a('Volver al Listado', ['index'], ['class' => 'btn btn-ghost']) ?>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
