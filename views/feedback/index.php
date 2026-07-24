<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\ServiceFeedbackSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int $totalReviews */
/** @var float $averageRating */
/** @var float $averageNps */
/** @var float $npsScore */
/** @var float $averageCes */
/** @var float $resolutionRate */
/** @var int $resolvedCount */
/** @var int $unresolvedCount */
/** @var int $promotersCount */
/** @var int $passivesCount */
/** @var int $detractorsCount */
/** @var array $ratingCountsMap */
/** @var array $trendLabels */
/** @var array $trendData */

$this->title = 'Módulo de Satisfacción y Encuestas de Servicio';
?>

<div class="feedback-index space-y-8">
    <!-- Encabezado del Módulo -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-base-100 p-6 rounded-2xl border border-base-200 shadow-sm">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-base-content flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-sm text-base-content/60 mt-1">
                Consolidado de calificaciones, métricas de servicio (CSAT, NPS, CES) e identificación de encuestados.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= Url::to(['/feedback/export']) ?>" class="btn btn-outline btn-primary gap-2 shadow-sm rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Exportar CSV
            </a>
        </div>
    </div>

    <!-- Cards de Métricas Principales (KPIs) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Encuestas -->
        <div class="stat bg-base-100 border border-base-200 rounded-2xl shadow-sm">
            <div class="stat-figure text-primary">
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
            <div class="stat-title font-semibold text-xs text-base-content/60 uppercase tracking-wider">Total Respuestas</div>
            <div class="stat-value text-primary text-3xl font-black mt-1"><?= number_format($totalReviews) ?></div>
            <div class="stat-desc text-xs mt-1">Encuestas diligenciadas</div>
        </div>

        <!-- CSAT Promedio -->
        <div class="stat bg-base-100 border border-base-200 rounded-2xl shadow-sm">
            <div class="stat-figure text-warning">
                <div class="w-12 h-12 rounded-xl bg-warning/10 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                </div>
            </div>
            <div class="stat-title font-semibold text-xs text-base-content/60 uppercase tracking-wider">CSAT Promedio</div>
            <div class="stat-value text-warning text-3xl font-black mt-1"><?= $averageRating ?> <span class="text-sm font-normal text-base-content/50">/ 5</span></div>
            <div class="stat-desc text-xs mt-1">Satisfacción del cliente</div>
        </div>

        <!-- NPS Score -->
        <div class="stat bg-base-100 border border-base-200 rounded-2xl shadow-sm">
            <div class="stat-figure text-secondary">
                <div class="w-12 h-12 rounded-xl bg-secondary/10 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="stat-title font-semibold text-xs text-base-content/60 uppercase tracking-wider">NPS Global</div>
            <div class="stat-value text-secondary text-3xl font-black mt-1"><?= ($npsScore > 0 ? '+' : '') . $npsScore ?></div>
            <div class="stat-desc text-xs mt-1">Promotores vs Detractores</div>
        </div>

        <!-- Tasa de Resolución -->
        <div class="stat bg-base-100 border border-base-200 rounded-2xl shadow-sm">
            <div class="stat-figure text-success">
                <div class="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="stat-title font-semibold text-xs text-base-content/60 uppercase tracking-wider">Tasa Resolución</div>
            <div class="stat-value text-success text-3xl font-black mt-1"><?= $resolutionRate ?>%</div>
            <div class="stat-desc text-xs mt-1"><?= $resolvedCount ?> resueltos de <?= $totalReviews ?></div>
        </div>

        <!-- CES Promedio -->
        <div class="stat bg-base-100 border border-base-200 rounded-2xl shadow-sm">
            <div class="stat-figure text-accent">
                <div class="w-12 h-12 rounded-xl bg-accent/10 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
            <div class="stat-title font-semibold text-xs text-base-content/60 uppercase tracking-wider">Esfuerzo (CES)</div>
            <div class="stat-value text-accent text-3xl font-black mt-1"><?= $averageCes ?> <span class="text-sm font-normal text-base-content/50">/ 5</span></div>
            <div class="stat-desc text-xs mt-1">Facilidad de gestión</div>
        </div>
    </div>

    <!-- Gráficos del Módulo (2x2 Grid) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Gráfico 1: Distribución CSAT -->
        <div class="card bg-base-100 border border-base-200 shadow-sm rounded-2xl">
            <div class="card-body">
                <h3 class="card-title text-base font-bold flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    Distribución de Calificaciones (CSAT)
                </h3>
                <div class="relative h-64 w-full mt-2">
                    <canvas id="csatChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico 2: Desglose NPS -->
        <div class="card bg-base-100 border border-base-200 shadow-sm rounded-2xl">
            <div class="card-body">
                <h3 class="card-title text-base font-bold flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Clasificación NPS (Recomendación)
                </h3>
                <div class="relative h-64 w-full mt-2">
                    <canvas id="npsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico 3: Resolución de Caso -->
        <div class="card bg-base-100 border border-base-200 shadow-sm rounded-2xl">
            <div class="card-body">
                <h3 class="card-title text-base font-bold flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    ¿Solicitud Resuelta?
                </h3>
                <div class="relative h-64 w-full mt-2">
                    <canvas id="resolutionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico 4: Tendencia de Evaluaciones -->
        <div class="card bg-base-100 border border-base-200 shadow-sm rounded-2xl">
            <div class="card-body">
                <h3 class="card-title text-base font-bold flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                    Histórico / Tendencia Reciente (1-5★)
                </h3>
                <div class="relative h-64 w-full mt-2">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- Tabla Detallada de Encuestas Diligenciadas -->
    <div class="card bg-base-100 border border-base-200 shadow-sm rounded-2xl">
        <div class="card-body overflow-x-auto">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="card-title text-xl font-bold">Consolidado y Detalle de Respuestas</h2>
                    <p class="text-xs text-base-content/60">Consulta en detalle quiénes han respondido y cuáles fueron sus valoraciones.</p>
                </div>
            </div>

            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'tableOptions' => ['class' => 'table table-zebra w-full text-sm'],
                'columns' => [
                    [
                        'class' => 'yii\grid\SerialColumn',
                        'header' => '#',
                    ],
                    [
                        'attribute' => 'client_email',
                        'label' => 'Encuestado / Cliente',
                        'format' => 'raw',
                        'value' => function ($model) {
                            /** @var app\models\ServiceFeedback $model */
                            $customer = $model->getResolvedCustomer();
                            if ($customer) {
                                $name = Html::encode($customer->business_name ?: $customer->contact_name);
                                $email = Html::encode($model->client_email ?: $customer->email);
                                $url = Url::to(['/customers/view', 'id' => $customer->id]);
                                return "<div class='font-bold text-base-content'><a href='{$url}' class='hover:underline text-primary' target='_blank'>{$name}</a></div>" .
                                       "<div class='text-xs text-base-content/60'>{$email}</div>";
                            }
                            if ($model->client_email) {
                                return "<div class='font-medium text-base-content'>" . Html::encode($model->client_email) . "</div>" .
                                       "<div class='text-xs text-base-content/50'>Cliente sin vincular</div>";
                            }
                            return "<span class='badge badge-ghost badge-sm italic text-base-content/50'>Anónimo</span>";
                        }
                    ],
                    [
                        'attribute' => 'ticket_id',
                        'label' => 'Ticket',
                        'format' => 'raw',
                        'value' => function ($model) {
                            /** @var app\models\ServiceFeedback $model */
                            $ticket = $model->getResolvedTicket();
                            if ($ticket) {
                                $url = Url::to(['/tickets/view', 'id' => $ticket->id]);
                                $code = Html::encode($ticket->ticket_code);
                                $subj = Html::encode($ticket->subject);
                                return "<a href='{$url}' target='_blank' class='badge badge-neutral badge-outline hover:badge-primary gap-1 font-mono text-xs' title='{$subj}'>#{$code}</a>";
                            }
                            if ($model->ticket_id) {
                                return "<span class='badge badge-ghost font-mono text-xs'>#" . Html::encode($model->ticket_id) . "</span>";
                            }
                            return "<span class='text-xs text-base-content/40'>Sin Ticket</span>";
                        }
                    ],
                    [
                        'attribute' => 'rating_service',
                        'label' => 'CSAT (Estrellas)',
                        'format' => 'raw',
                        'filter' => [
                            5 => '5 Estrellas',
                            4 => '4 Estrellas',
                            3 => '3 Estrellas',
                            2 => '2 Estrellas',
                            1 => '1 Estrella',
                        ],
                        'value' => function ($model) {
                            /** @var app\models\ServiceFeedback $model */
                            return $model->getRatingStarsHtml();
                        }
                    ],
                    [
                        'attribute' => 'nps_score',
                        'label' => 'NPS',
                        'format' => 'raw',
                        'value' => function ($model) {
                            /** @var app\models\ServiceFeedback $model */
                            return $model->getNpsCategoryBadge();
                        }
                    ],
                    [
                        'attribute' => 'effort_score',
                        'label' => 'CES (Esfuerzo)',
                        'format' => 'raw',
                        'value' => function ($model) {
                            /** @var app\models\ServiceFeedback $model */
                            return "<span class='text-xs font-medium text-base-content/70'>" . Html::encode($model->getEffortScoreLabel()) . "</span>";
                        }
                    ],
                    [
                        'attribute' => 'is_resolved',
                        'label' => '¿Resuelto?',
                        'format' => 'raw',
                        'filter' => [1 => 'Sí', 0 => 'No'],
                        'value' => function ($model) {
                            return $model->is_resolved 
                                ? '<span class="badge badge-success badge-sm text-white font-medium">Sí</span>'
                                : '<span class="badge badge-error badge-sm text-white font-medium">No</span>';
                        }
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Fecha',
                        'format' => ['datetime', 'php:d M Y, h:i a'],
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view}',
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                /** @var app\models\ServiceFeedback $model */
                                $customer = $model->getResolvedCustomer();
                                $customerName = $customer ? ($customer->business_name ?: $customer->contact_name) : 'Anónimo / No identificado';
                                $customerContact = $customer ? ($customer->contact_name ?: 'N/A') : 'N/A';
                                $customerPhone = $customer ? ($customer->primary_phone ?: 'N/A') : 'N/A';
                                
                                $ticket = $model->getResolvedTicket();
                                $ticketTitle = $ticket ? "#{$ticket->ticket_code} - {$ticket->subject}" : ($model->ticket_id ? "#{$model->ticket_id}" : 'Sin ticket vinculado');

                                return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>', '#', [
                                    'class' => 'btn btn-sm btn-ghost btn-square text-primary view-feedback-btn',
                                    'title' => 'Ver Ficha Completa del Encuestado',
                                    'data-id' => $model->id,
                                    'data-customer-name' => Html::encode($customerName),
                                    'data-customer-contact' => Html::encode($customerContact),
                                    'data-customer-phone' => Html::encode($customerPhone),
                                    'data-email' => $model->client_email ? Html::encode($model->client_email) : 'Anónimo',
                                    'data-ticket' => Html::encode($ticketTitle),
                                    'data-date' => Yii::$app->formatter->asDatetime($model->created_at, 'php:d M Y, h:i a'),
                                    'data-ip' => Html::encode($model->ip_address ?: 'N/A'),
                                    'data-rating-stars' => $model->getRatingStarsHtml(),
                                    'data-rating-num' => $model->rating_service . ' / 5',
                                    'data-nps' => $model->nps_score !== null ? $model->nps_score : '-',
                                    'data-nps-badge' => $model->getNpsCategoryBadge(),
                                    'data-ces' => Html::encode($model->getEffortScoreLabel()),
                                    'data-resolved' => $model->is_resolved ? '<span class="badge badge-success text-white">Sí</span>' : '<span class="badge badge-error text-white">No</span>',
                                    'data-comment' => Html::encode($model->comments),
                                ]);
                            },
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>

<!-- Modal Ficha de Evaluación y Detalle del Encuestado -->
<dialog id="feedback_modal" class="modal modal-bottom sm:modal-middle">
  <div class="modal-box p-6 sm:p-8 max-w-xl">
    <form method="dialog">
        <button class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4 text-base-content/50 hover:text-base-content">✕</button>
    </form>
    
    <h3 class="font-bold text-2xl mb-6 text-base-content flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        Detalle del Encuestado
    </h3>
    
    <div class="space-y-4">
        
        <!-- Ficha del Cliente -->
        <div class="bg-base-200/50 p-4 rounded-xl border border-base-300">
            <h4 class="text-xs font-extrabold uppercase text-base-content/50 mb-3 tracking-wider flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Identificación de la Persona / Empresa
            </h4>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-base-content/60 text-xs block font-semibold">Cliente / Razón Social:</span>
                    <span id="modal-customer-name" class="font-bold text-base-content"></span>
                </div>
                <div>
                    <span class="text-base-content/60 text-xs block font-semibold">Persona de Contacto:</span>
                    <span id="modal-customer-contact" class="font-medium text-base-content"></span>
                </div>
                <div>
                    <span class="text-base-content/60 text-xs block font-semibold">Correo de respuesta:</span>
                    <span id="modal-email" class="font-mono text-xs text-primary font-semibold"></span>
                </div>
                <div>
                    <span class="text-base-content/60 text-xs block font-semibold">Teléfono móvil:</span>
                    <span id="modal-customer-phone" class="font-medium text-base-content"></span>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-base-content/60 text-xs block font-semibold">Ticket Asociado:</span>
                    <span id="modal-ticket" class="font-medium text-base-content"></span>
                </div>
                <div class="sm:col-span-2 text-xs text-base-content/50 border-t border-base-300 pt-2 mt-1">
                    Registrado el <span id="modal-date" class="font-semibold"></span> desde la IP <span id="modal-ip" class="font-mono font-semibold"></span>
                </div>
            </div>
        </div>

        <!-- Métricas Evaluadas -->
        <div class="bg-base-200/50 p-4 rounded-xl border border-base-300">
            <h4 class="text-xs font-extrabold uppercase text-base-content/50 mb-3 tracking-wider flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Calificaciones y Desempeño
            </h4>
            
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="bg-base-100 p-3 rounded-lg border border-base-200">
                    <span class="text-xs text-base-content/60 font-semibold block">CSAT (Servicio)</span>
                    <div id="modal-rating-stars" class="text-lg my-1"></div>
                    <span id="modal-rating-num" class="text-xs font-bold text-base-content/70"></span>
                </div>
                <div class="bg-base-100 p-3 rounded-lg border border-base-200">
                    <span class="text-xs text-base-content/60 font-semibold block">NPS (Recomendación)</span>
                    <div id="modal-nps-badge" class="mt-2"></div>
                </div>
                <div class="bg-base-100 p-3 rounded-lg border border-base-200">
                    <span class="text-xs text-base-content/60 font-semibold block">CES (Facilidad)</span>
                    <span id="modal-ces" class="font-bold text-base-content text-sm mt-1 block"></span>
                </div>
                <div class="bg-base-100 p-3 rounded-lg border border-base-200">
                    <span class="text-xs text-base-content/60 font-semibold block">¿Problema Resuelto?</span>
                    <div id="modal-resolved" class="mt-1"></div>
                </div>
            </div>
        </div>

        <!-- Comentarios -->
        <div>
            <span class="text-xs font-bold uppercase text-base-content/60 block mb-1">Comentarios del cliente:</span>
            <div class="p-4 bg-base-200/60 border border-base-300 rounded-xl italic text-base-content min-h-[5rem] shadow-inner text-sm leading-relaxed" id="modal-comment">
            </div>
        </div>

    </div>

    <div class="modal-action mt-6">
      <form method="dialog" class="w-full">
        <button class="btn btn-primary w-full rounded-xl shadow-md">Cerrar Ficha</button>
      </form>
    </div>
  </div>
</dialog>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark' || document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#9CA3AF' : '#4B5563';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';

    // 1. Gráfico CSAT (Estrellas)
    const csatCtx = document.getElementById('csatChart').getContext('2d');
    new Chart(csatCtx, {
        type: 'bar',
        data: {
            labels: ['5 Estrellas', '4 Estrellas', '3 Estrellas', '2 Estrellas', '1 Estrella'],
            datasets: [{
                label: 'Cantidad de Encuestas',
                data: <?= json_encode(array_values($ratingCountsMap)) ?>,
                backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#6B7280'],
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: textColor }, grid: { display: false } },
                y: { ticks: { color: textColor, stepSize: 1 }, grid: { color: gridColor }, beginAtZero: true }
            }
        }
    });

    // 2. Gráfico NPS (Promotores vs Pasivos vs Detractores)
    const npsCtx = document.getElementById('npsChart').getContext('2d');
    new Chart(npsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Promotores (9-10)', 'Pasivos (7-8)', 'Detractores (0-6)'],
            datasets: [{
                data: [<?= $promotersCount ?>, <?= $passivesCount ?>, <?= $detractorsCount ?>],
                backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: textColor } }
            }
        }
    });

    // 3. Gráfico Resolución
    const resCtx = document.getElementById('resolutionChart').getContext('2d');
    new Chart(resCtx, {
        type: 'pie',
        data: {
            labels: ['Solicitud Resuelta', 'No Resuelta'],
            datasets: [{
                data: [<?= $resolvedCount ?>, <?= $unresolvedCount ?>],
                backgroundColor: ['#10B981', '#EF4444'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: textColor } }
            }
        }
    });

    // 4. Gráfico Tendencia
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($trendLabels) ?>,
            datasets: [{
                label: 'Calificación (1-5)',
                data: <?= json_encode($trendData) ?>,
                borderColor: '#6366F1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: '#6366F1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: textColor }, grid: { display: false } },
                y: { min: 1, max: 5, ticks: { color: textColor, stepSize: 1 }, grid: { color: gridColor } }
            }
        }
    });
});

// Lógica del Modal
const modal = document.getElementById('feedback_modal');
document.body.addEventListener('click', function(e) {
    const btn = e.target.closest('.view-feedback-btn');
    if (btn) {
        e.preventDefault();
        
        document.getElementById('modal-customer-name').innerText = btn.getAttribute('data-customer-name');
        document.getElementById('modal-customer-contact').innerText = btn.getAttribute('data-customer-contact');
        document.getElementById('modal-customer-phone').innerText = btn.getAttribute('data-customer-phone');
        document.getElementById('modal-email').innerText = btn.getAttribute('data-email');
        document.getElementById('modal-ticket').innerText = btn.getAttribute('data-ticket');
        document.getElementById('modal-date').innerText = btn.getAttribute('data-date');
        document.getElementById('modal-ip').innerText = btn.getAttribute('data-ip');
        
        document.getElementById('modal-rating-stars').innerHTML = btn.getAttribute('data-rating-stars');
        document.getElementById('modal-rating-num').innerText = btn.getAttribute('data-rating-num');
        document.getElementById('modal-nps-badge').innerHTML = btn.getAttribute('data-nps-badge');
        document.getElementById('modal-ces').innerText = btn.getAttribute('data-ces');
        document.getElementById('modal-resolved').innerHTML = btn.getAttribute('data-resolved');
        
        const comment = btn.getAttribute('data-comment');
        document.getElementById('modal-comment').innerText = comment ? comment : 'Sin comentarios adicionales registrados.';
        
        modal.showModal();
    }
});
</script>