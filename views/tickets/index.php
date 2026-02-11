<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Tickets';
$this->params['breadcrumbs'][] = $this->title;
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;

// URL para la acción masiva
$bulkUrl = Url::to(['bulk']);

?>
<div class="tickets-index relative min-h-screen">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?=$this->title?></h1>

        <?= Html::a(
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Crear Ticket', 
            ['create'], 
            ['class' => 'btn btn-primary text-white shadow-lg']
        ) ?>
    </div>

    <div class="overflow-x-auto w-full bg-base-100 shadow-xl rounded-box border border-base-200 mb-20">
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
                    'label' => 'Cliente',
                ],
                'subject',
                [
                    'attribute' => 'email:email',
                    'visible' => Yii::$app->user->identity->isAdmin,
                    'label' => 'E-mail',
                ],
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'filter' => ['open'=>'Open', 'answered'=>'Answered', 'closed'=>'Closed'],
                    'value' => function ($model) {
                        // Mapeo de colores según estado
                        $colors = [
                            'open' => 'badge-error',      // Rojo
                            'answered' => 'badge-success', // Verde
                            'customer_reply' => 'badge-warning', // Amarillo
                            'closed' => 'badge-neutral',   // Gris
                        ];

                        $colorClass = $colors[$model->status] ?? 'badge-ghost';
                        $textClass = $model->status === 'open' || $model->status === 'closed' ? 'text-white' : 'text-black';

                        return "<span class='badge {$colorClass} {$textClass} badge-sm gap-2'>
                                    {$model->getStatusText()}
                                </span>";
                    },
                    'contentOptions' => ['class' => 'text-center'], // Centrar la columna
                ],
                
                'priority',
                'source',
                'created_at',
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

    <div id="bulk-actions-bar" class="fixed bottom-10 left-1/2 transform -translate-x-1/2 z-50 transition-all duration-300 translate-y-32 opacity-0 pointer-events-none">
        <div class="bg-neutral text-neutral-content shadow-2xl rounded-full px-6 py-3 flex items-center gap-4 border border-white/10">
            <div class="font-bold text-sm whitespace-nowrap">
                <span id="selected-count" class="text-accent text-lg font-extrabold">0</span> seleccionados
            </div>

            <div class="h-6 w-px bg-white/20"></div>

            <div class="flex gap-2">
                <button type="button" onclick="applyBulkAction('close')" class="btn btn-sm btn-ghost hover:bg-white/10 text-white gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    Cerrar
                </button>

                <?php if ($isAdmin): ?>
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
    .then(response => {
        if (response.ok) {
            window.location.reload();
        } else {
            console.error('Error status:', response.status);
            alert('Error al procesar (Código ' + response.status + '). Revisa la consola.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión.');
    });
}
</script>