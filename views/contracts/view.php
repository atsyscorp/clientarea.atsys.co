<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
/* @var $newTask app\models\ContractTasks */
/* @var $newDoc app\models\ContractDocuments */

$this->title = 'Contrato: ' . $model->code . ' - ' . $model->title;
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
$percent = floatval($model->progress_percentage);
$colorClass = $model->getProgressColorClass();
?>

<div class="contracts-view space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-base-100 p-6 rounded-box shadow-lg border border-base-200">
        <div>
            <div class="flex items-center gap-3">
                <span class="font-mono text-2xl font-black text-primary"><?= Html::encode($model->code) ?></span>
                <?= $model->getStatusHtml() ?>
                <span class="badge badge-outline text-xs"><?= $model->progress_mode == 0 ? 'Avance Auto' : 'Avance Manual' ?></span>
            </div>
            <h1 class="text-2xl font-bold mt-1 text-base-content"><?= Html::encode($model->title) ?></h1>
            <p class="text-sm text-base-content/70 flex items-center gap-2 mt-1">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Cliente: <span class="font-bold text-base-content"><?= Html::encode($model->customer->business_name) ?></span>
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <?php if ($model->contract_file): ?>
                <a href="<?= Html::encode($model->contract_file) ?>" target="_blank" class="btn btn-success btn-sm shadow">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Descargar PDF
                </a>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                <?= Html::a('<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Recalcular Avance', ['recalculate-progress', 'id' => $model->id], ['class' => 'btn btn-outline btn-sm']) ?>
                <?= Html::a('<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"></path></svg> Crear Orden', ['/work-orders/create', 'contract_id' => $model->id, 'customer_id' => $model->customer_id], ['class' => 'btn btn-primary text-white btn-sm shadow']) ?>
                <?= Html::a('<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-warning btn-sm']) ?>
            <?php endif; ?>

            <?= Html::a('← Volver', ['index'], ['class' => 'btn btn-ghost btn-sm']) ?>
        </div>
    </div>

    <!-- KPI Stats Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Stat: Porcentaje de Avance -->
        <div class="bg-base-100 p-5 rounded-box shadow-md border border-base-200 flex flex-col justify-between">
            <div class="flex justify-between items-center text-xs text-base-content/70 uppercase font-bold tracking-wider">
                <span>Porcentaje de Avance</span>
                <span class="badge badge-sm badge-ghost font-mono"><?= number_format($percent, 1) ?>%</span>
            </div>
            <div class="my-3">
                <div class="text-3xl font-black text-primary mb-2 flex items-baseline gap-1">
                    <span><?= number_format($percent, 1) ?>%</span>
                    <span class="text-xs font-normal text-base-content/60">completado</span>
                </div>
                <progress class="progress <?= $colorClass ?> w-full h-3" value="<?= $percent ?>" max="100"></progress>
            </div>
            <div class="text-xs text-base-content/60 flex justify-between">
                <span>Cálculo: <?= $model->progress_mode == 0 ? 'Automático' : 'Manual' ?></span>
                <span><?= count($model->tasks) ?> tareas</span>
            </div>
        </div>

        <!-- Stat: Monto Total -->
        <div class="bg-base-100 p-5 rounded-box shadow-md border border-base-200 flex flex-col justify-between">
            <div class="text-xs text-base-content/70 uppercase font-bold tracking-wider">
                Monto del Contrato
            </div>
            <div class="my-2">
                <div class="text-3xl font-black text-success font-mono">
                    <?= $model->currency ?> $<?= number_format($model->total_amount, 2) ?>
                </div>
            </div>
            <div class="text-xs text-base-content/60">
                Presupuesto contratado acumulado
            </div>
        </div>

        <!-- Stat: Vigencia -->
        <div class="bg-base-100 p-5 rounded-box shadow-md border border-base-200 flex flex-col justify-between">
            <div class="text-xs text-base-content/70 uppercase font-bold tracking-wider">
                Vigencia del Contrato
            </div>
            <div class="my-2 space-y-1">
                <div class="text-sm font-semibold flex items-center justify-between">
                    <span class="text-base-content/60">Inicio:</span>
                    <span class="font-mono"><?= $model->start_date ? date('d/m/Y', strtotime($model->start_date)) : 'N/A' ?></span>
                </div>
                <div class="text-sm font-semibold flex items-center justify-between">
                    <span class="text-base-content/60">Fin:</span>
                    <span class="font-mono text-warning"><?= $model->end_date ? date('d/m/Y', strtotime($model->end_date)) : 'N/A' ?></span>
                </div>
            </div>
            <div class="text-xs text-base-content/60">
                Periodo contractual activo
            </div>
        </div>

        <!-- Stat: Órdenes de Trabajo -->
        <div class="bg-base-100 p-5 rounded-box shadow-md border border-base-200 flex flex-col justify-between">
            <div class="text-xs text-base-content/70 uppercase font-bold tracking-wider">
                Órdenes de Trabajo
            </div>
            <div class="my-2">
                <div class="text-3xl font-black text-info font-mono">
                    <?= count($model->workOrders) ?>
                </div>
            </div>
            <div class="text-xs text-base-content/60">
                Proyectos / OTs vinculadas
            </div>
        </div>
    </div>

    <!-- DEDICATED CARD: DOCUMENTO PRINCIPAL CARGADO -->
    <div class="bg-base-100 rounded-box shadow-md border border-base-200 p-5">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-error/10 text-error flex items-center justify-center shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-base text-base-content">Documento Principal del Contrato (PDF Firmado)</h3>
                    <?php if ($model->contract_file): ?>
                        <p class="text-xs text-success font-semibold flex items-center gap-1 mt-0.5">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Archivo original subido correctamente al sistema.
                        </p>
                    <?php else: ?>
                        <p class="text-xs text-base-content/60 mt-0.5">Aún no se ha adjuntado el documento en PDF del contrato firmado.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <?php if ($model->contract_file): ?>
                    <a href="<?= Html::encode($model->contract_file) ?>" target="_blank" class="btn btn-primary text-white btn-sm flex-1 sm:flex-none">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path></svg>
                        Abrir / Previsualizar Documento
                    </a>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                    <?= Html::a($model->contract_file ? 'Reemplazar PDF' : '+ Cargar PDF del Contrato', ['update', 'id' => $model->id], ['class' => 'btn btn-outline btn-sm flex-1 sm:flex-none']) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MAIN TABS AREA (JavaScript Controlled for Clean Display) -->
    <div class="bg-base-100 rounded-box shadow-xl border border-base-200 p-6">
        
        <!-- Tab Buttons Bar -->
        <div class="flex flex-wrap border-b border-base-300 gap-2 mb-6" id="contract-tab-buttons">
            <button type="button" onclick="switchContractTab('tab-info', this)" class="contract-tab-btn active px-5 py-3 font-bold border-b-2 border-primary text-primary transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Información General
            </button>
            <button type="button" onclick="switchContractTab('tab-orders', this)" class="contract-tab-btn px-5 py-3 font-bold border-b-2 border-transparent text-base-content/70 hover:text-primary transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                Órdenes de Trabajo (<?= count($model->workOrders) ?>)
            </button>
            <button type="button" onclick="switchContractTab('tab-tasks', this)" class="contract-tab-btn px-5 py-3 font-bold border-b-2 border-transparent text-base-content/70 hover:text-primary transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Hitos y Tareas (<?= count($model->tasks) ?>)
            </button>
            <button type="button" onclick="switchContractTab('tab-docs', this)" class="contract-tab-btn px-5 py-3 font-bold border-b-2 border-transparent text-base-content/70 hover:text-primary transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                Documentos Anexos (<?= count($model->documents) ?>)
            </button>
        </div>

        <!-- TAB 1: Información General -->
        <div id="tab-info" class="contract-tab-panel space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Detalles del Contrato -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold border-b pb-2 text-primary flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3 3 0 00-3 3h6a3 3 0 00-3-3z"></path></svg>
                        Datos del Contrato
                    </h3>

                    <table class="table table-compact w-full border border-base-200 rounded-lg">
                        <tbody>
                            <tr>
                                <th class="w-1/3 bg-base-200/50">Código Contrato:</th>
                                <td class="font-mono font-bold text-primary"><?= Html::encode($model->code) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-base-200/50">Cliente:</th>
                                <td>
                                    <div class="font-bold"><?= Html::encode($model->customer->business_name) ?></div>
                                    <div class="text-xs text-base-content/60">NIT / Doc: <?= Html::encode($model->customer->document_number) ?></div>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-base-200/50">Estado:</th>
                                <td><?= $model->getStatusHtml() ?></td>
                            </tr>
                            <tr>
                                <th class="bg-base-200/50">Monto Total:</th>
                                <td class="font-bold text-success font-mono text-base"><?= $model->currency ?> $<?= number_format($model->total_amount, 2) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-base-200/50">Fecha Inicio:</th>
                                <td><?= $model->start_date ? date('d/m/Y', strtotime($model->start_date)) : '-' ?></td>
                            </tr>
                            <tr>
                                <th class="bg-base-200/50">Fecha Vencimiento:</th>
                                <td><?= $model->end_date ? date('d/m/Y', strtotime($model->end_date)) : '-' ?></td>
                            </tr>
                            <tr>
                                <th class="bg-base-200/50">Modo Avance:</th>
                                <td><span class="badge badge-ghost font-semibold"><?= $model->progress_mode == 0 ? 'Automático (vía OTs/Tareas)' : 'Manual' ?></span></td>
                            </tr>
                            <tr>
                                <th class="bg-base-200/50">Registrado el:</th>
                                <td><?= date('d/m/Y H:i', strtotime($model->created_at)) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Alcance y Descripción -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold border-b pb-2 text-primary flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Objeto, Alcance y Condiciones
                    </h3>
                    <div class="bg-base-200/40 p-4 rounded-xl border border-base-200 min-h-[220px] whitespace-pre-wrap text-sm text-base-content/90 leading-relaxed">
                        <?= $model->description ? Html::encode($model->description) : '<span class="text-base-content/50 italic">Sin descripción o cláusulas adicionales registradas.</span>' ?>
                    </div>
                </div>
            </div>

            <!-- PDF Embedded Preview (si hay contrato subido) -->
            <?php if ($model->contract_file): ?>
                <div class="mt-8 border-t border-base-200 pt-6">
                    <h3 class="text-lg font-bold mb-4 text-primary flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path></svg>
                        Previsualización del PDF del Contrato
                    </h3>
                    <div class="w-full h-[500px] border border-base-300 rounded-xl overflow-hidden shadow-inner bg-base-200/50">
                        <iframe src="<?= Html::encode($model->contract_file) ?>" class="w-full h-full" frameborder="0"></iframe>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 2: Órdenes de Trabajo -->
        <div id="tab-orders" class="contract-tab-panel hidden space-y-4">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-primary">Órdenes de Trabajo Asociadas al Contrato</h3>
                    <p class="text-xs text-base-content/70">Proyectos y órdenes de trabajo ejecutándose bajo este marco contractual.</p>
                </div>
                <?php if ($isAdmin): ?>
                    <?= Html::a('+ Nueva Orden de Trabajo', ['/work-orders/create', 'contract_id' => $model->id, 'customer_id' => $model->customer_id], ['class' => 'btn btn-primary text-white btn-sm shadow']) ?>
                <?php endif; ?>
            </div>

            <?php if (empty($model->workOrders)): ?>
                <div class="text-center py-12 bg-base-200/40 rounded-xl border border-base-200">
                    <svg class="w-12 h-12 mx-auto text-base-content/40 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <p class="text-base-content/70 font-semibold">No hay órdenes de trabajo vinculadas actualmente a este contrato.</p>
                    <?php if ($isAdmin): ?>
                        <div class="mt-4">
                            <?= Html::a('+ Crear Primera Orden de Trabajo', ['/work-orders/create', 'contract_id' => $model->id, 'customer_id' => $model->customer_id], ['class' => 'btn btn-primary text-white btn-sm']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto border border-base-200 rounded-xl">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr class="bg-base-200/60">
                                <th>Código OT</th>
                                <th>Título del Proyecto</th>
                                <th>% Avance</th>
                                <th>Inversión</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($model->workOrders as $wo): ?>
                                <tr>
                                    <td class="font-mono font-bold">
                                        <?= Html::a(Html::encode($wo->code), ['/work-orders/view', 'id' => $wo->id], ['class' => 'link link-primary']) ?>
                                    </td>
                                    <td class="font-medium"><?= Html::encode($wo->title) ?></td>
                                    <td>
                                        <div class="w-32">
                                            <div class="text-xs font-bold text-right mb-1"><?= number_format($wo->progress_percentage, 1) ?>%</div>
                                            <progress class="progress progress-primary w-full h-2" value="<?= floatval($wo->progress_percentage) ?>" max="100"></progress>
                                        </div>
                                    </td>
                                    <td class="font-mono font-semibold"><?= $wo->currency ?> $<?= number_format($wo->total_cost, 2) ?></td>
                                    <td><?= $wo->getStatusHtml() ?></td>
                                    <td>
                                        <?= Html::a('Ver OT', ['/work-orders/view', 'id' => $wo->id], ['class' => 'btn btn-ghost btn-xs text-info']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 3: Hitos y Tareas -->
        <div id="tab-tasks" class="contract-tab-panel hidden space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-primary">Desglose de Hitos y Tareas del Contrato</h3>
                    <p class="text-xs text-base-content/70">Asigne porcentajes de peso a cada hito para que el avance total del contrato se calcule automáticamente.</p>
                </div>
                <?php if ($isAdmin): ?>
                    <button type="button" onclick="openAddTaskModal()" class="btn btn-primary text-white btn-sm shadow">+ Agregar Hito / Tarea</button>
                <?php endif; ?>
            </div>

            <?php if (empty($model->tasks)): ?>
                <div class="text-center py-12 bg-base-200/40 rounded-xl border border-base-200">
                    <svg class="w-12 h-12 mx-auto text-base-content/40 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-base-content/70 font-semibold">No se han registrado tareas ni hitos para este contrato.</p>
                    <?php if ($isAdmin): ?>
                        <div class="mt-4">
                            <button type="button" onclick="openAddTaskModal()" class="btn btn-primary text-white btn-sm">+ Registrar Primer Hito</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto border border-base-200 rounded-xl">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr class="bg-base-200/60">
                                <th>#</th>
                                <th>Tarea / Hito</th>
                                <th>Peso (%)</th>
                                <th>% Cumplido</th>
                                <th>Fecha Límite</th>
                                <th>Estado</th>
                                <?php if ($isAdmin): ?><th>Acciones</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($model->tasks as $idx => $task): ?>
                                <tr>
                                    <td class="font-bold"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="font-bold text-base-content"><?= Html::encode($task->title) ?></div>
                                        <?php if ($task->description): ?>
                                            <div class="text-xs text-base-content/70 mt-0.5"><?= Html::encode($task->description) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-mono font-bold text-info"><?= number_format($task->weight_percentage, 1) ?>%</td>
                                    <td>
                                        <div class="w-32">
                                            <div class="text-xs font-bold mb-1"><?= number_format($task->progress_percentage, 1) ?>%</div>
                                            <progress class="progress progress-success w-full h-2" value="<?= floatval($task->progress_percentage) ?>" max="100"></progress>
                                        </div>
                                    </td>
                                    <td class="text-xs font-mono"><?= $task->due_date ? date('d/m/Y', strtotime($task->due_date)) : '-' ?></td>
                                    <td>
                                        <?php
                                        $st = [
                                            0 => ['Pendiente', 'badge-ghost'],
                                            1 => ['En Progreso', 'badge-warning font-bold'],
                                            2 => ['Completada', 'badge-success font-bold'],
                                        ];
                                        $s = $st[$task->status] ?? ['Desconocido', 'badge-ghost'];
                                        echo "<span class='badge {$s[1]}'>{$s[0]}</span>";
                                        ?>
                                    </td>
                                    <?php if ($isAdmin): ?>
                                        <td>
                                            <?= Html::a('Eliminar', ['delete-task', 'id' => $task->id], [
                                                'class' => 'btn btn-ghost btn-xs text-error',
                                                'data' => [
                                                    'confirm' => '¿Está seguro de eliminar este hito/tarea?',
                                                    'method' => 'post',
                                                ],
                                            ]) ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 4: Anexos y Documentos -->
        <div id="tab-docs" class="contract-tab-panel hidden space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-primary">Anexos y Documentos Adicionales</h3>
                    <p class="text-xs text-base-content/70">Actas de inicio, pólizas, adiciones o anexos técnicos del contrato.</p>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <div class="bg-base-200/50 p-5 rounded-xl border border-base-200">
                    <h4 class="font-bold mb-3 text-sm text-primary">Adjuntar documento(s) o anexo(s) al contrato</h4>
                    <?php $formDoc = ActiveForm::begin([
                        'action' => ['upload-document', 'id' => $model->id],
                        'options' => ['enctype' => 'multipart/form-data', 'id' => 'form-upload-docs'],
                    ]); ?>

                    <div id="doc-rows-container" class="space-y-3">
                        <div class="doc-row flex flex-col md:flex-row gap-3 items-end bg-base-100 p-3 rounded-lg border border-base-200">
                            <div class="form-control flex-1">
                                <label class="label"><span class="label-text font-bold text-xs">Título del Documento</span></label>
                                <input type="text" name="doc_titles[]" required class="input input-bordered input-sm w-full" placeholder="ej: Anexo Técnico 1 / Póliza de Cumplimiento" />
                            </div>
                            <div class="form-control flex-1">
                                <label class="label"><span class="label-text font-bold text-xs">Archivo</span></label>
                                <input type="file" name="docFiles[]" required class="file-input file-input-bordered file-input-sm w-full" />
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" onclick="removeDocRow(this)" class="btn btn-square btn-ghost btn-sm text-error hidden remove-doc-btn" title="Eliminar fila">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-center gap-3 mt-4 pt-3 border-t border-base-300">
                        <button type="button" onclick="addDocRow()" class="btn btn-outline btn-sm btn-primary">
                            + Agregar otro documento / anexo
                        </button>
                        <button type="submit" class="btn btn-primary text-white btn-sm px-6 font-bold shadow">
                            Subir Documento(s)
                        </button>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($model->documents)): ?>
                <div class="text-center py-10 text-base-content/60 bg-base-200/30 rounded-xl">
                    No hay documentos anexos cargados adicionales.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($model->documents as $doc): ?>
                        <div class="flex justify-between items-center bg-base-200/40 p-4 rounded-xl border border-base-300">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-info/10 text-info flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                </div>
                                <div>
                                    <div class="font-bold text-sm text-base-content"><?= Html::encode($doc->title) ?></div>
                                    <div class="text-xs text-base-content/60">Cargado: <?= date('d/m/Y H:i', strtotime($doc->uploaded_at)) ?></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="<?= Html::encode($doc->file_url) ?>" target="_blank" class="btn btn-sm btn-outline btn-info">Descargar</a>
                                <?php if ($isAdmin): ?>
                                    <?= Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>', ['delete-document', 'id' => $doc->id], [
                                        'class' => 'btn btn-square btn-ghost btn-xs text-error',
                                        'title' => 'Eliminar anexo',
                                        'data' => [
                                            'confirm' => '¿Está seguro de eliminar este documento anexo?',
                                            'method' => 'post',
                                        ],
                                    ]) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<!-- Modal para agregar Tarea/Hito -->
<?php if ($isAdmin): ?>
<dialog id="dialog-add-task" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg text-primary mb-4">Agregar Hito o Tarea al Contrato</h3>
    
    <?php $formTask = ActiveForm::begin([
        'action' => ['add-task', 'id' => $model->id],
    ]); ?>

    <?= $formTask->field($newTask, 'title')->textInput(['required' => true, 'class' => 'input input-bordered w-full', 'placeholder' => 'ej: Fase 1 - Entrega de Prototipo']) ?>
    
    <div class="grid grid-cols-2 gap-4 mt-3">
        <?= $formTask->field($newTask, 'weight_percentage')->textInput(['type' => 'number', 'step' => '0.1', 'class' => 'input input-bordered w-full'])->label('Peso en Contrato (%)') ?>
        <?= $formTask->field($newTask, 'progress_percentage')->textInput(['type' => 'number', 'step' => '0.1', 'class' => 'input input-bordered w-full'])->label('% de Avance Actual') ?>
    </div>

    <div class="grid grid-cols-2 gap-4 mt-3">
        <?= $formTask->field($newTask, 'status')->dropDownList([
            0 => 'Pendiente',
            1 => 'En Progreso',
            2 => 'Completada',
        ], ['class' => 'select select-bordered w-full']) ?>
        <?= $formTask->field($newTask, 'due_date')->input('date', ['class' => 'input input-bordered w-full']) ?>
    </div>

    <?= $formTask->field($newTask, 'description')->textarea(['rows' => 3, 'class' => 'textarea textarea-bordered w-full', 'placeholder' => 'Detalles o entregables esperados de este hito...']) ?>

    <div class="modal-action">
        <button type="button" onclick="closeAddTaskModal()" class="btn btn-ghost">Cancelar</button>
        <button type="submit" class="btn btn-primary text-white font-bold">Guardar Hito</button>
    </div>

    <?php ActiveForm::end(); ?>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button type="button" onclick="closeAddTaskModal()">close</button>
  </form>
</dialog>
<?php endif; ?>

<!-- Script para control limpio de Pestañas (Tabs), Modal y Réplica de Filas de Documentos -->
<script>
function switchContractTab(panelId, btnElement) {
    // Ocultar todos los paneles
    var panels = document.querySelectorAll('.contract-tab-panel');
    panels.forEach(function(panel) {
        panel.classList.add('hidden');
    });

    // Desactivar todos los botones
    var btns = document.querySelectorAll('.contract-tab-btn');
    btns.forEach(function(btn) {
        btn.classList.remove('border-primary', 'text-primary');
        btn.classList.add('border-transparent', 'text-base-content/70');
    });

    // Mostrar el panel seleccionado
    var targetPanel = document.getElementById(panelId);
    if (targetPanel) {
        targetPanel.classList.remove('hidden');
    }

    // Activar el botón presionado
    btnElement.classList.remove('border-transparent', 'text-base-content/70');
    btnElement.classList.add('border-primary', 'text-primary');
}

function openAddTaskModal() {
    var dialog = document.getElementById('dialog-add-task');
    if (dialog) {
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.classList.add('modal-open');
        }
    }
}

function closeAddTaskModal() {
    var dialog = document.getElementById('dialog-add-task');
    if (dialog) {
        if (typeof dialog.close === 'function') {
            dialog.close();
        } else {
            dialog.classList.remove('modal-open');
        }
    }
}

function addDocRow() {
    var container = document.getElementById('doc-rows-container');
    if (!container) return;

    var rows = container.querySelectorAll('.doc-row');
    if (rows.length === 0) return;

    var firstRow = rows[0];
    var newRow = firstRow.cloneNode(true);

    // Limpiar campos
    var textInput = newRow.querySelector('input[type="text"]');
    var fileInput = newRow.querySelector('input[type="file"]');
    if (textInput) textInput.value = '';
    if (fileInput) fileInput.value = '';

    container.appendChild(newRow);
    updateRemoveDocButtons();
}

function removeDocRow(btn) {
    var container = document.getElementById('doc-rows-container');
    var rows = container.querySelectorAll('.doc-row');
    if (rows.length > 1) {
        var row = btn.closest('.doc-row');
        if (row) {
            row.remove();
        }
    }
    updateRemoveDocButtons();
}

function updateRemoveDocButtons() {
    var container = document.getElementById('doc-rows-container');
    if (!container) return;
    var rows = container.querySelectorAll('.doc-row');
    rows.forEach(function(row) {
        var btn = row.querySelector('.remove-doc-btn');
        if (btn) {
            if (rows.length > 1) {
                btn.classList.remove('hidden');
            } else {
                btn.classList.add('hidden');
            }
        }
    });
}
</script>
