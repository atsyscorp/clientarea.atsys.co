<?php 

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */
/* @var $gateway string */ 
/* @var $wompi array|null */ 
/* @var $paypal array|null */ 

$this->title = 'Confirmación de pago - Orden ' . $model->code;

// Lógica rápida para definir totales y sufijos según la moneda
$isUsd = $gateway === 'USD';
$currencySuffix = $isUsd ? ' USD' : ' COP';
$displayTotal = $isUsd ? ($model->total_usd ?? $model->total) : $model->total;
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
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S12 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S12 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                                        <?= Html::encode($item->domain_name) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($item->action_type == 'restore'): ?>
                                    <span class="badge badge-error badge-xs mt-2 text-white">Restauración (+Cargo Extra)</span>
                                <?php elseif ($item->action_type == 'renew'): ?>
                                    <span class="badge badge-success badge-outline badge-xs mt-2">Renovación</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-right pr-0 font-mono text-lg align-top pt-4">
                                <?php 
                                    // Asumiendo que guardaste el valor del item en USD, de lo contrario se muestra en COP adaptado
                                    $itemTotal = $isUsd ? ($item->subtotal_usd ?? $item->total) : $item->total;
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
                                <?= Yii::$app->formatter->asCurrency($displayTotal) . $currencySuffix ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl mt-6 border border-base-200 relative z-0">
        <div class="card-body">
            <?php if ($model->status == 0): // Solo si está PENDIENTE ?>
                
                <h3 class="font-bold text-lg mb-2">Finalizar Pago Seguro</h3>
                <p class="text-sm mb-6 opacity-70">
                    <?php if (!$isUsd): ?>
                        Aceptamos tarjetas de crédito/débito, PSE, Nequi y Bancolombia. 
                        <br>La transacción es procesada de forma segura por Wompi.
                    <?php else: ?>
                        Aceptamos tarjetas de crédito/débito internacionales y saldo de PayPal. 
                        <br>La transacción es procesada de forma segura por PayPal.
                    <?php endif; ?>
                </p>

                <?php if (!$isUsd && isset($wompi)): ?>
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
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                            Pagar <?= Yii::$app->formatter->asCurrency($displayTotal) . $currencySuffix ?> con Wompi
                        </button>
                    </form>
                
                <?php elseif ($isUsd && isset($paypal)): ?>
                    <!-- CONTENEDOR BOTONES PAYPAL (USD) -->
                    <div id="paypal-button-container"></div>
                <?php endif; ?>

            <?php elseif ($model->status == 1): ?>
                
                <div class="alert alert-success shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
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
/* --- SCRIPT DE PAYPAL --- */
if ($model->status == 0 && $isUsd && isset($paypal)): 

    $paypalClientId = $paypal['clientId'];
    $paypalCurrency = $paypal['currency'];
    $paypalAmount = $paypal['amount'];
    $confirmUrl = Url::to(['orders/paypal-confirm']);
    // Yii2 requiere el token CSRF para validaciones POST de seguridad
    $csrfToken = Yii::$app->request->csrfToken;

    // Registramos la librería de PayPal de manera dinámica
    $this->registerJsFile("https://www.paypal.com/sdk/js?client-id={$paypalClientId}&currency={$paypalCurrency}", [
        'position' => \yii\web\View::POS_HEAD
    ]);

    // Inicializamos el comportamiento del botón
    $js = <<<JS
    paypal.Buttons({
        // Crea la transacción
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '{$paypalAmount}' // Debe ser un string con punto decimal, ej: '150.00'
                    },
                    description: 'Orden de Trabajo {$model->code}'
                }]
            });
        },
        // Captura el pago tras la aprobación del cliente
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                
                // Mostrar un feedback visual nativo en tu interfaz si lo deseas aquí
                console.log('Capture result', details);

                // Enviar confirmación asíncrona a Yii2 para ejecutar la lógica de CyberPanel
                fetch('{$confirmUrl}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '{$csrfToken}'
                    },
                    body: JSON.stringify({
                        order_id: {$model->id},
                        transaction_id: details.id,
                        status: details.status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recarga la página: Yii detectará que la orden tiene status = 1 y mostrará el alert de éxito
                        window.location.reload();
                    } else {
                        alert('El pago se recibió en PayPal, pero ocurrió un problema al procesar los servicios: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error de red al notificar al servidor:', error);
                    alert('Error de conexión al verificar el pago con nuestros servidores.');
                });
            });
        },
        onError: function (err) {
            console.error('PayPal Error:', err);
        }
    }).render('#paypal-button-container');
    JS;

    $this->registerJs($js, \yii\web\View::POS_END);

endif; 
?>