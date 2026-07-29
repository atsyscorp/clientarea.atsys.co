<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */
/* @var $gateway string */
/* @var $wompi array|null */
/* @var $paypalUsd array|null */
/* @var $paypalEur array|null */
/* @var $totalUsd float */
/* @var $totalEur float */
/* @var $exchangeRateUsd float */
/* @var $exchangeRateEur float */

$this->title = 'Confirmación de pago - Orden ' . $model->code;

// Lógica rápida para definir totales y sufijos según la moneda
$isForeign = in_array($gateway, ['USD', 'EUR']);
$isUsd = $gateway === 'USD';
$isEur = $gateway === 'EUR';
$currencySuffix = $isForeign ? ' ' . $gateway : ' COP';
$displayTotal = $isForeign ? ($model->total_usd ?? $model->total) : $model->total;

// Obtener el Client ID de PayPal para evaluar si está configurado
$paypalClientId = Yii::$app->params['paypalClientId'] ?? '';
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
?>

<div class="container mx-auto max-w-3xl">

    <?php if ($isAdmin): ?>
        <div class="card bg-base-100 shadow-xl mb-6 border-2 border-warning/50">
            <div class="card-body bg-warning/5">
                <div class="flex items-center justify-between border-b border-warning/20 pb-3">
                    <h3 class="card-title text-lg text-warning-content flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Panel de Administración de la Orden
                    </h3>
                    <span class="badge badge-warning text-xs font-mono font-bold">SOLO ADMIN</span>
                </div>

                <form action="<?= Url::to(['orders/change-status', 'id' => $model->id]) ?>" method="POST" class="mt-3">
                    <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>" />

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="label p-1 font-bold text-xs">Estado del Pedido</label>
                            <select name="status" class="select select-bordered select-sm w-full font-semibold">
                                <option value="0" <?= $model->status == 0 ? 'selected' : '' ?>>⚠️ Pendiente (0)</option>
                                <option value="1" <?= $model->status == 1 ? 'selected' : '' ?>>✅ Pagado (1)</option>
                                <option value="2" <?= $model->status == 2 ? 'selected' : '' ?>>🔷 Activo (2)</option>
                                <option value="3" <?= $model->status == 3 ? 'selected' : '' ?>>❌ Cancelado (3)</option>
                            </select>
                        </div>

                        <div>
                            <label class="label p-1 font-bold text-xs">Método de Pago</label>
                            <input type="text" name="payment_method" value="<?= Html::encode($model->payment_method ?: 'Manual (Admin)') ?>" class="input input-bordered input-sm w-full" placeholder="Ej: Wompi / Transferencia / Nequi" />
                        </div>

                        <div>
                            <label class="label p-1 font-bold text-xs">Referencia de Transacción</label>
                            <input type="text" name="transaction_ref" value="<?= Html::encode($model->transaction_ref ?: '') ?>" class="input input-bordered input-sm w-full" placeholder="Ej: REF-987654" />
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col md:flex-row justify-between items-center gap-4 pt-2 border-t border-base-200">
                        <label class="label cursor-pointer justify-start gap-2 bg-base-100 p-2 rounded-lg border border-base-200 w-full md:w-auto">
                            <input type="checkbox" name="execute_provisioning" value="1" class="checkbox checkbox-primary checkbox-sm" <?= $model->status == 0 ? 'checked' : '' ?> />
                            <span class="label-text font-semibold text-xs">
                                🚀 Ejecutar aprovisionamiento automático y notificar al cliente (si pasa a Pagado).
                            </span>
                        </label>

                        <button type="submit" class="btn btn-warning btn-sm w-full md:w-auto font-bold gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Actualizar Estado
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card bg-base-100 shadow-xl mb-6 border border-base-200">
        <div class="card-body">

            <div class="flex justify-between items-center mb-4 border-b border-base-200 pb-4">
                <h2 class="card-title text-2xl">Resumen de Compra</h2>
                <div class="badge badge-outline font-mono"><?= $model->code ?></div>
            </div>

            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr class="text-base-content/70 border-b border-base-200">
                            <th class="pl-0">Descripción del Servicio</th>
                            <th class="text-right pr-0">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($model->orderItems as $item): ?>
                            <tr>
                                <td class="pl-0 py-4">
                                    <div class="font-bold text-lg text-primary">
                                        <?= Html::encode($item->service_name) ?>
                                    </div>

                                    <?php if (!empty($item->domain_name)): ?>
                                        <div class="text-sm opacity-70 flex items-center gap-1 mt-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S12 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S12 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                                            </svg>
                                            <?= Html::encode($item->domain_name) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($item->action_type == 'restore'): ?>
                                        <span class="badge badge-error badge-xs mt-2 text-white">Restauración (+Cargo
                                            Extra)</span>
                                    <?php elseif ($item->action_type == 'renew'): ?>
                                        <span class="badge badge-success badge-outline badge-xs mt-2">Renovación</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-right pr-0 font-mono text-lg align-top pt-4">
                                    <?php
                                    // CORRECCIÓN VISUAL: Cálculo seguro del ítem
                                    $itemTotal = $item->total;
                                    if ($isForeign) {
                                        $itemTotal = $item->total_usd ?? ($model->exchange_rate ? round($item->total / $model->exchange_rate, 2) : $item->total);
                                    }

                                    echo Yii::$app->formatter->asCurrency($itemTotal) . $currencySuffix;
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                    <tfoot class="border-t-2 border-base-300">
                        <tr>
                            <td class="pl-0 text-right text-xl font-bold pt-4 text-base-content">TOTAL A PAGAR</td>
                            <td class="pr-0 text-right text-2xl font-black text-primary pt-4">
                                <span id="total-cop-display" class="total-display"><?= Yii::$app->formatter->asCurrency($model->total) ?> COP</span>
                                <span id="total-usd-display" class="total-display hidden"><?= Yii::$app->formatter->asCurrency($totalUsd) ?> USD</span>
                                <span id="total-eur-display" class="total-display hidden"><?= Yii::$app->formatter->asCurrency($totalEur) ?> EUR</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <?php if ($model->status == 0 && !empty($paypalClientId)): // Solo si está PENDIENTE y PayPal está configurado ?>
        <!-- Selector de moneda / Pasarelas de Pago -->
        <div class="tabs tabs-boxed mb-6 justify-center bg-base-200 p-1 rounded-xl max-w-lg mx-auto no-print">
            <a id="tab-cop" class="tab tab-lg font-bold px-4 md:px-8 transition-all duration-200">🇨🇴 COP (Wompi, QR)</a>
            <a id="tab-usd" class="tab tab-lg font-bold px-4 md:px-8 transition-all duration-200">🇺🇸 USD (PayPal)</a>
            <a id="tab-eur" class="tab tab-lg font-bold px-4 md:px-8 transition-all duration-200">🇪🇺 EUR (PayPal)</a>
        </div>
    <?php endif; ?>

    <div class="card bg-base-100 shadow-xl mt-6 border border-base-200 relative z-0">
        <div class="card-body">
            <?php if ($model->status == 0): // Solo si está PENDIENTE ?>

                <div id="payment-cop-sec" class="payment-section">
                    <h3 class="font-bold text-lg mb-2">Finalizar Pago Seguro en COP</h3>
                    <p class="text-sm mb-6 opacity-70">
                        Selecciona tu método de pago preferido para transacciones en pesos colombianos (COP).
                    </p>

                    <?php if (isset($wompi)): ?>
                        <!-- Subselector para COP (Wompi vs QR) -->
                        <div class="tabs tabs-boxed mb-6 justify-center bg-base-200/50 p-1 rounded-xl max-w-md mx-auto no-print">
                            <a id="tab-cop-wompi" class="tab tab-md font-bold px-4 md:px-6 transition-all duration-200">💳 Pago en Línea (Wompi)</a>
                            <a id="tab-cop-qr" class="tab tab-md font-bold px-4 md:px-6 transition-all duration-200">📲 Transferencia / QR</a>
                        </div>

                        <!-- Sección de Wompi -->
                        <div id="cop-wompi-sec" class="cop-sub-section transition-all duration-300">
                            <?php if (isset($isWeekend) && $isWeekend): ?>
                                <div class="alert alert-info shadow-sm mb-6 bg-info/10 border border-info/20 text-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5 text-info" fill="none" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <h4 class="font-bold">Aviso de fin de semana</h4>
                                        <div>Los pagos realizados por Wompi durante el fin de semana se concilian el próximo día hábil, lo cual puede retrasar la activación automática de tu servicio. Si requieres activación inmediata hoy mismo, por favor usa el método <strong>Transferencia / QR</strong>.</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <p class="text-sm mb-6 opacity-70">
                                Aceptamos tarjetas de crédito/débito, PSE, Nequi y Bancolombia.<br>
                                La transacción es procesada de forma segura por Wompi.
                            </p>

                            <!-- FORMULARIO WOMPI (COP) -->
                            <form action="https://checkout.wompi.co/p/" method="GET">
                                <input type="hidden" name="public-key" value="<?= $wompi['publicKey'] ?>" />
                                <input type="hidden" name="currency" value="<?= $wompi['currency'] ?>" />
                                <input type="hidden" name="amount-in-cents" value="<?= $wompi['amountInCents'] ?>" />
                                <input type="hidden" name="reference" value="<?= $wompi['reference'] ?>" />
                                <input type="hidden" name="signature:integrity" value="<?= $wompi['signature'] ?>" />
                                <input type="hidden" name="redirect-url" value="<?= $wompi['redirectUrl'] ?>" />
                                <input type="hidden" name="customer-data:email" value="<?= $model->customer->email ?>" />
                                <input type="hidden" name="customer-data:full-name" value="<?= $model->customer->business_name ?>" />
                                <input type="hidden" name="customer-data:phone-number" value="<?= $model->customer->primary_phone ?>" />
                                <input type="hidden" name="customer-data:legal-id" value="<?= $model->customer->document_number ?>" />
                                <input type="hidden" name="customer-data:legal-id-type" value="CC" />

                                <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg animate-pulse gap-2 text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                    </svg>
                                    Pagar <?= Yii::$app->formatter->asCurrency($model->total) ?> COP con Wompi
                                </button>
                            </form>

                            <div class="alert alert-success/10 bg-success/5 border border-success/20 shadow-sm mt-4 p-3 rounded-lg text-xs flex items-center gap-3 text-success">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5 text-success" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <strong>Sincronización en segundo plano:</strong> Si completas tu pago y cierras la ventana, Wompi notificará a la plataforma para procesar tu orden automáticamente.
                                </div>
                            </div>
                        </div>

                        <!-- Sección de Transferencia / QR -->
                        <div id="cop-qr-sec" class="cop-sub-section transition-all duration-300">
                            <?php if (isset($isWeekend) && $isWeekend): ?>
                                <div class="alert alert-warning shadow-lg mb-6 bg-warning/10 border border-warning/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6 text-warning" fill="none"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div>
                                        <h3 class="font-bold">Aviso de fin de semana</h3>
                                        <div class="text-xs">Para garantizar la activación inmediata de tu servicio durante el fin de semana, por favor realiza una transferencia directa escaneando el código QR.</div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info shadow-sm mb-6 bg-info/10 border border-info/20 text-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5 text-info" fill="none" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <h4 class="font-bold">Transferencia Directa</h4>
                                        <div>Puedes realizar una transferencia directa para registrar tu pago de forma manual. Una vez transferido, envíanos el comprobante a través del botón inferior.</div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-base-50 p-6 rounded-2xl border border-base-200">
                                <div class="flex flex-col justify-center items-center">
                                    <h4 class="font-bold text-lg text-primary mb-4">Escanea para pagar</h4>
                                    
                                    <div class="relative group inline-block cursor-pointer">
                                        <a href="<?= Yii::getAlias('@web') ?>/images/qr-atsys.jpg" class="glightbox block" data-title="QR ATSYS" data-description="Escanea para pagar">
                                            <img src="<?= Yii::getAlias('@web') ?>/images/qr-atsys.jpg" alt="Código QR ATSYS"
                                                class="w-48 h-48 object-cover rounded-xl shadow-md border-2 border-primary/20 transition-all duration-300 group-hover:brightness-50">
                                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="w-12 h-12 drop-shadow-md">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" />
                                                </svg>
                                            </div>
                                        </a>
                                    </div>
                                    
                                    <p class="text-xs text-center mt-3 opacity-70">Llave Bre-B 0090212060</p>
                                    <a href="<?= Yii::getAlias('@web') ?>/images/qr-atsys.jpg" class="glightbox text-xs font-semibold text-primary hover:underline mt-1">Haz clic para ampliar la imagen</a>
                                </div>

                                <div class="flex flex-col justify-center">
                                    <h4 class="font-bold text-lg mb-3">O transfiere a:</h4>
                                    <ul class="space-y-3 font-mono text-sm">
                                        <li class="p-3 bg-base-100 rounded-lg border border-base-200">
                                            <span class="opacity-60 block text-xs">Banco:</span>
                                            <strong>Bancolombia Ahorros</strong>
                                        </li>
                                        <li class="p-3 bg-base-100 rounded-lg border border-base-200">
                                            <span class="opacity-60 block text-xs">Número de Cuenta:</span>
                                            <strong>639-685573-29</strong>
                                        </li>
                                        <li class="p-3 bg-base-100 rounded-lg border border-base-200">
                                            <span class="opacity-60 block text-xs">A nombre de:</span>
                                            <strong>Arkitech Systems SAS<br>NIT: 901.005.699-9</strong>
                                        </li>
                                    </ul>

                                    <div class="mt-6">
                                        <a href="<?= Url::to([
                                            '/tickets/create',
                                            'subject' => 'Reporte de Pago OT-' . $model->id,
                                            'department' => 'commercial'
                                        ]) ?>" class="btn btn-outline btn-primary btn-block">
                                            Ya pagué, enviar comprobante
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($paypalClientId)): ?>
                    <div id="payment-usd-sec" class="payment-section hidden">
                        <h3 class="font-bold text-lg mb-2">Finalizar Pago Seguro en USD</h3>
                        <p class="text-sm mb-6 opacity-70">
                            Aceptamos tarjetas de crédito/débito internacionales y saldo de PayPal.<br>
                            La transacción es procesada de forma segura por PayPal.
                            <?php if (!empty($exchangeRateUsd)): ?>
                                <br><span class="text-xs opacity-60">Tasa de cambio aplicada (TRM USD): <?= Yii::$app->formatter->asCurrency($exchangeRateUsd) ?> COP</span>
                            <?php endif; ?>
                        </p>

                        <?php if (isset($paypalUsd)): ?>
                            <!-- CONTENEDOR BOTONES PAYPAL (USD) -->
                            <div id="paypal-usd-button-container" style="min-height: 150px;"></div>

                            <!-- NUEVO CONTENEDOR DE CARGA (Oculto por defecto) -->
                            <div id="paypal-usd-processing" style="display: none;" class="text-center py-4">
                                <span class="loading loading-spinner loading-lg text-primary"></span>
                                <p class="mt-2 text-sm opacity-70">Procesando pago...</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="payment-eur-sec" class="payment-section hidden">
                        <h3 class="font-bold text-lg mb-2">Finalizar Pago Seguro en EUR</h3>
                        <p class="text-sm mb-6 opacity-70">
                            Aceptamos tarjetas de crédito/débito internacionales y saldo de PayPal.<br>
                            La transacción es procesada de forma segura por PayPal.
                            <?php if (!empty($exchangeRateEur)): ?>
                                <br><span class="text-xs opacity-60">Tasa de cambio aplicada (TRM EUR): <?= Yii::$app->formatter->asCurrency($exchangeRateEur) ?> COP</span>
                            <?php endif; ?>
                        </p>

                        <?php if (isset($paypalEur)): ?>
                            <!-- CONTENEDOR BOTONES PAYPAL (EUR) -->
                            <div id="paypal-eur-button-container" style="min-height: 150px;"></div>

                            <!-- NUEVO CONTENEDOR DE CARGA (Oculto por defecto) -->
                            <div id="paypal-eur-processing" style="display: none;" class="text-center py-4">
                                <span class="loading loading-spinner loading-lg text-primary"></span>
                                <p class="mt-2 text-sm opacity-70">Procesando pago...</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($model->status == 1): ?>

                <div class="alert alert-success shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 class="font-bold">¡Pago Exitoso!</h3>
                        <div class="text-xs">Esta orden ya se encuentra pagada y los servicios han sido procesados.</div>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>

</div>

<?php
/* --- SCRIPT DE TABS Y PAYPAL --- */
if ($model->status == 0):

    $paypalClientIdVal = !empty($paypalClientId) ? $paypalClientId : '';
    $paypalUsdAmount = isset($paypalUsd) ? number_format($paypalUsd['amount'], 2, '.', '') : '0.00';
    $paypalEurAmount = isset($paypalEur) ? number_format($paypalEur['amount'], 2, '.', '') : '0.00';
    $isWeekendVal = (isset($isWeekend) && $isWeekend) ? 'true' : 'false';

    // URL Absoluta
    $confirmUrl = Url::to(['orders/paypal-confirm'], true);

    // Variable CSRF
    $csrfToken = Yii::$app->request->csrfToken;

    $jsTabsAndPaypal = <<<JS
document.addEventListener("DOMContentLoaded", function() {
    const tabCop = document.getElementById('tab-cop');
    const tabUsd = document.getElementById('tab-usd');
    const tabEur = document.getElementById('tab-eur');
    const secCop = document.getElementById('payment-cop-sec');
    const secUsd = document.getElementById('payment-usd-sec');
    const secEur = document.getElementById('payment-eur-sec');
    const dispCop = document.getElementById('total-cop-display');
    const dispUsd = document.getElementById('total-usd-display');
    const dispEur = document.getElementById('total-eur-display');

    // COP Sub-tabs
    const tabCopWompi = document.getElementById('tab-cop-wompi');
    const tabCopQr = document.getElementById('tab-cop-qr');
    const secCopWompi = document.getElementById('cop-wompi-sec');
    const secCopQr = document.getElementById('cop-qr-sec');

    function selectCopWompi() {
        if(tabCopWompi && tabCopQr) {
            tabCopWompi.classList.add('tab-active', 'btn-primary', 'text-white');
            tabCopQr.classList.remove('tab-active', 'btn-primary', 'text-white');
        }
        if(secCopWompi) secCopWompi.classList.remove('hidden');
        if(secCopQr) secCopQr.classList.add('hidden');
    }

    function selectCopQr() {
        if(tabCopWompi && tabCopQr) {
            tabCopQr.classList.add('tab-active', 'btn-primary', 'text-white');
            tabCopWompi.classList.remove('tab-active', 'btn-primary', 'text-white');
        }
        if(secCopQr) secCopQr.classList.remove('hidden');
        if(secCopWompi) secCopWompi.classList.add('hidden');
    }

    if (tabCopWompi && tabCopQr) {
        tabCopWompi.addEventListener('click', selectCopWompi);
        tabCopQr.addEventListener('click', selectCopQr);
        
        // Determinar qué sub-tab de COP activar por defecto
        if ({$isWeekendVal}) {
            selectCopQr();
        } else {
            selectCopWompi();
        }
    }

    function selectCop() {
        if(tabCop && tabUsd && tabEur) {
            tabCop.classList.add('tab-active', 'btn-primary', 'text-white');
            tabUsd.classList.remove('tab-active', 'btn-primary', 'text-white');
            tabEur.classList.remove('tab-active', 'btn-primary', 'text-white');
        }
        if(secCop) secCop.classList.remove('hidden');
        if(secUsd) secUsd.classList.add('hidden');
        if(secEur) secEur.classList.add('hidden');
        if(dispCop) dispCop.classList.remove('hidden');
        if(dispUsd) dispUsd.classList.add('hidden');
        if(dispEur) dispEur.classList.add('hidden');
    }

    function selectUsd() {
        if(tabCop && tabUsd && tabEur) {
            tabUsd.classList.add('tab-active', 'btn-primary', 'text-white');
            tabCop.classList.remove('tab-active', 'btn-primary', 'text-white');
            tabEur.classList.remove('tab-active', 'btn-primary', 'text-white');
        }
        if(secUsd) secUsd.classList.remove('hidden');
        if(secCop) secCop.classList.add('hidden');
        if(secEur) secEur.classList.add('hidden');
        if(dispUsd) dispUsd.classList.remove('hidden');
        if(dispCop) dispCop.classList.add('hidden');
        if(dispEur) dispEur.classList.add('hidden');

        // Inicializar PayPal USD
        if ('{$paypalClientIdVal}' !== '' && typeof window.initPaypalButton === 'function') {
            window.initPaypalButton('USD', '{$paypalUsdAmount}', 'paypal-usd-button-container', 'paypal-usd-processing');
        }
    }

    function selectEur() {
        if(tabCop && tabUsd && tabEur) {
            tabEur.classList.add('tab-active', 'btn-primary', 'text-white');
            tabCop.classList.remove('tab-active', 'btn-primary', 'text-white');
            tabUsd.classList.remove('tab-active', 'btn-primary', 'text-white');
        }
        if(secEur) secEur.classList.remove('hidden');
        if(secCop) secCop.classList.add('hidden');
        if(secUsd) secUsd.classList.add('hidden');
        if(dispEur) dispEur.classList.remove('hidden');
        if(dispCop) dispCop.classList.add('hidden');
        if(dispUsd) dispUsd.classList.add('hidden');

        // Inicializar PayPal EUR
        if ('{$paypalClientIdVal}' !== '' && typeof window.initPaypalButton === 'function') {
            window.initPaypalButton('EUR', '{$paypalEurAmount}', 'paypal-eur-button-container', 'paypal-eur-processing');
        }
    }

    if (tabCop && tabUsd && tabEur) {
        tabCop.addEventListener('click', selectCop);
        tabUsd.addEventListener('click', selectUsd);
        tabEur.addEventListener('click', selectEur);

        // Inicializar según la moneda original de la factura o por defecto COP
        if ('{$model->currency}' === 'USD') {
            selectUsd();
        } else if ('{$model->currency}' === 'EUR') {
            selectEur();
        } else {
            selectCop();
        }
    } else {
        // Si no hay tabs principales de moneda, nos aseguramos que se muestre COP
        selectCop();
    }
});

if ('{$paypalClientIdVal}' !== '') {
    window.initPaypalButton = function(currency, amount, containerId, processingId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        // Si ya está inicializado este contenedor, no hacer nada
        if (container.dataset.initialized === 'true') return;
        
        // Marcar como inicializado
        container.dataset.initialized = 'true';

        // Remover script anterior para evitar conflicto de divisas
        const oldScript = document.getElementById('paypal-sdk-script');
        if (oldScript) {
            oldScript.remove();
            delete window.paypal;
        }

        // Resetear el estado de inicialización del otro botón para que pueda alternar libremente
        const otherContainerId = containerId === 'paypal-usd-button-container' ? 'paypal-eur-button-container' : 'paypal-usd-button-container';
        const otherContainer = document.getElementById(otherContainerId);
        if (otherContainer) {
            otherContainer.dataset.initialized = 'false';
            otherContainer.innerHTML = '';
        }

        // Asegurarse de que el contenedor actual esté limpio y visible
        container.innerHTML = '';
        container.style.display = 'block';

        const script = document.createElement('script');
        script.id = 'paypal-sdk-script';
        script.src = "https://www.paypal.com/sdk/js?client-id={$paypalClientIdVal}&currency=" + currency;
        script.onload = function() {
            if (typeof paypal !== 'undefined') {
                paypal.Buttons({
                    createOrder: function(data, actions) {
                        return actions.order.create({
                            purchase_units: [{
                                amount: {
                                    value: amount 
                                },
                                description: 'Orden de Trabajo {$model->code}'
                            }]
                        });
                    },
                    onApprove: function(data, actions) {
                        document.getElementById(containerId).style.display = 'none';
                        document.getElementById(processingId).style.display = 'block';

                        return actions.order.capture().then(function(details) {
                            fetch('{$confirmUrl}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-Token': '{$csrfToken}'
                                },
                                body: JSON.stringify({
                                    order_id: {$model->id},
                                    transaction_id: details.id,
                                    status: details.status,
                                    currency: currency
                                })
                            })
                            .then(response => {
                                if (!response.ok) throw new Error("HTTP " + response.status);
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    window.location.reload();
                                } else {
                                    alert('El pago fue recibido, pero hubo un problema al procesar los servicios: ' + data.message);
                                    window.location.reload();
                                }
                            })
                            .catch(error => {
                                console.error('Fetch error:', error);
                                alert('El pago se realizó con éxito en PayPal, pero ATSYS tardó en responder. La página se recargará para verificar el estado.');
                                window.location.reload();
                            });
                        }).catch(function(err) {
                            document.getElementById(processingId).style.display = 'none';
                            document.getElementById(containerId).style.display = 'block';
                            console.error('Error en captura de PayPal:', err);
                            alert('La transacción en PayPal no pudo completarse. Por favor, intenta de nuevo.');
                        });
                    },
                    onError: function (err) {
                        console.error('PayPal Error:', err);
                        alert('Ocurrió un error de conexión con PayPal. Intenta de nuevo.');
                        window.location.reload();
                    }
                }).render('#' + containerId);
            }
        };
        document.head.appendChild(script);
    };
}

// Iniciar GLightbox
if (typeof GLightbox !== 'undefined') {
    GLightbox({
        selector: '.glightbox',
        touchNavigation: true,
        loop: true,
        autoplayVideos: true
    });
}
JS;

    $this->registerCssFile('https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css', ['position' => \yii\web\View::POS_HEAD]);
    $this->registerJsFile('https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js', ['position' => \yii\web\View::POS_END]);
    $this->registerJs($jsTabsAndPaypal, \yii\web\View::POS_END);

endif;
?>