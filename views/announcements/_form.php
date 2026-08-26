<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Announcements $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="announcements-form card bg-base-100 shadow-xl border border-base-200">
    <div class="card-body">

    <?php $form = ActiveForm::begin(); ?>

    <?php
    $isDanger = ($model->type === 'danger');
    if ($isDanger) {
        $model->is_pinned = 1;
    }
    ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <?= $form->field($model, 'type')->dropDownList([
            'info' => '🔵 Noticia General (Azul)',
            'success' => '🟢 Éxito / Logro (Verde)',
            'warning' => '🟡 Advertencia / Mantenimiento (Amarillo)',
            'danger' => '🔴 URGENTE / CRÍTICO (Rojo - Siempre arriba)',
        ], [
            'class' => 'select select-bordered w-full',
            'id' => 'announcement-type-select'
        ]) ?>

        <div class="form-control">
            <label class="label cursor-pointer justify-start gap-4 mt-8">
                <div>
                    <span class="label-text font-bold block">¿Fijar arriba?</span>
                    <span id="pinned-help-text" class="text-xs text-error font-medium <?= $isDanger ? '' : 'hidden' ?>">(Obligatorio si es Urgente)</span>
                </div>
                <?= $form->field($model, 'is_pinned')->checkbox([
                    'template' => "{input}", 
                    'class' => 'toggle toggle-secondary',
                    'id' => 'announcement-pinned-toggle',
                    'disabled' => $isDanger,
                    'title' => 'Muestra este comunicado en el banner superior del panel'
                ]) ?>
            </label>
        </div>

        <div class="form-control">
            <label class="label cursor-pointer justify-start gap-4 mt-8">
                <span class="label-text font-bold">¿Publicar inmediatamente?</span> 
                <?= $form->field($model, 'is_active')->checkbox([
                    'template' => "{input}", 
                    'class' => 'toggle toggle-primary'
                ]) ?>
            </label>
        </div>

    </div>

    <?= $form->field($model, 'title')->textInput([
        'maxlength' => true, 
        'class' => 'input input-bordered w-full',
        'placeholder' => 'Ej: Ventana de Mantenimiento Sábado'
    ]) ?>

    <?= $form->field($model, 'content')->textarea([
        'rows' => 6, 
        'class' => 'textarea textarea-bordered w-full',
        'placeholder' => 'Detalles del comunicado...'
    ]) ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
        
        <?= $form->field($model, 'expires_at')->input('datetime-local', [
            'class' => 'input input-bordered w-full'
        ])->label('Expira el (Opcional - Dejar vacío para que sea permanente)') ?>

    </div>

    <div class="form-group mt-6 flex justify-end">
        <?= Html::submitButton('Guardar Comunicado', ['class' => 'btn btn-primary text-white px-8']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    </div>
</div>

<?php
$script = <<<JS
document.getElementById('announcement-type-select').addEventListener('change', function() {
    var pinnedToggle = document.getElementById('announcement-pinned-toggle');
    var helpText = document.getElementById('pinned-help-text');
    if (this.value === 'danger') {
        pinnedToggle.checked = true;
        pinnedToggle.disabled = true;
        if (helpText) helpText.classList.remove('hidden');
    } else {
        pinnedToggle.disabled = false;
        if (helpText) helpText.classList.add('hidden');
    }
});
JS;
$this->registerJs($script);
?>