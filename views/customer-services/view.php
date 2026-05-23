<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\CustomerServices $model */

$this->title = 'Detalle de Hosting: ' . $model->domain;
$this->params['breadcrumbs'][] = ['label' => 'Servicios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->domain;

$server = $model->server;
$product = $model->product;
?>
<div class="customer-services-view fade-in">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-3xl font-bold text-base-content"><?= Html::encode($model->domain) ?></h1>
                <?= $model->getStatusHtml() ?>
            </div>
            <p class="text-base-content/60 mt-1">
                Plan: <span class="font-semibold text-primary"><?= Html::encode($product->name) ?></span>
                <?php if ($server): ?>
                    | Servidor: <span class="font-semibold"><?= Html::encode($server->name) ?> (<?= Html::encode($server->type) ?>)</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="flex gap-2">
            <?= Html::a('← Volver', ['index'], ['class' => 'btn btn-ghost btn-sm']) ?>
            <?= Html::a('Soporte', ['/tickets/create', 'service_id' => $model->id, 'subject' => 'Consulta sobre: ' . ($model->domain ?? $model->product->name)], ['class' => 'btn btn-outline btn-sm']) ?>
        </div>
    </div>

    <!-- Live Resource Monitoring Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- CPU Card -->
        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-6 flex flex-col items-center text-center relative overflow-hidden">
                <div class="absolute top-2 left-2 text-xs font-bold opacity-50 uppercase">CPU</div>
                <div class="radial-progress text-primary mb-2 transition-all duration-500" id="cpu-radial" style="--value:0; --size:6rem; --thickness: 8px;">
                    <span class="font-mono text-xl font-bold" id="cpu-text">0%</span>
                </div>
                <span class="text-xs opacity-60 mt-1" id="cpu-status">Estabilizando...</span>
            </div>
        </div>

        <!-- RAM Card -->
        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-6 flex flex-col items-center text-center relative overflow-hidden">
                <div class="absolute top-2 left-2 text-xs font-bold opacity-50 uppercase">RAM</div>
                <div class="radial-progress text-secondary mb-2 transition-all duration-500" id="ram-radial" style="--value:0; --size:6rem; --thickness: 8px;">
                    <span class="font-mono text-xl font-bold" id="ram-text">0%</span>
                </div>
                <span class="text-xs opacity-60 mt-1" id="ram-status">Estabilizando...</span>
            </div>
        </div>

        <!-- Disk Card -->
        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-6 flex flex-col items-center text-center relative overflow-hidden">
                <div class="absolute top-2 left-2 text-xs font-bold opacity-50 uppercase">Disco Duro</div>
                <div class="radial-progress text-accent mb-2 transition-all duration-500" id="disk-radial" style="--value:0; --size:6rem; --thickness: 8px;">
                    <span class="font-mono text-xl font-bold" id="disk-text">0%</span>
                </div>
                <span class="text-xs opacity-60 mt-1" id="disk-status">Estabilizando...</span>
            </div>
        </div>

        <!-- Bandwidth Card -->
        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-6 flex flex-col items-center text-center relative overflow-hidden">
                <div class="absolute top-2 left-2 text-xs font-bold opacity-50 uppercase">Ancho de Banda</div>
                <div class="radial-progress text-warning mb-2 transition-all duration-500" id="bandwidth-radial" style="--value:0; --size:6rem; --thickness: 8px;">
                    <span class="font-mono text-xl font-bold" id="bandwidth-text">0%</span>
                </div>
                <span class="text-xs opacity-60 mt-1" id="bandwidth-status">Estabilizando...</span>
            </div>
        </div>
    </div>

    <!-- Live Performance Chart & Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Live History Chart Card -->
        <div class="lg:col-span-2 card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="card-title text-lg flex items-center gap-2">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                        Monitoreo de Recursos en Tiempo Real
                    </h2>
                    <span class="badge badge-outline text-xs" id="last-update">Actualizando...</span>
                </div>
                <div class="w-full" style="position: relative; height:320px;">
                    <canvas id="liveChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Server credentials card -->
        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-6 flex flex-col justify-between">
                <div>
                    <h2 class="card-title text-lg border-b border-base-200 pb-3 mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
                        Credenciales del Servicio
                    </h2>

                    <div class="space-y-4">
                        <div class="form-control w-full">
                            <label class="label p-1"><span class="label-text text-xs opacity-60">Dominio / URL</span></label>
                            <div class="flex items-center gap-2 justify-between bg-base-200 p-2 rounded-lg text-sm">
                                <span class="font-mono truncate select-all"><?= Html::encode($model->domain) ?></span>
                                <a href="http://<?= Html::encode($model->domain) ?>" target="_blank" class="btn btn-ghost btn-square btn-xs text-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                </a>
                            </div>
                        </div>

                        <?php if ($model->username_service): ?>
                        <div class="form-control w-full">
                            <label class="label p-1"><span class="label-text text-xs opacity-60">Usuario del Panel</span></label>
                            <div class="join w-full">
                                <input type="text" value="<?= Html::encode($model->username_service) ?>" class="input input-bordered input-sm join-item w-full bg-base-200 font-mono text-sm" readonly id="usr-inp" />
                                <button class="btn btn-square btn-sm join-item" onclick="navigator.clipboard.writeText(document.getElementById('usr-inp').value)" type="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($model->password_service): ?>
                        <div class="form-control w-full">
                            <label class="label p-1"><span class="label-text text-xs opacity-60">Contraseña del Panel</span></label>
                            <div class="join w-full">
                                <input type="password" value="<?= Html::encode($model->password_service) ?>" class="input input-bordered input-sm join-item w-full bg-base-200 font-mono text-sm" readonly id="pwd-inp" />
                                <button class="btn btn-square btn-sm join-item text-base-content/60" onclick="togglePasswordVisibility()" type="button">
                                    <svg id="pwd-eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </button>
                                <button class="btn btn-square btn-sm join-item" onclick="navigator.clipboard.writeText(document.getElementById('pwd-inp').value)" type="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="divider my-2"></div>

                        <div class="flex justify-between text-xs opacity-60">
                            <span>Próxima Renovación:</span>
                            <span class="font-semibold font-mono"><?= Yii::$app->formatter->asDate($model->next_due_date, 'long') ?></span>
                        </div>
                    </div>
                </div>

                <?php if ($server && $server->hostname): ?>
                <div class="mt-6">
                    <a href="https://<?= Html::encode($server->hostname) ?>:<?= $server->type == 'cyberpanel' ? '8090' : '10000' ?>" target="_blank" class="btn btn-primary btn-block btn-sm shadow-md gap-2">
                        Ir al Panel de Control
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function togglePasswordVisibility() {
    const input = document.getElementById('pwd-inp');
    const icon = document.getElementById('pwd-eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"; />';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize Chart
    const canvas = document.getElementById('liveChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    
    // Theme aware color detection
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark' || document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
    const textColor = isDark ? '#A6ADBB' : '#4B5563';

    const liveChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [], // Timestamps
            datasets: [
                {
                    label: 'CPU (%)',
                    data: [],
                    borderColor: '#4f46e5', // Indigo
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2
                },
                {
                    label: 'RAM (%)',
                    data: [],
                    borderColor: '#ec4899', // Pink
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { color: textColor }
                }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: { color: textColor }
                },
                y: {
                    min: 0,
                    max: 100,
                    grid: { color: gridColor },
                    ticks: { color: textColor }
                }
            }
        }
    });

    // 2. Fetch and Update Stats loop
    function updateMetrics() {
        const statsUrl = '<?= \yii\helpers\Url::to(['get-stats', 'id' => $model->id]) ?>';
        
        fetch(statsUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.metrics) {
                    const metrics = data.metrics;
                    
                    // Update Radials
                    updateRadial('cpu', metrics.cpu);
                    updateRadial('ram', metrics.ram);
                    updateRadial('disk', metrics.disk);
                    updateRadial('bandwidth', metrics.bandwidth);
                    
                    // Update Chart datasets
                    const timeLabel = metrics.timestamp;
                    liveChart.data.labels.push(timeLabel);
                    liveChart.data.datasets[0].data.push(metrics.cpu);
                    liveChart.data.datasets[1].data.push(metrics.ram);
                    
                    // Limit data points to show only the last 12 points
                    if (liveChart.data.labels.length > 12) {
                        liveChart.data.labels.shift();
                        liveChart.data.datasets[0].data.shift();
                        liveChart.data.datasets[1].data.shift();
                    }
                    
                    liveChart.update();
                    
                    // Update header timestamp
                    const updEl = document.getElementById('last-update');
                    if (updEl) {
                        updEl.innerText = 'Actualizado: ' + timeLabel;
                    }
                }
            })
            .catch(error => console.error('Error fetching resource metrics:', error));
    }

    function updateRadial(type, val) {
        const radialEl = document.getElementById(type + '-radial');
        const textEl = document.getElementById(type + '-text');
        const statusEl = document.getElementById(type + '-status');
        
        if (radialEl && textEl) {
            // Apply numeric formatting
            const displayVal = typeof val === 'number' ? val.toFixed(1) + '%' : val + '%';
            
            // Set css custom property variable --value for radial progress
            const intVal = Math.round(parseFloat(val));
            radialEl.style.setProperty('--value', intVal);
            textEl.innerText = displayVal;
            
            // Adjust classes / status depending on thresholds
            radialEl.className = radialEl.className.replace(/text-(success|warning|error|primary|secondary|accent)/g, '');
            let colorClass = 'text-primary';
            let statusText = 'Normal';
            
            if (type === 'cpu') {
                colorClass = 'text-primary';
                if (intVal > 80) { colorClass = 'text-error'; statusText = 'Crítico'; }
                else if (intVal > 60) { colorClass = 'text-warning'; statusText = 'Moderado'; }
                else { statusText = 'Óptimo'; }
            } else if (type === 'ram') {
                colorClass = 'text-secondary';
                if (intVal > 85) { colorClass = 'text-error'; statusText = 'Crítico'; }
                else if (intVal > 65) { colorClass = 'text-warning'; statusText = 'Elevado'; }
                else { statusText = 'Estable'; }
            } else if (type === 'disk') {
                colorClass = 'text-accent';
                if (intVal > 90) { colorClass = 'text-error'; statusText = 'Lleno'; }
                else if (intVal > 75) { colorClass = 'text-warning'; statusText = 'Alto'; }
                else { statusText = 'Saludable'; }
            } else if (type === 'bandwidth') {
                colorClass = 'text-warning';
                if (intVal > 80) { colorClass = 'text-error'; statusText = 'Excedido'; }
                else { statusText = 'Adecuado'; }
            }
            
            radialEl.classList.add(colorClass);
            if (statusEl) {
                statusEl.innerText = statusText;
                // Add status coloring class too
                statusEl.className = 'text-xs mt-1 font-bold ' + colorClass;
            }
        }
    }

    // Run first update immediately, then queue every 3 seconds
    updateMetrics();
    setInterval(updateMetrics, 3000);
});
</script>
