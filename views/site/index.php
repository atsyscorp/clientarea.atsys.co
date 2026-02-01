<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */

$this->title = 'Dashboard - ATSYS Client Area';
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
?>

<div class="flex flex-col gap-8">

    <div class="flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-bold text-primary">Hola 👋</h1>
            <p class="text-base-content/60">Aquí tienes el resumen de hoy, <?= Yii::$app->formatter->asDate(date('Y-m-d'), 'long') ?></p>
        </div>
        <?= Html::a('+ Nuevo Ticket', ['/tickets/create'], ['class' => 'btn btn-primary shadow-lg']) ?>
    </div>

    <?php if(Yii::$app->user->identity->isAdmin) { ?>
    <div class="stats stats-vertical lg:stats-horizontal shadow-xl bg-base-100 w-full">

        <div class="stat">
            <div class="stat-figure text-error">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div class="stat-title">Pendientes de Atención</div>
            <div class="stat-value text-error"><?= $countOpen ?></div>
            <div class="stat-desc">Requieren respuesta inmediata</div>
        </div>
        
        <div class="stat">
            <div class="stat-figure text-warning">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            </div>
            <div class="stat-title">En Gestión</div>
            <div class="stat-value text-warning"><?= $countAnswered ?></div>
            <div class="stat-desc">Esperando respuesta del cliente</div>
        </div>
        
        <div class="stat">
            <div class="stat-figure text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
            </div>
            <div class="stat-title">Total Histórico</div>
            <div class="stat-value text-primary"><?= $countTotal ?></div>
            <div class="stat-desc">Tickets procesados desde el inicio</div>
        </div>
        
    </div>
    <?php } else { ?>
    <?php
    // Buscamos noticias (excluyendo las urgentes que ya salen arriba)
    $news = \app\models\Announcements::findActive()
        ->andWhere(['!=', 'type', 'danger']) 
        ->orderBy(['created_at' => SORT_DESC])
        ->limit(6) // Mostrar solo las últimas 6
        ->all();
    ?>

    <div class="flex items-center gap-2 mb-6 mt-8">
        <div class="bg-primary w-2 h-8 rounded"></div>
        <h2 class="text-2xl font-bold">Novedades y Actualizaciones</h2>
    </div>

    <?php if (empty($news)): ?>
        <div class="text-center opacity-50 py-10">
            <p>No hay novedades recientes para mostrar.</p>
        </div>
    <?php else: ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($news as $item): ?>
                <?php 
                    // 1. Configuración de Colores e Íconos
                    $typeConfig = match($item->type) {
                        'success' => ['color' => 'badge-success text-white', 'icon' => '🎉', 'label' => 'Logro'],
                        'warning' => ['color' => 'badge-warning', 'icon' => '🛠️', 'label' => 'Aviso'],
                        'danger'  => ['color' => 'badge-error text-white', 'icon' => '🚨', 'label' => 'Urgente'],
                        default   => ['color' => 'badge-info text-white', 'icon' => '📢', 'label' => 'Noticia'],
                    };
                    
                    // 2. Borde rojo si es urgente
                    $borderClass = ($item->type === 'danger') ? 'border-error' : 'border-base-200';
                ?>

                <div class="card bg-base-100 shadow-xl border <?= $borderClass ?> hover:border-primary/50 transition-all h-full flex flex-col group">
                    <div class="card-body p-6 flex-grow">
                        
                        <div class="flex justify-between items-start mb-3">
                            <div class="badge <?= $typeConfig['color'] ?> badge-outline gap-1 font-semibold">
                                <?= $typeConfig['icon'] ?> <?= $typeConfig['label'] ?>
                            </div>
                            <span class="text-xs text-base-content/50 font-mono mt-1">
                                <?= Yii::$app->formatter->asDate($item->created_at, 'short') ?>
                            </span>
                        </div>
                        
                        <h2 class="card-title text-lg mb-2 leading-tight">
                            <a href="<?= Url::to(['announcements/view', 'id' => $item->id]) ?>" class="hover:text-primary transition-colors">
                                <?= Html::encode($item->title) ?>
                            </a>
                        </h2>
                        
                        <div class="text-sm opacity-70 line-clamp-3 mb-4">
                            <?= StringHelper::truncate(strip_tags($item->content), 150) ?>
                        </div>

                        <div class="flex items-center gap-4 text-xs text-base-content/40 mt-auto pt-4 border-t border-base-100">
                            
                            <?php if ($isAdmin): ?>
                                <div class="flex items-center gap-1 tooltip tooltip-right" data-tip="Vistas totales (Solo Admin)">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                                    <span class="font-bold text-base-content/70"><?= $item->getViewsCount() ?? 0 ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center gap-1 tooltip tooltip-right" data-tip="Reacciones de la comunidad">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 20 20" fill="currentColor"><path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z" /></svg>
                                <span class="font-bold text-base-content/70"><?= $item->getReactions()->count() ?? 0 ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card-actions p-6 pt-0 mt-auto">
                        <?= Html::a('Leer comunicado →', ['announcements/view', 'id' => $item->id], [
                            'class' => 'btn btn-sm btn-outline btn-primary w-full'
                        ]) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
    <?php } ?>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title text-lg mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Últimos Tickets Recibidos
            </h2>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Asunto</th>
                            <th>Estado</th>
                            <th>Hace</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTickets as $ticket): ?>
                            <tr class="hover">
                                <td class="font-mono text-xs"><?= $ticket->ticket_code ?></td>
                                <td class="font-bold">
                                    <?= Html::encode(mb_strimwidth($ticket->subject, 0, 50, '...')) ?>
                                    <div class="text-xs font-normal opacity-50"><?= $ticket->email ?></div>
                                </td>
                                <td>
                                    <?php 
                                        $badges = [
                                            'open' => 'badge-error',
                                            'answered' => 'badge-success',
                                            'closed' => 'badge-neutral'
                                        ];
                                        $class = $badges[$ticket->status] ?? 'badge-ghost';
                                        $textClass = $ticket->status === 'open' || $ticket->status === 'closed' ? 'text-white' : 'text-black';
                                    ?>
                                    <span class="badge <?= $class ?> <?= $textClass?> badge-sm"><?= $ticket->getStatusText() ?></span>
                                </td>
                                <td class="text-sm opacity-70">
                                    <?= Yii::$app->formatter->asRelativeTime($ticket->created_at) ?>
                                </td>
                                <td>
                                    <?= Html::a('Ver', ['/tickets/view', 'id' => $ticket->id], ['class' => 'btn btn-xs btn-ghost']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($recentTickets)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-base-content/50">No hay actividad reciente.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card-actions justify-center mt-4 border-t pt-4 border-base-200">
                <?= Html::a('Ver Todos los Tickets', ['/tickets/index'], ['class' => 'btn btn-wide btn-ghost']) ?>
            </div>
        </div>
    </div>

</div>