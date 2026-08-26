<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ContractsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Contratos de Servicios';
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
?>

<div class="contracts-index">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-primary flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-sm text-base-content/70 mt-1">Gestión de contratos comerciales, seguimiento de hitos y control del porcentaje de avance.</p>
        </div>

        <?php if ($isAdmin): ?>
            <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Nuevo Contrato', ['create'], ['class' => 'btn btn-primary text-white shadow-md']) ?>
        <?php endif; ?>
    </div>

    <div class="overflow-x-auto bg-base-100 shadow-xl rounded-box border border-base-200">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'tableOptions' => ['class' => 'table table-zebra w-full'],
            'summary' => '<div class="p-4 text-sm text-base-content/70">Mostrando <b>{begin}-{end}</b> de <b>{totalCount}</b> contratos.</div>',
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],

                [
                    'attribute' => 'code',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return Html::a($model->code, ['view', 'id' => $model->id], ['class' => 'font-bold link link-primary no-underline']);
                    }
                ],

                [
                    'attribute' => 'customer_id',
                    'value' => 'customer.business_name',
                    'label' => 'Cliente',
                    'visible' => $isAdmin,
                ],

                [
                    'attribute' => 'title',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return '<div class="font-semibold">' . Html::encode($model->title) . '</div>' .
                               ($model->contract_file ? '<span class="text-xs text-success flex items-center gap-1 mt-0.5"><svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Documento Adjunto</span>' : '');
                    }
                ],

                [
                    'attribute' => 'progress_percentage',
                    'label' => '% Avance',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $percent = floatval($model->progress_percentage);
                        $colorClass = $model->getProgressColorClass();
                        return '<div class="w-36">
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="font-bold">' . number_format($percent, 1) . '%</span>
                                        <span class="text-base-content/60">' . ($model->progress_mode == 0 ? 'Auto' : 'Manual') . '</span>
                                    </div>
                                    <progress class="progress ' . $colorClass . ' w-full h-2.5" value="' . $percent . '" max="100"></progress>
                                </div>';
                    }
                ],

                [
                    'attribute' => 'total_amount',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return '<span class="font-mono font-bold">' . $model->currency . ' $' . number_format($model->total_amount, 2) . '</span>';
                    }
                ],

                [
                    'attribute' => 'start_date',
                    'label' => 'Vigencia',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $start = $model->start_date ? date('d/m/Y', strtotime($model->start_date)) : '-';
                        $end = $model->end_date ? date('d/m/Y', strtotime($model->end_date)) : '<span class="badge badge-ghost badge-sm text-xs font-semibold">♾️ Indefinido</span>';
                        return '<span class="text-xs">' . $start . ' → ' . $end . '</span>';
                    }
                ],

                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return $model->getStatusHtml();
                    }
                ],

                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => 'Acciones',
                    'template' => '{view} ' . ($isAdmin ? '{update} {delete}' : ''),
                    'buttons' => [
                        'view' => function ($url, $model) {
                            return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>', ['view', 'id' => $model->id], ['class' => 'btn btn-square btn-ghost btn-xs text-info', 'title' => 'Ver Contrato']);
                        },
                        'update' => function ($url, $model) {
                            return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>', ['update', 'id' => $model->id], ['class' => 'btn btn-square btn-ghost btn-xs text-warning', 'title' => 'Editar Contrato']);
                        },
                        'delete' => function ($url, $model) {
                            return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>', ['delete', 'id' => $model->id], [
                                'class' => 'btn btn-square btn-ghost btn-xs text-error',
                                'title' => 'Eliminar',
                                'data' => [
                                    'confirm' => '¿Está seguro de eliminar este contrato y todas sus relaciones?',
                                    'method' => 'post',
                                ],
                            ]);
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>
</div>
