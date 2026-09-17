<?php
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\WorkOrders */

$orderLink = Url::to(['work-orders/view', 'id' => $model->id], true);
?>
<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333;">
    <h3>El cliente ha respondido a un avance</h3>
    <p><strong>Orden:</strong> OT-<?= Html::encode($model->code) ?> | <?= Html::encode($model->title) ?></p>
    <p><strong>Avance original:</strong><br><em><?= nl2br(Html::encode($update->description)) ?></em></p>
    <?php if (!empty($update->attachment_url)): ?>
        <p style="margin: 6px 0 12px 0;">
            📎 <strong>Adjunto del avance original:</strong>
            <a href="<?= Html::encode($update->attachment_url) ?>" target="_blank" style="color: #134C42; font-weight: bold; text-decoration: underline;">Ver Documento Adjunto</a>
        </p>
    <?php endif; ?>
    <hr>
    <strong>Respuesta del cliente:</strong><br>
    <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #ccc;">
        <?= nl2br(Html::encode($update->client_reply)) ?>
        <?php if (!empty($update->reply_attachment_url)): ?>
            <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid #e5e7eb;">
                📎 <strong>Archivo adjunto del cliente:</strong>
                <a href="<?= Html::encode($update->reply_attachment_url) ?>" target="_blank" style="color: #134C42; font-weight: bold; text-decoration: underline;">Ver Archivo Adjunto</a>
            </div>
        <?php endif; ?>
    </div>
    <br><br>
    <a href="<?= $orderLink ?>" style="background-color: #28a745; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Ver Orden de Trabajo</a>
</div>