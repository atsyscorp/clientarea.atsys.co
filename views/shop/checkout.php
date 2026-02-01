<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $cart array */
/* @var $isGuest boolean */
/* @var $customer app\models\Customers|null */
/* @var $modelLogin app\models\LoginForm|null */

$this->title = 'Finalizar Compra';
?>

<div class="py-10 px-4 min-h-screen bg-base-200">
    <div class="max-w-6xl mx-auto">
        
        <div class="mb-8">
            <?= Html::a('← Volver atrás', ['shop/configure', 'id' => $cart['product_id']], ['class' => 'link link-hover text-sm opacity-60']) ?>
            <h1 class="text-3xl font-bold mt-2">Revisión y Pago</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-xl sticky top-8">
                    <div class="card-body p-6">
                        <div class="flex justify-between items-center border-b border-base-200 pb-4 mb-4">
                            <h2 class="card-title text-lg m-0">Resumen del Pedido</h2>
                            <?= Html::a('Vaciar', ['clear-cart'], [
                                'class' => 'btn btn-xs btn-ghost text-error',
                                'data-confirm' => '¿Cancelar toda la compra?'
                            ]) ?>
                        </div>
                        
                        <?php if (isset($cart['hosting_price'])): ?>
                        <div class="flex justify-between items-start mb-2 bg-base-200/50 p-2 pl-7 rounded-lg relative group transition-all hover:bg-base-200">
                            <div>
                                <h3 class="font-bold text-primary text-sm"><?= Html::encode($cart['product_name']) ?></h3>
                                <p class="text-xs text-base-content/60">Plan de Hosting</p>
                            </div>
                            <div class="text-right font-medium text-sm">
                                <?= Yii::$app->formatter->asCurrency($cart['hosting_price']) ?>
                            </div>

                            <?= Html::a('×', ['clear-cart'], [
                                'class' => 'absolute -left-4 start-auto end-auto btn btn-circle btn-xs btn-error text-white opacity-0 group-hover:opacity-100 transition-opacity shadow-md',
                                'title' => 'Quitar producto',
                                'data' => [
                                    'confirm' => '¿Quitar el plan de hosting vaciará tu carrito. ¿Continuar?',
                                    'method' => 'post'
                                ]
                            ]) ?>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($cart['domain_action']) && $cart['domain_action'] != 'none' && $cart['domain_action'] != 'own'): ?>
                        <div class="flex justify-between items-start mb-2 bg-base-200/50 p-2 pl-7 rounded-lg relative group">
                            <div>
                                <h3 class="font-bold text-primary text-sm">Dominio: <?= Html::encode($cart['domain']) ?></h3>
                                <span class="badge badge-xs badge-ghost uppercase mt-1"><?php
                                    switch($cart['domain_action']):
                                        case 'register': echo 'Registro nuevo'; break;
                                        case 'transfer': echo 'Transferencia'; break;
                                        default: echo $cart['domain_action']; break;
                                    endswitch;
                                ?></span>
                            </div>
                            <div class="text-right font-medium text-sm">
                                <?= Yii::$app->formatter->asCurrency($cart['domain_price']) ?>
                            </div>
                            <?= Html::a('×', ['remove-domain'], [
                                'class' => 'absolute -left-4 start-auto end-auto btn btn-circle btn-xs btn-error text-white opacity-0 group-hover:opacity-100 transition-opacity shadow-md',
                                'title' => 'Quitar dominio',
                                'data-method' => 'post'
                            ]) ?>
                        </div>
                        <?php elseif (isset($cart['domain_action']) && $cart['domain_action'] == 'own'): ?>
                        <div class="flex justify-between items-start mb-2 bg-base-200/50 p-2 pl-7 rounded-lg relative group">
                            <div>
                                <h3 class="font-bold text-sm"><?= Html::encode($cart['domain']) ?></h3>
                                <span class="text-xs opacity-60">Dominio Propio (DNS)</span>
                            </div>
                            <div class="text-right font-medium text-sm">
                                $0
                            </div>
                            <?= Html::a('×', ['remove-domain'], [
                                'class' => 'absolute -left-4 start-auto end-auto btn btn-circle btn-xs btn-error text-white opacity-0 group-hover:opacity-100 transition-opacity shadow-md',
                                'data-method' => 'post'
                            ]) ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning shadow-sm py-2 text-xs flex justify-between">
                            <span>Sin dominio seleccionado.</span>
                            <?= Html::a('Agregar', ['configure', 'id' => $cart['product_id']], ['class' => 'link font-bold']) ?>
                        </div>
                        <?php endif; ?>

                        <div class="divider my-2"></div>

                        <div class="flex justify-between items-center text-xl font-extrabold text-primary">
                            <span>Total a Pagar:</span>
                            <span><?= Yii::$app->formatter->asCurrency($cart['total']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">

                <div class="card bg-base-100 shadow-xl border border-base-200">
                    <div class="card-body text-center pt-8 pb-8">

                        <?php if ($isGuest): ?>
                        <div class="card bg-base-100 shadow-xl border-t-4 border-primary">
                            <div class="card-body">
                                <h2 class="card-title">1. Identifícate para continuar</h2>
                                <p class="text-sm opacity-70 mb-4">Necesitamos crear una cuenta para asignarte tus servicios.</p>

                                <div class="grid md:grid-cols-2 gap-8 mt-4">
                                    <div>
                                        <h3 class="font-bold mb-3">Ya tengo cuenta</h3>
                                        <?php $form = ActiveForm::begin(['action' => ['site/login']]); ?>
                                            <?= $form->field($modelLogin, 'username')->textInput(['placeholder' => 'Usuario', 'class' => 'input input-bordered w-full'])->label(false) ?>
                                            <?= $form->field($modelLogin, 'password')->passwordInput(['placeholder' => 'Contraseña', 'class' => 'input input-bordered w-full mt-2'])->label(false) ?>
                                            <button type="submit" class="btn btn-primary btn-sm mt-3 w-full">Iniciar Sesión</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                    
                                    <div class="border-l border-base-200 pl-8">
                                        <h3 class="font-bold mb-3">Soy nuevo cliente</h3>
                                        <p class="text-xs mb-4">Crea una cuenta en 1 minuto para gestionar tus servicios.</p>
                                        <?= Html::a('Crear Cuenta', ['site/signup'], ['class' => 'btn btn-outline w-full']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php else: ?>

                        <div class="avatar placeholder mb-4 justify-center">
                            <div class="bg-primary text-primary-content rounded-full w-24 h-24 shadow-lg shadow-primary/30">
                                <span class="text-4xl font-bold tracking-wider">
                                    <?= strtoupper(substr($customer->business_name, 0, 1)) ?>
                                </span>
                            </div>
                        </div>

                        <h2 class="text-2xl font-bold mb-1">
                            ¡Hola, <?= Html::encode(explode(' ', $customer->business_name)[0]) ?>!
                        </h2>
                        <p class="text-sm opacity-70 mb-6">
                            La orden se generará a nombre de esta cuenta.
                        </p>

                        <div class="flex flex-wrap flex-col gap-2 justify-center items-center opacity-80">
                            <span class="badge badge-ghost badge-sm py-3 px-4 gap-1 font-mono">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3"><path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" /><path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" /></svg>
                                <?= $customer->email ?>
                            </span>
                            <span class="badge badge-ghost badge-sm py-3 px-4 gap-1 font-mono">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3"><path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 100-12 6 6 0 000 12zm-2-7a2 2 0 114 0 2 2 0 01-4 0z" clip-rule="evenodd" /></svg>
                                <?= $customer->document_number ?>
                            </span>
                        </div>

                        <?php endif; ?>
                    </div>
                </div>

                <?= Html::beginForm(['checkout'], 'post') ?>
                    <button type="submit" class="btn btn-primary btn-lg w-full shadow-xl hover:shadow-2xl transition-all hover:-translate-y-1 gap-3">
                        Realizar pago
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                    </button>
                    <p class="text-center text-xs mt-3 opacity-60 mt-3">
                        Al continuar aceptas nuestros términos de servicio y política de privacidad.
                    </p>
                <?= Html::endForm() ?>

            </div>
        </div>
    </div>
</div>