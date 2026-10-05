<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Meetings;

/** @var yii\web\View $this */
/** @var app\models\Meetings $model */

$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Reuniones', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="meetings-view max-w-5xl mx-auto space-y-6">

    <!-- Encabezado con estado y navegación -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-base-content/60 mb-1">
                <a href="<?= Url::to(['meetings/index']) ?>" class="hover:text-primary transition-colors flex items-center gap-1.5 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    Volver a Reuniones
                </a>
            </div>
            <h1 class="text-3xl font-bold text-base-content/85 flex items-center gap-3">
                <span class="p-2.5 bg-primary/10 text-primary rounded-xl inline-flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                </span>
                <?= Html::encode($model->title) ?>
            </h1>
            <div class="flex items-center gap-2 mt-1.5">
                <?= $model->getStatusBadge() ?>
                <span class="text-xs opacity-60">Creado el <?= date('d/m/Y H:i', strtotime($model->created_at)) ?></span>
            </div>
        </div>

        <!-- Barra de Botones de Acción -->
        <div class="flex items-center gap-2 flex-wrap">
            <?php if ($isAdmin && $model->status === Meetings::STATUS_PENDING): ?>
                <a href="<?= Url::to(['meetings/approve', 'id' => $model->id]) ?>" 
                   onclick="return confirm('¿Deseas autorizar esta reunión y generar la sala oficial de Google Meet con n8n?');"
                   class="btn btn-success text-white btn-sm gap-1.5 shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    Aprobar Cita
                </a>
                <a href="<?= Url::to(['meetings/reject', 'id' => $model->id]) ?>" 
                   onclick="return confirm('¿Deseas marcar esta solicitud como rechazada?');"
                   class="btn btn-outline btn-error btn-sm gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    Rechazar
                </a>
            <?php endif; ?>

            <?php if (!empty($model->meet_url)): ?>
                <a href="<?= Html::encode($model->meet_url) ?>" target="_blank" rel="noopener noreferrer" 
                   class="btn btn-success text-white btn-sm gap-1.5 shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    Google Meet
                </a>
            <?php endif; ?>

            <?php if (!empty($model->calendar_html_link)): ?>
                <a href="<?= Html::encode($model->calendar_html_link) ?>" target="_blank" rel="noopener noreferrer" 
                   class="btn btn-outline btn-sm gap-1.5" title="Ver en Google Calendar">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                    Google Calendar
                </a>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                <!-- Reenviar Invitación -->
                <?php if (!empty($model->meet_url)): ?>
                    <a href="<?= Url::to(['meetings/resend-invitation', 'id' => $model->id]) ?>" 
                       onclick="return confirm('¿Deseas reenviar el correo de invitación corporativo a <?= Html::encode($model->client_email) ?>?');"
                       class="btn btn-outline btn-sm gap-1.5" title="Reenviar invitación">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                        Reenviar Invitación
                    </a>
                <?php endif; ?>

                <!-- Marcar Realizada -->
                <?php if ($model->status !== Meetings::STATUS_COMPLETED && $model->status !== Meetings::STATUS_PENDING): ?>
                    <a href="<?= Url::to(['meetings/complete', 'id' => $model->id]) ?>" 
                       onclick="return confirm('¿Deseas marcar esta reunión como realizada?');"
                       class="btn btn-outline btn-success btn-sm gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        Realizada
                    </a>
                <?php endif; ?>

                <!-- Cancelar -->
                <?php if ($model->status !== Meetings::STATUS_CANCELED && $model->status !== Meetings::STATUS_PENDING): ?>
                    <a href="<?= Url::to(['meetings/cancel', 'id' => $model->id]) ?>" 
                       onclick="return confirm('¿Seguro que deseas cancelar esta reunión?');"
                       class="btn btn-outline btn-warning btn-sm gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                        Cancelar
                    </a>
                <?php endif; ?>

                <!-- Eliminar -->
                <a href="<?= Url::to(['meetings/delete', 'id' => $model->id]) ?>" 
                   onclick="return confirm('¿Estás seguro de que deseas eliminar permanentemente esta reunión del historial?');"
                   class="btn btn-outline btn-error btn-sm gap-1.5 font-medium" 
                   title="Eliminar reunión">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    Eliminar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Banner de Aprobación Pendiente -->
    <?php if ($model->status === Meetings::STATUS_PENDING): ?>
        <div class="card bg-warning/15 border border-warning/40 shadow-sm">
            <div class="card-body p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-warning text-white rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                    </div>
                    <div>
                        <?php if ($isAdmin): ?>
                            <div class="font-bold text-sm text-base-content">Solicitud Pendiente de Autorización</div>
                            <div class="text-xs text-base-content/70 mt-0.5">
                                Esta reunión fue solicitada por el usuario. Al hacer clic en <b>"Aprobar Cita"</b>, n8n creará el evento en Google Calendar y le enviará la invitación con Google Meet al solicitante.
                            </div>
                        <?php else: ?>
                            <div class="font-bold text-sm text-base-content">Solicitud Pendiente de Confirmación</div>
                            <div class="text-xs text-base-content/70 mt-0.5">
                                Hemos recibido tu petición para esta fecha y hora. Nuestro equipo validará la agenda corporativa y te notificará la confirmación con el enlace de Google Meet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($isAdmin): ?>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a href="<?= Url::to(['meetings/approve', 'id' => $model->id]) ?>" 
                           onclick="return confirm('¿Deseas autorizar esta reunión y generar la sala de Google Meet?');"
                           class="btn btn-success text-white btn-sm gap-1.5 shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            Aprobar y Generar Meet
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Banner de Google Meet Enlace Directo -->
    <?php if (!empty($model->meet_url)): ?>
        <div class="card bg-success/10 border border-success/30 shadow-sm">
            <div class="card-body p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-success text-white rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    </div>
                    <div>
                        <div class="font-bold text-sm text-base-content">Enlace oficial de Google Meet:</div>
                        <div class="text-xs text-base-content/70 font-mono select-all">
                            <?= Html::encode($model->meet_url) ?>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="navigator.clipboard.writeText('<?= Html::encode($model->meet_url) ?>'); alert('¡Enlace de Meet copiado!');" 
                            class="btn btn-sm btn-outline btn-success gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                        Copiar Enlace
                    </button>
                    <a href="<?= Html::encode($model->meet_url) ?>" target="_blank" rel="noopener noreferrer" 
                       class="btn btn-sm btn-success text-white gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                        Entrar a la Sala
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Fila de Detalles: Participante y Horario -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Tarjeta de Participante -->
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-base-content/50 flex items-center gap-1.5 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    Datos del Asistente
                </h3>
                <div class="space-y-2">
                    <div>
                        <span class="text-xs opacity-60 block">Nombre del cliente:</span>
                        <span class="text-base font-bold text-base-content"><?= Html::encode($model->client_name) ?></span>
                    </div>
                    <div>
                        <span class="text-xs opacity-60 block">Correo electrónico de invitación:</span>
                        <a href="mailto:<?= Html::encode($model->client_email) ?>" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                            <?= Html::encode($model->client_email) ?>
                        </a>
                    </div>
                    <div>
                        <span class="text-xs opacity-60 block">Tipo de cuenta:</span>
                        <?php if ($model->customer): ?>
                            <a href="<?= Url::to(['customers/view', 'id' => $model->customer_id]) ?>" class="badge badge-primary badge-sm gap-1 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                                <?= Html::encode($model->customer->business_name) ?> (Ver Perfil)
                            </a>
                        <?php else: ?>
                            <span class="badge badge-warning text-white badge-sm gap-1 mt-0.5 font-semibold">
                                ⚠️ Contacto Externo / No registrado
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Horario -->
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-base-content/50 flex items-center gap-1.5 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" /></svg>
                    Horario de la Reunión
                </h3>
                <div class="space-y-2">
                    <div>
                        <span class="text-xs opacity-60 block">Fecha y Hora de Inicio:</span>
                        <span class="text-base font-bold text-base-content flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            <?= Yii::$app->formatter->asDatetime($model->start_time, 'full') ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-xs opacity-60 block">Duración Estimada:</span>
                        <span class="text-sm font-semibold text-base-content">
                            <?= $model->getDurationMinutes() ?> minutos (Termina aprox. <?= date('h:i A', strtotime($model->end_time)) ?>)
                        </span>
                    </div>
                    <?php if (!empty($model->google_event_id)): ?>
                        <div>
                            <span class="text-xs opacity-60 block">ID Evento Google Calendar:</span>
                            <span class="text-xs font-mono opacity-70 select-all"><?= Html::encode($model->google_event_id) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Agenda / Descripción -->
    <?php if (!empty($model->description)): ?>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-base-content/50 flex items-center gap-1.5 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" /></svg>
                    Agenda / Temas a Tratar
                </h3>
                <div class="text-sm whitespace-pre-line text-base-content/85">
                    <?= Html::encode($model->description) ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <!-- Tarjeta Especial: Minuta y Transcripción con Gemini (Solo Admin) -->
        <div class="card bg-base-100 shadow-md border border-base-200">
            <div class="card-body p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-base-200 pb-4 mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-base-content/90 flex items-center gap-2">
                            <span class="p-1.5 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-lg inline-flex items-center justify-center text-sm shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" /></svg>
                            </span>
                            Transcripción y Minuta con Gemini
                        </h3>
                        <p class="text-xs opacity-60 mt-0.5">
                            Guarda aquí el resumen ejecutivo, puntos acordados o la transcripción completa de la reunión generada con Gemini.
                        </p>
                    </div>
                </div>

                <form method="post" action="<?= Url::to(['meetings/save-notes', 'id' => $model->id]) ?>" class="space-y-4">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

                    <div class="form-control">
                        <textarea name="notes" rows="10" 
                                  class="textarea textarea-bordered w-full font-mono text-sm leading-relaxed bg-base-200/40 focus:bg-base-100 transition-colors" 
                                  placeholder="Ejemplo de lo que puedes pegar aquí:
---
RESUMEN EJECUTIVO (Gemini):
- Se revisó el rendimiento del servidor VPS principal.
- El cliente solicitó ampliación de memoria RAM a 16GB.

COMPROMISOS / PRÓXIMOS PASOS:
1. ATSYS enviará cotización de ampliación de servidor antes del viernes.
2. Cliente enviará backup del proyecto.
---"><?= Html::encode($model->notes) ?></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-2">
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <label class="text-xs font-semibold opacity-70">Actualizar Estado:</label>
                            <select name="status" class="select select-bordered select-sm bg-base-200/50">
                                <?php foreach (Meetings::optsStatus() as $stKey => $stLbl): ?>
                                    <option value="<?= $stKey ?>" <?= $model->status === $stKey ? 'selected' : '' ?>><?= $stLbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm gap-2 w-full sm:w-auto shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            Guardar Minuta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif (!empty($model->notes)): ?>
        <!-- Minuta y Acuerdos de la Reunión (Vista Cliente) -->
        <div class="card bg-base-100 shadow-md border border-base-200">
            <div class="card-body p-6">
                <h3 class="text-base font-bold text-base-content/90 flex items-center gap-2 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                    Minuta y Acuerdos de la Reunión
                </h3>
                <div class="text-sm font-mono whitespace-pre-line bg-base-200/50 p-4 rounded-xl text-base-content/85 leading-relaxed border border-base-200">
                    <?= Html::encode($model->notes) ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
