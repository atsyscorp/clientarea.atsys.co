<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use app\models\Todos;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\TodosSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $viewMode */
/** @var int $totalCount */
/** @var int $pendingCount */
/** @var int $inProgressCount */
/** @var int $completedCount */
/** @var int $overdueCount */
/** @var string $todayTrackedTime */
/** @var app\models\TodoTimeLogs|null $runningTimer */
/** @var array $boardTasks */
/** @var array $customersList */

$this->title = 'To-Do List (Administración)';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="todos-index space-y-4">

    <!-- Active Timer Banner (si el admin tiene un cronómetro en marcha) -->
    <?php if ($runningTimer && $runningTimer->todo): ?>
        <div class="alert alert-warning shadow-lg border border-warning/30 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="relative flex h-4 w-4">
                    <span class="animate-ping motion-reduce:animate-none absolute inline-flex h-full w-full rounded-full bg-warning opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-4 w-4 bg-warning"></span>
                </span>
                <div>
                    <div class="font-bold text-sm">Cronómetro activo en curso:</div>
                    <div class="text-xs opacity-80">
                        Tarea: <b><?= Html::encode($runningTimer->todo->title) ?></b> 
                        (Iniciado a las <?= date('H:i', strtotime($runningTimer->start_time)) ?>)
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <?= Html::a('<i class="fas fa-external-link-alt mr-1"></i> Ir a la Tarea', ['view', 'id' => $runningTimer->todo_id], ['class' => 'btn btn-sm btn-outline']) ?>
                <button type="button" onclick="openStopTimerGlobal(<?= $runningTimer->todo_id ?>)" class="btn btn-sm btn-error text-white">
                    <i class="fas fa-stop mr-1"></i> Detener Cronómetro
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Encabezado y Acciones -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-base-content/85 flex items-center gap-3">
                <span class="p-2.5 bg-primary/10 text-primary rounded-xl inline-flex items-center justify-center">
                    <i class="fas fa-clipboard-check text-2xl"></i>
                </span>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-sm text-base-content/60 mt-1">Control de tareas, medición de tiempos trabajados, seguimiento continuo y alertas de recordatorio.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Selector de Vista: Tablero vs Lista -->
            <div class="join border border-base-300 rounded-lg p-0.5 bg-base-100">
                <a href="<?= Url::current(['view' => 'board']) ?>" class="join-item btn btn-sm <?= $viewMode === 'board' ? 'btn-primary text-white' : 'btn-ghost' ?>">
                    <i class="fas fa-columns mr-1"></i> Tablero
                </a>
                <a href="<?= Url::current(['view' => 'list']) ?>" class="join-item btn btn-sm <?= $viewMode === 'list' ? 'btn-primary text-white' : 'btn-ghost' ?>">
                    <i class="fas fa-list mr-1"></i> Lista
                </a>
            </div>

            <?= Html::a('<i class="fas fa-plus mr-1"></i> Nueva Tarea', ['create'], [
                'class' => 'btn btn-primary text-white shadow-md hover:scale-105 motion-reduce:hover:scale-100 transition-transform'
            ]) ?>
        </div>
    </div>

    <style>
        .kpi-row-5 {
            display: grid !important;
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            gap: 0.625rem !important;
            width: 100% !important;
        }
        @media (max-width: 767px) {
            .kpi-row-5 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            .kpi-row-5 > div:last-child {
                grid-column: span 2 !important;
            }
        }

        .filter-toolbar-grid {
            display: grid !important;
            grid-template-columns: 2.5fr 1.2fr 1.8fr auto !important;
            gap: 0.75rem !important;
            align-items: center !important;
            width: 100% !important;
        }
        .filter-toolbar-grid-list {
            display: grid !important;
            grid-template-columns: 2fr 1fr 1fr 1.5fr auto !important;
            gap: 0.75rem !important;
            align-items: center !important;
            width: 100% !important;
        }
        @media (max-width: 991px) {
            .filter-toolbar-grid,
            .filter-toolbar-grid-list {
                grid-template-columns: 1fr 1fr !important;
            }
        }
        @media (max-width: 640px) {
            .filter-toolbar-grid,
            .filter-toolbar-grid-list {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <!-- Tarjetas KPI Estadísticas (Garantizadas en 1 sola fila de 5 columnas) -->
    <div class="kpi-row-5 mb-4" style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.625rem; width: 100%;">
        <!-- Pendientes -->
        <div class="bg-base-100 rounded-xl p-3 shadow-sm border border-base-200 flex items-center justify-between hover:shadow-md transition-shadow">
            <div class="min-w-0 pr-1">
                <span class="text-[10px] sm:text-[11px] font-bold text-base-content/60 uppercase tracking-wider block truncate">Pendientes</span>
                <div class="text-xl sm:text-2xl font-extrabold text-warning leading-tight mt-0.5"><?= $pendingCount ?></div>
            </div>
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-warning/10 text-warning flex items-center justify-center shrink-0" aria-hidden="true">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- En Progreso -->
        <div class="bg-base-100 rounded-xl p-3 shadow-sm border border-base-200 flex items-center justify-between hover:shadow-md transition-shadow">
            <div class="min-w-0 pr-1">
                <span class="text-[10px] sm:text-[11px] font-bold text-base-content/60 uppercase tracking-wider block truncate">En Progreso</span>
                <div class="text-xl sm:text-2xl font-extrabold text-primary leading-tight mt-0.5"><?= $inProgressCount ?></div>
            </div>
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0" aria-hidden="true">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
        </div>

        <!-- Completadas -->
        <div class="bg-base-100 rounded-xl p-3 shadow-sm border border-base-200 flex items-center justify-between hover:shadow-md transition-shadow">
            <div class="min-w-0 pr-1">
                <span class="text-[10px] sm:text-[11px] font-bold text-base-content/60 uppercase tracking-wider block truncate">Completadas</span>
                <div class="text-xl sm:text-2xl font-extrabold text-success leading-tight mt-0.5"><?= $completedCount ?></div>
            </div>
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-success/10 text-success flex items-center justify-center shrink-0" aria-hidden="true">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Vencidas -->
        <div class="bg-base-100 rounded-xl p-3 shadow-sm border border-base-200 flex items-center justify-between hover:shadow-md transition-shadow">
            <div class="min-w-0 pr-1">
                <span class="text-[10px] sm:text-[11px] font-bold text-base-content/60 uppercase tracking-wider block truncate">Vencidas</span>
                <div class="text-xl sm:text-2xl font-extrabold <?= $overdueCount > 0 ? 'text-error animate-pulse motion-reduce:animate-none' : 'text-base-content/70' ?> leading-tight mt-0.5">
                    <?= $overdueCount ?>
                </div>
            </div>
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg <?= $overdueCount > 0 ? 'bg-error/10 text-error' : 'bg-base-200 text-base-content/40' ?> flex items-center justify-center shrink-0" aria-hidden="true">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>

        <!-- Tiempo Hoy -->
        <div class="bg-base-100 rounded-xl p-3 shadow-sm border border-base-200 flex items-center justify-between hover:shadow-md transition-shadow">
            <div class="min-w-0 pr-1">
                <span class="text-[10px] sm:text-[11px] font-bold text-base-content/60 uppercase tracking-wider block truncate">Tiempo Hoy</span>
                <div class="text-xl sm:text-2xl font-extrabold text-info leading-tight mt-0.5"><?= $todayTrackedTime ?></div>
            </div>
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-info/10 text-info flex items-center justify-center shrink-0" aria-hidden="true">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros y Búsqueda -->
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-6">
        <div class="card-body p-4">
            <form method="get" action="<?= Url::to(['index']) ?>" class="<?= $viewMode === 'list' ? 'filter-toolbar-grid-list' : 'filter-toolbar-grid' ?>">
                <input type="hidden" name="view" value="<?= Html::encode($viewMode) ?>">

                <!-- Búsqueda por texto -->
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/40 pointer-events-none" aria-hidden="true">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="TodosSearch[q]" value="<?= Html::encode($searchModel->q) ?>" placeholder="Buscar tarea por título o descripción..." aria-label="Buscar tarea por título o descripción" class="input input-bordered input-sm w-full pl-10 focus:input-primary">
                </div>

                <!-- Filtro Prioridad -->
                <div class="w-full">
                    <select name="TodosSearch[priority]" aria-label="Filtrar por prioridad" class="select select-bordered select-sm w-full">
                        <option value="">-- Prioridad --</option>
                        <?php foreach (Todos::optsPriority() as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= $searchModel->priority === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro Estado (en vista lista) -->
                <?php if ($viewMode === 'list'): ?>
                    <div class="w-full">
                        <select name="TodosSearch[status]" aria-label="Filtrar por estado" class="select select-bordered select-sm w-full">
                            <option value="">-- Estado --</option>
                            <?php foreach (Todos::optsStatus() as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= $searchModel->status === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Filtro Cliente -->
                <div class="w-full">
                    <select name="TodosSearch[customer_id]" aria-label="Filtrar por cliente" class="select select-bordered select-sm w-full">
                        <option value="">-- Todos los Clientes --</option>
                        <?php foreach ($customersList as $cId => $cName): ?>
                            <option value="<?= $cId ?>" <?= (string)$searchModel->customer_id === (string)$cId ? 'selected' : '' ?>><?= Html::encode($cName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center gap-2 whitespace-nowrap">
                    <button type="submit" class="btn btn-sm btn-primary text-white">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                    <a href="<?= Url::to(['index', 'view' => $viewMode]) ?>" class="btn btn-sm btn-ghost border border-base-300" title="Limpiar filtros" aria-label="Limpiar filtros">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Vista de Tablero KANBAN en Columnas -->
    <?php if ($viewMode === 'board'): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">

            <!-- Columna 1: Pendientes -->
            <div class="bg-base-200/40 rounded-2xl p-4 border border-base-200 border-t-4 border-t-warning shadow-sm flex flex-col">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-base-300/80">
                    <div class="flex items-center gap-2 font-bold text-sm text-base-content/85">
                        <span class="w-2.5 h-2.5 rounded-full bg-warning"></span>
                        <span>Pendientes</span>
                        <span class="badge badge-warning badge-sm font-semibold"><?= count($boardTasks[Todos::STATUS_PENDING] ?? []) ?></span>
                    </div>
                    <a href="<?= Url::to(['create']) ?>" class="btn btn-xs btn-ghost btn-circle hover:bg-warning/20 hover:text-warning" title="Agregar tarea" aria-label="Agregar tarea pendiente">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>

                <div class="space-y-3 flex-1 overflow-y-visible md:overflow-y-auto max-h-none md:max-h-[calc(100vh-270px)] min-h-[460px] kanban-column-scroll pr-1">
                    <?php if (empty($boardTasks[Todos::STATUS_PENDING])): ?>
                        <div class="text-center py-12 px-4 rounded-xl border-2 border-dashed border-base-300/70 text-base-content/40 text-xs italic flex flex-col items-center justify-center gap-1.5">
                            <svg class="w-8 h-8 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <span>No hay tareas pendientes</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($boardTasks[Todos::STATUS_PENDING] as $task): ?>
                            <?= $this->render('_task_card', ['task' => $task]) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna 2: En Progreso -->
            <div class="bg-base-200/40 rounded-2xl p-4 border border-base-200 border-t-4 border-t-primary shadow-sm flex flex-col">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-base-300/80">
                    <div class="flex items-center gap-2 font-bold text-sm text-base-content/85">
                        <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                        <span>En Progreso</span>
                        <span class="badge badge-primary badge-sm font-semibold text-white"><?= count($boardTasks[Todos::STATUS_IN_PROGRESS] ?? []) ?></span>
                    </div>
                </div>

                <div class="space-y-3 flex-1 overflow-y-visible md:overflow-y-auto max-h-none md:max-h-[calc(100vh-270px)] min-h-[460px] kanban-column-scroll pr-1">
                    <?php if (empty($boardTasks[Todos::STATUS_IN_PROGRESS])): ?>
                        <div class="text-center py-12 px-4 rounded-xl border-2 border-dashed border-base-300/70 text-base-content/40 text-xs italic flex flex-col items-center justify-center gap-1.5">
                            <svg class="w-8 h-8 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <span>No hay tareas en progreso actualmente</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($boardTasks[Todos::STATUS_IN_PROGRESS] as $task): ?>
                            <?= $this->render('_task_card', ['task' => $task]) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna 3: Completadas -->
            <div class="bg-base-200/40 rounded-2xl p-4 border border-base-200 border-t-4 border-t-success shadow-sm flex flex-col">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-base-300/80">
                    <div class="flex items-center gap-2 font-bold text-sm text-base-content/85">
                        <span class="w-2.5 h-2.5 rounded-full bg-success"></span>
                        <span>Completadas</span>
                        <span class="badge badge-success badge-sm font-semibold text-white"><?= count($boardTasks[Todos::STATUS_COMPLETED] ?? []) ?></span>
                    </div>
                </div>

                <div class="space-y-3 flex-1 overflow-y-visible md:overflow-y-auto max-h-none md:max-h-[calc(100vh-270px)] min-h-[460px] kanban-column-scroll pr-1">
                    <?php if (empty($boardTasks[Todos::STATUS_COMPLETED])): ?>
                        <div class="text-center py-12 px-4 rounded-xl border-2 border-dashed border-base-300/70 text-base-content/40 text-xs italic flex flex-col items-center justify-center gap-1.5">
                            <svg class="w-8 h-8 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>No hay tareas completadas recientemente</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($boardTasks[Todos::STATUS_COMPLETED] as $task): ?>
                            <?= $this->render('_task_card', ['task' => $task]) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    <!-- Vista de LISTA / TABLA -->
    <?php else: ?>
        <div class="overflow-x-auto w-full bg-base-100 shadow-xl rounded-box border border-base-200">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-zebra w-full text-sm'],
                'summary' => '<div class="p-4 text-xs text-base-content/60">Mostrando <b>{begin}-{end}</b> de <b>{totalCount}</b> tareas.</div>',
                'layout' => "{items}\n{summary}\n{pager}",
                'pager' => [
                    'options' => ['class' => 'join mt-4 justify-center w-full'],
                    'linkOptions' => ['class' => 'join-item btn btn-sm'],
                    'disabledListItemSubTagOptions' => ['class' => 'join-item btn btn-sm btn-disabled'],
                    'activePageCssClass' => 'btn-active btn-primary text-white',
                ],
                'columns' => [
                    // Prioridad
                    [
                        'attribute' => 'priority',
                        'label' => 'Prioridad',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return '<span class="badge ' . $model->getPriorityBadgeClass() . ' badge-sm">' . 
                                Html::encode(Todos::optsPriority()[$model->priority] ?? $model->priority) . 
                            '</span>';
                        },
                    ],

                    // Tarea y Cliente
                    [
                        'attribute' => 'title',
                        'label' => 'Tarea / Requerimiento',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $html = '<div class="font-bold text-base-content/85 text-sm hover:text-primary transition-colors">' . 
                                Html::a(Html::encode($model->title), ['view', 'id' => $model->id]) . 
                            '</div>';

                            if ($model->customer) {
                                $cName = $model->customer->trade_name ?: $model->customer->business_name;
                                $html .= '<div class="text-xs text-base-content/50 mt-0.5 flex items-center gap-1">
                                    <i class="fas fa-building text-xs"></i> ' . Html::encode($cName) . '
                                </div>';
                            }
                            return $html;
                        },
                    ],

                    // Responsable
                    [
                        'attribute' => 'assigned_to',
                        'label' => 'Responsable',
                        'format' => 'raw',
                        'value' => function ($model) {
                            if ($model->assignedToUser) {
                                return '<div class="text-xs font-semibold text-base-content/80 flex items-center gap-1">
                                    <i class="fas fa-user-circle text-primary text-sm"></i> ' . 
                                    Html::encode($model->assignedToUser->username) . 
                                '</div>';
                            }
                            return '<span class="text-xs text-base-content/40 italic">Sin asignar</span>';
                        },
                    ],

                    // Vencimiento y Recordatorio
                    [
                        'attribute' => 'due_date',
                        'label' => 'Vencimiento',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $html = '';
                            if ($model->due_date) {
                                $isOver = $model->getIsOverdue();
                                $class = $isOver ? 'text-error font-bold' : 'text-base-content/70';
                                $html .= '<div class="text-xs ' . $class . '">
                                    <i class="fas fa-calendar-day mr-1"></i> ' . date('d/m/Y H:i', strtotime($model->due_date)) . 
                                '</div>';
                            } else {
                                $html .= '<span class="text-xs text-base-content/30">Sin fecha límite</span>';
                            }

                            if ($model->reminder_at) {
                                $html .= '<div class="text-xs text-warning/90 mt-0.5" title="Recordatorio programado">
                                    <i class="fas fa-bell mr-1"></i> ' . date('d/m H:i', strtotime($model->reminder_at)) . 
                                '</div>';
                            }

                            return $html;
                        },
                    ],

                    // Tiempo Registrado
                    [
                        'attribute' => 'total_time_spent',
                        'label' => 'Tiempo Medido',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return '<span class="badge badge-ghost font-mono font-semibold text-xs">' . 
                                '<i class="fas fa-stopwatch mr-1 text-primary"></i> ' . $model->getFormattedTotalTime() . 
                            '</span>';
                        },
                    ],

                    // Avance
                    [
                        'label' => 'Avance',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $pct = $model->getCompletionPercentage();
                            $color = $pct >= 100 ? 'progress-success' : ($pct > 30 ? 'progress-primary' : 'progress-warning');
                            return '<div class="w-24">
                                <div class="flex justify-between text-xs font-semibold text-base-content/60 mb-0.5">
                                    <span>Progreso</span>
                                    <span>' . $pct . '%</span>
                                </div>
                                <progress class="progress ' . $color . ' w-full h-1.5" value="' . $pct . '" max="100" aria-label="Porcentaje de avance: ' . $pct . '%" aria-valuenow="' . $pct . '"></progress>
                            </div>';
                        },
                    ],

                    // Estado
                    [
                        'attribute' => 'status',
                        'label' => 'Estado',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return '<span class="badge ' . $model->getStatusBadgeClass() . ' badge-sm">' . 
                                Html::encode(Todos::optsStatus()[$model->status] ?? $model->status) . 
                            '</span>';
                        },
                    ],

                    // Acciones
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Acciones',
                        'headerOptions' => ['class' => 'text-right min-w-[130px]'],
                        'contentOptions' => ['class' => 'text-right whitespace-nowrap space-x-1'],
                        'template' => '{view} {update} {delete}',
                        'buttons' => [
                            'view' => function ($url, $model) {
                                return Html::a('<i class="fas fa-eye text-primary"></i>', $url, [
                                    'class' => 'btn btn-ghost btn-sm sm:btn-xs btn-circle',
                                    'title' => 'Ver detalles',
                                    'aria-label' => 'Ver detalles de la tarea: ' . Html::encode($model->title),
                                ]);
                            },
                            'update' => function ($url, $model) {
                                return Html::a('<i class="fas fa-pencil-alt text-base-content/70"></i>', $url, [
                                    'class' => 'btn btn-ghost btn-sm sm:btn-xs btn-circle',
                                    'title' => 'Editar tarea',
                                    'aria-label' => 'Editar tarea: ' . Html::encode($model->title),
                                ]);
                            },
                            'delete' => function ($url, $model) {
                                return Html::a('<i class="fas fa-trash text-error"></i>', $url, [
                                    'class' => 'btn btn-ghost btn-sm sm:btn-xs btn-circle',
                                    'title' => 'Eliminar tarea',
                                    'aria-label' => 'Eliminar tarea: ' . Html::encode($model->title),
                                    'data' => [
                                        'confirm' => '¿Estás seguro de que deseas eliminar esta tarea?',
                                        'method' => 'post',
                                    ],
                                ]);
                            },
                        ],
                    ],
                ],
            ]); ?>
        </div>
    <?php endif; ?>

</div>

<!-- Modal: Detener Cronómetro Activo -->
<dialog id="modal-stop-timer-global" class="modal">
    <div class="modal-box">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3" aria-label="Cerrar modal">✕</button>
        </form>
        <h3 class="font-bold text-lg text-primary flex items-center gap-2 mb-2">
            <i class="fas fa-stop-circle text-error"></i> Detener Cronómetro de Trabajo
        </h3>
        <p class="text-xs text-base-content/60 mb-4">
            El tiempo transcurrido quedará registrado en la bitácora de la tarea. Opcionalmente puedes detallar qué actividades realizaste.
        </p>
        <form id="form-stop-timer-global" onsubmit="submitStopTimerGlobal(event)" class="space-y-4">
            <input type="hidden" id="stop-timer-todo-id" value="">
            <div class="form-control">
                <label class="label font-semibold text-xs" for="stop-timer-desc-global">
                    Detalle de las actividades realizadas (Opcional):
                </label>
                <textarea id="stop-timer-desc-global" name="description" rows="3" class="textarea textarea-bordered w-full text-sm focus:textarea-primary" placeholder="Ej: Revisión de logs, solución al incidente y pruebas de verificación..."></textarea>
            </div>
            <div id="stop-timer-error-global" class="alert alert-error text-xs p-2 hidden"></div>
            <div class="modal-action">
                <button type="button" onclick="document.getElementById('modal-stop-timer-global').close()" class="btn btn-ghost btn-sm">Continuar Trabajando</button>
                <button type="submit" id="btn-confirm-stop-global" class="btn btn-error btn-sm text-white">
                    <i class="fas fa-stop mr-1"></i> Detener y Guardar Tiempo
                </button>
            </div>
        </form>
    </div>
</dialog>

<script>
function openStopTimerGlobal(todoId) {
    document.getElementById('stop-timer-todo-id').value = todoId;
    document.getElementById('stop-timer-desc-global').value = '';
    const errBox = document.getElementById('stop-timer-error-global');
    if (errBox) errBox.classList.add('hidden');
    document.getElementById('modal-stop-timer-global').showModal();
}

function submitStopTimerGlobal(e) {
    e.preventDefault();
    const todoId = document.getElementById('stop-timer-todo-id').value;
    const desc = document.getElementById('stop-timer-desc-global').value.trim();
    const btnConfirm = document.getElementById('btn-confirm-stop-global');
    const errBox = document.getElementById('stop-timer-error-global');

    btnConfirm.disabled = true;
    btnConfirm.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';

    const formData = new FormData();
    formData.append('description', desc);
    formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

    fetch('/todos/stop-timer?id=' + todoId, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            errBox.textContent = data.message || "Error al detener el cronómetro.";
            errBox.classList.remove('hidden');
            btnConfirm.disabled = false;
            btnConfirm.innerHTML = '<i class="fas fa-stop mr-1"></i> Detener y Guardar Tiempo';
        }
    })
    .catch(err => {
        errBox.textContent = "Error de conexión con el servidor.";
        errBox.classList.remove('hidden');
        btnConfirm.disabled = false;
        btnConfirm.innerHTML = '<i class="fas fa-stop mr-1"></i> Detener y Guardar Tiempo';
    });
}
</script>
