<?php
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\WorkOrders */

$orderLink = Url::to(['work-orders/view', 'id' => $model->id], true);
?>

<div style="font-size: 14px; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
    
    <p style="font-size: 16px; margin-bottom: 20px;">
        Hola, <strong><?= Html::encode($model->customer->business_name ?? 'Cliente Estimado') ?></strong>.
    </p>

    <p>Te informamos que la orden de trabajo <strong>#<?= Html::encode($model->code) ?></strong> ha sido pausada.</p>

    <div style="border-left: 4px solid #f0ad4e; background-color: #fcf8f2; padding: 15px; margin: 25px 0; border-radius: 4px;">
        <p style="margin: 0; color: #a97c36; font-size: 14px; line-height: 1.5;">
            ⏸️ <strong>Orden en Pausa:</strong> Te confirmamos que esta orden está temporalmente en pausa. <strong>No realizaremos ninguna acción adicional de nuestro lado</strong> hasta que nos indiques lo contrario, y recuerda que <strong>puedes retomar el trabajo en cualquier momento</strong> que consideres oportuno.
        </p>
    </div>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; color: #777; width: 40%;">Referencia:</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;"><?= Html::encode($model->code) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; color: #777;">Servicio:</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;"><?= Html::encode($model->title) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; color: #777;">Fecha de Pausa:</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;"><?= Yii::$app->formatter->asDate(date('Y-m-d'), 'long') ?></td>
        </tr>
    </table>

    <p style="color: #555; margin-bottom: 20px;">
        Queremos asegurarte que tu proyecto y toda la información relacionada se conservarán de manera segura en nuestros sistemas. Para tu total tranquilidad, <strong>nuestro equipo mantendrá detenidas todas las actividades</strong> y no se ejecutará ninguna acción o gestión por parte de ATSYS mientras la orden permanezca en este estado.
    </p>

    <p style="color: #555; margin-bottom: 25px;">
        Cuando decidas reactivar este proyecto, el proceso es muy sencillo: solo debes <strong>crear un ticket dirigido al departamento comercial</strong> desde tu panel de cliente y con gusto te asistiremos en el proceso para retomarlo de inmediato.
    </p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="<?= $orderLink ?>" style="background-color: #134C42; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
            Ver Orden en ATSYS
        </a>
    </div>

</div>
