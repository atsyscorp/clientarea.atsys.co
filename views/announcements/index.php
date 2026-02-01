<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Novedades y Comunicados';
$this->params['breadcrumbs'][] = $this->title;

// Verificación de Rol (Admin)
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
?>

<div class="announcements-index fade-in">

    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-primary">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                </svg>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-base-content/60 mt-1">Mantente al día con las últimas noticias de ATSYS.</p>
        </div>

        <?php if ($isAdmin): ?>
            <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Crear Comunicado', ['create'], ['class' => 'btn btn-primary text-white shadow-lg']) ?>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <?php if ($dataProvider->count > 0): ?>
            <?php foreach ($dataProvider->getModels() as $model): ?>
            <?php 
                // Configuración visual según tipo
                $typeConfig = match($model->type) {
                    'success' => ['color' => 'badge-success text-white', 'icon' => '🎉', 'label' => 'Logro'],
                    'warning' => ['color' => 'badge-warning', 'icon' => '🛠️', 'label' => 'Aviso'],
                    'danger'  => ['color' => 'badge-error text-white', 'icon' => '🚨', 'label' => 'Urgente'],
                    default   => ['color' => 'badge-info text-white', 'icon' => '📢', 'label' => 'Noticia'],
                };
                $borderClass = ($model->type === 'danger') ? 'border-error' : 'border-base-200';
            ?>

            <div class="card bg-base-100 shadow-xl border <?= $borderClass ?> hover:shadow-2xl transition-all h-full flex flex-col group">
                <div class="card-body p-6 flex-grow">
                    
                    <div class="flex justify-between items-start mb-3">
                        <div class="badge <?= $typeConfig['color'] ?> gap-2 p-3 font-semibold">
                            <?= $typeConfig['icon'] ?> <?= $typeConfig['label'] ?>
                        </div>
                        <div class="text-xs text-base-content/50 font-mono mt-1">
                            <?= Yii::$app->formatter->asDate($model->created_at, 'medium') ?>
                        </div>
                    </div>

                    <h2 class="card-title text-xl font-bold mb-2 leading-tight group-hover:text-primary transition-colors">
                        <a href="<?= Url::to(['view', 'id' => $model->id]) ?>">
                            <?= Html::encode($model->title) ?>
                        </a>
                    </h2>

                    <div class="text-base-content/70 text-sm line-clamp-3 mb-4 prose prose-sm">
                        <?= StringHelper::truncate(strip_tags($model->content), 140) ?>
                    </div>

                    <div class="flex items-center gap-4 text-xs text-base-content/40 mt-auto pt-4 border-t border-base-100">
                        
                        <?php if ($isAdmin): ?>
                            <div class="flex items-center gap-1 tooltip tooltip-right" data-tip="Vistas totales (Solo Admin)">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                                <span class="font-bold text-base-content/70"><?= $model->getViewsCount() ?? 0 ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center gap-1 tooltip tooltip-right" data-tip="Reacciones de la comunidad">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 20 20" fill="currentColor"><path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z" /></svg>
                            <span class="font-bold text-base-content/70"><?= $model->getReactions()->count() ?? 0 ?></span>
                        </div>
                    </div>
                </div>

                <div class="card-actions bg-base-200/50 p-4 rounded-b-2xl flex justify-between items-center">
                    
                    <?= Html::a('Leer completo', ['view', 'id' => $model->id], ['class' => 'btn btn-sm btn-ghost hover:bg-base-300']) ?>

                    <?php if ($isAdmin): ?>
                        <div class="flex gap-1">
                            <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>', 
                                ['update', 'id' => $model->id], 
                                ['class' => 'btn btn-sm btn-square btn-ghost text-warning', 'title' => 'Editar']
                            ) ?>
                            
                            <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>', 
                                ['delete', 'id' => $model->id], 
                                [
                                    'class' => 'btn btn-sm btn-square btn-ghost text-error',
                                    'title' => 'Eliminar',
                                    'data' => [
                                        'confirm' => '¿Estás seguro de eliminar este comunicado?',
                                        'method' => 'post',
                                    ],
                                ]
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full">
                <div class="alert alert-info shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>No hay novedades publicadas en este momento.</span>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <div class="mt-8 flex justify-center">
        <?= LinkPager::widget([
            'pagination' => $dataProvider->pagination,
            'options' => ['class' => 'join shadow-sm'], // Contenedor (UL)
            
            // Estilo para cada ítem (LI)
            'linkContainerOptions' => ['class' => 'join-item'], 
            
            // Estilo para el enlace (A)
            'linkOptions' => ['class' => 'join-item btn btn-md bg-base-100 hover:bg-base-200 border-base-300'], 
            
            // Clase CSS que se añade al ítem ACTIVO (LI)
            // Usamos 'btn-active' de DaisyUI para resaltarlo
            'activePageCssClass' => 'btn-active btn-primary text-white',
            
            // Clase para ítems deshabilitados
            'disabledPageCssClass' => 'btn-disabled opacity-50',
            
            'prevPageLabel' => '«',
            'nextPageLabel' => '»',
            
            // IMPORTANTE: Eliminamos 'activeLinkOptions' que causaba el error
        ]) ?>
    </div>

</div>