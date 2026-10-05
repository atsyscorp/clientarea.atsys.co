<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Todos $model */
/** @var array $customersList */
/** @var array $adminsList */

$this->title = 'Nueva Tarea (To-Do)';
$this->params['breadcrumbs'][] = ['label' => 'To-Do List', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="todos-create space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-base-content/85 flex items-center gap-3">
                <span class="p-2 bg-primary/10 text-primary rounded-xl inline-flex items-center justify-center">
                    <i class="fas fa-plus-circle text-xl"></i>
                </span>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-sm text-base-content/60 mt-1">Crea una nueva tarea para medir tiempo, dar seguimiento y programar recordatorios.</p>
        </div>
        <div>
            <?= Html::a('<i class="fas fa-arrow-left mr-1"></i> Volver al Tablero', ['index'], ['class' => 'btn btn-ghost']) ?>
        </div>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'customersList' => $customersList,
        'adminsList' => $adminsList,
    ]) ?>

</div>
