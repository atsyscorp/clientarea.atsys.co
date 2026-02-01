<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ListView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\WorkOrdersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Órdenes de Trabajo';
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
?>

<div class="work-orders-index">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary">
            <?= Html::encode($this->title) ?>
        </h1>

        <?php if ($isAdmin): ?>
            <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Nueva Orden', ['create'], ['class' => 'btn btn-primary text-white']) ?>
        <?php endif; ?>
    </div>

    <?php if ($isAdmin): ?>
        
        <div class="overflow-x-auto bg-base-100 shadow-xl rounded-box border border-base-200">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'tableOptions' => ['class' => 'table table-zebra w-full'],
                'summary' => '<div class="p-4 text-sm text-base-content/70">Mostrando <b>{begin}-{end}</b> de <b>{totalCount}</b> órdenes.</div>',
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],

                    // Columna Código con Enlace
                    [
                        'attribute' => 'code',
                        'format' => 'raw',
                        'value' => function($model) {
                            return Html::a($model->code, ['view', 'id' => $model->id], ['class' => 'font-bold link link-primary no-underline']);
                        }
                    ],

                    // Cliente (Solo útil si hay filtro)
                    [
                        'attribute' => 'customer_id',
                        'value' => 'customer.business_name',
                        'label' => 'Cliente',
                    ],

                    'title',

                    [
                        'attribute' => 'total_cost',
                        'format' => ['currency'],
                        'contentOptions' => ['class' => 'font-mono text-right'],
                        'headerOptions' => ['class' => 'text-right'],
                    ],

                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function($model) { return $model->getStatusHtml(); },
                        'filter' => [
                            0 => 'Borrador', 
                            1 => 'Pendiente', 
                            2 => 'Aprobada', 
                            3 => 'Rechazada', 
                            4 => 'Finalizada'
                        ],
                    ],

                    [
                        'attribute' => 'created_at',
                        'format' => ['date', 'php:d M, Y'],
                        'label' => 'Fecha',
                    ],

                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Acciones',
                        'template' => '{view} {update} {delete}',
                        'buttonOptions' => ['class' => 'btn btn-ghost btn-xs'],
                        'buttons' => [
                            'view' => function ($url, $model) {
                                // Ícono de OJO (Heroicons)
                                return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>', 
                                $url, [
                                    'class' => 'btn btn-square btn-ghost btn-xs', // Botón pequeño y transparente
                                    'title' => 'Editar',
                                ]);
                            },
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

    <?php else: ?>

        <?= ListView::widget([
            'dataProvider' => $dataProvider,
            'layout' => "{items}\n<div class='mt-8 flex justify-center'>{pager}</div>",
            'options' => ['class' => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'],
            'itemOptions' => ['tag' => false],
            'emptyText' => '
                <div class="col-span-3 text-center py-12 opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 mx-auto mb-4"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                    <p>No tienes órdenes de trabajo registradas aún.</p>
                </div>
            ',
            'itemView' => function ($model, $key, $index, $widget) {
                // Bordes de color según estado para llamar la atención
                $borderClass = ($model->status == 1) ? 'border-l-4 border-l-warning' : 'border border-base-200';
                
                return '
                <div class="card bg-base-100 shadow-xl hover:-translate-y-1 transition-transform duration-300 ' . $borderClass . '">
                    <div class="card-body p-6">
                        <div class="flex justify-between items-start mb-2">
                            <div class="font-mono text-sm opacity-60 font-bold">' . Html::encode($model->code) . '</div>
                            ' . $model->getStatusHtml() . '
                        </div>
                        
                        <h2 class="card-title text-lg leading-tight mb-2">
                            ' . Html::encode($model->title) . '
                        </h2>
                        
                        <p class="text-sm opacity-70 line-clamp-3 mb-4">
                            ' . Html::encode(strip_tags($model->requirements)) . '
                        </p>

                        <div class="divider my-2"></div>

                        <div class="flex justify-between items-center">
                            <div class="text-xs opacity-50">
                                ' . Yii::$app->formatter->asDate($model->created_at) . '
                            </div>
                            <div class="text-lg font-bold text-primary">
                                ' . Yii::$app->formatter->asCurrency($model->total_cost) . '
                            </div>
                        </div>

                        <div class="card-actions justify-end mt-4">
                            ' . Html::a('Ver Documento', ['view', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline btn-primary w-full']) . '
                        </div>
                    </div>
                </div>
                ';
            },
        ]); ?>

    <?php endif; ?>

</div>