<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\OrdersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Órdenes de Pago';
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
?>
<div class="orders-index container mx-auto px-4 py-8">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-base-content"><?= Html::encode($this->title) ?></h1>
    </div>

    <div class="card bg-base-100 shadow-xl border border-base-200">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'id' => 'orders-grid',
                    'tableOptions' => ['class' => 'table table-zebra w-full'],
                    'summaryOptions' => ['class' => 'p-4 text-sm text-base-content/70'],
                    'layout' => "{items}\n<div class='p-4 border-t border-base-200 flex justify-between items-center'>{summary}\n{pager}</div>",
                    'pager' => [
                        'options' => ['class' => 'join'],
                        'linkOptions' => ['class' => 'join-item btn btn-sm'],
                        'activePageCssClass' => 'btn-active',
                        'disabledPageCssClass' => 'btn-disabled',
                        'prevPageLabel' => '«',
                        'nextPageLabel' => '»',
                    ],
                    'columns' => [
                        [
                            'class' => 'yii\grid\CheckboxColumn',
                            'name' => 'selection',
                            'cssClass' => 'text-center',
                        ],
                        [
                            'attribute' => 'id',
                            'headerOptions' => ['class' => 'w-16'],
                        ],
                        [
                            'attribute' => 'code',
                            'format' => 'raw',
                            'value' => function($model) {
                                return Html::a($model->code, ['view', 'id' => $model->id], ['class' => 'link link-primary font-mono']);
                            }
                        ],
                        [
                            'attribute' => 'customer_id',
                            'label' => 'Cliente',
                            'value' => function($model) {
                                return $model->customer ? $model->customer->business_name . ' (' . $model->customer->email . ')' : 'N/A';
                            }
                        ],
                        [
                            'attribute' => 'total',
                            'format' => 'raw',
                            'value' => function($model) {
                                $total = Yii::$app->formatter->asCurrency($model->total) . ' ' . $model->currency;
                                if ($model->currency === 'USD' && $model->total_usd) {
                                    $total .= ' <br><span class="text-xs text-base-content/60">(' . Yii::$app->formatter->asCurrency($model->total_usd) . ' USD)</span>';
                                }
                                return $total;
                            }
                        ],
                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'filter' => [0 => 'Pendiente', 1 => 'Pagado', 2 => 'Activo', 3 => 'Cancelado'],
                            'value' => function($model) {
                                $badges = [
                                    0 => '<span class="badge badge-warning">Pendiente</span>',
                                    1 => '<span class="badge badge-success text-white">Pagado</span>',
                                    2 => '<span class="badge badge-info text-white">Activo</span>',
                                    3 => '<span class="badge badge-error text-white">Cancelado</span>',
                                ];
                                return $badges[$model->status] ?? '<span class="badge">Desconocido</span>';
                            }
                        ],
                        [
                            'attribute' => 'payment_method',
                            'label' => 'Método',
                            'value' => function($model) {
                                return $model->payment_method ?: '-';
                            }
                        ],
                        [
                            'attribute' => 'created_at',
                            'format' => ['date', 'php:d/m/Y H:i'],
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view}',
                            'buttons' => [
                                'view' => function ($url, $model) {
                                    return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>', 
                                    ['view', 'id' => $model->id], 
                                    ['class' => 'btn btn-sm btn-ghost btn-circle', 'title' => 'Ver Detalles']);
                                }
                            ]
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </div>

    <div id="bulk-actions-bar" class="fixed bottom-10 left-1/2 transform -translate-x-1/2 z-50 transition-all duration-300 translate-y-32 opacity-0 pointer-events-none">
        <div class="bg-neutral text-neutral-content shadow-2xl rounded-full px-6 py-3 flex items-center gap-4 border border-white/10">
            <div class="font-bold text-sm whitespace-nowrap">
                <span id="selected-count" class="text-accent text-lg font-extrabold">0</span> seleccionados
            </div>

            <div class="h-6 w-px bg-white/20"></div>

            <div class="flex gap-2">
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
    function updateFloatingBar() {
        const bar = document.getElementById('bulk-actions-bar');
        const countSpan = document.getElementById('selected-count');
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

    const grid = document.getElementById('orders-grid');
    if (grid) {
        grid.addEventListener('change', function(e) {
            if (e.target.type === 'checkbox') {
                setTimeout(updateFloatingBar, 10);
            }
        });
    }
});

function applyBulkAction(actionType) {
    const checkboxes = document.querySelectorAll('input[name="selection[]"]:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);

    if (ids.length === 0) return;

    const confirmMessage = '⚠️ ¿Estás seguro de ELIMINAR estas ' + ids.length + ' órdenes permanentemente?';

    if (!confirm(confirmMessage)) return;

    const formData = new FormData();
    ids.forEach(id => formData.append('ids[]', id));
    formData.append('action', actionType);
    
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfParamMeta = document.querySelector('meta[name="csrf-param"]');
    if (csrfTokenMeta && csrfParamMeta) {
        formData.append(csrfParamMeta.getAttribute('content'), csrfTokenMeta.getAttribute('content'));
    }

    fetch('<?= \yii\helpers\Url::to(['/orders/bulk']) ?>', {
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
</script>
