<?php
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\WorkOrders|null */
/* @var $orderCode string */
/* @var $orderTitle string */
/* @var $customerName string */
/* @var $createdAt string */
/* @var $expirationDays int */
/* @var $totalCost float|null */
/* @var $currency string */
/* @var $totalCostUsd float|null */

$portalLink = Yii::$app->urlManager->createAbsoluteUrl(['work-orders/index']);
$newTicketLink = Yii::$app->urlManager->createAbsoluteUrl(['tickets/create']);
?>

<div style="font-family: Arial, sans-serif; color: #333333; line-height: 1.6; font-size: 14px;">
    <h2 style="color: #b91c1c; margin-top: 0;">Notificación: Orden de Trabajo Expirada</h2>
    
    <p>Hola <strong><?= Html::encode($customerName) ?></strong>,</p>
    
    <p>Te informamos que la propuesta correspondiente a la Orden de Trabajo <strong><?= Html::encode($orderCode) ?></strong> (<em><?= Html::encode($orderTitle) ?></em>) ha alcanzado su fecha límite de vigencia de <strong><?= (int)$expirationDays ?> días calendario</strong> y ha sido <strong>expirada automáticamente</strong> por inactividad.</p>

    <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 14px 18px; margin: 20px 0; border-radius: 0 4px 4px 0;">
        <strong style="color: #991b1b; display: block; margin-bottom: 5px;">⚠️ Caducidad de la Propuesta</strong>
        <p style="margin: 0; color: #7f1d1d; font-size: 13px;">
            Al haber expirado el plazo de validez sin confirmación de aprobación, las estimaciones técnicas, disponibilidad de agenda y costos ofertados han dejado de estar vigentes.
        </p>
    </div>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0; border-collapse: collapse; font-size: 13px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; color: #666; width: 40%;">Código de Orden:</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold; font-family: monospace;"><?= Html::encode($orderCode) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; color: #666;">Proyecto / Servicio:</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;"><?= Html::encode($orderTitle) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; color: #666;">Fecha de Emisión:</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?= Yii::$app->formatter->asDate($createdAt, 'long') ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; color: #666;">Vigencia Aplicada:</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?= (int)$expirationDays ?> días calendario</td>
        </tr>
        <?php if (!empty($totalCost) && $totalCost > 0): ?>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; color: #666;">Valor Cotizado:</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">
                <?= Yii::$app->formatter->asCurrency($totalCost) ?> COP
                <?php if (!empty($totalCostUsd) && in_array($currency, ['USD', 'EUR'])): ?>
                    (<?= Yii::$app->formatter->asCurrency($totalCostUsd) ?> <?= Html::encode($currency) ?>)
                <?php endif; ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>

    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 16px 20px; border-radius: 6px; margin: 24px 0;">
        <h4 style="margin: 0 0 8px 0; color: #1e293b; font-size: 14px;">¿Aún requieres realizar este trabajo?</h4>
        <p style="margin: 0 0 14px 0; font-size: 13px; color: #475569;">
            Si deseas retomar este proyecto o actualizar los requerimientos, nuestro equipo con gusto generará una nueva propuesta ajustada a tus necesidades actuales.
        </p>
        <p style="margin: 0; text-align: center;">
            <a href="<?= $portalLink ?>" style="background-color: #134C42; color: #ffffff; padding: 10px 22px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 13px; display: inline-block; margin-right: 8px;">
                Ingresar al Área de Clientes
            </a>
            <a href="<?= $newTicketLink ?>" style="background-color: #0284c7; color: #ffffff; padding: 10px 22px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 13px; display: inline-block;">
                Crear Ticket de Consulta
            </a>
        </p>
    </div>

    <p style="font-size: 12px; color: #94a3b8; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px;">
        Si tienes preguntas adicionales o necesitas asistencia, puedes responder a este correo o ponerte en contacto con nosotros a través de <a href="mailto:soporte@atsys.co" style="color: #134C42;">soporte@atsys.co</a>.
    </p>
</div>
