<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\WorkOrders */

$this->title = $model->code . ' - ' . $model->title;
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
$newUpdate = new \app\models\WorkOrderUpdates();
?>

<div class="max-w-4xl mx-auto my-8">

    <div class="flex justify-between items-center mb-6 no-print">
        <div>
            <?= Html::a('← Volver', ['index'], ['class' => 'btn btn-ghost btn-sm']) ?>
        </div>
        <div class="flex gap-2">
            
            <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg> Descargar PDF', ['pdf', 'id' => $model->id], ['class' => 'btn btn-outline btn-sm', 'target' => '_blank']) ?>

            <?php if($isAdmin): ?>
                <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg> Enviar al Cliente', 
                    ['send', 'id' => $model->id], 
                    [
                        'class' => 'btn btn-primary btn-sm text-white',
                        'data' => ['confirm' => '¿Enviar esta orden por correo al cliente? Se adjuntará el PDF.', 'method' => 'post']
                    ]
                ) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-base-100 shadow-2xl rounded-xl p-8 md:p-12 border border-base-200 print:shadow-none print:border-none">
        
        <div class="flex flex-col md:flex-row justify-between items-start border-b border-base-300 pb-8 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-primary tracking-tight">ORDEN DE TRABAJO</h1>
                <div class="text-sm opacity-60 mt-1 uppercase tracking-widest">Requerimientos de Desarrollo</div>
            </div>
            <div class="text-right mt-4 md:mt-0">
                <div class="text-2xl font-mono font-bold"><?= $model->code ?></div>
                <div class="mt-2"><?= $model->getStatusHtml() ?></div>
                <div class="text-sm opacity-60 mt-1">Fecha: <?= Yii::$app->formatter->asDate($model->created_at) ?></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
            <div>
                <h3 class="text-xs font-bold uppercase opacity-50 mb-2">Cliente</h3>
                <div class="font-bold text-lg"><?= Html::encode($model->customer->name ?? 'Cliente') ?></div>
                <div class="text-sm opacity-70"><?= Html::encode($model->customer->email ?? '') ?></div>
                <div class="text-sm opacity-70"><?= Html::encode($model->customer->document_number ?? '') ?></div>
            </div>
            <div class="md:text-right">
                <h3 class="text-xs font-bold uppercase opacity-50 mb-2">Proveedor</h3>
                <div class="font-bold text-lg">Arkitech Systems SAS</div>
                <div class="text-sm opacity-70">ATSYS - Desarrollo de Software</div>
                <div class="text-sm opacity-70">Trascendemos</div>
            </div>
        </div>

        <div class="mb-8">
            <h2 class="text-xl font-bold mb-2">Proyecto: <?= Html::encode($model->title) ?></h2>
        </div>

        <div class="bg-base-200/30 rounded-lg p-6 mb-8 border border-base-200">
            <h3 class="font-bold text-sm uppercase opacity-50 mb-4 border-b border-base-300 pb-2">Alcance y Requerimientos</h3>
            
            <div class="prose max-w-none text-justify">
                <?= Yii::$app->formatter->asNtext($model->requirements) ?>
            </div>
        </div>

        <?php if(!empty($model->notes)): ?>
            <div class="mb-8 text-sm italic opacity-70">
                <strong>Notas:</strong> <?= Html::encode($model->notes) ?>
            </div>
        <?php endif; ?>

        <div class="flex justify-end mb-12">
            <div class="w-full md:w-1/3 bg-base-200 p-4 rounded-lg">
                <div class="flex justify-between items-center text-lg font-bold">
                    <span>Inversión:</span>
                    <span class="text-primary"><?= Yii::$app->formatter->asCurrency($model->total_cost) ?></span>
                </div>
            </div>
        </div>

        <?php if (!$isAdmin && $model->status == \app\models\WorkOrders::STATUS_PENDING): ?>
            <div class="border-t-2 border-dashed border-base-300 pt-8 mt-8 text-center no-print">
                <h3 class="text-lg font-bold mb-4">Aprobación del Cliente</h3>
                <p class="text-sm mb-6 max-w-2xl mx-auto opacity-70">
                    Al aprobar esta orden de trabajo, confirmas que los requerimientos descritos arriba son correctos y autorizas el inicio del desarrollo bajo los costos estipulados.
                </p>
                
                <div class="flex justify-center gap-4">
                    <?= Html::a('✓ Aprobar e Iniciar', ['approve', 'id' => $model->id], [
                        'class' => 'btn btn-primary text-white px-8',
                        'data' => ['confirm' => '¿Estás seguro de aprobar esta orden? Esto autoriza el inicio del trabajo.', 'method' => 'post']
                    ]) ?>
                    
                    <?= Html::a('✕ Rechazar / Solicitar Cambios', ['reject', 'id' => $model->id], [
                        'class' => 'btn btn-outline btn-error px-6',
                        'data' => ['confirm' => '¿Deseas rechazar esta orden?', 'method' => 'post']
                    ]) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isAdmin && $model->status == \app\models\WorkOrders::STATUS_PENDING): ?>
            <div class="bg-base-200 p-3 rounded-xl">
                <div class="text-sm italic">
                    El cliente aún no ha aprobado esta orden, vuelve cuando hayas recibido una notificación.
                </div>
            </div>
        <?php endif; ?>

        <?php if ($model->status == \app\models\WorkOrders::STATUS_APPROVED): ?>
            <div class="mt-8 text-center border-2 border-success border-dashed p-4 rounded-xl opacity-80 rotate-1 max-w-xs mx-auto">
                <div class="text-success font-bold text-xl uppercase">APROBADO DIGITALMENTE</div>
                <div class="text-xs text-success">Fecha: <?= Yii::$app->formatter->asDatetime($model->updated_at) ?></div>
            </div>
            <div class="mb-4">
                <?php if ($model->down_payment_sent_at === null): ?>
                    
                    <?= \yii\helpers\Html::a('Generar Cobro 50%', ['generate-payment', 'id' => $model->id], [
                        'class' => 'btn btn-primary gap-2',
                        'data' => [
                            'confirm' => '¿Generar cobro del 50% y enviar correo?',
                            'method' => 'post',
                        ]
                    ]) ?>

                <?php else: ?>
                    
                    <div class="alert alert-success shadow-sm inline-flex w-auto py-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div class="flex flex-col">
                            <span class="font-bold text-sm">Anticipo Solicitado</span>
                            <span class="text-xs">Enviado el: <?= Yii::$app->formatter->asDatetime($model->down_payment_sent_at) ?></span>
                        </div>
                        </div>

                <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>
    
    <?php if ($model->status == \app\models\WorkOrders::STATUS_APPROVED): ?>
    <div class="divider my-10">LÍNEA DE TIEMPO / AVANCES</div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
        <div class="lg:col-span-2">
            <ul class="timeline timeline-snap-icon max-md:timeline-compact timeline-vertical">
                <?php 
                // Obtenemos los avances
                $query = \app\models\WorkOrderUpdates::find()->where(['work_order_id' => $model->id]);
                
                // Si NO es admin, solo mostrar los visibles
                if (!$isAdmin) {
                    $query->andWhere(['is_visible' => 1]);
                }
                
                $updates = $query->orderBy(['created_at' => SORT_DESC])->all();
                ?>
    
                <?php if (empty($updates)): ?>
                    <div class="text-center opacity-50 py-10">
                        <p>Aún no hay reportes de avance en este proyecto.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($updates as $index => $update): ?>
                        <li>
                            <div class="timeline-middle">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-primary"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            </div>
                            <div class="timeline-end mb-10 bg-base-100 p-4 rounded-box shadow-sm border border-base-200 w-full">
                                <time class="font-mono italic text-xs opacity-50 block mb-1">
                                    <?= Yii::$app->formatter->asDatetime($update->created_at) ?>
                                    <?php if($isAdmin && !$update->is_visible): ?>
                                        <span class="badge badge-xs badge-ghost ml-2">Privado 🔒</span>
                                    <?php endif; ?>
                                </time>
                                <div class="text-sm text-justify">
                                    <?= nl2br(\yii\helpers\Html::encode($update->description)) ?>
                                </div>
                            </div>
                            <hr class="bg-primary"/>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    
        <?php if ($isAdmin && $model->status != \app\models\WorkOrders::STATUS_COMPLETED): ?>
        <div>
            <div class="card bg-base-200 shadow-inner">
                <div class="card-body p-5">
                    <h3 class="font-bold text-lg mb-4">Registrar Avance</h3>
                    
                    <?php $form = \yii\widgets\ActiveForm::begin(['action' => ['add-update', 'id' => $model->id]]); ?>
                    
                    <?= $form->field($newUpdate, 'description')->textarea([
                        'rows' => 4, 
                        'class' => 'textarea textarea-bordered w-full',
                        'placeholder' => 'Describe qué se trabajó hoy...'
                    ])->label(false) ?>
    
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-4">
                            <?= $form->field($newUpdate, 'is_visible')->checkbox(['class' => 'checkbox checkbox-sm checkbox-primary'], false)->label(false) ?>
                            <span class="label-text">Visible para el Cliente</span>
                        </label>
                    </div>
    
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-4">
                            <?= $form->field($newUpdate, 'notify_email')->checkbox(['class' => 'checkbox checkbox-sm checkbox-secondary'], false)->label(false) ?>
                            <span class="label-text">Notificar por Email</span>
                        </label>
                    </div>
    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-sm w-full">Publicar en Bitácora</button>
                    </div>
                    
                    <?php \yii\widgets\ActiveForm::end(); ?>
                </div>
            </div>
            
            <div class="alert alert-info shadow-sm mt-4 text-xs">
                <span>💡 <strong>Tip:</strong> Usa "Visible" para logros completados. Usa notas privadas (sin check) para tus propios recordatorios técnicos.</span>
            </div>
        </div>
        <?php endif; ?>
    
    </div>
    <?php endif; ?>

    <?php if ($model->status === \app\models\WorkOrders::STATUS_APPROVED && $isAdmin): ?>
    <div class="card bg-base-100 shadow-xl border border-success/30 mt-8">
        <div class="card-body">
            <h3 class="card-title text-success flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Finalizar Orden de Trabajo
            </h3>
            <p class="text-base-content/70">
                Si el trabajo técnico ha concluido, puedes cerrar esta orden. Esto cambiará el estado a "Finalizado".
            </p>

            <?= Html::beginForm(['close', 'id' => $model->id], 'post', ['class' => 'mt-4']) ?>
                
                <div class="form-control mb-4">
                    <label class="cursor-pointer label justify-start gap-4">
                        <input type="checkbox" name="notify_client" value="1" checked class="checkbox checkbox-success" />
                        <span class="label-text font-medium">Enviar notificación por correo al cliente ("Trabajo Terminado")</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-success text-white w-full md:w-auto gap-2" onclick="return confirm('¿Confirmas que el trabajo está terminado?');">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                    Cerrar Orden y Finalizar
                </button>

            <?= Html::endForm() ?>
        </div>
    </div>
<?php endif; ?>
</div>
