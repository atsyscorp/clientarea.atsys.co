<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Meetings;

/** @var yii\web\View $this */
/** @var app\models\MeetingsSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int $upcomingCount */
/** @var int $todayCount */
/** @var int $completedCount */
/** @var int $pendingCount */
/** @var int $totalCount */
/** @var bool|null $isAdmin */

$isAdmin = isset($isAdmin) ? $isAdmin : (!Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin);

$this->title = 'Reuniones y Sesiones Meet';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="meetings-index space-y-6">

    <!-- Encabezado y Acciones -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-base-content/85 flex items-center gap-3">
                <span class="p-2.5 bg-primary/10 text-primary rounded-xl inline-flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                </span>
                Reuniones Google Meet
            </h1>
            <p class="text-sm opacity-60 mt-1">
                <?= $isAdmin ? 'Programa sesiones virtuales con clientes o prospectos, sincroniza con Google Calendar y guarda las minutas.' : 'Consulta tus reuniones programadas y solicita nuevas sesiones virtuales con nuestro equipo.' ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($isAdmin): ?>
                <a href="/reuniones/solicitar" target="_blank" class="btn btn-outline btn-sm gap-1.5 shadow-sm" title="Abrir página pública de solicitud">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                    Portal Público
                </a>
                <a href="<?= Url::to(['meetings/create']) ?>" class="btn btn-primary btn-sm md:btn-md shadow-lg shadow-primary/20 gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Agendar Reunión
                </a>
            <?php else: ?>
                <a href="/reuniones/solicitar" class="btn btn-primary btn-sm md:btn-md shadow-lg shadow-primary/20 gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Solicitar Cita
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tarjetas de Métricas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stats shadow-sm border border-base-200 bg-base-100">
            <div class="stat py-3 px-4">
                <div class="stat-figure text-primary opacity-80">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" /></svg>
                </div>
                <div class="stat-title text-xs font-semibold uppercase tracking-wider">Hoy</div>
                <div class="stat-value text-2xl text-primary"><?= $todayCount ?></div>
                <div class="stat-desc text-xs mt-0.5">Reuniones para hoy</div>
            </div>
        </div>

        <div class="stats shadow-sm border border-base-200 bg-base-100">
            <div class="stat py-3 px-4">
                <div class="stat-figure text-info opacity-80">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <div class="stat-title text-xs font-semibold uppercase tracking-wider">Próximas</div>
                <div class="stat-value text-2xl text-info"><?= $upcomingCount ?></div>
                <div class="stat-desc text-xs mt-0.5">Pendientes por realizar</div>
            </div>
        </div>

        <div class="stats shadow-sm border border-base-200 bg-base-100">
            <div class="stat py-3 px-4">
                <div class="stat-figure text-success opacity-80">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <div class="stat-title text-xs font-semibold uppercase tracking-wider">Realizadas</div>
                <div class="stat-value text-2xl text-success"><?= $completedCount ?></div>
                <div class="stat-desc text-xs mt-0.5">Concluidas con éxito</div>
            </div>
        </div>

        <div class="stats shadow-sm border border-base-200 bg-base-100">
            <div class="stat py-3 px-4">
                <div class="stat-figure text-base-content opacity-60">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                </div>
                <div class="stat-title text-xs font-semibold uppercase tracking-wider">Total Histórico</div>
                <div class="stat-value text-2xl text-base-content/80"><?= $totalCount ?></div>
                <div class="stat-desc text-xs mt-0.5">Todas las reuniones</div>
            </div>
        </div>
    </div>

    <!-- Alerta de Solicitudes Pendientes -->
    <?php if ($pendingCount > 0 && $searchModel->filterPeriod !== 'pending'): ?>
        <div class="alert alert-warning shadow-sm border border-warning/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-warning-content flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                <div>
                    <?php if ($isAdmin): ?>
                        <span class="font-bold text-sm">Tienes <?= $pendingCount ?> <?= $pendingCount === 1 ? 'solicitud de reunión pendiente' : 'solicitudes de reunión pendientes' ?> por autorizar.</span>
                        <span class="text-xs opacity-75 block">Revisa las solicitudes públicas y apruébalas para generar automáticamente la sala de Google Meet.</span>
                    <?php else: ?>
                        <span class="font-bold text-sm">Tienes <?= $pendingCount ?> <?= $pendingCount === 1 ? 'cita solicitada pendiente' : 'citas solicitadas pendientes' ?> de confirmación.</span>
                        <span class="text-xs opacity-75 block">Estamos revisando la agenda corporativa para confirmar el espacio y notificarte por correo con el enlace de Google Meet.</span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="<?= Url::to(['meetings/index', 'MeetingsSearch[filterPeriod]' => 'pending']) ?>" class="btn btn-sm btn-neutral gap-1.5 flex-shrink-0">
                Ver <?= $isAdmin ? 'Solicitudes' : 'Pendientes' ?>
            </a>
        </div>
    <?php endif; ?>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4">
            <form method="get" action="<?= Url::to(['meetings/index']) ?>" class="flex flex-col md:flex-row items-center justify-between gap-3">
                <div class="relative w-full md:w-96">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-base-content/50">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    </span>
                    <input type="text" name="MeetingsSearch[searchQuery]" value="<?= Html::encode($searchModel->searchQuery) ?>" 
                           placeholder="Buscar por cliente, correo o asunto..." 
                           class="input input-bordered input-sm w-full pl-9 bg-base-200/50 focus:bg-base-100 transition-colors">
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
                    <div class="join">
                        <a href="<?= Url::to(['meetings/index', 'MeetingsSearch[filterPeriod]' => '']) ?>" 
                           class="btn btn-sm join-item <?= empty($searchModel->filterPeriod) ? 'btn-active btn-neutral' : 'btn-ghost' ?>">Todas</a>
                        <a href="<?= Url::to(['meetings/index', 'MeetingsSearch[filterPeriod]' => 'pending']) ?>" 
                           class="btn btn-sm join-item <?= $searchModel->filterPeriod === 'pending' ? 'btn-active btn-warning text-white' : 'btn-ghost' ?>">
                            Por Aprobar
                            <?php if ($pendingCount > 0): ?>
                                <span class="badge badge-warning text-white badge-xs font-bold"><?= $pendingCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?= Url::to(['meetings/index', 'MeetingsSearch[filterPeriod]' => 'upcoming']) ?>" 
                           class="btn btn-sm join-item <?= $searchModel->filterPeriod === 'upcoming' ? 'btn-active btn-primary' : 'btn-ghost' ?>">Próximas</a>
                        <a href="<?= Url::to(['meetings/index', 'MeetingsSearch[filterPeriod]' => 'today']) ?>" 
                           class="btn btn-sm join-item <?= $searchModel->filterPeriod === 'today' ? 'btn-active btn-primary' : 'btn-ghost' ?>">Hoy</a>
                        <a href="<?= Url::to(['meetings/index', 'MeetingsSearch[filterPeriod]' => 'past']) ?>" 
                           class="btn btn-sm join-item <?= $searchModel->filterPeriod === 'past' ? 'btn-active btn-neutral' : 'btn-ghost' ?>">Pasadas</a>
                    </div>

                    <select name="MeetingsSearch[status]" onchange="this.form.submit()" class="select select-bordered select-sm bg-base-200/50">
                        <option value="">Estado (Todos)</option>
                        <?php foreach (Meetings::optsStatus() as $key => $lbl): ?>
                            <option value="<?= $key ?>" <?= $searchModel->status === $key ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (!empty($searchModel->searchQuery) || !empty($searchModel->filterPeriod) || !empty($searchModel->status)): ?>
                        <a href="<?= Url::to(['meetings/index']) ?>" class="btn btn-sm btn-ghost text-error" title="Limpiar filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Listado de Reuniones -->
    <div class="card bg-base-100 shadow-md border border-base-200">
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full text-left">
                <thead>
                    <tr class="bg-base-200/60 text-xs font-bold uppercase tracking-wider text-base-content/70">
                        <th>Fecha y Hora</th>
                        <th>Asunto & Detalles</th>
                        <th>Cliente / Asistente</th>
                        <th>Google Meet</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200 text-sm">
                    <?php 
                    $meetings = $dataProvider->getModels();
                    if (empty($meetings)): 
                    ?>
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center opacity-60">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mb-3 opacity-40"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" /></svg>
                                    <p class="font-semibold text-base">No se encontraron reuniones</p>
                                    <p class="text-xs mt-1">
                                        <?= $isAdmin ? 'Programa una nueva reunión para sincronizar con Google Calendar.' : 'Solicita una reunión con nuestro equipo de ingeniería o soporte.' ?>
                                    </p>
                                    <div class="mt-4">
                                        <?php if ($isAdmin): ?>
                                            <a href="<?= Url::to(['meetings/create']) ?>" class="btn btn-primary btn-sm gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                                Agendar Reunión
                                            </a>
                                        <?php else: ?>
                                            <a href="/reuniones/solicitar" class="btn btn-primary btn-sm gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                                Solicitar Cita
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($meetings as $meeting): ?>
                            <?php 
                                $isToday = $meeting->isToday();
                                $rowHighlight = $isToday ? 'bg-primary/5 font-medium' : '';
                            ?>
                            <tr class="hover <?= $rowHighlight ?> transition-colors">
                                <td class="whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="p-2 rounded-lg <?= $isToday ? 'bg-primary text-white' : 'bg-base-200 text-base-content/70' ?> text-center min-w-[50px]">
                                            <div class="text-xs uppercase font-bold leading-none"><?= date('M', strtotime($meeting->start_time)) ?></div>
                                            <div class="text-lg font-extrabold leading-none mt-1"><?= date('d', strtotime($meeting->start_time)) ?></div>
                                        </div>
                                        <div>
                                            <div class="font-bold text-sm text-base-content flex items-center gap-1.5">
                                                <?= date('h:i A', strtotime($meeting->start_time)) ?>
                                                <?php if ($isToday): ?>
                                                    <span class="badge badge-primary badge-xs">HOY</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-xs opacity-60">
                                                <?= $meeting->getDurationMinutes() ?> min (hasta <?= date('h:i A', strtotime($meeting->end_time)) ?>)
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="font-bold text-base-content">
                                        <a href="<?= Url::to(['meetings/view', 'id' => $meeting->id]) ?>" class="hover:text-primary transition-colors">
                                            <?= Html::encode($meeting->title) ?>
                                        </a>
                                    </div>
                                    <?php if (!empty($meeting->description)): ?>
                                        <div class="text-xs opacity-70 line-clamp-1 max-w-xs mt-0.5">
                                            <?= Html::encode($meeting->description) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($meeting->notes)): ?>
                                        <div class="inline-flex items-center gap-1 text-[11px] text-success font-medium mt-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                            Minuta registrada
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="font-semibold text-sm text-base-content">
                                        <?= Html::encode($meeting->client_name) ?>
                                    </div>
                                    <div class="text-xs opacity-70 flex items-center gap-1 mt-0.5">
                                        <?= Html::encode($meeting->client_email) ?>
                                    </div>
                                    <?php if ($meeting->customer): ?>
                                        <div class="mt-1">
                                            <a href="<?= Url::to(['customers/view', 'id' => $meeting->customer_id]) ?>" class="badge badge-ghost badge-sm text-[10px] hover:badge-primary transition-colors">
                                                <?= Html::encode($meeting->customer->business_name) ?>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-1">
                                            <span class="badge badge-ghost badge-sm text-[10px] opacity-70">
                                                Externo / Prospecto
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($meeting->meet_url)): ?>
                                        <div class="flex items-center gap-1.5">
                                            <a href="<?= Html::encode($meeting->meet_url) ?>" target="_blank" rel="noopener noreferrer" 
                                               class="btn btn-sm btn-success text-white gap-1.5 shadow-sm" title="Abrir Google Meet">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                                Unirse
                                            </a>
                                            <button type="button" onclick="navigator.clipboard.writeText('<?= Html::encode($meeting->meet_url) ?>'); alert('¡Enlace de Google Meet copiado!');" 
                                                    class="btn btn-sm btn-ghost btn-square" title="Copiar enlace">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-base-content/70"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs opacity-50 italic">Sin enlace Meet</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= $meeting->getStatusBadge() ?>
                                </td>

                                <td class="text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if ($isAdmin && $meeting->status === Meetings::STATUS_PENDING): ?>
                                            <!-- Botón Aprobar Rápido (Solo Admin) -->
                                            <a href="<?= Url::to(['meetings/approve', 'id' => $meeting->id]) ?>" 
                                               onclick="return confirm('¿Deseas autorizar esta reunión y generar automáticamente la sala de Google Meet con n8n?');"
                                               class="btn btn-sm btn-success text-white gap-1 font-semibold shadow-sm" 
                                               title="Aprobar reunión y generar Meet">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                Aprobar
                                            </a>
                                        <?php endif; ?>

                                        <!-- Botón Ver Detalle -->
                                        <a href="<?= Url::to(['meetings/view', 'id' => $meeting->id]) ?>" 
                                           class="btn btn-sm btn-outline btn-primary gap-1.5 font-medium" 
                                           title="Ver detalles y notas">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                            Ver
                                        </a>

                                        <?php if ($isAdmin): ?>
                                            <!-- Botón Eliminar (Solo Admin) -->
                                            <a href="<?= Url::to(['meetings/delete', 'id' => $meeting->id]) ?>" 
                                               onclick="return confirm('¿Estás seguro de que deseas eliminar permanentemente esta reunión del historial?');"
                                               class="btn btn-sm btn-outline btn-error gap-1.5 font-medium" 
                                               title="Eliminar reunión">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                                Eliminar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($dataProvider->pagination->pageCount > 1): ?>
            <div class="card-footer p-4 border-t border-base-200 flex justify-center">
                <?= \yii\widgets\LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'join'],
                    'linkContainerOptions' => ['class' => 'join-item'],
                    'linkOptions' => ['class' => 'btn btn-sm btn-outline'],
                    'disabledListItemSubTagOptions' => ['class' => 'btn btn-sm btn-disabled'],
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
