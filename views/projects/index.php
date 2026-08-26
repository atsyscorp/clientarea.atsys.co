<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\Projects;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ProjectsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Proyectos / Empresas de Clientes';
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
?>

<div class="projects-index space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-primary flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                </svg>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-sm text-base-content/70 mt-1">Segmentación de proyectos, marcas y filiales por cliente para evitar mezclar frentes de trabajo.</p>
        </div>

        <?php if ($isAdmin): ?>
            <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Nuevo Proyecto', ['create'], ['class' => 'btn btn-primary text-white shadow-md']) ?>
        <?php endif; ?>
    </div>

    <div class="overflow-x-auto bg-base-100 shadow-xl rounded-box border border-base-200">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'tableOptions' => ['class' => 'table table-zebra w-full'],
            'summary' => '<div class="p-4 text-sm text-base-content/70">Mostrando <b>{begin}-{end}</b> de <b>{totalCount}</b> proyectos.</div>',
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
                    'attribute' => 'name',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $html = '<div class="font-semibold text-base">' . Html::encode($model->name) . '</div>';
                        if ($model->is_default) {
                            $html .= '<span class="badge badge-info text-xs">Predeterminado</span>';
                        }
                        return $html;
                    }
                ],

                [
                    'attribute' => 'business_name',
                    'label' => 'Razón Social / Filial',
                    'value' => function ($model) {
                        return $model->business_name ?: '<span class="text-gray-400 italic">Misma del cliente</span>';
                    },
                    'format' => 'raw',
                ],

                [
                    'attribute' => 'document_number',
                    'label' => 'NIT / Doc. Filial',
                    'value' => function ($model) {
                        return $model->document_number ?: '<span class="text-gray-400 italic">Mismo del cliente</span>';
                    },
                    'format' => 'raw',
                ],

                [
                    'label' => 'Órdenes de Trabajo',
                    'value' => function ($model) {
                        $count = count($model->workOrders);
                        return '<span class="badge badge-outline font-bold">' . $count . ' OTs</span>';
                    },
                    'format' => 'raw',
                ],

                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'value' => function ($model) {
                        if ($model->status == Projects::STATUS_ACTIVE) {
                            return '<span class="badge badge-success">Activo</span>';
                        }
                        return '<span class="badge badge-ghost">Inactivo</span>';
                    }
                ],

                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view} ' . ($isAdmin ? '{update}' : ''),
                    'buttons' => [
                        'view' => function ($url, $model) {
                            return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>', ['view', 'id' => $model->id], ['class' => 'btn btn-sm btn-ghost text-info', 'title' => 'Ver Detalle']);
                        },
                        'update' => function ($url, $model) {
                            return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>', ['update', 'id' => $model->id], ['class' => 'btn btn-sm btn-ghost text-warning', 'title' => 'Editar']);
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>

</div>
