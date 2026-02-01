<?php

/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */
/** @var Exception $exception */

use yii\helpers\Html;

$this->title = $name;
$code = (property_exists($exception, 'statusCode')) ? $exception->statusCode : 500;
?>

<div class="hero min-h-screen bg-base-200">
    <div class="hero-content text-center flex justify-center">
        <div class="max-w-md">
            
            <div class="mb-8 relative inline-block">
                <div class="absolute inset-0 bg-error/20 blur-xl rounded-full"></div>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-64 h-64 text-error relative z-10 mx-auto">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>

            <h1 class="text-9xl font-black text-base-300 select-none"><?= $code ?></h1>
            
            <h2 class="text-3xl font-bold mt-4 mb-2">¡Ups! Algo salió mal</h2>
            
            <div class="alert alert-warning shadow-lg my-6 text-left text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span><?= nl2br(Html::encode($message)) ?></span>
            </div>

            <p class="py-6 opacity-70">
                Lo sentimos, la página que buscas no existe o ha ocurrido un error interno. 
                Por favor intenta volver al inicio o contacta a soporte si el problema persiste.
            </p>

            <div class="flex gap-4 justify-center">
                <?= Html::a('Volver al Inicio', Yii::$app->homeUrl, ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Contactar Soporte', ['tickets/create'], ['class' => 'btn btn-outline']) ?>
            </div>
        </div>
    </div>
</div>