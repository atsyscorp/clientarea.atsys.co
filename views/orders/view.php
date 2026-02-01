<?php 

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */
/* @var $wompi array */ 

$this->title = 'Confirmación de pago - Orden ' . $model->code;
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
                                <?= Yii::$app->formatter->asCurrency($item->total) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    
                    <tfoot class="border-t-2 border-base-300">
                        <tr>
                            <td class="pl-0 text-right text-xl font-bold pt-4 text-base-content">TOTAL A PAGAR</td>
                            <td class="pr-0 text-right text-2xl font-black text-primary pt-4">
                                <?= Yii::$app->formatter->asCurrency($model->total) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl mt-6 border border-base-200">
        <div class="card-body">
            <?php if ($model->status == 0): // Solo si está PENDIENTE ?>
                
                <h3 class="font-bold text-lg mb-2">Finalizar Pago Seguro</h3>
                <p class="text-sm mb-6 opacity-70">
                    Aceptamos tarjetas de crédito/débito, PSE, Nequi y Bancolombia. 
                    <br>La transacción es procesada de forma segura por Wompi.
                </p>

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
                        Pagar <?= Yii::$app->formatter->asCurrency($model->total) ?> con Wompi
                    </button>
                </form>

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