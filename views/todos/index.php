<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use app\models\Todos;
use app\models\Customers;
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

$this->title = 'To-Do List (Administración)';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="todos-index space-y-6">

    <!-- Active Timer Banner (si el admin tiene un cronómetro en marcha) -->
    <?php if ($runningTimer && $runningTimer->todo): ?>
        <div class="alert alert-warning shadow-lg border border-warning/30 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="relative flex h-4 w-4">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-warning opacity-75"></span>
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
                <button type="button" onclick="stopRunningTimerGlobal(<?= $runningTimer->todo_id ?>)" class="btn btn-sm btn-error text-white">
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
                'class' => 'btn btn-primary text-white shadow-md hover:scale-105 transition-transform'
            ]) ?>
        </div>
    </div>

    <!-- Tarjetas KPI Estadísticas -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <!-- Pendientes -->
        <div class="card bg-base-100 shadow-md border border-base-200">
            <div class="card-body p-4 flex flex-row items-center justify-between">
                <div>
                    <span class="text-xs text-base-content/50 uppercase font-bold tracking-wider">Pendientes</span>
                    <div class="text-2xl font-bold text-warning mt-1"><?= $pendingCount ?></div>
                </div>
                <div class="p-3 bg-warning/10 text-warning rounded-xl">
                    <i class="fas fa-hourglass-start text-xl"></i>
                </div>
            </div>
        </div>

        <!-- En Progreso -->
        <div class="card bg-base-100 shadow-md border border-base-200">
            <div class="card-body p-4 flex flex-row items-center justify-between">
                <div>
                    <span class="text-xs text-base-content/50 uppercase font-bold tracking-wider">En Progreso</span>
                    <div class="text-2xl font-bold text-primary mt-1"><?= $inProgressCount ?></div>
                </div>
                <div class="p-3 bg-primary/10 text-primary rounded-xl">
                    <i class="fas fa-spinner fa-spin text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Completadas -->
        <div class="card bg-base-100 shadow-md border border-base-200">
            <div class="card-body p-4 flex flex-row items-center justify-between">
                <div>
                    <span class="text-xs text-base-content/50 uppercase font-bold tracking-wider">Completadas</span>
                    <div class="text-2xl font-bold text-success mt-1"><?= $completedCount ?></div>
                </div>
                <div class="p-3 bg-success/10 text-success rounded-xl">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Vencidas -->
        <div class="card bg-base-100 shadow-md border border-base-200">
            <div class="card-body p-4 flex flex-row items-center justify-between">
                <div>
                    <span class="text-xs text-base-content/50 uppercase font-bold tracking-wider">Vencidas</span>
                    <div class="text-2xl font-bold <?= $overdueCount > 0 ? 'text-error animate-pulse' : 'text-base-content/70' ?> mt-1">
                        <?= $overdueCount ?>
                    </div>
                </div>
                <div class="p-3 <?= $overdueCount > 0 ? 'bg-error/10 text-error' : 'bg-base-200 text-base-content/40' ?> rounded-xl">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Tiempo Hoy -->
        <div class="card bg-base-100 shadow-md border border-base-200 col-span-2 md:col-span-1">
            <div class="card-body p-4 flex flex-row items-center justify-between">
                <div>
                    <span class="text-xs text-base-content/50 uppercase font-bold tracking-wider">Tiempo Hoy</span>
                    <div class="text-2xl font-bold text-info mt-1"><?= $todayTrackedTime ?></div>
                </div>
                <div class="p-3 bg-info/10 text-info rounded-xl">
                    <i class="fas fa-stopwatch text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros y Búsqueda -->
    <div class="card bg-base-100 shadow-md border border-base-200">
        <div class="card-body p-4">
            <form method="get" action="<?= Url::to(['index']) ?>" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                <input type="hidden" name="view" value="<?= Html::encode($viewMode) ?>">

                <div class="md:col-span-4">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/40">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="TodosSearch[q]" value="<?= Html::encode($searchModel->q) ?>" placeholder="Buscar tarea por título o descripción..." class="input input-bordered input-sm w-full pl-9 focus:input-primary">
                    </div>
                </div>

                <div class="md:col-span-2">
                    <select name="TodosSearch[priority]" class="select select-bordered select-sm w-full">
                        <option value="">-- Prioridad --</option>
                        <?php foreach (Todos::optsPriority() as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= $searchModel->priority === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($viewMode === 'list'): ?>
                    <div class="md:col-span-2">
                        <select name="TodosSearch[status]" class="select select-bordered select-sm w-full">
                            <option value="">-- Estado --</option>
                            <?php foreach (Todos::optsStatus() as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= $searchModel->status === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="<?= $viewMode === 'list' ? 'md:col-span-2' : 'md:col-span-4' ?>">
                    <select name="TodosSearch[customer_id]" class="select select-bordered select-sm w-full">
                        <option value="">-- Todos los Clientes --</option>
                        <?php 
                        $custs = Customers::find()->select(['id', 'business_name', 'trade_name'])->orderBy(['business_name' => SORT_ASC])->all();
                        foreach ($custs as $c): 
                            $cName = $c->trade_name ?: $c->business_name;
                        ?>
                            <option value="<?= $c->id ?>" <?= (string)$searchModel->customer_id === (string)$c->id ? 'selected' : '' ?>><?= Html::encode($cName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-2 flex items-center gap-2">
                    <button type="submit" class="btn btn-sm btn-primary text-white flex-1">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                    <a href="<?= Url::to(['index', 'view' => $viewMode]) ?>" class="btn btn-sm btn-ghost" title="Limpiar filtros">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Vista de Tablero KANBAN -->
    <?php if ($viewMode === 'board'): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Columna 1: Pendientes -->
            <div class="bg-base-200/50 rounded-2xl p-4 border border-base-200 flex flex-col">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-base-300">
                    <div class="flex items-center gap-2 font-bold text-base text-base-content/80">
                        <span class="w-3 h-3 rounded-full bg-warning"></span>
                        Pendientes
                        <span class="badge badge-warning badge-sm"><?= count($boardTasks[Todos::STATUS_PENDING] ?? []) ?></span>
                    </div>
                    <a href="<?= Url::to(['create']) ?>" class="btn btn-xs btn-ghost btn-circle" title="Agregar tarea">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>

                <div class="space-y-3 flex-1 overflow-y-auto max-h-[75vh]">
                    <?php if (empty($boardTasks[Todos::STATUS_PENDING])): ?>
                        <div class="text-center py-10 text-base-content/40 text-xs italic">
                            No hay tareas pendientes
                        </div>
                    <?php else: ?>
                        <?php foreach ($boardTasks[Todos::STATUS_PENDING] as $task): ?>
                            <?= $this->render('_task_card', ['task' => $task]) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna 2: En Progreso -->
            <div class="bg-base-200/50 rounded-2xl p-4 border border-base-200 flex flex-col">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-base-300">
                    <div class="flex items-center gap-2 font-bold text-base text-base-content/80">
                        <span class="w-3 h-3 rounded-full bg-primary"></span>
                        En Progreso
                        <span class="badge badge-primary badge-sm text-white"><?= count($boardTasks[Todos::STATUS_IN_PROGRESS] ?? []) ?></span>
                    </div>
                </div>

                <div class="space-y-3 flex-1 overflow-y-auto max-h-[75vh]">
                    <?php if (empty($boardTasks[Todos::STATUS_IN_PROGRESS])): ?>
                        <div class="text-center py-10 text-base-content/40 text-xs italic">
                            No hay tareas en progreso actualmente
                        </div>
                    <?php else: ?>
                        <?php foreach ($boardTasks[Todos::STATUS_IN_PROGRESS] as $task): ?>
                            <?= $this->render('_task_card', ['task' => $task]) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna 3: Completadas -->
            <div class="bg-base-200/50 rounded-2xl p-4 border border-base-200 flex flex-col">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-base-300">
                    <div class="flex items-center gap-2 font-bold text-base text-base-content/80">
                        <span class="w-3 h-3 rounded-full bg-success"></span>
                        Completadas
                        <span class="badge badge-success badge-sm text-white"><?= count($boardTasks[Todos::STATUS_COMPLETED] ?? []) ?></span>
                    </div>
                </div>

                <div class="space-y-3 flex-1 overflow-y-auto max-h-[75vh]">
                    <?php if (empty($boardTasks[Todos::STATUS_COMPLETED])): ?>
                        <div class="text-center py-10 text-base-content/40 text-xs italic">
                            No hay tareas completadas recientemente
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
                                    <i class="fas fa-building text-[10px]"></i> ' . Html::encode($cName) . '
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
                                $html .= '<div class="text-[10px] text-warning mt-0.5" title="Recordatorio programado">
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
                                <div class="flex justify-between text-[10px] font-bold text-base-content/60 mb-0.5">
                                    <span>Progreso</span>
                                    <span>' . $pct . '%</span>
                                </div>
                                <progress class="progress ' . $color . ' w-full h-1.5" value="' . $pct . '" max="100"></progress>
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
                        'template' => '{view} {update} {delete}',
                        'buttons' => [
                            'view' => function ($url, $model) {
                                return Html::a('<i class="fas fa-eye text-primary"></i>', $url, [
                                    'class' => 'btn btn-ghost btn-xs btn-circle',
                                    'title' => 'Ver detalles',
                                ]);
                            },
                            'update' => function ($url, $model) {
                                return Html::a('<i class="fas fa-pencil-alt text-base-content/70"></i>', $url, [
                                    'class' => 'btn btn-ghost btn-xs btn-circle',
                                    'title' => 'Editar tarea',
                                ]);
                            },
                            'delete' => function ($url, $model) {
                                return Html::a('<i class="fas fa-trash text-error"></i>', $url, [
                                    'class' => 'btn btn-ghost btn-xs btn-circle',
                                    'title' => 'Eliminar tarea',
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

<script>
function stopRunningTimerGlobal(todoId) {
    const desc = prompt("Ingresa brevemente qué se realizó en esta sesión (Opcional):", "");
    if (desc === null) return; // Cancelado

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
            alert("Cronómetro detenido exitosamente. Tiempo registrado: " + data.duration);
            location.reload();
        } else {
            alert(data.message || "Error al detener el cronómetro.");
        }
    })
    .catch(err => {
        alert("Error de conexión al detener cronómetro.");
    });
}
</script>
