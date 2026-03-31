<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Añadir Delegado';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <?= Html::a('← Volver a Mi Equipo', ['index'], ['class' => 'btn btn-ghost btn-sm']) ?>
    </div>

    <div class="card bg-base-100 shadow-xl border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-2xl mb-4"><?= Html::encode($this->title) ?></h2>
            
            <div class="alert alert-info shadow-sm mb-6 bg-blue-50 text-blue-900 border-blue-100">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>El delegado recibirá un correo con su contraseña. Podrá ver e interactuar con los tickets de la empresa.</span>
            </div>

            <?php $form = ActiveForm::begin(); ?>

                <div class="form-control mb-4">
                    <label class="label"><span class="label-text font-bold">Nombre Completo</span></label>
                    <?= Html::activeTextInput($model, 'contact_name', [ // Cambia a la columna real de tu nombre
                        'class' => 'input input-bordered w-full',
                        'required' => true,
                        'placeholder' => 'Ej: Juan Pérez'
                    ]) ?>
                </div>

                <div class="form-control mb-4">
                    <label class="label"><span class="label-text font-bold">Correo Electrónico</span></label>
                    <?= Html::activeTextInput($model, 'email', [
                        'type' => 'email',
                        'class' => 'input input-bordered w-full',
                        'required' => true,
                        'placeholder' => 'juan@tuempresa.com'
                    ]) ?>
                    <label class="label"><span class="label-text-alt text-base-content/60">Este será su usuario de acceso.</span></label>
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
                    <label class="label"><span class="label-text font-bold">Contraseña Inicial</span></label>
                    <?= Html::activePasswordInput($model, 'password', [
                        'class' => 'input input-bordered w-full',
                        'required' => true,
                        'placeholder' => '••••••••'
                    ]) ?>
                </div>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary text-white w-full shadow-lg">Guardar y Enviar Accesos</button>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>