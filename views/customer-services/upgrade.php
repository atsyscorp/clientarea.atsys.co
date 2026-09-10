<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\CustomerServices $model */
/** @var app\models\Products[] $availablePlans */
/** @var float $currentPrice */
/** @var int $daysRemaining */
/** @var bool $isNearOrExpired */

$this->title = 'Mejorar Plan de Hosting: ' . $model->domain;
$this->params['breadcrumbs'][] = ['label' => 'Servicios', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->domain, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Mejorar Plan';

$currentProduct = $model->product;
?>

<div class="customer-services-upgrade fade-in max-w-5xl mx-auto pb-12">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-xs text-primary font-bold tracking-wider uppercase mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Ampliación de Capacidad y Recursos
            </div>
            <h1 class="text-3xl font-bold text-base-content">
                Mejorar Plan: <span class="text-primary"><?= Html::encode($model->domain) ?></span>
            </h1>
            <p class="text-base-content/60 text-sm mt-1">
                Selecciona el plan superior que mejor se adapte al crecimiento de tu proyecto o empresa.
            </p>
        </div>
        <div class="flex gap-2">
            <?= Html::a('← Volver al Servicio', ['view', 'id' => $model->id], ['class' => 'btn btn-ghost btn-sm']) ?>
        </div>
    </div>

    <!-- Resumen del Plan Actual -->
    <div class="card bg-base-100 shadow-lg border border-base-200 mb-8 overflow-hidden">
        <div class="bg-base-200/50 p-4 border-b border-base-200 flex flex-wrap justify-between items-center gap-3">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-primary/10 text-primary rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                    </svg>
                </div>
                <div>
                    <span class="text-xs opacity-60 block font-semibold">TU PLAN ACTUAL</span>
                    <span class="text-lg font-bold text-base-content"><?= Html::encode($currentProduct->name) ?></span>
                    <?php if (!empty($currentProduct->server_package)): ?>
                        <span class="badge badge-sm badge-ghost font-mono ml-2"><?= Html::encode($currentProduct->server_package) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center gap-6 text-sm">
                <div>
                    <span class="text-xs opacity-60 block">Precio Anual</span>
                    <span class="font-bold font-mono text-base-content"><?= Yii::$app->formatter->asCurrency($currentPrice) ?></span>
                </div>
                <div>
                    <span class="text-xs opacity-60 block">Vencimiento</span>
                    <span class="font-bold font-mono text-base-content"><?= Yii::$app->formatter->asDate($model->next_due_date, 'medium') ?></span>
                </div>
                <div>
                    <span class="text-xs opacity-60 block">Días Restantes</span>
                    <span class="badge badge-primary badge-outline font-mono font-bold"><?= $daysRemaining ?> días</span>
                </div>
            </div>
        </div>

        <!-- Banner Explicativo de Cobro -->
        <div class="p-4 bg-primary/5 text-xs text-base-content/80 flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <?php if ($isNearOrExpired): ?>
                    <p class="font-semibold text-primary mb-0.5">Renovación y Mejora Inmediata:</p>
                    <p>Como tu servicio está próximo a vencer (menos de 30 días), la mejora incluirá la renovación completa del nuevo plan por un año adicional a partir de tu fecha actual de vencimiento.</p>
                <?php else: ?>
                    <p class="font-semibold text-primary mb-0.5">Cobro Justo Prorrateado:</p>
                    <p>Solo pagas la diferencia de costo correspondiente a los <strong><?= $daysRemaining ?> días restantes</strong> de tu ciclo anual. Tu fecha de renovación se mantendrá intacta.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Título de Planes Disponibles -->
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-base-content">Elige tu Nuevo Plan</h2>
        <p class="text-sm opacity-60">El cambio de capacidad se procesa de forma instantánea en tu servidor una vez confirmado el pago.</p>
    </div>

    <?php if (empty($availablePlans)): ?>
        <div class="alert alert-info shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <h3 class="font-bold">¡Actualmente estás en el plan más alto disponible!</h3>
                <div class="text-xs">Si necesitas una configuración personalizada o servidor dedicado, contacta a nuestro equipo de soporte.</div>
            </div>
            <?= Html::a('Contactar Soporte', ['/tickets/create', 'service_id' => $model->id, 'subject' => 'Solicitud plan superior: ' . $model->domain], ['class' => 'btn btn-sm btn-ghost']) ?>
        </div>
    <?php else: ?>

        <!-- Grid de Planes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            <?php foreach ($availablePlans as $plan): 
                $targetPrice = $plan->price_renewal > 0 ? (float)$plan->price_renewal : (float)$plan->price;
                $priceDiff = max(0, $targetPrice - $currentPrice);

                if ($isNearOrExpired) {
                    $upgradeCost = $targetPrice;
                } else {
                    $upgradeCost = round(($priceDiff * $daysRemaining) / 365, 2);
                    if ($upgradeCost <= 0 && $priceDiff > 0) {
                        $upgradeCost = round($priceDiff * 0.10, 2);
                    }
                }

                $isSuperior = $targetPrice > $currentPrice;
            ?>
                <div class="card bg-base-100 shadow-xl border-2 <?= $isSuperior ? 'border-primary/40 hover:border-primary' : 'border-base-200' ?> transition-all duration-300 flex flex-col justify-between">
                    <div class="card-body p-6">
                        <!-- Cabecera del plan -->
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="card-title text-xl font-bold text-base-content"><?= Html::encode($plan->name) ?></h3>
                                <?php if (!empty($plan->server_package)): ?>
                                    <span class="badge badge-sm badge-secondary badge-outline font-mono mt-1">
                                        <?= Html::encode($plan->server_package) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($isSuperior): ?>
                                <span class="badge badge-primary text-xs font-bold text-white uppercase tracking-wider">
                                    Recomendado
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Descripción del plan -->
                        <div class="text-xs opacity-75 my-3 min-h-[40px] leading-relaxed">
                            <?= !empty($plan->description) ? nl2br(Html::encode($plan->description)) : 'Incluye mayor capacidad de almacenamiento, correos corporativos y mayor velocidad.' ?>
                        </div>

                        <div class="divider my-2"></div>

                        <!-- Precios del plan -->
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between text-xs opacity-60">
                                <span>Precio de lista anual:</span>
                                <span class="font-mono"><?= Yii::$app->formatter->asCurrency($targetPrice) ?></span>
                            </div>

                            <!-- Cálculo del Upgrade -->
                            <div class="bg-base-200 p-3 rounded-lg">
                                <div class="flex justify-between items-center text-xs opacity-70 mb-1">
                                    <span><?= $isNearOrExpired ? 'Costo Renovación:' : 'Diferencia Prorrateada:' ?></span>
                                    <span class="font-mono font-semibold"><?= Yii::$app->formatter->asCurrency($upgradeCost) ?></span>
                                </div>
                                <div class="flex justify-between items-center pt-1 border-t border-base-300">
                                    <span class="font-bold text-xs uppercase text-primary">Pagas Hoy:</span>
                                    <span class="text-xl font-bold font-mono text-primary">
                                        <?= Yii::$app->formatter->asCurrency($upgradeCost) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de acción -->
                        <?= Html::beginForm(['upgrade', 'id' => $model->id], 'post', [
                            'onsubmit' => "return confirm('¿Confirmas que deseas mejorar al plan " . Html::encode($plan->name) . " por " . Yii::$app->formatter->asCurrency($upgradeCost) . "? Se generará la orden de pago correspondiente.');"
                        ]) ?>
                            <input type="hidden" name="target_product_id" value="<?= $plan->id ?>">
                            <button type="submit" class="btn btn-primary btn-block gap-2 shadow-md text-white font-bold">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Mejorar a este Plan
                            </button>
                        <?= Html::endForm() ?>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <!-- Preguntas Frecuentes / Garantías -->
    <div class="card bg-base-100 shadow-md border border-base-200 p-6">
        <h3 class="text-lg font-bold text-base-content mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Preguntas Frecuentes sobre la Mejora de Plan
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs text-base-content/80">
            <div>
                <p class="font-bold text-sm text-base-content mb-1">¿Se perderán mis correos, archivos o bases de datos?</p>
                <p>No. El cambio de plan es 100% transparente. Se aumentan las cuotas de almacenamiento y los recursos en tu panel de control existente sin interrumpir el funcionamiento de tu sitio ni tocar tus datos.</p>
            </div>
            <div>
                <p class="font-bold text-sm text-base-content mb-1">¿Cuándo se hace efectiva la ampliación?</p>
                <p>Inmediatamente. Tan pronto como la orden sea marcada como pagada (automáticamente vía pasarela Wompi / PayPal o manualmente por administración), el sistema aplica los nuevos límites en el servidor físico.</p>
            </div>
            <div>
                <p class="font-bold text-sm text-base-content mb-1">¿Qué medios de pago puedo utilizar?</p>
                <p>Al confirmar el plan serás dirigido al resumen de orden donde podrás pagar con Tarjeta de Crédito, PSE, Nequi, Bancolombia (Wompi), PayPal o mediante transferencia bancaria.</p>
            </div>
            <div>
                <p class="font-bold text-sm text-base-content mb-1">¿Necesitas una capacidad específica no listada?</p>
                <p>Si necesitas un paquete a medida o migración a un VPS dedicado, puedes abrir un ticket con el área de soporte para cotizar una solución personalizada.</p>
            </div>
        </div>
    </div>

</div>
