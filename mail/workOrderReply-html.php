<?php
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\WorkOrders */

$orderLink = Url::to(['work-orders/view', 'id' => $model->id], true);
?>
<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333;">
    <h3>El cliente ha respondido a un avance</h3>
    <p><strong>Orden:</strong> OT-<?= $model->code ?> | <?= $model->title ?></p>
    <p><strong>Avance original:</strong><br><em><?= $update->description ?></em></p>
    <hr>
    <strong>Respuesta del cliente:</strong><br>
    <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #ccc;">
        <?= $update->client_reply ?></p>
    </div>
    <br><br>
    <a href="<?= \yii\helpers\Url::to(['work-orders/view', 'id' => $model->id], true) ?>" style="background-color: #28a745; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Ver Orden de Trabajo</a>
</div>