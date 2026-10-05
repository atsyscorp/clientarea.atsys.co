<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Customers $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="customers-form">

    <?php 
    // Configuración global para que todos los inputs tengan estilo DaisyUI
    $form = ActiveForm::begin([
        'options' => ['class' => 'space-y-6'], // Espacio vertical entre tarjetas
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'options' => ['class' => 'form-control w-full'], // Wrapper del campo
            'labelOptions' => ['class' => 'label label-text font-semibold'], // Estilo del label
            'inputOptions' => ['class' => 'input input-bordered w-full focus:input-primary'], // Estilo del input
            'errorOptions' => ['class' => 'text-error text-sm mt-1'], // Estilo del error
        ],
    ]); 
    ?>

    <div class="card w-full bg-base-100 shadow-xl border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-primary border-b border-base-200 pb-2 mb-4">
                <i class="fas fa-building mr-2"></i> Información de la Empresa
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-3">
                    <?= $form->field($model, 'document_type')->dropDownList([
                        'NIT' => 'NIT',
                        'CC' => 'Cédula (CC)',
                        'RUT' => 'RUT',
                        'PASSPORT' => 'Pasaporte',
                        'OTHER' => 'Otro',
                    ], ['class' => 'select select-bordered w-full']) ?>
                </div>
                <div class="md:col-span-3">
                    <?= $form->field($model, 'document_number')->textInput() ?>
                </div>
                <div class="md:col-span-6">
                    <?= $form->field($model, 'business_name')->textInput(['placeholder' => 'Razón Social']) ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-2">
                <div class="md:col-span-6">
                    <?= $form->field($model, 'trade_name')->textInput(['placeholder' => 'Nombre Comercial (si es diferente)']) ?>
                </div>
                <div class="md:col-span-3">
                    <?= $form->field($model, 'email')->input('email') ?>
                </div>
                <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin): ?>
                <div class="md:col-span-3">
                    <?= $form->field($model, 'status')->dropDownList([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'prospect' => 'Prospecto',
                    ], ['class' => 'select select-bordered w-full']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin): ?>
        <?php
        $usersList = \yii\helpers\ArrayHelper::map(
            \app\models\User::find()
                ->select(['id', 'username', 'email', 'role'])
                ->where(['status' => \app\models\User::STATUS_ACTIVE])
                ->orderBy(['username' => SORT_ASC])
                ->all(),
            'id',
            function ($user) {
                $roleLabel = ($user->role == \app\models\User::ROLE_ADMIN) ? ' [Admin]' : (($user->role == \app\models\User::ROLE_SUBACCOUNT) ? ' [Subcuenta]' : ' [Cliente]');
                return $user->username . ' (' . $user->email . ')' . $roleLabel;
            }
        );
        ?>
        <div class="card w-full bg-base-100 shadow-xl border border-primary/20 bg-primary/5">
            <div class="card-body">
                <h2 class="card-title text-primary border-b border-base-200 pb-2 mb-2">
                    <i class="fas fa-user-check mr-2"></i> Asignación de Usuario (Cuenta de Acceso)
                </h2>
                <p class="text-xs text-base-content/60 mb-3">
                    Selecciona la cuenta de usuario que administrará y tendrá acceso como titular de este perfil de cliente.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-8">
                        <?= $form->field($model, 'user_id')->dropDownList(
                            $usersList,
                            [
                                'prompt' => '-- Sin usuario vinculado (Opcional) --',
                                'class' => 'select select-bordered w-full'
                            ]
                        )->label('Usuario Designado') ?>
                    </div>
                    <div class="md:col-span-4 flex items-end pb-1">
                        <span class="text-xs text-base-content/50">
                            <i class="fas fa-info-circle mr-1"></i> Puedes crear el usuario primero en el módulo de Usuarios o asignarlo posteriormente.
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card w-full bg-base-100 shadow-xl border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-primary border-b border-base-200 pb-2 mb-4">
                <i class="fas fa-user-tie mr-2"></i> Contacto Principal
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?= $form->field($model, 'contact_name')->textInput() ?>
                <?= $form->field($model, 'contact_position')->textInput() ?>
                <?= $form->field($model, 'primary_phone')->textInput() ?>
                <?= $form->field($model, 'secondary_phone')->textInput() ?>
            </div>
        </div>
    </div>

    <div class="card w-full bg-base-100 shadow-xl border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-primary border-b border-base-200 pb-2 mb-4">
                <i class="fas fa-map-marker-alt mr-2"></i> Ubicación y Notas
            </h2>

            <div class="grid grid-cols-1 gap-4">
                <?= $form->field($model, 'address')->textInput() ?>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                <?= $form->field($model, 'city')->textInput() ?>
                <?= $form->field($model, 'state_province')->textInput() ?>
            </div>

            <div class="mt-4">
                <?= $form->field($model, 'notes', [
                    'inputOptions' => ['class' => 'textarea textarea-bordered h-24 w-full']
                ])->textarea() ?>
            </div>
        </div>
    </div>

    <div class="flex justify-end mt-6">
        <?= Html::submitButton('<i class="fas fa-save mr-2"></i> Guardar Cliente', [
            'class' => 'btn btn-primary btn-wide text-white shadow-lg hover:scale-105 transition-transform'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>