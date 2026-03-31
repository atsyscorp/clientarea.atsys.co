<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Editar Delegado: ' . $model->contact_name;
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <?= Html::a('← Volver a Mi Equipo', ['index'], ['class' => 'btn btn-ghost btn-sm']) ?>
    </div>

    <div class="card bg-base-100 shadow-xl border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-2xl mb-4"><?= Html::encode($this->title) ?></h2>

            <?php $form = ActiveForm::begin(); ?>

                <div class="form-control mb-4">
                    <label class="label"><span class="label-text font-bold">Nombre Completo</span></label>
                    <?= Html::activeTextInput($model, 'contact_name', ['class' => 'input input-bordered w-full', 'required' => true]) ?>
                </div>

                <div class="form-control mb-4">
                    <label class="label"><span class="label-text font-bold">Correo Electrónico</span></label>
                    <?= Html::activeTextInput($model, 'email', ['type' => 'email', 'class' => 'input input-bordered w-full', 'required' => true]) ?>
                </div>

                <div class="form-control mb-4">
                    <label class="label"><span class="label-text font-bold">Nivel de Acceso</span></label>
                    <?= Html::activeDropDownList($model, 'role', [
                        11 => 'Estándar (Solo Soporte)',
                        12 => 'Administrativo (Acceso Total / Backup)',
                    ], ['class' => 'select select-bordered w-full']) ?>
                    <label class="label">
                        <span class="label-text-alt text-base-content/60 italic">El nivel Administrativo permite gestionar la cuenta en caso de ausencia del titular.</span>
                    </label>
                </div>

                <div class="form-control mb-6">
                    <label class="label"><span class="label-text font-bold text-error">Resetear Contraseña</span></label>
                    <?= Html::activePasswordInput($model, 'password', [
                        'class' => 'input input-bordered w-full border-error/30', 
                        'placeholder' => 'Dejar en blanco para no cambiar',
                        'value' => '' // Siempre vacío por seguridad
                    ]) ?>
                </div>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary text-white w-full">Guardar Cambios</button>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>