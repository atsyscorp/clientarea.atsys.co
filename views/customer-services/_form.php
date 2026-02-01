<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $customers array Map [id => name] */
/* @var $products array Map [id => name] */
?>

<div class="card bg-base-100 shadow-xl max-w-4xl mx-auto">
    <div class="card-body">
        
        <?php $form = ActiveForm::begin([
            'options' => ['class' => 'space-y-4'],
            'fieldConfig' => [
                'labelOptions' => ['class' => 'label-text font-bold'],
                'inputOptions' => ['class' => 'input input-bordered w-full focus:input-primary'],
            ]
        ]); ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="form-control">
                <div class="form-group">
                    <?php 
                        if ($model->customer_id) {
                            echo Html::activeHiddenInput($model, 'customer_id');
                            echo '<label class="label-text font-bold">Cliente</label>';
                            echo Html::textInput('', $model->customer->business_name ?? '', ['class' => 'input input-bordered w-full bg-base-200', 'disabled' => true]);
                        } else {
                            echo $form->field($model, 'customer_id')->dropDownList($customers, ['prompt' => 'Selecciona un Cliente...']);
                        }
                    ?>
                </div>
            </div>
            
            <div class="form-control">
                <?= $form->field($model, 'product_id')->dropDownList($products, ['prompt' => 'Selecciona el Servicio...', 'class']) ?>
            </div>

            <div class="form-control md:col-span-2">
                <?= $form->field($model, 'domain', [
                    'inputOptions' => ['placeholder' => 'Ej: atsys.co', 'class' => 'input input-bordered w-full font-mono']
                ])->label('Dominio (Opcional)') ?>
            </div>

            <div class="form-control">
                <?= $form->field($model, 'description_label')->textInput(['placeholder' => 'Ej: Servidor de Producción']) ?>
            </div>

            <div class="form-control">
                <?= $form->field($model, 'start_date')->input('date') ?>
            </div>
            
            <div class="form-control">
                <?= $form->field($model, 'next_due_date')->input('date') ?>
            </div>
            
            <div class="divider md:col-span-2 text-xs font-bold opacity-50">CREDENCIALES INTERNAS</div>

            <div class="form-control">
                <?= $form->field($model, 'username_service')->textInput(['placeholder' => 'Usuario cPanel/CyberPanel/SSH']) ?>
            </div>

            <div class="form-control">
                <?= $form->field($model, 'status')->dropDownList([
                    1 => 'Activo',
                    2 => 'Suspendido',
                    0 => 'Cancelado'
                ]) ?>
            </div>
        </div>

        <div class="card-actions justify-between mt-8 border-t border-base-200 pt-4">
            <span><?= Html::checkbox( 'silent', false, ['label' => 'Crear sin avisar al cliente'])?></span>
            <?= Html::submitButton('Asignar Servicio', ['class' => 'btn btn-primary text-white']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>