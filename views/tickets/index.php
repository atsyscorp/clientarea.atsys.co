<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Tickets';
$this->params['breadcrumbs'][] = $this->title;
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;

?>
<div class="tickets-index">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?=$this->title?></h1>
        <?= Html::a(
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Crear Ticket', 
            ['create'], 
            ['class' => 'btn btn-primary text-white shadow-lg']
        ) ?>
    </div>

    <div class="overflow-x-auto w-full bg-base-100 shadow-xl rounded-box border border-base-200">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'emptyText' => Yii::t('tickets','No tickets registered now.'), 
            'summary' => '<div class="p-4 text-sm text-base-content/70">Mostrando <b>{begin}-{end}</b> de <b>{totalCount}</b> tickets.</div>',
            'tableOptions' => [
                'id' => 'ticket-list',
                'class' => 'table table-zebra table-hover w-full'
            ],
            'pager' => Yii::$app->params['paginationStyles'],
            'columns' => [
                [
                    'class' => 'yii\grid\CheckboxColumn',
                    'name' => 'id'
                ],
                [
                    'attribute' => 'ticket_code',
                    'format' => 'raw',
                    'value' => function($model) {
                        // Hacemos que el código sea el enlace al ticket
                        return Html::a($model->ticket_code, ['view', 'id' => $model->id], ['style' => 'font-weight:bold; color:#007bff;']);
                    }
                ],
                [
                    'attribute' => 'customer.business_name',
                    'visible' => Yii::$app->user->identity->isAdmin,
                    'label' => 'Cliente',
                ],
                'subject',
                [
                    'attribute' => 'email:email',
                    'visible' => Yii::$app->user->identity->isAdmin,
                    'label' => 'E-mail',
                ],
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'filter' => ['open'=>'Open', 'answered'=>'Answered', 'closed'=>'Closed'],
                    'value' => function ($model) {
                        // Mapeo de colores según estado
                        $colors = [
                            'open' => 'badge-error',      // Rojo
                            'answered' => 'badge-success', // Verde
                            'customer_reply' => 'badge-warning', // Amarillo
                            'closed' => 'badge-neutral',   // Gris
                        ];

                        $colorClass = $colors[$model->status] ?? 'badge-ghost';
                        $textClass = $model->status === 'open' || $model->status === 'closed' ? 'text-white' : 'text-black';

                        return "<span class='badge {$colorClass} {$textClass} badge-sm gap-2'>
                                    {$model->getStatusText()}
                                </span>";
                    },
                    'contentOptions' => ['class' => 'text-center'], // Centrar la columna
                ],
                
                'priority',
                'source',
                'created_at',
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => 'Acciones',
                    'template' => '{view} {close}', // Botones que queremos
                    'contentOptions' => ['class' => 'flex gap-2 justify-center'], // Alineación horizontal
                    'visibleButtons' => [
                        'close' => function ($model, $key, $index) {
                            return $model->status !== 'closed'; // Mostrar solo si no está cerrado
                        },
                    ],
                    'buttons' => [
                        'view' => function ($url, $model) {
                            // Ícono de OJO (Heroicons)
                            return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>', 
                            $url, [
                                'class' => 'btn btn-square btn-ghost btn-xs', // Botón pequeño y transparente
                                'title' => 'Ver',
                            ]);
                        },
                        'close' => function ($url, $model) {
                            // Ícono de BASURA
                            return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>', 
                            $url, [
                                'class' => 'btn btn-square btn-ghost btn-xs text-error', // Color rojo
                                'data-confirm' => 'Se cerrará este ticket ¿Desea continuar?',
                                'data-method' => 'post',
                                'title' => 'Cerrar ticket',
                            ]);
                        },
                    ],
                ],
            ]
        ]); ?>
    </div>
</div>

