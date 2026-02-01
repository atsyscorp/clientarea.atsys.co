<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>

<div class="card bg-base-100 shadow-xl max-w-2xl mx-auto">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="form-control">
            <?= $form->field($model, 'name')->textInput(['placeholder' => 'Ej: Hosting Empresarial 5GB', 'class' => 'input input-bordered']) ?>
        </div>

        <div class="form-control">
            <?= $form->field($model, 'description')->textarea(['rows' => 3, 'class' => 'textarea textarea-bordered', 'placeholder' => 'Detalles técnicos...']) ?>
        </div>

        <div class="divider text-xs font-bold opacity-50">ESQUEMA DE PRECIOS</div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-base-200 p-4 rounded-lg">
            
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-bold">Registro / Alta</span>
                    <span class="label-text-alt text-xs opacity-60">Primer pago</span>
                </label>
                <?= $form->field($model, 'price', ['template' => "{input}\n{error}"])
                    ->textInput(['type' => 'number', 'step' => '0.01', 'class' => 'input input-bordered w-full']) ?>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text font-bold text-primary">Renovación</span>
                    <span class="label-text-alt text-xs opacity-60">Recurrente</span>
                </label>
                <?= $form->field($model, 'price_renewal', ['template' => "{input}\n{error}"])
                    ->textInput(['type' => 'number', 'step' => '0.01', 'class' => 'input input-bordered w-full border-primary']) ?>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text font-bold text-error">Restauración</span>
                    <span class="label-text-alt text-xs opacity-60">Tras vencimiento</span>
                </label>
                <?= $form->field($model, 'price_restoration', ['template' => "{input}\n{error}"])
                    ->textInput(['type' => 'number', 'step' => '0.01', 'class' => 'input input-bordered w-full border-error']) ?>
            </div>
        </div>
        
        <div class="text-xs text-gray-500 mt-2 mb-4 px-1">
            * Para servicios estándar (Hosting/Soporte), puedes poner el mismo valor en Registro y Renovación, y 0 en Restauración.
        </div>

        <div class="grid grid-cols-2 gap-4">
            
            <div class="form-control">
                <?= $form->field($model, 'status')->dropDownList([1 => 'Activo', 0 => 'Inactivo'], ['class' => 'select select-bordered']) ?>
            </div>
        </div>

        <div class="card-actions justify-end mt-6">
            <?= Html::submitButton('Guardar Producto', ['class' => 'btn btn-primary text-white']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>