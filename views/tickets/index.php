<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

use yii\helpers\ArrayHelper;
use app\models\Customers;
use app\models\Tickets;
use app\widgets\StatusBadge;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Tickets';
$this->params['breadcrumbs'][] = $this->title;
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;

// URL para la acción masiva
$bulkUrl = Url::to(['bulk']);

// Preparamos el array de clientes para el selector
$clientesList = ArrayHelper::map(Customers::find()->orderBy('business_name')->all(), 'id', 'business_name');
// Preparamos el array de estados (Ajústalo a los textos exactos que manejes)
$estadosList = [
    'open' => 'Abierto',
    'answered' => 'Respondido',
    'customer_reply' => 'Resp. cliente',
    'closed' => 'Cerrado',
];

$isUserBlocked = !$isAdmin && !Yii::$app->user->isGuest && Yii::$app->user->identity->isTicketBlocked;
?>
<div class="tickets-index relative min-h-screen">
    <?php if ($isUserBlocked): ?>
        <div class="alert alert-error shadow-md mb-6 bg-error/10 border border-error/30 text-error-content rounded-2xl p-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6 text-error" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
            <div>
                <h3 class="font-bold text-sm">Creación de tickets restringida</h3>
                <div class="text-xs mt-0.5 opacity-90">
                    Tu cuenta se encuentra bloqueada para la creación y respuesta de tickets de soporte. Para más información, comunícate con la administración.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?=$this->title?></h1>

        <?php if (!$isUserBlocked): ?>
            <?= Html::a(
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Crear Ticket', 
                ['create'], 
                ['class' => 'btn btn-primary text-white shadow-lg']
            ) ?>
        <?php endif; ?>
    </div>

    <?= $this->render('_search', ['model' => $searchModel, 'isAdmin' => $isAdmin]) ?>

    <!-- Pestañas de Navegación -->
    <?php if ($isAdmin): ?>
    <div class="tabs tabs-boxed mb-6 justify-center bg-base-200 p-1 rounded-xl max-w-md mx-auto">
        <a id="tab-table-view" class="tab tab-lg font-bold px-6 transition-all duration-200 tab-active btn-primary text-white">
            <i class="fas fa-list mr-2"></i> Vista Tabla
        </a>
        <a id="tab-gantt-view" class="tab tab-lg font-bold px-6 transition-all duration-200">
            <i class="fas fa-tasks mr-2"></i> Cronograma Gantt
        </a>
    </div>
    <?php endif; ?>

    <!-- SECCIÓN: VISTA TABLA -->
    <div id="table-view-sec" class="tab-content-sec">
        <div class="overflow-x-auto w-full bg-base-100 shadow-xl rounded-box border border-base-200">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'id' => 'tickets-grid',
            'emptyText' => Yii::t('tickets','No tickets registered now.'), 
            'summary' => '<div class="p-4 text-sm text-base-content/70">Mostrando <b>{begin}-{end}</b> de <b>{totalCount}</b> tickets.</div>',
            'tableOptions' => [
                'id' => 'ticket-list',
                'class' => 'table table-zebra table-hover w-full'
            ],
            'layout' => "{items}\n{summary}\n{pager}",
            'pager' => [
                'options' => ['class' => 'join mt-4 justify-center w-full'],
                'linkOptions' => ['class' => 'join-item btn btn-sm'],
                'disabledListItemSubTagOptions' => ['class' => 'join-item btn btn-sm btn-disabled'],
                'activePageCssClass' => 'btn-active btn-primary text-white',
            ],
            'columns' => [
                [
                    'class' => 'yii\grid\CheckboxColumn',
                    'name' => 'selection',
                    'cssClass' => 'text-center',
                ],
                [
                    'attribute' => 'ticket_code',
                    'format' => 'raw',
                    'value' => function($model) {
                        // Hacemos que el código sea el enlace al ticket
                        return Html::a($model->ticket_code, ['view', 'id' => $model->id], ['style' => 'font-weight:bold; color:#007bff;']);
                    }
                ],
                [
                    'attribute' => 'customer.business_name',
                    'visible' => Yii::$app->user->identity->isAdmin,
                    'label' => 'Cliente'
                ],
                [
                    'attribute' => 'subject',
                    'format' => 'raw',
                    'value' => function($model) {
                        $responder = Html::encode($model->getLastResponderName());
                        return "<div>" . Html::encode($model->subject) . "</div>" .
                                "<div class='text-xs text-base-content/60 mt-1 flex items-center gap-1'>" .
                                "<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' class='w-3 h-3'><path stroke-linecap='round' stroke-linejoin='round' d='M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3' /></svg>" .
                                "Último msj: <span class='font-semibold'>{$responder}</span></div>";
                    }
                ],
                [
                    'attribute' => 'department',
                    'format' => 'raw',
                    'value' => function($model) {
                        return $model->getDepartmentLabelShort();
                    }
                ],
                [
                    'attribute' => 'email',
                    'visible' => Yii::$app->user->identity->isAdmin,
                    'label' => 'E-mail',
                    'format' => 'raw',
                    'value' => function($model) {
                        $emailHtml = Html::encode($model->email);
                        if ($model->isSenderBlacklisted()) {
                            $emailHtml .= " <span class='badge badge-error badge-xs font-bold ml-1' title='Remitente bloqueado en lista negra'>Bloqueado</span>";
                        }
                        return $emailHtml;
                    }
                ],
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $mergedBadge = '';
                        if (!empty($model->merged_into_id) && $model->mergedIntoTicket) {
                            $targetCode = Html::encode($model->mergedIntoTicket->ticket_code);
                            $targetUrl = Url::to(['view', 'id' => $model->merged_into_id]);
                            $mergedBadge = "<div class='mt-1'><a href='{$targetUrl}' class='badge badge-outline badge-secondary badge-xs gap-1' title='Fusionado en #{$targetCode}'>
                                                <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' class='w-3 h-3'><path stroke-linecap='round' stroke-linejoin='round' d='M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25' /></svg>
                                                #{$targetCode}
                                            </a></div>";
                        }

                        return StatusBadge::widget([
                            'model'   => $model,
                            'size'    => 'sm',
                            'options' => ['class' => 'gap-2'],
                        ]) . $mergedBadge;
                    },
                    'contentOptions' => ['class' => 'text-center'],
                ],
                
                'priority',
                'source',
                'updated_at',
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => 'Acciones',
                    'template' => '{view} {close}', // Botones que queremos
                    'contentOptions' => ['class' => 'flex gap-2 justify-center'], // Alineación horizontal
                    'visibleButtons' => [
                        'close' => function ($model, $key, $index) {
                            return $model->status !== 'closed'; // Mostrar solo si no está cerrado
                        },
                    ],
                    'buttons' => [
                        'view' => function ($url, $model) {
                            // Ícono de OJO (Heroicons)
                            return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>', 
                            $url, [
                                'class' => 'btn btn-square btn-ghost btn-xs', // Botón pequeño y transparente
                                'title' => 'Ver',
                            ]);
                        },
                        'close' => function ($url, $model) {
                            // Ícono de BASURA
                            return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>', 
                            $url, [
                                'class' => 'btn btn-square btn-ghost btn-xs text-error', // Color rojo
                                'data-confirm' => 'Se cerrará este ticket ¿Desea continuar?',
                                'data-method' => 'post',
                                'title' => 'Cerrar ticket',
                            ]);
                        },
                    ],
                ],
            ]
        ]); ?>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <!-- SECCIÓN: CRONOGRAMA GANTT GLOBAL -->
    <div id="gantt-view-sec" class="tab-content-sec hidden mb-20">
        <?php
        $ganttData = \app\models\Tickets::getGanttData($dataProvider->query, 25);
        ?>
        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-6">
                <h3 class="card-title text-xl font-bold text-base-content/80 mb-2">
                    <i class="fas fa-tasks text-primary mr-1"></i> Cronograma Global de Tickets (Gantt)
                </h3>
                <p class="text-xs text-base-content/40 mb-6 font-semibold">Línea de tiempo y avance de las últimas 25 solicitudes de soporte.</p>
                
                <?php if (!empty($ganttData['timeline'])): ?>
                    <div class="space-y-6">
                        <!-- Rulers de la Línea de Tiempo -->
                        <div class="grid grid-cols-12 text-center text-[10px] font-extrabold text-base-content/40 border-b border-base-200 pb-2">
                            <div class="col-span-3 text-left">Ticket / Asunto</div>
                            <div class="col-span-9 relative">
                                <div class="flex justify-between w-full px-2">
                                    <span><?= date('d M', $ganttData['min_time']) ?></span>
                                    <span>Mitad del Período</span>
                                    <span><?= date('d M', $ganttData['max_time']) ?></span>
                                </div>
                                <!-- Líneas verticales de rejilla en el fondo -->
                                <div class="absolute inset-0 flex justify-between pointer-events-none px-2 h-[450px] top-4">
                                    <div class="border-l border-base-300 border-dashed h-full opacity-60"></div>
                                    <div class="border-l border-base-300 border-dashed h-full opacity-60"></div>
                                    <div class="border-l border-base-300 border-dashed h-full opacity-60"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-4 max-h-[550px] overflow-y-auto pr-2 relative z-10">
                            <?php foreach ($ganttData['timeline'] as $tkt): ?>
                                <?php
                                $barColors = [
                                    'open' => 'from-blue-400 to-blue-500 shadow-blue-200',
                                    'answered' => 'from-emerald-400 to-emerald-500 shadow-emerald-200',
                                    'customer_reply' => 'from-amber-400 to-amber-500 shadow-amber-200',
                                    'closed' => 'from-gray-400 to-gray-500 shadow-gray-200',
                                    'in_progress' => 'from-indigo-400 to-indigo-500 shadow-indigo-200',
                                ];
                                $barColor = $barColors[$tkt['status']] ?? 'from-gray-400 to-gray-500';
                                
                                ?>
                                <div class="grid grid-cols-12 gap-3 items-center group">
                                    <!-- Código e Info del Ticket -->
                                    <div class="col-span-12 md:col-span-3 flex flex-col">
                                        <div class="flex items-center gap-1.5">
                                            <a href="/tickets/view?id=<?= $tkt['id'] ?>" class="text-xs font-extrabold text-primary hover:underline leading-none">
                                                <?= Html::encode($tkt['ticket_code']) ?>
                                            </a>
                                            <?= StatusBadge::widget([
                                                'status'  => $tkt['status'],
                                                'map'     => Tickets::statusBadgeMap(),
                                                'options' => ['class' => 'text-[9px] px-1 font-bold h-4 leading-none'],
                                            ]) ?>
                                        </div>
                                        <span class="text-xs font-semibold text-base-content/70 truncate mt-1 group-hover:text-primary transition-colors" title="<?= Html::encode($tkt['subject']) ?>">
                                            <?= Html::encode($tkt['subject']) ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Contenedor y Barra Gantt -->
                                    <div class="col-span-12 md:col-span-9 relative py-2">
                                        <div class="w-full bg-base-200 h-4 rounded-full shadow-inner flex items-center relative">
                                            <div class="absolute h-full rounded-full bg-gradient-to-r <?= $barColor ?> shadow-sm transition-all duration-500 cursor-help tooltip tooltip-primary font-bold text-xs"
                                                 style="left: <?= $tkt['left_percent'] ?>%; width: <?= $tkt['width_percent'] ?>%;"
                                                 data-tip="Creado: <?= date('d M H:i', strtotime($tkt['created_at'])) ?> | Duración: <?= $tkt['duration_text'] ?>">
                                            </div>
                                        </div>
                                        <div class="absolute right-0 top-0 text-[9px] text-base-content/40 font-mono opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none hidden md:block">
                                            Duración: <?= Html::encode($tkt['duration_text']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-16 text-base-content/40 flex flex-col items-center justify-center">
                        <i class="fas fa-tasks text-5xl mb-3 opacity-30"></i>
                        <span class="text-base font-semibold text-base-content/70">No hay tickets registrados</span>
                        <p class="text-xs max-w-xs mt-1">Los datos de progreso temporal se mostrarán aquí una vez que crees solicitudes de tickets.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    $jsGanttTab = <<<JS
    document.addEventListener("DOMContentLoaded", function() {
        const tabTable = document.getElementById('tab-table-view');
        const tabGantt = document.getElementById('tab-gantt-view');
        const secTable = document.getElementById('table-view-sec');
        const secGantt = document.getElementById('gantt-view-sec');

        function selectTable() {
            if (tabTable && tabGantt) {
                tabTable.classList.add('tab-active', 'btn-primary', 'text-white');
                tabGantt.classList.remove('tab-active', 'btn-primary', 'text-white');
            }
            if (secTable) secTable.classList.remove('hidden');
            if (secGantt) secGantt.classList.add('hidden');
        }

        function selectGantt() {
            if (tabTable && tabGantt) {
                tabGantt.classList.add('tab-active', 'btn-primary', 'text-white');
                tabTable.classList.remove('tab-active', 'btn-primary', 'text-white');
            }
            if (secGantt) secGantt.classList.remove('hidden');
            if (secTable) secTable.classList.add('hidden');
        }

        if (tabTable && tabGantt) {
            tabTable.addEventListener('click', selectTable);
            tabGantt.addEventListener('click', selectGantt);
        }
    });
    JS;
    $this->registerJs($jsGanttTab, \yii\web\View::POS_END);
    ?>
    <?php endif; ?>

    <div id="bulk-actions-bar" class="fixed bottom-10 left-1/2 transform -translate-x-1/2 z-50 transition-all duration-300 translate-y-32 opacity-0 pointer-events-none">
        <div class="bg-neutral text-neutral-content shadow-2xl rounded-full px-6 py-3 flex items-center gap-4 border border-white/10">
            <div class="font-bold text-sm whitespace-nowrap">
                <span id="selected-count" class="text-accent text-lg font-extrabold">0</span> seleccionados
            </div>

            <div class="h-6 w-px bg-base-100/20"></div>

            <div class="flex gap-2">
                <button type="button" onclick="applyBulkAction('close')" class="btn btn-sm btn-ghost hover:bg-base-100/10 text-white gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    Cerrar
                </button>

                <?php if ($isAdmin): ?>
                    <button type="button" onclick="openBulkMergeModal()" class="btn btn-sm btn-ghost text-secondary hover:bg-secondary/20 gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m-3-13.5L18 7.5m0 0L13.5 12M18 7.5H4.5" />
                        </svg>
                        Fusionar
                    </button>

                    <button type="button" onclick="applyBulkAction('delete')" class="btn btn-sm btn-ghost text-error hover:bg-error/20 gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        Eliminar
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- MODAL PARA FUSIÓN MASIVA -->
<dialog id="bulk_merge_modal" class="modal">
  <div class="modal-box max-w-lg">
    <h3 class="font-bold text-lg text-primary flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m-3-13.5L18 7.5m0 0L13.5 12M18 7.5H4.5" />
      </svg>
      Fusionar Tickets Seleccionados
    </h3>
    <p class="py-2 text-sm text-base-content/70">
      Selecciona cuál de los tickets marcados será el <strong>Ticket Destino (Target)</strong>. Los demás tickets seleccionados se unificarán dentro de él y sus respuestas se consolidarán.
    </p>

    <div class="form-control w-full mt-3">
      <label class="label"><span class="label-text font-bold">Seleccionar Ticket Destino:</span></label>
      <select id="bulk-merge-target-select" class="select select-bordered w-full">
      </select>
    </div>

    <div class="modal-action mt-6">
      <button type="button" class="btn btn-ghost" onclick="document.getElementById('bulk_merge_modal').close()">Cancelar</button>
      <button type="button" class="btn btn-secondary gap-2" onclick="submitBulkMerge()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m-3-13.5L18 7.5m0 0L13.5 12M18 7.5H4.5" />
        </svg>
        Confirmar Fusión
      </button>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Función para actualizar la barra flotante
    function updateFloatingBar() {
        const bar = document.getElementById('bulk-actions-bar');
        const countSpan = document.getElementById('selected-count');
        
        // Contamos los checkboxes MARCADOS que tengan el nombre correcto
        const checkboxes = document.querySelectorAll('input[name="selection[]"]:checked');
        const count = checkboxes.length;

        if(countSpan) countSpan.innerText = count;

        if (bar) {
            if (count > 0) {
                bar.classList.remove('translate-y-32', 'opacity-0', 'pointer-events-none');
            } else {
                bar.classList.add('translate-y-32', 'opacity-0', 'pointer-events-none');
            }
        }
    }

    // LISTENER INTELIGENTE
    // Usamos 'change' en la tabla. Cuando Yii marca "todos", dispara eventos.
    const grid = document.getElementById('tickets-grid');
    if (grid) {
        grid.addEventListener('change', function(e) {
            // Si el cambio viene de un checkbox...
            if (e.target.type === 'checkbox') {
                // TRUCO: Esperamos 10ms a que Yii termine de marcar/desmarcar todo
                setTimeout(updateFloatingBar, 10);
            }
        });
    }
});

// Función de Envío
function applyBulkAction(actionType) {
    const checkboxes = document.querySelectorAll('input[name="selection[]"]:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);

    if (ids.length === 0) return;

    const confirmMessage = actionType === 'delete' 
        ? '⚠️ ¿Estás seguro de ELIMINAR estos ' + ids.length + ' tickets permanentemente?' 
        : '¿Deseas CERRAR estos ' + ids.length + ' tickets?';

    if (!confirm(confirmMessage)) return;

    const formData = new FormData();
    ids.forEach(id => formData.append('ids[]', id));
    formData.append('action', actionType);
    
    // Tokens CSRF
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfParamMeta = document.querySelector('meta[name="csrf-param"]');
    if (csrfTokenMeta && csrfParamMeta) {
        formData.append(csrfParamMeta.getAttribute('content'), csrfTokenMeta.getAttribute('content'));
    }

    // URL ABSOLUTA para evitar errores 404
    fetch('<?= \yii\helpers\Url::to(['/tickets/bulk']) ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload(); 
        } else {
            alert('Error: ' + (data.message || 'Ocurrió un error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión o respuesta inesperada del servidor.');
    });
}

function openBulkMergeModal() {
    const checkboxes = document.querySelectorAll('input[name="selection[]"]:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);

    if (ids.length < 2) {
        alert('Debes seleccionar al menos 2 tickets para realizar la fusión.');
        return;
    }

    const select = document.getElementById('bulk-merge-target-select');
    select.innerHTML = '';

    checkboxes.forEach(cb => {
        const tr = cb.closest('tr');
        let label = 'Ticket ID #' + cb.value;
        if (tr) {
            const codeEl = tr.querySelector('td:nth-child(2)');
            const subjEl = tr.querySelector('td:nth-child(4)');
            const codeText = codeEl ? codeEl.innerText.trim() : '';
            const subjText = subjEl ? subjEl.innerText.split('\n')[0].trim() : '';
            if (codeText) {
                label = codeText + (subjText ? ' - ' + subjText : '');
            }
        }

        const opt = document.createElement('option');
        opt.value = cb.value;
        opt.textContent = label;
        select.appendChild(opt);
    });

    const modal = document.getElementById('bulk_merge_modal');
    if (modal) {
        modal.showModal();
    }
}

function submitBulkMerge() {
    const checkboxes = document.querySelectorAll('input[name="selection[]"]:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    const targetSelect = document.getElementById('bulk-merge-target-select');
    const targetId = targetSelect ? targetSelect.value : null;

    if (!targetId || ids.length < 2) {
        alert('Selección no válida.');
        return;
    }

    if (!confirm('⚠️ ¿Confirmas que deseas fusionar los tickets seleccionados en el ticket destino? Esta acción consolidará los mensajes y cerrará los otros tickets.')) {
        return;
    }

    const formData = new FormData();
    ids.forEach(id => formData.append('ids[]', id));
    formData.append('action', 'merge');
    formData.append('target_id', targetId);

    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfParamMeta = document.querySelector('meta[name="csrf-param"]');
    if (csrfTokenMeta && csrfParamMeta) {
        formData.append(csrfParamMeta.getAttribute('content'), csrfTokenMeta.getAttribute('content'));
    }

    fetch('<?= \yii\helpers\Url::to(['/tickets/bulk']) ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Ocurrió un error al fusionar los tickets.'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al fusionar.');
    });
}
</script>