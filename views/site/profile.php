<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\ProfileForm $model */
/** @var app\models\Customers|null $customer */

$this->title = 'Mi Perfil';
?>

<div class="max-w-4xl mx-auto mt-6 mb-12">

    <div class="flex flex-col md:flex-row gap-6">
        
        <div class="w-full md:w-1/3 sticky top-20 self-start">
            <div class="card bg-base-100 shadow-xl border border-base-200 top-6 sticky">
                <div class="card-body items-center text-center">
                    <div class="avatar placeholder mb-4">
                        <div class="bg-primary text-primary-content rounded-full w-24 shadow-lg">
                            <span class="text-3xl font-bold"><?= strtoupper(substr($model->username ?? 'U', 0, 1)) ?></span>
                        </div>
                    </div>
                    <h2 class="card-title"><?= Html::encode($model->username) ?></h2>
                    <p class="text-sm opacity-70 break-all"><?= Html::encode($model->email) ?></p>
                    <div class="divider my-2"></div>
                    <div class="stats stats-vertical shadow w-full text-xs bg-base-200/50">
                        <div class="stat p-2">
                            <div class="stat-title opacity-60">Miembro desde</div>
                            <div class="stat-value text-lg font-medium">
                                <?= Yii::$app->formatter->asDate(Yii::$app->user->identity->created_at, 'php:M Y') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full md:w-2/3 space-y-6">
            
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-6 md:p-8">
                    <h2 class="card-title mb-6 flex items-center gap-3 text-xl">
                        <div class="p-2 bg-primary/10 rounded-lg text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        Datos de Acceso
                    </h2>

                    <?php $form = ActiveForm::begin(['id' => 'user-form']); ?>
                        <div class="space-y-4">
                            <div>
                                <label class="label font-bold">Nombre de Usuario</label>
                                <?= $form->field($model, 'username', ['template' => '{input}'])->textInput(['readonly' => true, 'class' => 'input input-bordered w-full bg-base-200 cursor-not-allowed']) ?>
                            </div>
                            <?= $form->field($model, 'email')->textInput(['class' => 'input input-bordered w-full']) ?>
                        </div>

                        <div class="divider text-xs font-bold opacity-50 mt-6">CAMBIAR CONTRASEÑA</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?= $form->field($model, 'password')->passwordInput(['placeholder' => 'Nueva contraseña', 'class' => 'input input-bordered w-full']) ?>
                            <?= $form->field($model, 'confirm_password')->passwordInput(['placeholder' => 'Confirmar', 'class' => 'input input-bordered w-full']) ?>
                        </div>

                        <div class="card-actions justify-end mt-4">
                            <?= Html::submitButton('Actualizar Acceso', ['class' => 'btn btn-primary text-white']) ?>
                        </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>

            <?php if ($customer): ?>
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-6 md:p-8">
                    <h2 class="card-title mb-6 flex items-center gap-3 text-xl">
                        <div class="p-2 bg-secondary/10 rounded-lg text-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        Información de facturación
                    </h2>
                    
                    <div class="alert alert-warning/10 text-warning text-xs mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <span>La Razón Social y número de identificación no se pueden editar. Contacta a soporte para cambios legales.</span>
                    </div>

                    <?php $formCust = ActiveForm::begin(['id' => 'customer-form']); ?>
                        
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-6">
                                <label class="label font-bold">Tipo Doc.</label>
                                <?= $formCust->field($customer, 'document_type', ['template' => '{input}'])->dropDownList([
                                    'NIT' => 'NIT', 'CC' => 'Cédula (CC)', 'RUT' => 'RUT', 'PASSPORT' => 'Pasaporte', 'OTHER' => 'Otro',
                                ], ['class' => 'select select-bordered w-full bg-base-200 pointer-events-none', 'readonly' => true]) ?>
                            </div>
                            <div class="md:col-span-6">
                                <label class="label font-bold">No. Documento</label>
                                <?= $formCust->field($customer, 'document_number', ['template' => '{input}'])->textInput(['class' => 'input input-bordered w-full bg-base-200', 'readonly' => true]) ?>
                            </div>
                            
                        </div>
                        <div>
                            <label class="label font-bold">Razón Social</label>
                            <?= $formCust->field($customer, 'business_name', ['template' => '{input}'])->textInput(['class' => 'input input-bordered w-full bg-base-200', 'readonly' => true, 'placeholder' => 'Razón Social']) ?>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-4">
                            <div class="md:col-span-6">
                                <label class="label font-bold">Nombre Comercial</label>
                                <?= $formCust->field($customer, 'trade_name', ['template' => '{input}'])->textInput(['class' => 'input input-bordered w-full', 'placeholder' => 'Nombre Comercial (si es diferente)']) ?>
                            </div>
                            
                            <div class="md:col-span-6">
                                <label class="label font-bold">Email Facturación</label>
                                <?= $formCust->field($customer, 'email', ['template' => '{input}'])->input('email', ['class' => 'input input-bordered w-full']) ?>
                            </div>

                            <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin): ?>
                            <div class="md:col-span-12 mt-4 p-4 bg-base-200 rounded-lg">
                                <label class="label font-bold text-xs uppercase opacity-50">Zona Admin</label>
                                <?= $formCust->field($customer, 'status')->dropDownList([
                                    'active' => 'Activo',
                                    'inactive' => 'Inactivo',
                                    'prospect' => 'Prospecto',
                                ], ['class' => 'select select-bordered w-full']) ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-actions justify-end mt-6">
                            <?= Html::submitButton('Guardar Datos Fiscales', ['class' => 'btn btn-primary text-white']) ?>
                        </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>