<?php
use yii\helpers\Html;
/** @var app\models\Notifications[] $recentNotifications */
/** @var bool $isMobile */

$isMobile = $isMobile ?? false;
$svgClass = $isMobile ? 'w-3.5 h-3.5' : 'w-4 h-4';
$iconPadding = $isMobile ? 'p-1' : 'p-1.5';
$gapClass = $isMobile ? 'gap-2' : 'gap-2.5';
$titleSize = 'text-xs';
$bodySize = 'text-[11px]';
$timeSize = 'text-[9px]';
$dotSize = $isMobile ? 'w-1.5 h-1.5 mt-2' : 'w-1.5 h-1.5 mt-1.5';
?>
<?php if (empty($recentNotifications)): ?>
    <div class="text-center py-6 text-xs text-base-content/50">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mx-auto mb-2 opacity-40">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        No tienes notificaciones
    </div>
<?php else: ?>
    <?php foreach ($recentNotifications as $notif): ?>
        <?php 
            $bgClass = $notif->is_read ? 'hover:bg-base-200/50' : 'bg-primary/5 hover:bg-primary/10';
            $typeColors = [
                'success' => 'text-success bg-success/10',
                'warning' => 'text-warning bg-warning/10',
                'danger' => 'text-error bg-error/10',
                'promo' => 'text-secondary bg-secondary/10',
                'info' => 'text-info bg-info/10'
            ];
            $badgeColor = $typeColors[$notif->type] ?? $typeColors['info'];
        ?>
        <li>
            <a href="/notifications/read?id=<?= $notif->id ?>" class="flex <?= $gapClass ?> items-start py-1.5 px-2 rounded-xl <?= $bgClass ?> transition-all">
                <div class="<?= $iconPadding ?> rounded-lg <?= $badgeColor ?> shrink-0 mt-0.5">
                    <?php if ($notif->type === 'success'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="<?= $svgClass ?>"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    <?php elseif ($notif->type === 'warning'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="<?= $svgClass ?>"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    <?php elseif ($notif->type === 'danger'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="<?= $svgClass ?>"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 7.5h.008v.008H12v-.008z" /></svg>
                    <?php elseif ($notif->type === 'promo'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="<?= $svgClass ?>"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.357.205a1.125 1.125 0 01-1.424-.282l-2.078-2.497m5.337-1.724a18.396 18.396 0 010-9.18m0 9.18a18.425 18.425 0 003.38 1.11m-3.38-10.29a18.425 18.425 0 013.38-1.11M19.5 12h.008v.008H19.5V12z" /></svg>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="<?= $svgClass ?>"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.085 1.085l-.04.04m-2.137.375L11.75 14.25v2.25m3-3h.008v.008H14.75v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <?php endif; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold <?= $titleSize ?> text-base-content leading-tight truncate"><?= Html::encode($notif->title) ?></p>
                    <p class="<?= $bodySize ?> text-base-content/70 mt-0.5 line-clamp-2"><?= Html::encode($notif->body) ?></p>
                    <span class="<?= $timeSize ?> text-base-content/40 font-medium block mt-0.5"><?= Yii::$app->formatter->asRelativeTime($notif->created_at) ?></span>
                </div>
                <?php if (!$notif->is_read): ?>
                    <span class="rounded-full bg-primary shrink-0 <?= $dotSize ?>"></span>
                <?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
<?php endif; ?>
