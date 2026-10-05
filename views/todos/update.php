<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Todos $model */

$this->title = 'Editar Tarea: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'To-Do List', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>
<div class="todos-update space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-base-content/85 flex items-center gap-3">
                <span class="p-2 bg-primary/10 text-primary rounded-xl inline-flex items-center justify-center">
                    <i class="fas fa-edit text-xl"></i>
                </span>
                Editar Tarea
            </h1>
            <p class="text-sm text-base-content/60 mt-1"><?= Html::encode($model->title) ?></p>
        </div>
        <div>
            <?= Html::a('<i class="fas fa-eye mr-1"></i> Ver Tarea', ['view', 'id' => $model->id], ['class' => 'btn btn-ghost']) ?>
        </div>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
