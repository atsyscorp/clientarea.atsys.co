<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */

$customer = $model->customer;
$isForeign = in_array($model->currency, ['USD', 'EUR']);
?>
<div class="receipt-container">
    <table class="header-container">
        <tr>
            <td class="logo-cell" style="border: none; vertical-align: top;">
                <img src="<?= Yii::getAlias('@webroot') ?>/images/atsys-logo-src-clear-2026.png" style="max-height: 35px; margin-bottom: 10px;" alt="ATSYS Logo">
                <p style="margin: 0;"><strong>Arkitech Systems SAS</strong></p>
                <p style="margin: 0;">NIT: 901.005.699-9</p>
                <p style="margin: 0;">hola@atsys.co | www.atsys.co</p>
            </td>
            <td class="info-cell" style="border: none; vertical-align: top;">
                <h2 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 18px;">ORDEN DE PAGO</h2>
                <p class="font-bold">ORDEN N°: <?= Html::encode($model->code) ?></p>
                <p><strong>Fecha de emisión:</strong> <?= Yii::$app->formatter->asDate($model->updated_at, 'dd/MM/yyyy') ?></p>

            </td>
        </tr>
    </table>

    <div style="margin-bottom: 20px;">
        <h3 style="margin-bottom: 5px;">Facturado a:</h3>
        <p>
            <strong><?= Html::encode($customer->business_name) ?></strong><br>
            <?= Html::encode($customer->document_number) ?><br>
            <?= Html::encode($customer->email) ?><br>
            <?= Html::encode($customer->address) ?>, <?= Html::encode($customer->city) ?>
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-left">Descripción del Servicio</th>
                <th class="text-right" style="width: 150px;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($model->orderItems as $item): ?>
                <tr>
                    <td>
                        <div class="font-bold"><?= Html::encode($item->service_name) ?></div>
                        <?php if ($item->domain_name): ?>
                            <div class="text-sm"><?= Html::encode($item->domain_name) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <?php
                            $val = ($isForeign && $model->exchange_rate) ? ($item->total_usd ?? $item->total) : $item->total;
                            echo Yii::$app->formatter->asCurrency($val) . ' ' . $model->currency;
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th class="text-right font-bold text-lg">TOTAL A PAGAR:</th>
                <th class="text-right font-bold text-lg text-primary">
                    <?php
                        $totalVal = $isForeign ? ($model->total_usd ?? $model->total) : $model->total;
                        echo Yii::$app->formatter->asCurrency($totalVal) . ' ' . $model->currency;
                    ?>
                </th>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
        <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #2c3e50;">Información de Pago</h3>
        
        <?php if ($model->currency == 'COP'): ?>
            <p style="margin: 0 0 5px 0; font-size: 13px;">Puede realizar el pago a través de los siguientes medios:</p>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                <li><strong>En línea:</strong> Desde nuestro portal de clientes a través de Wompi (Tarjetas de crédito/débito, PSE, Nequi).</li>
                <li><strong>Transferencia Bancaria:</strong>
                    <ul style="margin: 5px 0 0 0; padding-left: 20px;">
                        <li>Banco: Bancolombia Ahorros</li>
                        <li>Cuenta N°: 639-685573-29</li>
                        <li>A nombre de: Arkitech Systems SAS (NIT 901.005.699-9)</li>
                    </ul>
                </li>
            </ul>
        <?php else: ?>
            <p style="margin: 0; font-size: 13px;">
                Puede realizar el pago en dólares (USD) o euros (EUR) a través de nuestro portal de clientes utilizando PayPal (saldo de PayPal o tarjeta de crédito internacional).
            </p>
        <?php endif; ?>
    </div>

    <div class="alert-box">
        <strong>NOTA IMPORTANTE:</strong> Este documento es una orden de pago o proforma generada por el sistema y <strong>no se constituye como una factura electrónica de venta</strong> de acuerdo con la normatividad de la DIAN. La factura electrónica será generada una vez se confirme el pago correspondiente.
    </div>
</div>
