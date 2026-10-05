<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Todos;

/** @var yii\web\View $this */
/** @var app\models\Todos $task */

$pct = $task->getCompletionPercentage();
$progressColor = $pct >= 100 ? 'progress-success' : ($pct > 40 ? 'progress-primary' : 'progress-warning');
$isOverdue = $task->getIsOverdue();
$activeTimer = $task->getActiveTimer(Yii::$app->user->id);
?>

<div class="card bg-base-100 shadow-sm border <?= $activeTimer ? 'border-warning shadow-md' : 'border-base-200' ?> hover:shadow-md transition-shadow rounded-xl">
    <div class="card-body p-4 space-y-2.5">

        <!-- Top row: Priority & Reminder -->
        <div class="flex items-center justify-between">
            <span class="badge <?= $task->getPriorityBadgeClass() ?> badge-xs font-semibold py-1 px-2">
                <?= Html::encode(Todos::optsPriority()[$task->priority] ?? $task->priority) ?>
            </span>

            <div class="flex items-center gap-1">
                <?php if ($task->reminder_at): ?>
                    <span class="text-xs text-warning tooltip tooltip-left" data-tip="Recordatorio: <?= date('d/m H:i', strtotime($task->reminder_at)) ?>">
                        <i class="fas fa-bell"></i>
                    </span>
                <?php endif; ?>

                <?php if ($activeTimer): ?>
                    <span class="badge badge-warning badge-xs font-bold animate-pulse motion-reduce:animate-none">
                        <i class="fas fa-stopwatch mr-1"></i> Cronómetro activo
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Title -->
        <div>
            <a href="<?= Url::to(['view', 'id' => $task->id]) ?>" class="font-bold text-sm text-base-content/90 hover:text-primary transition-colors line-clamp-2">
                <?= Html::encode($task->title) ?>
            </a>
            <?php if ($task->customer): ?>
                <div class="text-xs text-base-content/60 mt-1 flex items-center gap-1 truncate">
                    <i class="fas fa-building text-xs"></i>
                    <?= Html::encode($task->customer->trade_name ?: $task->customer->business_name) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Checklist Progress Bar (if items exist) -->
        <?php $itemsCount = count($task->checklistItems); ?>
        <?php if ($itemsCount > 0): ?>
            <div>
                <div class="flex justify-between text-xs text-base-content/60 font-semibold mb-1">
                    <span>Subtareas</span>
                    <span><?= $pct ?>%</span>
                </div>
                <progress class="progress <?= $progressColor ?> w-full h-1.5" value="<?= $pct ?>" max="100" aria-label="Avance de subtareas: <?= $pct ?>%" aria-valuenow="<?= $pct ?>"></progress>
            </div>
        <?php endif; ?>

        <!-- Bottom row: Time Spent, Due Date, Responsible -->
        <div class="pt-2 border-t border-base-200 flex items-center justify-between text-xs text-base-content/60">
            <!-- Time spent -->
            <div class="font-mono flex items-center gap-1 font-semibold text-primary">
                <i class="fas fa-stopwatch text-xs"></i>
                <?= $task->getFormattedTotalTime() ?>
            </div>

            <!-- Due date -->
            <?php if ($task->due_date): ?>
                <div class="<?= $isOverdue ? 'text-error font-bold flex items-center gap-1' : 'text-base-content/50' ?>" title="Vencimiento: <?= date('d/m/Y H:i', strtotime($task->due_date)) ?>">
                    <?php if ($isOverdue): ?>
                        <i class="fas fa-exclamation-circle text-xs"></i>
                    <?php endif; ?>
                    <?= date('d/m', strtotime($task->due_date)) ?>
                </div>
            <?php endif; ?>

            <!-- Assigned User -->
            <?php if ($task->assignedToUser): ?>
                <div class="avatar placeholder tooltip tooltip-left" data-tip="Asignado a: <?= Html::encode($task->assignedToUser->username) ?>">
                    <div class="bg-primary/20 text-primary rounded-full w-6 h-6 sm:w-5 sm:h-5 text-xs font-bold flex items-center justify-center">
                        <?= strtoupper(substr($task->assignedToUser->username, 0, 1)) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
