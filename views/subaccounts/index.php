<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Mi Equipo (Sub-cuentas)';
?>

<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold"><?= Html::encode($this->title) ?></h1>
            <p class="text-base-content/60 mt-1">Gestiona los accesos de tu equipo a los tickets de soporte.</p>
        </div>
        <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Añadir Delegado', ['create'], ['class' => 'btn btn-primary gap-2 text-white']) ?>
    </div>

    <div class="card bg-base-100 shadow-xl border border-base-200">
        <div class="card-body p-0 overflow-x-auto">
            <?= GridView::widget([
                'dataProvider' => $dataProvider, // Necesitas pasar esto desde tu actionIndex()
                'layout' => "{items}\n<div class='p-4 border-t border-base-200'>{pager}</div>",
                'tableOptions' => ['class' => 'table table-zebra w-full'],
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    
                    [
                        'attribute' => 'contact_name', // O el nombre de tu columna
                        'label' => 'Nombre',
                        'format' => 'raw',
                        'value' => function($data) {
                            return '<div class="font-bold">' . Html::encode($data->contact_name ?? 'Sin nombre') . '</div>';
                        }
                    ],
                    'email:email',
                    [
                        'attribute' => 'created_at',
                        'label' => 'Fecha de Creación',
                        'format' => 'date',
                    ],
                    [
                        'attribute' => 'role',
                        'label' => 'Nivel',
                        'format' => 'raw',
                        'value' => function($data) {
                            if ($data->role == 12) {
                                return '<span class="badge badge-warning font-bold">ADMIN (BACKUP)</span>';
                            }
                            return '<span class="badge badge-ghost">Estándar</span>';
                        }
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{update} {delete}',
                        'buttons' => [
                            'update' => function ($url, $model, $key) {
                                if($model->id !== Yii::$app->user->identity->id) {
                                    return Html::a('<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>', ['update', 'id' => $model->id], ['class' => 'btn btn-sm btn-square btn-ghost text-info']);
                                }
                            },
                            'delete' => function ($url, $model, $key) {
                                // Si el usuario actual es uno con administración, no se puede borrar
                                if($model->id !== Yii::$app->user->identity->id) {
                                    return Html::a('<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>', ['delete', 'id' => $model->id], [
                                        'class' => 'btn btn-sm btn-square btn-ghost text-error',
                                        'data-confirm' => '¿Estás seguro?',
                                        'data-method' => 'post',
                                    ]);
                                }
                            }
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>