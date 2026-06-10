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
?>

<div class="container mx-auto max-w-3xl">

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

    <?php if ($model->status == 0): // Solo si está PENDIENTE ?>
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
                        Aceptamos tarjetas de crédito/débito, PSE, Nequi y Bancolombia.<br>
                        La transacción es procesada de forma segura por Wompi.
                    </p>

                    <?php if (isset($wompi)): ?>
                        <?php if ((isset($isWeekend) && $isWeekend)): ?>
                            <div class="alert alert-warning shadow-lg mb-6 bg-warning/10 border border-warning/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6 text-warning" fill="none"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <h3 class="font-bold">Aviso importante</h3>
                                    <div class="text-xs">Wompi está desactivado temporalmente hasta nuevo aviso, por favor realiza una
                                        transferencia directa escaneando el código QR.</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-base-50 p-6 rounded-2xl border border-base-200">
                                <div class="flex flex-col justify-center items-center">
                                    <h4 class="font-bold text-lg text-primary mb-4">Escanea para pagar</h4>
                                    <img src="<?= Yii::getAlias('@web') ?>/images/qr-atsys.jpg" alt="Código QR ATSYS"
                                        class="w-48 h-48 object-cover rounded-xl shadow-md border-2 border-primary/20">
                                    <p class="text-xs text-center mt-3 opacity-70">Llave Bre-B 0090212060</p>
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
                        <?php else: ?>
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
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

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
/* --- SCRIPT DE TABS --- */
$jsTabs = <<<JS
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
    }

    if (tabCop && tabUsd && tabEur) {
        tabCop.addEventListener('click', selectCop);
        tabUsd.addEventListener('click', selectUsd);
        tabEur.addEventListener('click', selectEur);

        // Inicializar según la moneda original de la factura
        if ('{$model->currency}' === 'USD') {
            selectUsd();
        } else if ('{$model->currency}' === 'EUR') {
            selectEur();
        } else {
            selectCop();
        }
    }
});
JS;
$this->registerJs($jsTabs, \yii\web\View::POS_END);

/* --- SCRIPT DE PAYPAL --- */
if ($model->status == 0 && (isset($paypalUsd) || isset($paypalEur))):

    $paypalClientId = isset($paypalUsd) ? $paypalUsd['clientId'] : $paypalEur['clientId'];
    $paypalUsdAmount = isset($paypalUsd) ? number_format($paypalUsd['amount'], 2, '.', '') : '0.00';
    $paypalEurAmount = isset($paypalEur) ? number_format($paypalEur['amount'], 2, '.', '') : '0.00';

    // URL Absoluta
    $confirmUrl = Url::to(['orders/paypal-confirm'], true);

    // Variable CSRF
    $csrfToken = Yii::$app->request->csrfToken;

    // Registramos los dos SDKs de PayPal con namespaces separados para evitar conflictos de divisas
    if (isset($paypalUsd)) {
        $this->registerJsFile("https://www.paypal.com/sdk/js?client-id={$paypalClientId}&currency=USD", [
            'position' => \yii\web\View::POS_HEAD,
            'data-namespace' => 'paypalUSD'
        ]);
    }
    if (isset($paypalEur)) {
        $this->registerJsFile("https://www.paypal.com/sdk/js?client-id={$paypalClientId}&currency=EUR", [
            'position' => \yii\web\View::POS_HEAD,
            'data-namespace' => 'paypalEUR'
        ]);
    }

    $jsPaypal = <<<JS
    // Configuración para USD
    if (typeof paypalUSD !== 'undefined') {
        paypalUSD.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: '{$paypalUsdAmount}' 
                        },
                        description: 'Orden de Trabajo {$model->code}'
                    }]
                });
            },
            onApprove: function(data, actions) {
                document.getElementById('paypal-usd-button-container').style.display = 'none';
                document.getElementById('paypal-usd-processing').style.display = 'block';

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
                            currency: 'USD'
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
                    document.getElementById('paypal-usd-processing').style.display = 'none';
                    document.getElementById('paypal-usd-button-container').style.display = 'block';
                    console.error('Error en captura de PayPal USD:', err);
                    alert('La transacción en PayPal no pudo completarse. Por favor, intenta de nuevo.');
                });
            },
            onError: function (err) {
                console.error('PayPal USD Error:', err);
                alert('Ocurrió un error de conexión con PayPal USD. Intenta de nuevo.');
                window.location.reload();
            }
        }).render('#paypal-usd-button-container');
    }

    // Configuración para EUR
    if (typeof paypalEUR !== 'undefined') {
        paypalEUR.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: '{$paypalEurAmount}' 
                        },
                        description: 'Orden de Trabajo {$model->code}'
                    }]
                });
            },
            onApprove: function(data, actions) {
                document.getElementById('paypal-eur-button-container').style.display = 'none';
                document.getElementById('paypal-eur-processing').style.display = 'block';

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
                            currency: 'EUR'
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
                    document.getElementById('paypal-eur-processing').style.display = 'none';
                    document.getElementById('paypal-eur-button-container').style.display = 'block';
                    console.error('Error en captura de PayPal EUR:', err);
                    alert('La transacción en PayPal no pudo completarse. Por favor, intenta de nuevo.');
                });
            },
            onError: function (err) {
                console.error('PayPal EUR Error:', err);
                alert('Ocurrió un error de conexión con PayPal EUR. Intenta de nuevo.');
                window.location.reload();
            }
        }).render('#paypal-eur-button-container');
    }
JS;

    $this->registerJs($jsPaypal, \yii\web\View::POS_END);

endif;
?>