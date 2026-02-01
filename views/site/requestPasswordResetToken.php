<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var yii\widgets\ActiveForm $form */
/** @var app\models\PasswordResetRequestForm $model */

$this->title = 'Recuperar Contraseña';
?>

<div class="min-h-screen flex bg-base-100">

    <div class="hidden lg:flex w-1/2 bg-[#134C42]/70 items-center justify-center relative overflow-hidden">
        <div class="absolute bg-white opacity-10 w-96 h-96 rounded-full -top-10 -left-10 blur-3xl"></div>
        <div class="absolute bg-white opacity-10 w-80 h-80 rounded-full bottom-10 right-10 blur-3xl"></div>

        <div class="z-10 text-center text-primary-content px-10">
            <div class="mb-6 flex justify-center">
                 <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl border border-white/30">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                    </svg>
                </div>
            </div>
            
            <h1 class="text-4xl font-bold mb-2">Recuperación</h1>
            <p class="text-lg opacity-90 max-w-md mx-auto">
                No te preocupes, te ayudaremos a restablecer tu acceso de forma segura.
            </p>
        </div>
    </div>

    <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-8 md:p-16">
        
        <div class="w-full max-w-md space-y-6">
            <div class="text-center lg:text-left">
                <h2 class="text-3xl font-bold text-gray-900">¿Olvidaste tu contraseña?</h2>
                <p class="mt-2 text-sm text-gray-500">Ingresa tu correo electrónico y te enviaremos un enlace para restablecerla.</p>
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'request-password-reset-form',
                'options' => ['class' => 'mt-8 space-y-6'],
                'fieldConfig' => [
                    'template' => "{label}\n<div class=\"relative\">{input}</div>\n{error}",
                    'labelOptions' => ['class' => 'label-text font-semibold text-gray-700 mb-1 block'],
                    'inputOptions' => ['class' => 'input input-bordered w-full pl-10 focus:input-primary transition-all'],
                    'errorOptions' => ['class' => 'text-error text-sm mt-1'],
                ],
            ]); ?>

            <?= $form->field($model, 'email', [
                'template' => "{label}\n<div class=\"relative\">
                    <div class=\"absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400\">
                        <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\" class=\"w-5 h-5\">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75\" />
                        </svg>
                    </div>
                    {input}
                </div>\n{error}"
            ])->textInput(['placeholder' => 'ejemplo@empresa.com', 'autofocus' => true]) ?>

            <div>
                <?= Html::submitButton('Enviar enlace de recuperación', [
                    'class' => 'btn btn-primary w-full text-white text-lg shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5',
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    ¿Lo recordaste? 
                    <?= Html::a('Volver al Login', ['site/login'], ['class' => 'font-medium text-primary hover:text-primary-focus hover:underline']) ?>
                </p>
            </div>
            
            <div class="mt-8 text-center text-xs text-gray-400">
                &copy; <?= date('Y') ?> Arkitech Systems SAS. Todos los derechos reservados.
            </div>
        </div>
    </div>
</div>