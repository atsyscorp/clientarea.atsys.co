<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Todos;
use app\models\TodoTimeLogs;

/** @var yii\web\View $this */
/** @var app\models\Todos $model */
/** @var app\models\TodoTimeLogs|null $activeTimer */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'To-Do List', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$pct = $model->getCompletionPercentage();
$isOverdue = $model->getIsOverdue();
$isRunning = $activeTimer !== null;
$startTimeStamp = $isRunning ? strtotime($activeTimer->start_time) : null;
?>

<div class="todos-view space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-base-100 p-6 rounded-2xl shadow-xl border border-base-200">
        <div class="space-y-2 max-w-3xl">
            <div class="flex flex-wrap items-center gap-2">
                <span id="task-status-badge" class="badge <?= $model->getStatusBadgeClass() ?> badge-lg shadow-sm">
                    <?= Html::encode(Todos::optsStatus()[$model->status] ?? $model->status) ?>
                </span>
                <span class="badge <?= $model->getPriorityBadgeClass() ?> badge-md">
                    Prioridad <?= Html::encode(Todos::optsPriority()[$model->priority] ?? $model->priority) ?>
                </span>
                <?php if ($isOverdue): ?>
                    <span class="badge badge-error text-white badge-md font-bold animate-pulse">
                        <i class="fas fa-exclamation-circle mr-1"></i> Vencida
                    </span>
                <?php endif; ?>
                <?php if ($model->reminder_at): ?>
                    <span class="badge badge-warning text-warning-content badge-md">
                        <i class="fas fa-bell mr-1"></i> <?= date('d/m/Y H:i', strtotime($model->reminder_at)) ?>
                    </span>
                <?php endif; ?>
            </div>

            <h1 class="text-2xl md:text-3xl font-bold text-base-content/90 leading-tight">
                <?= Html::encode($model->title) ?>
            </h1>

            <?php if ($model->customer): ?>
                <div class="text-sm text-base-content/60 flex items-center gap-2">
                    <i class="fas fa-building text-primary"></i>
                    <span>Cliente:</span>
                    <?= Html::a(Html::encode($model->customer->trade_name ?: $model->customer->business_name), ['customers/view', 'id' => $model->customer_id], ['class' => 'link link-primary font-semibold']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Status & Action Buttons -->
        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-start lg:justify-end">
            <!-- Cambio de Estado Rápido -->
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-outline btn-primary btn-sm">
                    <i class="fas fa-exchange-alt mr-1"></i> Cambiar Estado
                </label>
                <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-2xl bg-base-100 rounded-box w-52 border border-base-200">
                    <?php foreach (Todos::optsStatus() as $stKey => $stLabel): ?>
                        <li>
                            <button type="button" onclick="changeTaskStatus('<?= $stKey ?>')" class="<?= $model->status === $stKey ? 'active' : '' ?>">
                                <?= $stLabel ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?= Html::a('<i class="fas fa-pencil-alt mr-1"></i> Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-ghost btn-sm']) ?>
            <?= Html::a('<i class="fas fa-trash text-error mr-1"></i> Eliminar', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-ghost btn-sm text-error',
                'data' => [
                    'confirm' => '¿Estás seguro de que deseas eliminar esta tarea?',
                    'method' => 'post',
                ],
            ]) ?>
            <?= Html::a('<i class="fas fa-arrow-left mr-1"></i> Volver', ['index'], ['class' => 'btn btn-ghost btn-sm']) ?>
        </div>
    </div>

    <!-- Main Grid: Left (Time Tracking + Checklist + Comments) | Right (Meta & Details) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left Column (8 cols) -->
        <div class="lg:col-span-8 space-y-6">

            <!-- 1. HUB DE CONTROL Y MEDICIÓN DE TIEMPO -->
            <div class="card bg-base-100 shadow-xl border <?= $isRunning ? 'border-warning shadow-warning/10' : 'border-base-200' ?>">
                <div class="card-body p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-base-200 pb-3 mb-4 gap-2">
                        <h2 class="card-title text-primary text-lg flex items-center gap-2">
                            <i class="fas fa-stopwatch"></i> Medición y Control de Tiempo
                        </h2>
                        <div class="text-xs text-base-content/60">
                            Total invertido: <b id="total-time-display" class="font-mono text-sm text-primary"><?= $model->getFormattedTotalTime() ?></b>
                            <?php if ($model->estimated_minutes > 0): ?>
                                / Estimado: <span class="font-mono"><?= TodoTimeLogs::formatSeconds($model->estimated_minutes * 60) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Cronómetro Interactivo en Vivo -->
                    <div class="bg-base-200/50 rounded-2xl p-6 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="text-center md:text-left">
                            <div class="text-xs text-base-content/50 uppercase font-bold tracking-wider mb-1">
                                <?= $isRunning ? 'Cronómetro en Marcha' : 'Cronómetro de Sesión' ?>
                            </div>
                            <div id="stopwatch-display" class="font-mono text-4xl md:text-5xl font-extrabold <?= $isRunning ? 'text-warning animate-pulse' : 'text-base-content/80' ?>">
                                00:00:00
                            </div>
                            <div id="stopwatch-status-text" class="text-xs text-base-content/50 mt-1">
                                <?= $isRunning ? 'Iniciado a las ' . date('H:i:s', $startTimeStamp) : 'Listo para iniciar cuando comiences a trabajar en la tarea.' ?>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Iniciar Cronómetro -->
                            <button id="btn-start-timer" type="button" onclick="startTaskTimer()" class="btn btn-success text-white btn-md shadow-md <?= $isRunning ? 'hidden' : '' ?>">
                                <i class="fas fa-play mr-2"></i> Iniciar Cronómetro
                            </button>

                            <!-- Detener Cronómetro -->
                            <button id="btn-stop-timer" type="button" onclick="stopTaskTimer()" class="btn btn-error text-white btn-md shadow-md <?= !$isRunning ? 'hidden' : '' ?>">
                                <i class="fas fa-stop mr-2"></i> Detener y Guardar
                            </button>

                            <!-- Botón Registrar Manual -->
                            <button type="button" onclick="document.getElementById('modal-manual-time').showModal()" class="btn btn-outline btn-md">
                                <i class="fas fa-plus mr-1"></i> Carga Manual
                            </button>
                        </div>
                    </div>

                    <!-- Bitácora de Sesiones de Tiempo Realizadas -->
                    <div class="mt-6">
                        <h3 class="text-sm font-bold text-base-content/70 uppercase tracking-wider mb-3">
                            <i class="fas fa-history mr-1"></i> Sesiones de Trabajo Registradas (<?= count($model->timeLogs) ?>)
                        </h3>

                        <?php $timeLogs = $model->timeLogs; ?>
                        <?php if (empty($timeLogs)): ?>
                            <p class="text-xs text-base-content/40 italic py-2">No se han registrado sesiones de tiempo aún en esta tarea.</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="table table-zebra table-compact w-full text-xs">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Usuario</th>
                                            <th>Duración</th>
                                            <th>Detalle de Trabajo</th>
                                            <th class="text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($timeLogs as $log): ?>
                                            <tr>
                                                <td class="font-mono"><?= date('d/m/Y H:i', strtotime($log->start_time)) ?></td>
                                                <td><?= Html::encode($log->user ? $log->user->username : 'N/A') ?></td>
                                                <td class="font-mono font-bold text-primary">
                                                    <?php if ($log->is_running): ?>
                                                        <span class="badge badge-warning badge-xs">En curso...</span>
                                                    <?php else: ?>
                                                        <?= $log->getFormattedDuration() ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="max-w-xs truncate text-base-content/80">
                                                    <?= Html::encode($log->description ?: '(Sin descripción)') ?>
                                                </td>
                                                <td class="text-right">
                                                    <?php if (!$log->is_running): ?>
                                                        <?= Html::a('<i class="fas fa-times text-error"></i>', ['delete-time-log', 'id' => $log->id], [
                                                            'class' => 'btn btn-ghost btn-xs btn-circle',
                                                            'title' => 'Eliminar registro',
                                                            'data' => [
                                                                'confirm' => '¿Eliminar este registro de tiempo?',
                                                                'method' => 'post',
                                                            ],
                                                        ]) ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 2. DESCRIPCIÓN DE LA TAREA -->
            <?php if (!empty($model->description)): ?>
                <div class="card bg-base-100 shadow-xl border border-base-200">
                    <div class="card-body p-6">
                        <h2 class="card-title text-primary text-lg border-b border-base-200 pb-3 mb-3">
                            <i class="fas fa-align-left mr-1"></i> Descripción y Requerimientos
                        </h2>
                        <div class="text-sm text-base-content/80 leading-relaxed whitespace-pre-line">
                            <?= Html::encode($model->description) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 3. SUBTAREAS / CHECKLIST INTERACTIVA -->
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-base-200 pb-3 mb-4 gap-2">
                        <div>
                            <h2 class="card-title text-primary text-lg flex items-center gap-2">
                                <i class="fas fa-list-check"></i> Subtareas y Checklist
                            </h2>
                            <p class="text-xs text-base-content/60">Marca los hitos para dar seguimiento al avance de la tarea.</p>
                        </div>
                        <div class="text-right">
                            <span id="checklist-pct-badge" class="badge badge-primary badge-sm text-white font-bold">
                                <?= $pct ?>% Completado
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <progress id="checklist-progress-bar" class="progress progress-primary w-full h-2 mb-4" value="<?= $pct ?>" max="100"></progress>

                    <!-- Checklist Items List -->
                    <div id="checklist-items-container" class="space-y-2">
                        <?php foreach ($model->checklistItems as $item): ?>
                            <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-base-200/50 transition-colors border border-base-200" id="checklist-item-<?= $item->id ?>">
                                <label class="flex items-center gap-3 cursor-pointer flex-1 min-w-0">
                                    <input type="checkbox" onchange="toggleChecklistItem(<?= $item->id ?>)" class="checkbox checkbox-primary checkbox-sm" <?= $item->is_completed ? 'checked' : '' ?>>
                                    <span id="checklist-text-<?= $item->id ?>" class="text-sm text-base-content/90 truncate <?= $item->is_completed ? 'line-through opacity-50' : '' ?>">
                                        <?= Html::encode($item->title) ?>
                                    </span>
                                </label>
                                <button type="button" onclick="deleteChecklistItem(<?= $item->id ?>)" class="btn btn-ghost btn-xs btn-circle text-error ml-2" title="Eliminar subtarea">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Inline Add Subtask Input -->
                    <form onsubmit="addChecklistItem(event)" class="mt-4 flex gap-2">
                        <input id="input-new-subtask" type="text" placeholder="Escribe una nueva subtarea y presiona Enter..." class="input input-bordered input-sm flex-1 focus:input-primary text-sm" required>
                        <button type="submit" class="btn btn-sm btn-primary text-white">
                            <i class="fas fa-plus mr-1"></i> Agregar
                        </button>
                    </form>
                </div>
            </div>

            <!-- 4. SEGUIMIENTO CONTINUO Y COMENTARIOS / BITÁCORA -->
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-6">
                    <h2 class="card-title text-primary text-lg border-b border-base-200 pb-3 mb-4 flex items-center gap-2">
                        <i class="fas fa-comments"></i> Muro de Seguimiento y Notas de Avance
                    </h2>

                    <!-- Formulario de nuevo comentario / nota -->
                    <form method="post" action="<?= Url::to(['add-comment', 'id' => $model->id]) ?>" class="mb-6 space-y-3">
                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                        <textarea name="comment" rows="3" class="textarea textarea-bordered w-full text-sm focus:textarea-primary" placeholder="Escribe una actualización de estado, acuerdo con el cliente o notas sobre el trabajo realizado..." required></textarea>
                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-sm btn-primary text-white">
                                <i class="fas fa-paper-plane mr-1"></i> Publicar Nota de Seguimiento
                            </button>
                        </div>
                    </form>

                    <!-- Lista de Comentarios / Historial -->
                    <?php $comments = $model->comments; ?>
                    <?php if (empty($comments)): ?>
                        <div class="text-center py-6 text-base-content/40 text-xs italic">
                            No hay notas de seguimiento registradas todavía. Publica una nota arriba para llevar el historial.
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($comments as $c): ?>
                                <div class="flex items-start gap-3 p-4 rounded-xl bg-base-200/40 border border-base-200">
                                    <div class="avatar placeholder">
                                        <div class="bg-primary/20 text-primary rounded-full w-8 h-8 font-bold text-xs flex items-center justify-center">
                                            <?= strtoupper(substr($c->user ? $c->user->username : 'U', 0, 2)) ?>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <span class="font-bold text-xs text-base-content/85">
                                                <?= Html::encode($c->user ? $c->user->username : 'Usuario') ?>
                                            </span>
                                            <span class="text-[10px] text-base-content/50">
                                                <?= date('d/m/Y H:i', strtotime($c->created_at)) ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-base-content/80 whitespace-pre-line leading-relaxed">
                                            <?= Html::encode($c->comment) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Right Column (4 cols): Meta, Reminders & Customer -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Card: Planificación y Fechas -->
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-6 space-y-4">
                    <h2 class="card-title text-primary text-base border-b border-base-200 pb-2">
                        <i class="fas fa-calendar-check mr-2"></i> Planificación y Plazos
                    </h2>

                    <!-- Fecha Límite -->
                    <div>
                        <span class="text-xs text-base-content/50 uppercase font-bold tracking-wider block">Fecha Límite (Entrega)</span>
                        <?php if ($model->due_date): ?>
                            <div class="text-sm font-semibold mt-0.5 <?= $isOverdue ? 'text-error flex items-center gap-1 font-bold' : 'text-base-content/90' ?>">
                                <i class="fas fa-clock text-xs"></i>
                                <?= date('d/m/Y H:i', strtotime($model->due_date)) ?>
                                <?php if ($isOverdue): ?>
                                    <span class="badge badge-error badge-xs text-white">¡Vencida!</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-xs text-base-content/40 italic">Sin fecha límite asignada</span>
                        <?php endif; ?>
                    </div>

                    <!-- Recordatorio Notificación -->
                    <div>
                        <span class="text-xs text-base-content/50 uppercase font-bold tracking-wider block">Recordatorio por Notificación</span>
                        <?php if ($model->reminder_at): ?>
                            <div class="text-sm font-semibold mt-0.5 text-warning flex items-center gap-1.5">
                                <i class="fas fa-bell"></i>
                                <?= date('d/m/Y H:i', strtotime($model->reminder_at)) ?>
                                <?php if ($model->reminder_sent): ?>
                                    <span class="badge badge-success badge-xs text-white">Enviado</span>
                                <?php else: ?>
                                    <span class="badge badge-warning badge-xs">Pendiente</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-[11px] text-base-content/50 block mt-1">
                                Se enviará una notificación en el panel al responsable cuando llegue la fecha.
                            </span>
                        <?php else: ?>
                            <span class="text-xs text-base-content/40 italic">No hay recordatorio programado</span>
                        <?php endif; ?>
                    </div>

                    <!-- Tiempo Estimado -->
                    <div>
                        <span class="text-xs text-base-content/50 uppercase font-bold tracking-wider block">Tiempo Estimado</span>
                        <div class="text-sm font-semibold text-base-content/80 mt-0.5 font-mono">
                            <?= $model->estimated_minutes > 0 ? TodoTimeLogs::formatSeconds($model->estimated_minutes * 60) : 'Sin estimar' ?>
                        </div>
                    </div>

                    <!-- Responsable -->
                    <div>
                        <span class="text-xs text-base-content/50 uppercase font-bold tracking-wider block">Responsable Asignado</span>
                        <div class="text-sm font-semibold text-base-content/80 mt-0.5 flex items-center gap-2">
                            <?php if ($model->assignedToUser): ?>
                                <div class="avatar placeholder">
                                    <div class="bg-primary text-primary-content rounded-full w-6 h-6 text-[10px] font-bold flex items-center justify-center">
                                        <?= strtoupper(substr($model->assignedToUser->username, 0, 1)) ?>
                                    </div>
                                </div>
                                <span><?= Html::encode($model->assignedToUser->username) ?></span>
                            <?php else: ?>
                                <span class="text-base-content/40 italic">Sin asignar</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Creado Por -->
                    <div class="pt-2 border-t border-base-200 text-xs text-base-content/50 space-y-1">
                        <div>Creada por: <b><?= Html::encode($model->createdByUser ? $model->createdByUser->username : 'N/A') ?></b></div>
                        <div>Fecha: <?= date('d/m/Y H:i', strtotime($model->created_at)) ?></div>
                        <?php if ($model->completed_at): ?>
                            <div class="text-success font-semibold">Completada el: <?= date('d/m/Y H:i', strtotime($model->completed_at)) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Card: Cliente Vinculado (si aplica) -->
            <?php if ($model->customer): ?>
                <div class="card bg-base-100 shadow-xl border border-primary/20">
                    <div class="card-body p-6 space-y-3">
                        <h2 class="card-title text-primary text-base border-b border-base-200 pb-2">
                            <i class="fas fa-building mr-2"></i> Cliente Vinculado
                        </h2>

                        <div>
                            <div class="font-bold text-base text-base-content/90">
                                <?= Html::encode($model->customer->trade_name ?: $model->customer->business_name) ?>
                            </div>
                            <div class="text-xs text-base-content/50">
                                <?= Html::encode($model->customer->document_type) ?>: <?= Html::encode($model->customer->document_number) ?>
                            </div>
                        </div>

                        <?php if ($model->customer->email): ?>
                            <div class="text-xs flex items-center gap-1.5 text-base-content/70">
                                <i class="fas fa-envelope text-primary"></i>
                                <a href="mailto:<?= Html::encode($model->customer->email) ?>" class="link link-hover">
                                    <?= Html::encode($model->customer->email) ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($model->customer->primary_phone): ?>
                            <div class="text-xs flex items-center gap-1.5 text-base-content/70">
                                <i class="fas fa-phone text-success"></i>
                                <?= Html::encode($model->customer->primary_phone) ?>
                            </div>
                        <?php endif; ?>

                        <div class="pt-2">
                            <?= Html::a('<i class="fas fa-external-link-alt mr-1"></i> Ver Ficha del Cliente', ['customers/view', 'id' => $model->customer_id], ['class' => 'btn btn-outline btn-primary btn-xs btn-block']) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </div>

</div>

<!-- MODAL: REGISTRO MANUAL DE TIEMPO -->
<dialog id="modal-manual-time" class="modal">
    <div class="modal-box">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
        </form>
        <h3 class="font-bold text-lg text-primary flex items-center gap-2 mb-4">
            <i class="fas fa-history"></i> Carga Manual de Tiempo
        </h3>
        <form method="post" action="<?= Url::to(['log-time', 'id' => $model->id]) ?>" class="space-y-4">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">

            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label font-semibold text-xs">Horas:</label>
                    <input type="number" name="hours" min="0" max="24" value="0" class="input input-bordered w-full focus:input-primary">
                </div>
                <div class="form-control">
                    <label class="label font-semibold text-xs">Minutos:</label>
                    <input type="number" name="minutes" min="0" max="59" value="30" class="input input-bordered w-full focus:input-primary" required>
                </div>
            </div>

            <div class="form-control">
                <label class="label font-semibold text-xs">Detalle del trabajo realizado (Opcional):</label>
                <textarea name="description" rows="3" class="textarea textarea-bordered w-full text-sm focus:textarea-primary" placeholder="Ej: Configuración de DNS, revisión de registros MX y pruebas de entrega..."></textarea>
            </div>

            <div class="modal-action">
                <button type="button" onclick="document.getElementById('modal-manual-time').close()" class="btn btn-ghost">Cancelar</button>
                <button type="submit" class="btn btn-primary text-white">Guardar Tiempo</button>
            </div>
        </form>
    </div>
</dialog>

<!-- SCRIPT JS PARA EL CRONÓMETRO Y ACCIONES EN TIEMPO REAL -->
<script>
let isRunning = <?= $isRunning ? 'true' : 'false' ?>;
let startTimestamp = <?= $startTimeStamp ? $startTimeStamp : 'null' ?>;
let timerInterval = null;

function formatSecondsToDigital(sec) {
    sec = Math.max(0, Math.floor(sec));
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;
    return (h < 10 ? '0' + h : h) + ':' + (m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s);
}

function updateStopwatch() {
    if (isRunning && startTimestamp) {
        const now = Math.floor(Date.now() / 1000);
        const elapsed = now - startTimestamp;
        const displayEl = document.getElementById('stopwatch-display');
        if (displayEl) {
            displayEl.textContent = formatSecondsToDigital(elapsed);
        }
    }
}

if (isRunning && startTimestamp) {
    updateStopwatch();
    timerInterval = setInterval(updateStopwatch, 1000);
}

function startTaskTimer() {
    const formData = new FormData();
    formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

    fetch('/todos/start-timer?id=<?= $model->id ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            isRunning = true;
            startTimestamp = data.startTimestamp;
            document.getElementById('btn-start-timer').classList.add('hidden');
            document.getElementById('btn-stop-timer').classList.remove('hidden');
            document.getElementById('stopwatch-display').classList.add('text-warning', 'animate-pulse');
            document.getElementById('stopwatch-status-text').textContent = 'Cronómetro en marcha...';
            updateStopwatch();
            timerInterval = setInterval(updateStopwatch, 1000);
        } else {
            alert(data.message || 'Error al iniciar cronómetro.');
        }
    })
    .catch(err => {
        alert('Error de conexión al iniciar el cronómetro.');
    });
}

function stopTaskTimer() {
    const desc = prompt("Ingresa brevemente qué se realizó en esta sesión (Opcional):", "");
    if (desc === null) return; // Cancelado

    const formData = new FormData();
    formData.append('description', desc);
    formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

    fetch('/todos/stop-timer?id=<?= $model->id ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            isRunning = false;
            clearInterval(timerInterval);
            document.getElementById('stopwatch-display').textContent = '00:00:00';
            document.getElementById('stopwatch-display').classList.remove('text-warning', 'animate-pulse');
            document.getElementById('btn-stop-timer').classList.add('hidden');
            document.getElementById('btn-start-timer').classList.remove('hidden');
            document.getElementById('total-time-display').textContent = data.totalTime;
            document.getElementById('stopwatch-status-text').textContent = 'Sesión guardada (' + data.duration + ').';
            location.reload(); // Recarga para actualizar historial de sesiones
        } else {
            alert(data.message || 'Error al detener el cronómetro.');
        }
    })
    .catch(err => {
        alert('Error de conexión al detener el cronómetro.');
    });
}

function changeTaskStatus(newStatus) {
    const formData = new FormData();
    formData.append('status', newStatus);
    formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

    fetch('/todos/toggle-status?id=<?= $model->id ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('task-status-badge');
            badge.className = 'badge ' + data.statusBadge + ' badge-lg shadow-sm';
            badge.textContent = data.statusLabel;
            location.reload();
        }
    })
    .catch(err => console.error(err));
}

function toggleChecklistItem(itemId) {
    const formData = new FormData();
    formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

    fetch('/todos/toggle-checklist?id=' + itemId, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const textEl = document.getElementById('checklist-text-' + itemId);
            if (data.is_completed) {
                textEl.classList.add('line-through', 'opacity-50');
            } else {
                textEl.classList.remove('line-through', 'opacity-50');
            }

            document.getElementById('checklist-progress-bar').value = data.percentage;
            document.getElementById('checklist-pct-badge').textContent = data.percentage + '% Completado';
        }
    });
}

function addChecklistItem(e) {
    e.preventDefault();
    const input = document.getElementById('input-new-subtask');
    const title = input.value.trim();
    if (!title) return;

    const formData = new FormData();
    formData.append('title', title);
    formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

    fetch('/todos/add-checklist?id=<?= $model->id ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            location.reload(); // Recarga limpia para mostrar el nuevo elemento
        }
    });
}

function deleteChecklistItem(itemId) {
    if (!confirm('¿Eliminar esta subtarea?')) return;

    const formData = new FormData();
    formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

    fetch('/todos/delete-checklist?id=' + itemId, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const el = document.getElementById('checklist-item-' + itemId);
            if (el) el.remove();
            document.getElementById('checklist-progress-bar').value = data.percentage;
            document.getElementById('checklist-pct-badge').textContent = data.percentage + '% Completado';
        }
    });
}
</script>
