<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var yii\widgets\ActiveForm $form */
/** @var app\models\ResetPasswordForm $model */

$this->title = 'Nueva Contraseña';
?>

<div class="min-h-screen flex bg-base-100">

    <div class="hidden lg:flex w-1/2 bg-[#134C42]/70 items-center justify-center relative overflow-hidden">
        <div class="absolute bg-white opacity-10 w-96 h-96 rounded-full -top-10 -left-10 blur-3xl"></div>
        <div class="absolute bg-white opacity-10 w-80 h-80 rounded-full bottom-10 right-10 blur-3xl"></div>

        <div class="z-10 text-center text-primary-content px-10">
            <div class="mb-6 flex justify-center">
                 <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl border border-white/30">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
            </div>
            
            <h1 class="text-4xl font-bold mb-2">Seguridad</h1>
            <p class="text-lg opacity-90 max-w-md mx-auto">
                Crea una contraseña segura para proteger tu cuenta y tus datos.
            </p>
        </div>
    </div>

    <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-8 md:p-16">
        
        <div class="w-full max-w-md space-y-6">
            <div class="text-center lg:text-left">
                <h2 class="text-3xl font-bold text-gray-900">Restablecer Contraseña</h2>
                <p class="mt-2 text-sm text-gray-500">Ingresa tu nueva clave a continuación.</p>
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'reset-password-form',
                'options' => ['class' => 'mt-8 space-y-6'],
                'fieldConfig' => [
                    'template' => "{label}\n<div class=\"relative\">{input}</div>\n{error}",
                    'labelOptions' => ['class' => 'label-text font-semibold text-gray-700 mb-1 block'],
                    'inputOptions' => ['class' => 'input input-bordered w-full pl-10 focus:input-primary transition-all'],
                    'errorOptions' => ['class' => 'text-error text-sm mt-1'],
                ],
            ]); ?>

            <?= $form->field($model, 'password', [
                'template' => "{label}\n<div class=\"relative\">
                    <div class=\"absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400\">
                        <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\" class=\"w-5 h-5\">
                          <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z\" />
                        </svg>
                    </div>
                    {input}
                </div>\n{error}"
            ])->passwordInput(['placeholder' => 'Nueva contraseña', 'autofocus' => true])->label('Nueva Contraseña') ?>
            
            <?= $form->field($model, 'confirm_password', [
                'template' => "{label}\n<div class=\"relative\">
                    <div class=\"absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400\">
                        <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\" class=\"w-5 h-5\">
                          <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z\" />
                        </svg>
                    </div>
                    {input}
                </div>\n{error}"
            ])->passwordInput(['placeholder' => 'Repite la contraseña'])->label('Confirmar Contraseña') ?>

            <div>
                <?= Html::submitButton('Guardar Nueva Contraseña', [
                    'class' => 'btn btn-primary w-full text-white text-lg shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5',
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
            
            <div class="mt-8 text-center text-xs text-gray-400">
                &copy; <?= date('Y') ?> Arkitech Systems SAS. Todos los derechos reservados.
            </div>
        </div>
    </div>
</div>