<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Reporte de Satisfacción de Clientes';

// Preparamos los datos de PHP para inyectarlos en el JavaScript de la gráfica
$chartLabels = [];
$chartData = [];
foreach ($ratingCounts as $row) {
    $chartLabels[] = $row['rating_service'] . ' Estrellas'; 
    $chartData[] = (int)$row['count'];
}
?>

<div class="feedback-index">
    <h1 class="text-3xl font-bold mb-6"><?= Html::encode($this->title) ?></h1>

    <div class="stats shadow w-full mb-8">
        <div class="stat">
            <div class="stat-figure text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </div>
            <div class="stat-title">Total Evaluaciones</div>
            <div class="stat-value text-primary"><?= $totalReviews ?></div>
            <div class="stat-desc">Respuestas recibidas</div>
        </div>
        
        <div class="stat">
            <div class="stat-figure text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
            </div>
            <div class="stat-title">Promedio General</div>
            <div class="stat-value text-secondary"><?= $averageRating ?> / 5</div>
            <div class="stat-desc">Calificación global de servicio</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-lg">Distribución de Calificaciones</h2>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="feedbackChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200 lg:col-span-2">
            <div class="card-body overflow-x-auto">
                <h2 class="card-title text-lg mb-4">Detalle de Respuestas</h2>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'tableOptions' => ['class' => 'table table-zebra w-full'],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        
                        // Ajusta estas columnas según los campos reales de tu modelo
                        'ticket_id',
                        'rating_service',
                        'comments:ntext',
                        [
                            'attribute' => 'created_at',
                            'format' => ['datetime', 'php:d M Y, h:i a'],
                        ],

                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view}',
                            'buttons' => [
                                'view' => function ($url, $model, $key) {
                                    $estrellas = str_repeat('⭐', $model->rating_service) . str_repeat('☆', 5 - $model->rating_service);
                                    $resuelto = $model->is_resolved ? '<span class="text-success font-bold">Sí</span>' : '<span class="text-error font-bold">No</span>';
                                    
                                    return Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>', '#', [ // Tu SVG del ojito aquí
                                        'class' => 'btn btn-sm btn-ghost btn-square text-info view-feedback-btn',
                                        'title' => 'Ver Detalles',
                                        'data-ticket' => $model->ticket_id,
                                        'data-date' => Yii::$app->formatter->asDatetime($model->created_at, 'php:d M Y, h:i a'),
                                        'data-email' => $model->client_email ? Html::encode($model->client_email) : 'Anónimo',
                                        'data-ip' => Html::encode($model->ip_address),
                                        'data-rating' => $estrellas,
                                        'data-nps' => $model->nps_score,
                                        'data-ces' => $model->effort_score,
                                        'data-resolved' => $resuelto,
                                        'data-comment' => Html::encode($model->comments),
                                    ]);
                                },
                            ],
                        ],
                    ],
                ]); ?>
            </div>
        </div>

    </div>
</div>

<dialog id="feedback_modal" class="modal modal-bottom sm:modal-middle">
  <div class="modal-box p-6 sm:p-8">
    <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4 text-base-content/50 hover:text-base-content">✕</button></form>
    
    <h3 class="font-bold text-2xl mb-6 text-base-content flex items-center gap-2">
        Detalle de Evaluación
    </h3>
    
    <div class="space-y-4">
        
        <div class="bg-base-200/50 p-4 rounded-lg border border-base-300">
            <h4 class="text-xs font-bold uppercase text-base-content/50 mb-3 tracking-wider">Datos del Cliente</h4>
            
            <div class="flex justify-between items-center mb-2">
                <span class="text-base-content/70 font-semibold text-sm">Email:</span>
                <span id="modal-email" class="font-medium text-sm"></span>
            </div>
            <div class="flex justify-between items-center mb-2">
                <span class="text-base-content/70 font-semibold text-sm">Ticket Asociado:</span>
                <span id="modal-ticket" class="badge badge-neutral shadow-sm"></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-base-content/70 font-semibold text-sm">Fecha e IP:</span>
                <span class="text-xs text-base-content/60"><span id="modal-date"></span> | <span id="modal-ip"></span></span>
            </div>
        </div>

        <div class="bg-base-200/50 p-4 rounded-lg border border-base-300">
            <h4 class="text-xs font-bold uppercase text-base-content/50 mb-3 tracking-wider">Métricas de Servicio</h4>
            
            <div class="flex justify-between items-center mb-3">
                <span class="text-base-content/70 font-semibold text-sm">CSAT (Calificación):</span>
                <span id="modal-rating" class="text-lg tracking-widest text-warning drop-shadow-sm"></span>
            </div>
            <div class="flex justify-between items-center mb-3">
                <span class="text-base-content/70 font-semibold text-sm">NPS (Recomendación):</span>
                <span id="modal-nps" class="font-bold text-lg"></span> <span class="text-xs text-base-content/50">/ 10</span>
            </div>
            <div class="flex justify-between items-center mb-3">
                <span class="text-base-content/70 font-semibold text-sm">CES (Esfuerzo):</span>
                <span id="modal-ces" class="font-bold text-lg"></span> <span class="text-xs text-base-content/50">/ 5</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-base-content/70 font-semibold text-sm">¿Problema Resuelto?:</span>
                <span id="modal-resolved" class="text-sm"></span>
            </div>
        </div>

        <div class="pt-2">
            <span class="text-base-content/70 font-semibold block mb-2 text-sm">Comentarios del cliente:</span>
            <div class="p-4 bg-base-200/50 border border-base-300 rounded-lg italic text-base-content min-h-[5rem] shadow-inner text-sm" id="modal-comment">
            </div>
        </div>

    </div>

    <div class="modal-action mt-6 flex justify-center">
      <form method="dialog" class="w-full">
        <button class="btn btn-primary w-full rounded-lg">Cerrar Detalle</button>
      </form>
    </div>
  </div>
</dialog>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('feedbackChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut', // Puedes cambiarlo a 'bar' o 'pie'
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Cantidad de Votos',
                data: <?= json_encode($chartData) ?>,
                backgroundColor: [
                    '#36D399', // Éxito (DaisyUI)
                    '#3ABFF8', // Info
                    '#FBBD23', // Warning
                    '#F87272', // Error
                    '#828DF8'  // Morado
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});

// LÓGICA DEL MODAL DE FEEDBACK
const modal = document.getElementById('feedback_modal');
    
document.body.addEventListener('click', function(e) {
    const btn = e.target.closest('.view-feedback-btn');
    if (btn) {
        e.preventDefault();
        
        // Inyectamos datos del cliente
        document.getElementById('modal-email').innerText = btn.getAttribute('data-email');
        document.getElementById('modal-ticket').innerText = btn.getAttribute('data-ticket') || 'N/A';
        document.getElementById('modal-date').innerText = btn.getAttribute('data-date');
        document.getElementById('modal-ip').innerText = btn.getAttribute('data-ip');
        
        // Inyectamos métricas
        document.getElementById('modal-rating').innerHTML = btn.getAttribute('data-rating');
        document.getElementById('modal-nps').innerText = btn.getAttribute('data-nps') || '-';
        document.getElementById('modal-ces').innerText = btn.getAttribute('data-ces') || '-';
        document.getElementById('modal-resolved').innerHTML = btn.getAttribute('data-resolved');
        
        // Inyectamos comentarios
        const comment = btn.getAttribute('data-comment');
        document.getElementById('modal-comment').innerText = comment ? comment : 'Sin comentarios adicionales.';
        
        modal.showModal();
    }
});
</script>