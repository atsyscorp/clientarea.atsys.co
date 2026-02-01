<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Customers $model */

$this->title = $model->trade_name ?: $model->business_name ?: $model->contact_name;
$this->params['breadcrumbs'][] = ['label' => 'Clientes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

// Lógica para el color del estado
$statusColors = [
    'active' => 'badge-success text-white',
    'inactive' => 'badge-error text-white',
    'prospect' => 'badge-warning text-white',
];
$statusColor = $statusColors[$model->status] ?? 'badge-ghost';
?>

<div class="customers-view fade-in">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-gray-800">
                    <?= Html::encode($this->title) ?>
                </h1>
                <span class="badge <?= $statusColor ?> badge-lg shadow-sm">
                    <?= Html::encode(['active'=>'Activo', 'inactive'=>'Inactivo', 'prospect'=>'Prospecto'][$model->status] ?? $model->status) ?>
                </span>
            </div>
            <p class="text-gray-500 mt-1">
                <i class="fas fa-id-card mr-1"></i> <?= Html::encode($model->document_type) ?>: <?= Html::encode($model->document_number) ?>
            </p>
        </div>

        <div class="flex gap-2">
            <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg> Volver', ['index'], ['class' => 'btn btn-ghost']) ?>
            
            <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg> Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-primary text-white shadow-md']) ?>
            
            <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg> Eliminar', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-error text-white shadow-md',
                'data' => [
                    'confirm' => '¿Estás seguro de que quieres eliminar este cliente?',
                    'method' => 'post',
                ],
            ]) ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-6">
                    <h2 class="card-title text-primary border-b border-base-200 pb-3 mb-4">
                        <i class="fas fa-building mr-2"></i> Datos Empresariales
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                        <div>
                            <span class="label-text text-xs uppercase font-bold text-gray-400">Razón Social</span>
                            <div class="font-semibold text-lg"><?= Html::encode($model->business_name ?: 'N/A') ?></div>
                        </div>
                        <div>
                            <span class="label-text text-xs uppercase font-bold text-gray-400">Nombre Comercial</span>
                            <div class="font-semibold text-lg"><?= Html::encode($model->trade_name ?: 'Igual a Razón Social') ?></div>
                        </div>
                        <div>
                            <span class="label-text text-xs uppercase font-bold text-gray-400">Correo Electrónico</span>
                            <div class="text-base break-words">
                                <?php if($model->email): ?>
                                    <a href="mailto:<?= Html::encode($model->email) ?>" class="link link-primary no-underline hover:underline">
                                        <?= Html::encode($model->email) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">No registrado</span>
                                <?php endif; ?>
                            </div>
                        </div>
                         <div>
                            <span class="label-text text-xs uppercase font-bold text-gray-400">Fecha de Registro</span>
                            <div class="text-base"><?= Yii::$app->formatter->asDate($model->created_at, 'long') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-6">
                    <h2 class="card-title text-primary border-b border-base-200 pb-3 mb-4">
                        <i class="fas fa-map-marker-alt mr-2"></i> Ubicación y Notas
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8 mb-6">
                        <div class="md:col-span-2">
                            <span class="label-text text-xs uppercase font-bold text-gray-400">Dirección</span>
                            <div class="text-base font-medium"><?= Html::encode($model->address ?: 'Sin dirección') ?></div>
                        </div>
                        <div>
                            <span class="label-text text-xs uppercase font-bold text-gray-400">Ciudad</span>
                            <div class="text-base"><?= Html::encode($model->city) ?></div>
                        </div>
                         <div>
                            <span class="label-text text-xs uppercase font-bold text-gray-400">Departamento / Provincia</span>
                            <div class="text-base"><?= Html::encode($model->state_province) ?></div>
                        </div>
                    </div>

                    <?php if($model->notes): ?>
                        <div class="bg-base-200 p-4 rounded-lg">
                            <span class="label-text text-xs uppercase font-bold text-gray-500 mb-1 block">Notas / Observaciones</span>
                            <p class="text-sm italic text-gray-700 whitespace-pre-wrap"><?= Html::encode($model->notes) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="lg:col-span-1 space-y-6">
            
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-6">
                    <h2 class="card-title text-primary border-b border-base-200 pb-3 mb-4">
                        <i class="fas fa-user-tie mr-2"></i> Contacto
                    </h2>

                    <div class="flex items-center gap-4 mb-6">
                        <div class="avatar placeholder">
                            <div class="bg-neutral text-neutral-content rounded-full w-12">
                                <span class="text-xl"><?= substr($model->contact_name, 0, 1) ?></span>
                            </div>
                        </div>
                        <div>
                            <div class="font-bold text-lg leading-tight"><?= Html::encode($model->contact_name) ?></div>
                            <div class="text-sm text-gray-500"><?= Html::encode($model->contact_position) ?></div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <?php if($model->primary_phone): ?>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                    <i class="fas fa-phone-alt text-sm"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-400 font-bold">Teléfono Principal</div>
                                    <a href="tel:<?= Html::encode($model->primary_phone) ?>" class="link link-hover font-medium">
                                        <?= Html::encode($model->primary_phone) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if($model->secondary_phone): ?>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <i class="fas fa-mobile-alt text-sm"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-400 font-bold">Teléfono Secundario</div>
                                    <a href="tel:<?= Html::encode($model->secondary_phone) ?>" class="link link-hover font-medium">
                                        <?= Html::encode($model->secondary_phone) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <button class="btn btn-outline btn-primary btn-block btn-sm">
                        <i class="fas fa-envelope mr-2"></i> Enviar Correo
                    </button>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200 opacity-80">
                <div class="card-body p-4 text-xs text-gray-500">
                    <div class="flex justify-between">
                        <span>ID Sistema:</span>
                        <span class="font-mono"><?= $model->id ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Creado:</span>
                        <span><?= $model->created_at ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Actualizado:</span>
                        <span><?= $model->updated_at ?></span>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <div class="divider"></div>

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.25l.07-.11a.75.75 0 01.71-.41h15.64a.75.75 0 01.71.41l.07.11a4.5 4.5 0 01.9 2.25M3.75 14.25V6m16.5 8.25V6" /></svg>
            Servicios Contratados
        </h2>
        
        <?= Html::a('Agregar Servicio', 
            ['customer-services/create', 'customer_id' => $model->id], 
            ['class' => 'btn btn-primary btn-sm text-white']
        ) ?>
    </div>

    <div class="overflow-x-auto bg-base-100 shadow-xl rounded-box">
        <?= \yii\grid\GridView::widget([
            'dataProvider' => new \yii\data\ActiveDataProvider([
                'query' => $model->getServices(),
                'pagination' => false,
                'sort' => ['defaultOrder' => ['status' => SORT_ASC, 'next_due_date' => SORT_ASC]],
            ]),
            'layout' => "{items}",
            'tableOptions' => ['class' => 'table table-zebra w-full'],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                
                // Columna 1: Servicio
                [
                    'attribute' => 'product_id',
                    'value' => 'product.name',
                    'label' => 'Servicio',
                    'contentOptions' => ['class' => 'font-bold'],
                ],
                
                // Columna 2: Dominio/Referencia
                [
                    'attribute' => 'domain',
                    'format' => 'raw',
                    'value' => function($service) {
                        $val = $service->domain ? $service->domain : $service->description_label;
                        return Html::encode($val);
                    }
                ],
                
                // Columna 3: Vencimiento (CON ALERTA DE COLORES)
                [
                    'attribute' => 'next_due_date',
                    'format' => 'raw',
                    'value' => function($service) {
                        if (!$service->next_due_date) return '<span class="text-gray-400">N/A</span>';
                        
                        $due = new DateTime($service->next_due_date);
                        $now = new DateTime();
                        $interval = $now->diff($due);
                        $daysLeft = (int)$interval->format('%r%a');

                        $dateStr = Yii::$app->formatter->asDate($service->next_due_date, 'php:d M, Y');
                        
                        // Lógica de colores
                        if ($daysLeft < 0) {
                            // Vencido (Rojo)
                            return "<div class='text-error font-bold flex items-center gap-1'>
                                        <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='currentColor' class='w-4 h-4'><path fill-rule='evenodd' d='M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z' clip-rule='evenodd' /></svg>
                                        {$dateStr} <span class='text-xs opacity-70'>(Vencido)</span>
                                    </div>";
                        } elseif ($daysLeft <= 30) {
                            // Próximo a vencer (Amarillo)
                            return "<div class='text-warning font-bold flex items-center gap-1'>
                                        <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='currentColor' class='w-4 h-4'><path fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z' clip-rule='evenodd' /></svg>
                                        {$dateStr}
                                    </div>";
                        } else {
                            // Normal
                            return $dateStr;
                        }
                    }
                ],
                
                // Columna 4: Estado (Badge)
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'value' => function($service) {
                        return $service->getStatusHtml();
                    }
                ],

                // Botones
                [
                    'label' => 'Acciones',
                    'format' => 'raw',
                    'contentOptions' => ['class' => 'text-right'], 
                    'value' => function ($model) {
                        
                        // SVG ICONO SUSPENDER (Switch activo)
                        $iconSuspend = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <rect x="2" y="6" width="20" height="12" rx="6" />
                        <circle cx="17" cy="12" r="3" fill="currentColor" />
                        </svg>';

                        $iconReactivate = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <rect x="2" y="6" width="20" height="12" rx="6" />
                        <circle cx="17" cy="12" r="3" fill="currentColor" />
                        </svg>';

                        // SVG ICONO EDITAR (Lápiz)
                        $iconUpdate = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>';

                        // SVG ICONO ELIMINAR (Basura)
                        $iconDelete = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>';

                        $confirmSuspend = '¿Estás seguro de que deseas SUSPENDER este servicio? El sitio web dejará de ser accesible.';
                        $confirmReactivate = '¿Deseas REACTIVAR este servicio inmediatamente?';

                        // Botón Suspender/Reactivar
                        $btnToggle = ($model->product->type == 'hosting') ? Html::a($model->status == 1 ? $iconSuspend : $iconReactivate, [
                            '/customer-services/toggle', 
                            'id' => $model->id
                        ], [
                            'class' => 'btn btn-square btn-ghost btn-sm  tooltip tooltip-left' . (($model->status == 1) ? ' text-success' : ' text-error'),
                            'data' => [
                                'confirm' => $model->status == 1 ? $confirmSuspend : $confirmReactivate,
                                'method' => 'post',
                            ],
                        ]) : '';

                        // Botón Editar
                        $btnUpdate = Html::a($iconUpdate, ['/customer-services/update', 'id' => $model->id], [
                            'class' => 'btn btn-square btn-ghost btn-sm text-info tooltip tooltip-left',
                            'data-tip' => 'Editar',
                            'title' => 'Editar'
                        ]);

                        // Botón Eliminar
                        $btnDelete = Html::a($iconDelete, ['/customer-services/delete', 'id' => $model->id], [
                            'class' => 'btn btn-square btn-ghost btn-sm text-error tooltip tooltip-left',
                            'data-confirm' => '¿Estás seguro de eliminar este cliente?',
                            'data-method' => 'post',
                            'data-tip' => 'Eliminar',
                            'title' => 'Eliminar'
                        ]);

                        return '<div class="flex justify-end gap-1">' . $btnToggle . $btnUpdate . $btnDelete . '</div>';
                    },
                ],
            ],
        ]); ?>
        
        <?php if (empty($model->services)): ?>
            <div class="p-6 text-center text-gray-500">
                Este cliente no tiene servicios activos aún.
            </div>
        <?php endif; ?>
    </div>
    
</div>