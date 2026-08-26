<?php
use yii\helpers\Html;
use yii\widgets\LinkPager;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var bool $unreadOnly */

$this->title = 'Centro de Notificaciones';
?>
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-extrabold text-base-content flex items-center gap-3">
                <span>🔔 Notificaciones</span>
            </h1>
            <p class="text-sm text-base-content/60 mt-1">Mantente al día con los últimos movimientos de tu cuenta.</p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto self-end md:self-center">
            <a href="/notifications<?= $unreadOnly ? '' : '?unread=1' ?>" class="btn btn-sm <?= $unreadOnly ? 'btn-primary text-white' : 'btn-outline border-base-300' ?> rounded-xl">Sin Leer</a>
            <a href="/notifications" class="btn btn-sm <?= !$unreadOnly ? 'btn-primary text-white' : 'btn-outline border-base-300' ?> rounded-xl">Todas</a>
            <a href="/notifications/mark-all-read" class="btn btn-sm btn-ghost gap-2 text-primary hover:bg-primary/10 rounded-xl" data-method="post">
                Marcar todas leídas
            </a>
            <?php if (Yii::$app->user->identity->isAdmin): ?>
                <a href="/notifications/create" class="btn btn-sm btn-secondary rounded-xl gap-2">
                    <span>📣 Crear Campaña</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-base-100 rounded-3xl border border-base-200 shadow-xl overflow-hidden">
        <?php $models = $dataProvider->getModels(); ?>
        <?php if (empty($models)): ?>
            <div class="text-center py-16 px-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-16 h-16 mx-auto mb-4 text-base-content/30">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                <h3 class="text-lg font-bold text-base-content/80">No hay notificaciones</h3>
                <p class="text-sm text-base-content/50 mt-1">Aquí aparecerán tus alertas de facturación, soporte técnico y actualizaciones.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-base-200">
                <?php foreach ($models as $notif): ?>
                    <?php 
                        $bgClass = $notif->is_read ? 'bg-base-100 opacity-75' : 'bg-primary/5 border-l-4 border-primary';
                        $typeColors = [
                            'success' => 'text-success bg-success/10',
                            'warning' => 'text-warning bg-warning/10',
                            'danger' => 'text-error bg-error/10',
                            'promo' => 'text-secondary bg-secondary/10',
                            'info' => 'text-info bg-info/10'
                        ];
                        $badgeColor = $typeColors[$notif->type] ?? $typeColors['info'];
                    ?>
                    <div class="p-5 flex gap-4 items-start <?= $bgClass ?> hover:bg-base-200/40 transition-all">
                        <div class="p-3 rounded-2xl <?= $badgeColor ?> shrink-0">
                            <?php if ($notif->type === 'success'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <?php elseif ($notif->type === 'warning'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                            <?php elseif ($notif->type === 'danger'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 7.5h.008v.008H12v-.008z" /></svg>
                            <?php elseif ($notif->type === 'promo'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.357.205a1.125 1.125 0 01-1.424-.282l-2.078-2.497m5.337-1.724a18.396 18.396 0 010-9.18m0 9.18a18.425 18.425 0 003.38 1.11m-3.38-10.29a18.425 18.425 0 013.38-1.11M19.5 12h.008v.008H19.5V12z" /></svg>
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.085 1.085l-.04.04m-2.137.375L11.75 14.25v2.25m3-3h.008v.008H14.75v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start gap-4">
                                <h3 class="font-bold text-base text-base-content leading-tight">
                                    <?= Html::encode($notif->title) ?>
                                </h3>
                                <span class="text-xs text-base-content/40 font-medium whitespace-nowrap">
                                    <?= Yii::$app->formatter->asRelativeTime($notif->created_at) ?>
                                </span>
                            </div>
                            <p class="text-sm text-base-content/75 mt-2 leading-relaxed">
                                <?= Html::encode($notif->body) ?>
                            </p>
                            <div class="flex items-center gap-3 mt-4">
                                <?php if (!empty($notif->link)): ?>
                                    <a href="/notifications/read?id=<?= $notif->id ?>" class="btn btn-xs btn-primary text-white rounded-lg gap-1.5 px-3">
                                        Ir al detalle
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                                    </a>
                                <?php endif; ?>
                                <?php if (!$notif->is_read): ?>
                                    <a href="/notifications/read?id=<?= $notif->id ?>" class="btn btn-xs btn-ghost text-base-content/60 hover:bg-base-200 rounded-lg px-2.5">
                                        Marcar como leída
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="p-4 bg-base-100 border-t border-base-200 flex justify-center">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'join pagination'],
                    'linkContainerOptions' => ['class' => 'join-item'],
                    'linkOptions' => ['class' => 'btn btn-ghost btn-sm'],
                    'disabledPageCssClass' => 'btn-disabled',
                    'activePageCssClass' => 'btn-active text-primary',
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
