<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Catálogo de Productos';
?>
<div class="products-index">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
        <?= Html::a('
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Crear Nuevo Producto', ['create'], ['class' => 'btn btn-primary text-white shadow-lg']) ?>
    </div>

    <div class="overflow-x-auto bg-base-100 shadow-xl rounded-box">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'table table-zebra w-full'],
            'summary' => '<div class="p-4 text-sm text-base-content/70">Mostrando <b>{begin}-{end}</b> de <b>{totalCount}</b> productos.</div>',
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                'name',
                //'description:ntext',
                [
                    'attribute' => 'price',
                    'format' => ['currency'],
                    'contentOptions' => ['class' => 'font-mono font-bold'],
                ],
                [
                    'attribute' => 'status',
                    'header' => 'Estado',
                    'format' => 'raw',
                    'value' => function($model) {
                        return $model->status ? 
                            '<span class="badge badge-success">Activo</span>' : 
                            '<span class="badge badge-ghost">Inactivo</span>';
                    }
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => 'Acciones',
                    'template' => '{update} {delete}',
                    'buttonOptions' => ['class' => 'btn btn-ghost btn-xs'],
                    'buttons' => [
                        'update' => function ($url, $model) {
                            // Ícono de OJO (Heroicons)
                            return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>', 
                            $url, [
                                'class' => 'btn btn-square btn-ghost btn-xs', // Botón pequeño y transparente
                                'title' => 'Editar',
                            ]);
                        },
                        'delete' => function ($url, $model) {
                            // Ícono de BASURA
                            return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>', 
                            $url, [
                                'class' => 'btn btn-square btn-ghost btn-xs text-error', // Color rojo
                                'data-confirm' => 'Se eliminará este producto y se mostrará como [Desconocido] a cualquier cliente que tenga este producto. ¿Desea continuar?',
                                'data-method' => 'post',
                                'title' => 'Eliminar producto',
                            ]);
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>
</div>