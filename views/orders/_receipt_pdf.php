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
                <h2 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 18px;">COMPROBANTE DE VENTA</h2>
                <p class="font-bold">ORDEN N°: <?= Html::encode($model->code) ?></p>
                <p><strong>Fecha de emisión:</strong> <?= Yii::$app->formatter->asDate($model->updated_at, 'dd/MM/yyyy') ?></p>
                <p><strong>Método de pago:</strong> <?= Html::encode($model->payment_method) ?></p>
                <?php if ($model->transaction_ref): ?>
                    <p><strong>Ref. Transacción:</strong> <?= Html::encode($model->transaction_ref) ?></p>
                <?php endif; ?>
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
                <th class="text-right font-bold text-lg">TOTAL PAGADO:</th>
                <th class="text-right font-bold text-lg text-primary">
                    <?php
                        $totalVal = $isForeign ? ($model->total_usd ?? $model->total) : $model->total;
                        echo Yii::$app->formatter->asCurrency($totalVal) . ' ' . $model->currency;
                    ?>
                </th>
            </tr>
        </tfoot>
    </table>

    <div class="alert-box">
        <strong>NOTA IMPORTANTE:</strong> Este documento es un comprobante de pago generado por el sistema y <strong>no se constituye como una factura electrónica de venta</strong> de acuerdo con la normatividad de la DIAN.
        <?php if ($model->require_invoice): ?>
            <br><br>Su factura electrónica ha sido solicitada y será enviada al correo registrado a la brevedad.
        <?php endif; ?>
    </div>
</div>
