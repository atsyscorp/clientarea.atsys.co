<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\WorkOrders;
use app\models\Tickets;

/** @var yii\web\View $this */
/** @var app\models\Customers $model */

$this->title = $model->trade_name ?: $model->business_name ?: $model->contact_name;
$this->params['breadcrumbs'][] = ['label' => 'Clientes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

// Lógica para el color del estado
$statusColors = [
    'active' => 'badge-success',
    'inactive' => 'badge-error',
    'prospect' => 'badge-warning',
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
            
            <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg> Eliminar', ['delete', 'id' => $model->id], [ 'class' => 'btn btn-error shadow-md', 'data' => [ 'confirm' => '¿Estás seguro de que quieres eliminar este cliente?', 'method' => 'post', ], ]) ?> </div> </div> <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

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

    <!-- Pestañas de Navegación -->
    <?php 
    $isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
    if ($isAdmin): 
    ?>
    <div class="tabs tabs-boxed mb-6 justify-center bg-base-200 p-1 rounded-xl max-w-2xl mx-auto">
        <a id="tab-services" class="tab tab-lg font-bold px-6 transition-all duration-200 tab-active btn-primary text-white">
            <i class="fas fa-server mr-2"></i> Servicios
        </a>
        <a id="tab-work-orders" class="tab tab-lg font-bold px-6 transition-all duration-200">
            <i class="fas fa-clipboard-list mr-2"></i> Órdenes
        </a>
        <a id="tab-tickets" class="tab tab-lg font-bold px-6 transition-all duration-200">
            <i class="fas fa-ticket-alt mr-2"></i> Tickets
        </a>
    </div>
    <?php endif; ?>

    <!-- SECCIÓN: SERVICIOS CONTRATADOS -->
    <div id="services-sec" class="tab-content-sec">
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
                            $btnToggle = ($model->product && $model->product->type == 'hosting') ? Html::a($model->status == 1 ? $iconSuspend : $iconReactivate, [
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

                            // Botón Gráfico (si es hosting)
                            $btnChart = '';
                            if ($model->product && $model->product->type == 'hosting') {
                                $iconChart = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v5.25c0 .621-.504 1.125-1.125 1.125h-2.25A1.125 1.125 0 013 18.375v-5.25zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125v-9.75zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v14.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>';
                                $btnChart = Html::a($iconChart, ['/customer-services/view', 'id' => $model->id], [
                                    'class' => 'btn btn-square btn-ghost btn-sm text-primary tooltip tooltip-left',
                                    'data-tip' => 'Ver Consumo',
                                    'title' => 'Ver Consumo'
                                ]);
                            }

                            return '<div class="flex justify-end gap-1">' . $btnChart . $btnToggle . $btnUpdate . $btnDelete . '</div>';
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

    <?php if ($isAdmin): ?>
    <!-- SECCIÓN: ÓRDENES DE TRABAJO -->
    <div id="work-orders-sec" class="tab-content-sec hidden">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                Últimas Órdenes de Trabajo
            </h2>
            
            <?= Html::a('<i class="fas fa-external-link-alt mr-1"></i> Ver Todas', 
                ['/work-orders/index', 'WorkOrdersSearch[customer_id]' => $model->id], 
                ['class' => 'btn btn-primary btn-sm text-white']
            ) ?>
        </div>

        <div class="overflow-x-auto bg-base-100 shadow-xl rounded-box border border-base-200">
            <?php
            $recentWorkOrders = $model->getWorkOrders()
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(10)
                ->all();
            ?>
            <?php if (!empty($recentWorkOrders)): ?>
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Estado</th>
                        <th>Costo</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentWorkOrders as $wo): ?>
                    <tr class="hover">
                        <td>
                            <?= Html::a(
                                Html::encode($wo->code), 
                                ['/work-orders/view', 'id' => $wo->id], 
                                ['class' => 'font-bold link link-primary no-underline']
                            ) ?>
                        </td>
                        <td class="max-w-xs truncate"><?= Html::encode($wo->title) ?></td>
                        <td><?= $wo->getStatusHtml() ?></td>
                        <td class="font-mono text-sm">
                            <?php if ($wo->total_cost > 0): ?>
                                $<?= Yii::$app->formatter->asDecimal($wo->total_cost, 0) ?>
                                <span class="text-xs text-gray-400"><?= $wo->currency ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-gray-500">
                            <?= Yii::$app->formatter->asDate($wo->created_at, 'php:d M, Y') ?>
                        </td>
                        <td class="text-right">
                            <?= Html::a(
                                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
                                ['/work-orders/view', 'id' => $wo->id],
                                ['class' => 'btn btn-square btn-ghost btn-sm text-primary tooltip tooltip-left', 'data-tip' => 'Ver Detalle', 'title' => 'Ver Detalle']
                            ) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="p-8 text-center text-gray-400">
                    <i class="fas fa-clipboard-list text-4xl mb-2 opacity-50"></i>
                    <p class="text-sm font-bold">Este cliente no tiene órdenes de trabajo aún.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECCIÓN: ANÁLISIS DE TICKETS -->
    <div id="tickets-sec" class="tab-content-sec hidden">
        <?php
        $ticketStats = $model->getTicketStats();
        ?>

        <!-- Tarjetas de Estadísticas (Stats) -->
        <div class="stats shadow-xl w-full mb-8 bg-base-100 border border-base-200 rounded-box grid grid-cols-1 md:grid-cols-4">
            <div class="stat p-6 border-b md:border-b-0 md:border-r border-base-200">
                <div class="stat-figure text-primary">
                    <i class="fas fa-ticket-alt text-3xl opacity-80"></i>
                </div>
                <div class="stat-title text-gray-400 font-semibold uppercase text-xs tracking-wider">Total Tickets</div>
                <div class="stat-value text-primary text-3xl font-extrabold mt-1"><?= $ticketStats['total'] ?></div>
                <div class="stat-desc text-gray-400 text-xs mt-1">Registrados en la cuenta</div>
            </div>
            
            <div class="stat p-6 border-b md:border-b-0 md:border-r border-base-200">
                <div class="stat-figure text-success">
                    <i class="fas fa-check-circle text-3xl opacity-80"></i>
                </div>
                <div class="stat-title text-gray-400 font-semibold uppercase text-xs tracking-wider">Contestados</div>
                <div class="stat-value text-success text-3xl font-extrabold mt-1"><?= $ticketStats['answered'] ?></div>
                <div class="stat-desc text-gray-400 text-xs mt-1">Respondidos o Cerrados</div>
            </div>
            
            <div class="stat p-6 border-b md:border-b-0 md:border-r border-base-200">
                <div class="stat-figure text-error">
                    <i class="fas fa-hourglass-half text-3xl opacity-80"></i>
                </div>
                <div class="stat-title text-gray-400 font-semibold uppercase text-xs tracking-wider">Pendientes</div>
                <div class="stat-value text-error text-3xl font-extrabold mt-1"><?= $ticketStats['pending'] ?></div>
                <div class="stat-desc text-gray-400 text-xs mt-1">En espera de respuesta</div>
            </div>
            
            <div class="stat p-6">
                <div class="stat-figure text-secondary">
                    <i class="fas fa-clock text-3xl opacity-80"></i>
                </div>
                <div class="stat-title text-gray-400 font-semibold uppercase text-xs tracking-wider">Tiempo de Resp.</div>
                <div class="stat-value text-secondary text-3xl font-extrabold mt-1"><?= $ticketStats['avg_response_time'] ?></div>
                <div class="stat-desc text-gray-400 text-xs mt-1">Promedio de respuesta admin</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Columna del Doughnut Chart -->
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="card-title text-lg font-bold text-gray-800 mb-2">
                            <i class="fas fa-chart-pie text-primary mr-1"></i> Estado de Respuesta
                        </h3>
                        <p class="text-xs text-gray-400 mb-4 font-semibold">Proporción de tickets contestados versus pendientes.</p>
                    </div>
                    <?php if ($ticketStats['total'] > 0): ?>
                        <div class="relative w-full flex items-center justify-center" style="height: 250px;">
                            <canvas id="ticketsChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 text-gray-400 flex flex-col items-center justify-center h-full">
                            <i class="fas fa-folder-open text-4xl mb-2 opacity-50"></i>
                            <span class="text-sm font-bold">Sin datos para graficar</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna del Bar Chart (Tickets Mensuales) -->
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="card-title text-lg font-bold text-gray-800 mb-2">
                            <i class="fas fa-chart-bar text-primary mr-1"></i> Tickets por Mes (<?= date('Y') ?>)
                        </h3>
                        <p class="text-xs text-gray-400 mb-4 font-semibold">Cantidad de solicitudes recibidas por mes en el año en curso.</p>
                    </div>
                    <?php if ($ticketStats['total'] > 0): ?>
                        <div class="relative w-full flex items-center justify-center" style="height: 250px;">
                            <canvas id="monthlyTicketsChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 text-gray-400 flex flex-col items-center justify-center h-full">
                            <i class="fas fa-folder-open text-4xl mb-2 opacity-50"></i>
                            <span class="text-sm font-bold">Sin datos para graficar</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tabla de Últimos Tickets -->
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" /></svg>
                Últimos Tickets
            </h2>
            
            <?= Html::a('<i class="fas fa-external-link-alt mr-1"></i> Ver Todos', 
                ['/tickets/index', 'TicketsSearch[customer_id]' => $model->id], 
                ['class' => 'btn btn-primary btn-sm text-white']
            ) ?>
        </div>

        <div class="overflow-x-auto bg-base-100 shadow-xl rounded-box border border-base-200">
            <?php
            $recentTickets = $model->getTickets()
                ->orderBy(['updated_at' => SORT_DESC])
                ->limit(10)
                ->all();
            
            $ticketPriorityColors = [
                'low' => 'badge-ghost',
                'medium' => 'badge-info badge-outline',
                'high' => 'badge-warning badge-outline',
                'critical' => 'badge-error',
            ];
            ?>
            <?php if (!empty($recentTickets)): ?>
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Asunto</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Departamento</th>
                        <th>Actualización</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTickets as $ticket): ?>
                    <tr class="hover">
                        <td>
                            <?= Html::a(
                                Html::encode($ticket->ticket_code), 
                                ['/tickets/view', 'id' => $ticket->id], 
                                ['class' => 'font-bold link link-primary no-underline']
                            ) ?>
                        </td>
                        <td class="max-w-xs truncate"><?= Html::encode($ticket->subject) ?></td>
                        <td>
                            <span class="badge <?= $ticketStatusColors[$ticket->status] ?? 'badge-ghost' ?> font-bold">
                                <?= Html::encode($ticket->getStatusText()) ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $priorityLabels = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'];
                            ?>
                            <span class="badge <?= $ticketPriorityColors[$ticket->priority] ?? 'badge-ghost' ?> font-bold">
                                <?= $priorityLabels[$ticket->priority] ?? $ticket->priority ?>
                            </span>
                        </td>
                        <td><?= $ticket->getDepartmentLabelShort() ?></td>
                        <td class="text-sm text-gray-500">
                            <?= Yii::$app->formatter->asDate($ticket->updated_at, 'php:d M, Y') ?>
                        </td>
                        <td class="text-right">
                            <?= Html::a(
                                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
                                ['/tickets/view', 'id' => $ticket->id],
                                ['class' => 'btn btn-square btn-ghost btn-sm text-primary tooltip tooltip-left', 'data-tip' => 'Ver Ticket', 'title' => 'Ver Ticket']
                            ) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="p-8 text-center text-gray-400">
                    <i class="fas fa-ticket-alt text-4xl mb-2 opacity-50"></i>
                    <p class="text-sm font-bold">Este cliente no tiene tickets aún.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts de Gráficos e Interactividad -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php
    $monthlyCountsJson = json_encode($ticketStats['monthly_counts']);
    $jsScript = <<<JS
    document.addEventListener("DOMContentLoaded", function() {
        // LÓGICA PARA NAVEGAR ENTRE PESTAÑAS (TABS)
        const tabServices = document.getElementById('tab-services');
        const tabWorkOrders = document.getElementById('tab-work-orders');
        const tabTickets = document.getElementById('tab-tickets');
        const secServices = document.getElementById('services-sec');
        const secWorkOrders = document.getElementById('work-orders-sec');
        const secTickets = document.getElementById('tickets-sec');

        const allTabs = [tabServices, tabWorkOrders, tabTickets];
        const allSections = [secServices, secWorkOrders, secTickets];

        function selectTab(activeTab, activeSection) {
            allTabs.forEach(function(tab) {
                if (tab) tab.classList.remove('tab-active', 'btn-primary', 'text-white');
            });
            allSections.forEach(function(sec) {
                if (sec) sec.classList.add('hidden');
            });
            if (activeTab) activeTab.classList.add('tab-active', 'btn-primary', 'text-white');
            if (activeSection) activeSection.classList.remove('hidden');
        }

        if (tabServices) tabServices.addEventListener('click', function() { selectTab(tabServices, secServices); });
        if (tabWorkOrders) tabWorkOrders.addEventListener('click', function() { selectTab(tabWorkOrders, secWorkOrders); });
        if (tabTickets) tabTickets.addEventListener('click', function() { selectTab(tabTickets, secTickets); });

        // INICIALIZACIÓN DE CHART.JS PARA TICKETS (DOUGHNUT)
        const chartCanvas = document.getElementById('ticketsChart');
        if (chartCanvas) {
            const ctx = chartCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Contestados', 'Pendientes'],
                    datasets: [{
                        data: [{$ticketStats['answered']}, {$ticketStats['pending']}],
                        backgroundColor: [
                            '#10B981', // Éxito / Verde (Contestados)
                            '#EF4444'  // Error / Rojo (Pendientes)
                        ],
                        borderWidth: 2,
                        borderColor: document.documentElement.getAttribute('data-theme') === 'dark' ? '#1d232a' : '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: document.documentElement.getAttribute('data-theme') === 'dark' ? '#a6adbb' : '#1f2937',
                                font: {
                                    family: 'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial',
                                    size: 12,
                                    weight: '600'
                                },
                                padding: 20
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        }

        // INICIALIZACIÓN DE CHART.JS PARA TICKETS MENSUALES (BAR)
        const barCanvas = document.getElementById('monthlyTicketsChart');
        if (barCanvas) {
            const ctxBar = barCanvas.getContext('2d');
            const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const monthlyData = {$monthlyCountsJson};
            
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Tickets Enviados',
                        data: monthlyData,
                        backgroundColor: '#134C42', // Color primario
                        borderRadius: 6,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: document.documentElement.getAttribute('data-theme') === 'dark' ? '#a6adbb' : '#1f2937',
                                font: {
                                    weight: '600'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: document.documentElement.getAttribute('data-theme') === 'dark' ? '#a6adbb' : '#1f2937',
                                font: {
                                    weight: '600'
                                }
                            },
                            grid: {
                                color: document.documentElement.getAttribute('data-theme') === 'dark' ? '#21262d' : '#e5e7eb'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    });
    JS;
    $this->registerJs($jsScript, \yii\web\View::POS_END);
    ?>
    <?php endif; ?>

</div>