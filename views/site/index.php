<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */

$this->title = 'Dashboard';
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
?>

<div class="flex flex-col gap-8">

    <div class="flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-bold text-primary">Hola 👋</h1>
            <p class="text-base-content/60">Aquí tienes el resumen de hoy,
                <?= Yii::$app->formatter->asDate(date('Y-m-d'), 'long') ?>
            </p>
        </div>
        <?= Html::a('+ Nuevo Ticket', ['/tickets/create'], ['class' => 'btn btn-primary shadow-lg']) ?>
    </div>

    <?php if (Yii::$app->user->identity->isAdmin) { ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 w-full">

            <!-- Tickets Pendientes -->
            <div class="stats bg-base-100 shadow-xl border border-base-200">
                <div class="stat">
                    <div class="stat-figure text-error">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            class="inline-block w-8 h-8 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>
                    <div class="stat-title">Pendientes</div>
                    <div class="stat-value text-error"><?= $countOpen ?></div>
                    <div class="stat-desc">Requieren respuesta</div>
                </div>
            </div>

            <!-- Tickets En Gestión -->
            <div class="stats bg-base-100 shadow-xl border border-base-200">
                <div class="stat">
                    <div class="stat-figure text-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            class="inline-block w-8 h-8 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                            </path>
                        </svg>
                    </div>
                    <div class="stat-title">En Gestión</div>
                    <div class="stat-value text-warning"><?= $countAnswered ?></div>
                    <div class="stat-desc">Esperando al cliente</div>
                </div>
            </div>

            <!-- Tickets Cerrados -->
            <div class="stats bg-base-100 shadow-xl border border-base-200">
                <div class="stat">
                    <div class="stat-figure text-base-content/50">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            class="inline-block w-8 h-8 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="stat-title">Tickets Cerrados</div>
                    <div class="stat-value text-base-content/70"><?= $countClosed ?></div>
                    <div class="stat-desc">Tickets resueltos</div>
                </div>
            </div>

            <!-- Tickets Total Histórico -->
            <div class="stats bg-base-100 shadow-xl border border-base-200">
                <div class="stat">
                    <div class="stat-figure text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            class="inline-block w-8 h-8 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                    </div>
                    <div class="stat-title">Total Histórico</div>
                    <div class="stat-value text-primary"><?= $countTotal ?></div>
                    <div class="stat-desc">Desde el inicio</div>
                </div>
            </div>

            <!-- Órdenes Pagadas -->
            <div class="stats bg-base-100 shadow-xl border border-base-200">
                <div class="stat">
                    <div class="stat-figure text-success">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="stat-title">Órdenes Pagadas</div>
                    <div class="stat-value text-success"><?= $countPaidOrders ?></div>
                    <div class="stat-desc">Pagos recibidos</div>
                </div>
            </div>

            <!-- Suma Órdenes Pagadas -->
            <div class="stats bg-base-100 shadow-xl border border-base-200">
                <div class="stat">
                    <div class="stat-figure text-success">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 opacity-50">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="stat-title">Ingresos Órdenes</div>
                    <div class="stat-value text-success text-2xl lg:text-3xl"><?= Yii::$app->formatter->asCurrency($sumPaidOrders, 'COP') ?></div>
                    <div class="stat-desc">Total recaudado</div>
                </div>
            </div>

            <!-- OT Aprobadas -->
            <div class="stats bg-base-100 shadow-xl border border-base-200">
                <div class="stat">
                    <div class="stat-figure text-info">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75" />
                        </svg>
                    </div>
                    <div class="stat-title">OT Aprobadas</div>
                    <div class="stat-value text-info"><?= $countApprovedWO ?></div>
                    <div class="stat-desc">Órdenes de trabajo</div>
                </div>
            </div>

            <!-- Suma OT Aprobadas -->
            <div class="stats bg-base-100 shadow-xl border border-base-200">
                <div class="stat">
                    <div class="stat-figure text-info">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 opacity-50">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="stat-title">Ingresos OT</div>
                    <div class="stat-value text-info text-2xl lg:text-3xl"><?= Yii::$app->formatter->asCurrency($sumApprovedWO, 'COP') ?></div>
                    <div class="stat-desc">Monto en OT aprobadas</div>
                </div>
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
                    $typeConfig = match ($item->type) {
                        'success' => ['color' => 'badge-success', 'icon' => '🎉', 'label' => 'Logro'],
                        'warning' => ['color' => 'badge-warning', 'icon' => '🛠️', 'label' => 'Aviso'],
                        'danger' => ['color' => 'badge-error', 'icon' => '🚨', 'label' => 'Urgente'],
                        default => ['color' => 'badge-info', 'icon' => '📢', 'label' => 'Noticia'],
                    };

                    // 2. Borde rojo si es urgente
                    $borderClass = ($item->type === 'danger') ? 'border-error' : 'border-base-200';
                    ?>

                    <div
                        class="card bg-base-100 shadow-xl border <?= $borderClass ?> hover:border-primary/50 transition-all h-full flex flex-col group">
                        <div class="card-body p-6 flex-grow">

                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <div class="badge <?= $typeConfig['color'] ?> badge-outline gap-1 font-semibold">
                                        <?= $typeConfig['icon'] ?> <?= $typeConfig['label'] ?>
                                    </div>
                                    <?php if ($item->is_pinned): ?>
                                        <div class="badge badge-secondary gap-1 font-semibold text-xs">
                                            📌 Fijado
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs text-base-content/50 font-mono mt-1">
                                    <?= Yii::$app->formatter->asDate($item->created_at, 'short') ?>
                                </span>
                            </div>

                            <h2 class="card-title text-lg mb-2 leading-tight">
                                <a href="<?= Url::to(['announcements/view', 'id' => $item->id]) ?>"
                                    class="hover:text-primary transition-colors">
                                    <?= Html::encode($item->title) ?>
                                </a>
                            </h2>

                            <div class="text-sm opacity-70 line-clamp-3 mb-4">
                                <?= StringHelper::truncate(strip_tags($item->content), 150) ?>
                            </div>

                            <div class="flex items-center gap-4 text-xs text-base-content/40 mt-auto pt-4 border-t border-base-100">

                                <?php if ($isAdmin): ?>
                                    <div class="flex items-center gap-1 tooltip tooltip-right" data-tip="Vistas totales (Solo Admin)">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd"
                                                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <span class="font-bold text-base-content/70"><?= $item->getViewsCount() ?? 0 ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center gap-1 tooltip tooltip-right" data-tip="Reacciones de la comunidad">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path
                                            d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z" />
                                    </svg>
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
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
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
                                    <div class="text-xs font-normal opacity-50 mt-1 flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                                        Último msj: <span class="font-semibold"><?= Html::encode($ticket->getLastResponderName()) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?= \app\widgets\StatusBadge::widget([
                                        'model' => $ticket,
                                        'size'  => 'sm',
                                    ]) ?>
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
                                <td colspan="5" class="text-center py-4 text-base-content/50">No hay actividad reciente.
                                </td>
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