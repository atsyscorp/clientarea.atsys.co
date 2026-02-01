<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ListView;

/** @var yii\web\View $this */
/** @var app\models\CustomerServicesSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
$this->title = $isAdmin ? 'Gestión Global de Servicios' : 'Mis Servicios Contratados';
?>
<div class="customer-services-index">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold"><?= Html::encode($this->title) ?></h1>
        <?php if ($isAdmin): ?>
        <?= Html::a('Nuevo Servicio', ['create'], ['class' => 'btn btn-primary text-white']) ?>
        <?php else: ?>
        <?= Html::a('Adquirir', ['/shop'], ['class' => 'btn btn-primary text-white']) ?>
        <?php endif; ?>
    </div>

    <?php /*
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body p-4">
            <?php echo $this->render('_search', ['model' => $searchModel]); ?>
        </div>
    </div>
    */ ?>

    <?php if ($isAdmin): ?>
    
        <div class="overflow-x-auto bg-base-100 shadow-xl rounded-box">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'tableOptions' => ['class' => 'table table-zebra w-full'],
                'summary' => '<div class="p-4 text-sm text-base-content/70">Mostrando <b>{begin}-{end}</b> de <b>{totalCount}</b> servicios.</div>',
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],

                    [
                        'attribute' => 'customer_id',
                        'value' => 'customer.business_name',
                        'label' => 'Cliente',
                        'format' => 'raw',
                        'value' => function($model) {
                            return Html::a($model->customer->business_name, ['customers/view', 'id' => $model->customer_id], ['class' => 'link link-hover font-bold']);
                        }
                    ],
                    [
                        'attribute' => 'product_id',
                        'value' => 'product.name',
                        'label' => 'Producto',
                    ],
                    [
                        'attribute' => 'domain',
                        'value' => function($model) {
                            return $model->domain ?: $model->description_label;
                        },
                        'label' => 'Referencia',
                    ],
                    [
                        'attribute' => 'next_due_date',
                        'format' => ['date', 'php:d M, Y'],
                        'contentOptions' => function ($model) {
                            // Colorear la celda si está vencida
                            $due = new DateTime($model->next_due_date);
                            $now = new DateTime();
                            if ($now > $due) {
                                return ['class' => 'text-error font-bold'];
                            } elseif ($now->diff($due)->days < 30) {
                                return ['class' => 'text-warning font-bold'];
                            }
                            return [];
                        },
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function($model) { return $model->getStatusHtml(); },
                        'filter' => [1 => 'Activo', 2 => 'Suspendido', 0 => 'Cancelado'],
                    ],

                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{update} {delete}',
                        'buttonOptions' => ['class' => 'btn btn-ghost btn-xs'],
                    ],
                ],
            ]); ?>
        </div>

    <?php else: ?>

        <?php /*ListView::widget([
            'dataProvider' => $dataProvider,
            'layout' => "{items}\n<div class='mt-8 flex justify-center'>{pager}</div>",
            'options' => ['class' => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'],
            'itemOptions' => ['tag' => false],
            'itemView' => function ($model, $key, $index, $widget) {
                // Cálculos de fecha para colores
                $daysLeft = null;
                $borderClass = 'border-base-200'; // Borde por defecto
                
                if ($model->next_due_date) {
                    $due = new DateTime($model->next_due_date);
                    $now = new DateTime();
                    $daysLeft = (int)$now->diff($due)->format('%r%a');
                    
                    if ($daysLeft < 0) $borderClass = 'border-error border-2'; // Vencido
                    elseif ($daysLeft < 30) $borderClass = 'border-warning border-2'; // Próximo
                }
                ?>
                <div class="card bg-base-100 shadow-xl border border-base-200 mb-4">
                    <div class="card-body">
                        
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="card-title text-2xl">
                                    <a href="http://<?= $model->domain ?>" target="_blank" class="link link-hover">
                                        <?= $model->domain ?>
                                    </a>
                                </h2>
                                <div class="badge badge-outline mt-2"><?= $model->product->name ?></div>
                            </h3>
                            </div>
                            
                            <div class="flex flex-col items-end">
                                <?php if ($model->status == 1): ?>
                                    <span class="badge badge-success gap-1">
                                        Activo
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-error gap-1">
                                        <?php 
                                        if($model->status == 2) {
                                            echo 'Suspendido';
                                        } else {
                                            echo ucfirst($model->status);
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                                <span class="text-xs opacity-50 mt-1">Vence: <span class="font-mono font-bold"><?= Yii::$app->formatter->asDate($model->next_due_date) ?></span></span>
                            </div>
                        </div>

                        <div class="bg-base-200 rounded-lg p-3 text-xs flex justify-between items-center mb-4">
                            <div class="text-center w-1/2 border-r border-base-300 pr-2">
                                <span class="block opacity-50 uppercase text-[10px]">Renovación</span>
                                <span class="font-bold text-lg text-primary">
                                    <?= Yii::$app->formatter->asCurrency(
                                        ($model->product->type=='hosting') ? $model->product->price : $model->product->price_renewal
                                    ) ?>
                                </span>
                            </div>
                            <?php if($model->product->price_restoration > 0): ?>
                            <div class="text-center w-1/2 pl-2 text-error">
                                <span class="block opacity-50 uppercase text-[10px]">Si vence</span>
                                <span class="font-bold text-lg">
                                    <?= Yii::$app->formatter->asCurrency($model->product->price_restoration) ?>
                                </span>
                            </div>
                            <?php else: ?>
                            <div class="text-center w-1/2 pl-2">
                                <span class="block opacity-50 uppercase text-[10px]">Tipo</span>
                                <span class="font-bold badge badge-ghost badge-sm">Fijo</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($model->username_service && $model->product->server->type == 'cyberpanel'): ?>
                        <div class="bg-base-200 rounded-box p-4 mt-4 collapse collapse-arrow border border-base-300"> 
                            <div class="collapse-title text-sm font-medium flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
                                Credenciales de Hosting (CyberPanel)
                            </div>
                            <div class="collapse-content">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                    
                                    <div class="form-control">
                                        <label class="label"><span class="label-text-alt">Usuario</span></label>
                                        <div class="join">
                                            <input type="text" value="<?= $model->username_service ?>" class="input input-bordered input-sm w-full join-item" readonly />
                                            <button class="btn btn-square btn-sm join-item" onclick="navigator.clipboard.writeText('<?= $model->username_service ?>')">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-control">
                                        <label class="label"><span class="label-text-alt">Contraseña</span></label>
                                        <div class="join">
                                            <input type="text" value="<?= $model->password_service ?>" class="input input-bordered input-sm w-full join-item font-mono" readonly />
                                            <button class="btn btn-square btn-sm join-item" onclick="navigator.clipboard.writeText('<?= $model->password_service ?>')">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                                
                                <div class="mt-4 text-right">
                                    <a href="https://<?= $model->product->server->hostname ?>:8090" target="_blank" class="btn btn-primary btn-sm gap-2">
                                        Ir al Panel de Control
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="card-actions justify-end mt-auto">
                            <?= Html::a('Soporte', ['tickets/create', 'service_id' => $model->id, 'subject' => 'Consulta sobre: ' . ($model->domain ?? $model->product->name)], ['class' => 'btn btn-sm btn-ghost']) ?>
                            
                            <?= Html::a('Renovar', ['renew', 'id' => $model->id], [
                                'class' => 'btn btn-primary btn-sm shadow-md animate-pulse',
                                'data' => [
                                    'method' => 'post',
                                    'confirm' => '¿Generar orden de renovación para ' . $model->domain . '?'
                                ]
                            ]) ?>
                        </div>

                    </div>
                </div>
                <?php
            },
        ]); */ ?>

        <?= Html::beginForm(['batch-renew'], 'post') ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($dataProvider->getModels() as $model): 
                
                $borderClass = 'border border-base-200';
                $hoverClass = '';
                if ($model->next_due_date) {
                    $due = new DateTime($model->next_due_date);
                    $now = new DateTime();
                    $daysLeft = (int)$now->diff($due)->format('%r%a');
                    
                    if ($daysLeft < 0) {
                        $borderClass = 'border-error border-2';
                    } elseif ($daysLeft < 30) {
                        $borderClass = 'border-warning border-2';
                    }
                }

            ?>
                
                <div class="card bg-base-100 shadow-xl <?=$borderClass?> hover:border-primary transition-colors">
                    <div class="card-body">
                        
                        <div class="flex justify-between items-start">
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-3">
                                    <input type="checkbox" name="selection[]" value="<?= $model->id ?>" class="checkbox checkbox-primary" />
                                    <span class="label-text font-bold text-lg"><?= $model->domain ?></span>
                                </label>
                            </div>
                            
                            <?php if ($model->status == 1): ?>
                                <span class="badge badge-success gap-1">
                                    Activo
                                </span>
                            <?php else: ?>
                                <span class="badge badge-error gap-1">
                                    <?php 
                                    if($model->status == 2) {
                                        echo 'Suspendido';
                                    } else {
                                        echo ucfirst($model->status);
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <p class="text-sm opacity-70 mt-2">
                            <?= $model->product->name ?> <br>
                            Vence: <span class="font-mono font-bold"><?= Yii::$app->formatter->asDate($model->next_due_date) ?></span>
                            <?php 
                                $isRestoration = false;
                                if ($model->product->type == 'domain') {
                                    $threshold = strtotime('+7 days', strtotime($model->next_due_date));
                                    if (time() > $threshold) $isRestoration = true;
                                }
                                
                                if ($isRestoration): ?>
                                    <br><span class="text-error font-bold">⚠️ En periodo de restauración (Cargo extra)</span>
                                <?php endif;
                            ?>
                        </p>

                        <?php /*
                        <div class="mt-4 font-bold text-xl text-right">
                            <?php if($model->product->price_restoration > 0): ?>
                            <div class="text-center w-1/2 pl-2 text-error">
                                <span class="block opacity-50 uppercase text-[10px]">Si vence</span>
                                <span class="font-bold text-lg">
                                    <?= Yii::$app->formatter->asCurrency($model->product->price_restoration) ?>
                                </span>
                            </div>
                            <?php else: ?>
                            <div class="text-center w-1/2 pl-2">
                                <span class="block opacity-50 uppercase text-[10px]">Tipo</span>
                                <span class="font-bold badge badge-ghost badge-sm">Fijo</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        */ ?>

                    </div>
                </div>

            <?php endforeach; ?>
        </div>

        <div class="flex items-end sm:items-center mt-6 card">
            <button type="submit" class="btn btn-primary gap-2 shadow-lg w-1/2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                Pagar Seleccionados
            </button>
        </div>

        <?= Html::endForm() ?>

    <?php endif; ?>
</div>