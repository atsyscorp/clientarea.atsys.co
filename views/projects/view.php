<?php

use yii\helpers\Html;
use app\models\Projects;

/* @var $this yii\web\View */
/* @var $model app\models\Projects */
/* @var $workOrders app\models\WorkOrders[] */
/* @var $workOrdersCount int */
/* @var $workOrdersCostCop float */

$this->title = $model->name;
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
?>

<div class="projects-view space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-base-100 p-6 rounded-box shadow-xl border border-base-200">
        <div>
            <div class="flex items-center gap-3">
                <span class="badge badge-primary text-white font-mono"><?= Html::encode($model->code) ?></span>
                <?php if ($model->is_default): ?>
                    <span class="badge badge-info font-semibold">Proyecto Predeterminado</span>
                <?php endif; ?>
                <?php if ($model->status == Projects::STATUS_ACTIVE): ?>
                    <span class="badge badge-success">Activo</span>
                <?php else: ?>
                    <span class="badge badge-ghost">Inactivo</span>
                <?php endif; ?>
            </div>
            <h1 class="text-3xl font-bold text-base-content mt-2"><?= Html::encode($model->name) ?></h1>
            <p class="text-sm text-base-content/70 mt-1">Cliente: <strong class="text-primary"><?= Html::encode($model->customer->business_name) ?></strong></p>
        </div>

        <?php if ($isAdmin): ?>
            <div class="flex gap-2">
                <?= Html::a('Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-warning shadow']) ?>
                <?php if (!$model->is_default && $workOrdersCount === 0): ?>
                    <?= Html::a('Eliminar', ['delete', 'id' => $model->id], [
                        'class' => 'btn btn-error shadow',
                        'data' => [
                            'confirm' => '¿Estás seguro de que deseas eliminar este proyecto?',
                            'method' => 'post',
                        ],
                    ]) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Metrics Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="stat bg-base-100 shadow-md rounded-box border border-base-200">
            <div class="stat-title">Órdenes de Trabajo</div>
            <div class="stat-value text-primary"><?= number_format($workOrdersCount) ?></div>
            <div class="stat-desc">Asociadas a este proyecto</div>
        </div>

        <div class="stat bg-base-100 shadow-md rounded-box border border-base-200">
            <div class="stat-title">Inversión Total (COP)</div>
            <div class="stat-value text-success">$<?= number_format($workOrdersCostCop, 2) ?></div>
            <div class="stat-desc">Acumulado de OTs</div>
        </div>

        <div class="stat bg-base-100 shadow-md rounded-box border border-base-200">
            <div class="stat-title">NIT / Documento Filial</div>
            <div class="stat-value text-xl font-semibold"><?= Html::encode($model->document_number ?: $model->customer->document_number) ?></div>
            <div class="stat-desc"><?= $model->document_number ? 'Específico de la Filial' : 'Heredado del Cliente' ?></div>
        </div>
    </div>

    <!-- Company Details Card -->
    <div class="card bg-base-100 shadow-xl border border-base-200 p-6">
        <h2 class="text-xl font-bold text-primary mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
            </svg>
            Información Empresarial / Filial
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div>
                <span class="text-base-content/60 block">Razón Social:</span>
                <span class="font-semibold text-base"><?= Html::encode($model->business_name ?: $model->customer->business_name) ?></span>
            </div>

            <div>
                <span class="text-base-content/60 block">Identificación Fiscal / NIT:</span>
                <span class="font-semibold text-base"><?= Html::encode($model->document_number ?: $model->customer->document_number) ?></span>
            </div>

            <div class="md:col-span-2">
                <span class="text-base-content/60 block">Dirección Sede / Filial:</span>
                <span class="font-semibold text-base"><?= Html::encode($model->address ?: ($model->customer->address ?: 'No especificada')) ?></span>
            </div>

            <?php if (!empty($model->notes)): ?>
                <div class="md:col-span-2">
                    <span class="text-base-content/60 block">Notas u Observaciones:</span>
                    <p class="mt-1 p-3 bg-base-200/50 rounded-lg text-base-content"><?= nl2br(Html::encode($model->notes)) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Work Orders List for this project -->
    <div class="card bg-base-100 shadow-xl border border-base-200 p-6 space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold text-primary flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Órdenes de Trabajo del Proyecto
            </h2>
            <?php if ($isAdmin): ?>
                <?= Html::a('+ Nueva OT para este proyecto', ['work-orders/create', 'customer_id' => $model->customer_id, 'project_id' => $model->id], ['class' => 'btn btn-sm btn-primary text-white']) ?>
            <?php endif; ?>
        </div>

        <?php if (empty($workOrders)): ?>
            <p class="text-base-content/60 italic text-center py-6">No hay órdenes de trabajo asociadas a este proyecto aún.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Título</th>
                            <th>Inversión (COP)</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workOrders as $wo): ?>
                            <tr>
                                <td class="font-bold"><?= Html::a($wo->code, ['work-orders/view', 'id' => $wo->id], ['class' => 'link link-primary no-underline']) ?></td>
                                <td class="font-semibold"><?= Html::encode($wo->title) ?></td>
                                <td>$<?= number_format($wo->total_cost, 2) ?></td>
                                <td><?= $wo->getStatusHtml() ?></td>
                                <td><?= date('d/m/Y', strtotime($wo->created_at)) ?></td>
                                <td>
                                    <?= Html::a('Ver OT', ['work-orders/view', 'id' => $wo->id], ['class' => 'btn btn-xs btn-ghost text-info']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>
