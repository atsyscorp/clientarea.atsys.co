<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Servers $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="server-form card bg-base-100 shadow-xl border border-base-200">
    <div class="card-body">
        <?php $form = ActiveForm::begin([
            'fieldConfig' => [
                'options' => ['class' => 'form-control w-full mb-4'],
                'labelOptions' => ['class' => 'label-text font-semibold mb-1'],
                'inputOptions' => ['class' => 'input input-bordered w-full'],
                'errorOptions' => ['class' => 'text-error text-xs mt-1'],
            ],
        ]); ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <h3 class="text-lg font-bold border-b border-base-300 pb-2 mb-4">Información General</h3>

                <?= $form->field($model, 'name')->textInput(['placeholder' => 'Ej: Nexus01 Principal']) ?>

                <?= $form->field($model, 'type')->dropDownList([
                    'virtualmin' => 'Virtualmin (Recomendado)',
                    'cyberpanel' => 'CyberPanel',
                    'cpanel' => 'cPanel',
                    'plesk' => 'Plesk'
                ], ['class' => 'select select-bordered w-full']) ?>

                <div class="grid grid-cols-2 gap-4">
                    <?= $form->field($model, 'current_accounts')->textInput(['type' => 'number', 'readonly' => true]) ?>
                    <?= $form->field($model, 'max_accounts')->textInput(['type' => 'number']) ?>
                </div>
            </div>

            <div class="space-y-2">
                <h3 class="text-lg font-bold border-b border-base-300 pb-2 mb-4">Credenciales y Conexión</h3>

                <?= $form->field($model, 'hostname')->textInput(['placeholder' => 'nexus01.atsys.co']) ?>

                <?= $form->field($model, 'ip_address')->textInput(['placeholder' => '192.168.1.1']) ?>

                <?= $form->field($model, 'username')->textInput() ?>

                <?= $form->field($model, 'auth_token')->passwordInput([
                    'placeholder' => 'API Key o Password',
                    'class' => 'input input-bordered w-full font-mono'
                ])->label('Token de Autenticación / API Key') ?>
            </div>
        </div>

        <div class="flex items-center gap-4 mt-6 p-4 bg-base-200 rounded-lg">
            <?= $form->field($model, 'is_active')->checkbox(['class' => 'checkbox checkbox-primary']) ?>
            <span class="text-sm opacity-70">Si el servidor no está activo, el sistema no lo tendrá en cuenta para
                nuevos aprovisionamientos automáticos.</span>
        </div>

        <div class="card-actions justify-end mt-8">
            <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-ghost']) ?>
            <?= Html::submitButton($model->isNewRecord ? 'Registrar Servidor' : 'Actualizar Cambios', [
                'class' => 'btn btn-primary px-8 text-white shadow-lg'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>