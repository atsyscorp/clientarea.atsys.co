<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Notifications $model */
/** @var app\models\Customers[] $customers */

$this->title = 'Crear Notificación Campaña';
?>
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-extrabold text-base-content flex items-center gap-3">
                <span>📣 Nueva Notificación Campaña</span>
            </h1>
            <p class="text-sm text-base-content/60 mt-1">Envía una alerta publicitaria o aviso importante.</p>
        </div>
        <a href="/notifications" class="btn btn-sm btn-outline border-base-300 rounded-xl">Volver</a>
    </div>

    <div class="bg-base-100 rounded-3xl border border-base-200 shadow-xl p-6 md:p-8">
        <?php $form = ActiveForm::begin([
            'id' => 'create-promo-notification-form',
            'options' => ['class' => 'space-y-6'],
        ]); ?>

        <!-- Target Destination Selector -->
        <div class="form-control w-full">
            <label class="label font-bold text-base-content" for="target-selector">
                <span class="label-text text-base font-semibold">Destinatarios</span>
            </label>
            <select name="target" id="target-selector" class="select select-bordered w-full rounded-xl bg-base-100 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="all">📢 Todos los clientes y subcuentas (Difusión global)</option>
                <option disabled>────────── Clientes Individuales ──────────</option>
                <?php foreach ($customers as $cust): ?>
                    <option value="<?= $cust->id ?>">👤 <?= Html::encode($cust->business_name) ?> (<?= Html::encode($cust->email) ?>)</option>
                <?php endforeach; ?>
            </select>
            <span class="label-text-alt text-base-content/50 mt-1">Selecciona quién recibirá esta alerta en su barra de notificaciones.</span>
        </div>

        <!-- Title Field -->
        <div class="form-control w-full">
            <label class="label font-bold text-base-content" for="notifications-title">
                <span class="label-text text-base font-semibold">Título de la Notificación</span>
            </label>
            <?= $form->field($model, 'title', [
                'options' => ['tag' => false],
            ])->textInput([
                'id' => 'notifications-title',
                'class' => 'input input-bordered w-full rounded-xl bg-base-100 text-sm focus:outline-none focus:ring-2 focus:ring-primary',
                'placeholder' => 'Ej: 🚀 Nueva funcionalidad disponible / Mantenimiento programado',
                'required' => true,
            ])->label(false) ?>
        </div>

        <!-- Body/Message Field -->
        <div class="form-control w-full">
            <label class="label font-bold text-base-content" for="notifications-body">
                <span class="label-text text-base font-semibold">Cuerpo del Mensaje</span>
            </label>
            <?= $form->field($model, 'body', [
                'options' => ['tag' => false],
            ])->textarea([
                'id' => 'notifications-body',
                'class' => 'textarea textarea-bordered w-full rounded-xl bg-base-100 text-sm focus:outline-none focus:ring-2 focus:ring-primary h-32',
                'placeholder' => 'Describe el anuncio con claridad y de forma atractiva...',
                'required' => true,
            ])->label(false) ?>
        </div>

        <!-- Link Field (Optional) -->
        <div class="form-control w-full">
            <label class="label font-bold text-base-content" for="notifications-link">
                <span class="label-text text-base font-semibold">Enlace de Destino (Opcional)</span>
            </label>
            <?= $form->field($model, 'link', [
                'options' => ['tag' => false],
            ])->textInput([
                'id' => 'notifications-link',
                'class' => 'input input-bordered w-full rounded-xl bg-base-100 text-sm focus:outline-none focus:ring-2 focus:ring-primary',
                'placeholder' => 'Ej: /shop /tickets/view?id=123 (URL interna o externa)',
            ])->label(false) ?>
            <span class="label-text-alt text-base-content/50 mt-1">Al hacer clic en la notificación, el usuario será redirigido a este enlace.</span>
        </div>

        <div class="pt-4 flex gap-4">
            <?= Html::submitButton('🚀 Lanzar Notificación', [
                'class' => 'btn btn-primary text-white rounded-xl grow shadow-md hover:scale-[1.02] active:scale-[0.98] transition-all',
            ]) ?>
            <a href="/notifications" class="btn btn-ghost rounded-xl border border-base-200">Cancelar</a>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
