<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $product app\models\Products */
/* @var $model yii\base\DynamicModel */

$this->title = 'Configurar Dominio - ' . $product->name;
?>

<div class="py-10 px-4 min-h-screen bg-base-200">
    <div class="max-w-6xl mx-auto">
        
        <div class="mb-8">
            <?= Html::a('← Volver al catálogo', ['index'], ['class' => 'link link-hover text-sm opacity-60']) ?>
            <h1 class="text-3xl font-bold mt-2">Configura tu servicio</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-3">
                <?php $form = ActiveForm::begin([
                    'id' => 'domain-config-form',
                    'options' => ['class' => 'space-y-6']
                ]); ?>

                <div class="card bg-base-100 shadow-xl overflow-hidden">
                    <div class="card-body p-0">
                        <div class="tabs tabs-boxed bg-base-300 p-2 rounded-none gap-1" x-data="{ tab: 'register' }">
                            </div>
                        
                        <div class="p-6 space-y-6">
                            <h3 class="font-bold text-lg mb-4">Elige una opción para tu dominio</h3>

                            <div class="form-control border border-base-300 rounded-box p-4 hover:border-primary transition-colors cursor-pointer group">
                                <label class="label cursor-pointer justify-start gap-4">
                                    <input type="radio" name="DynamicModel[action]" value="register" class="radio radio-primary" checked onclick="document.getElementById('domain-input-group').style.display='flex'">
                                    <div>
                                        <span class="label-text font-bold block group-hover:text-primary">Registrar un nuevo dominio</span>
                                        <span class="label-text-alt">Buscaremos la disponibilidad para ti.</span>
                                    </div>
                                </label>
                            </div>

                            <div class="form-control border border-base-300 rounded-box p-4 hover:border-primary transition-colors cursor-pointer group">
                                <label class="label cursor-pointer justify-start gap-4">
                                    <input type="radio" name="DynamicModel[action]" value="transfer" class="radio radio-primary" onclick="document.getElementById('domain-input-group').style.display='flex'">
                                    <div>
                                        <span class="label-text font-bold block group-hover:text-primary">Transferir mi dominio a ATSYS</span>
                                        <span class="label-text-alt">Mueve tu dominio con nosotros para renovarlo.</span>
                                    </div>
                                </label>
                            </div>

                            <div class="form-control border border-base-300 rounded-box p-4 hover:border-primary transition-colors cursor-pointer group">
                                <label class="label cursor-pointer justify-start gap-4">
                                    <input type="radio" name="DynamicModel[action]" value="own" class="radio radio-primary" onclick="document.getElementById('domain-input-group').style.display='flex'">
                                    <div>
                                        <span class="label-text font-bold block group-hover:text-primary">Usaré mi propio dominio</span>
                                        <span class="label-text-alt">Solo actualizaré los nombres de servidor (DNS).</span>
                                    </div>
                                </label>
                            </div>

                            <div class="divider">NOMBRE DEL DOMINIO</div>

                            <div id="domain-input-group" class="join w-full">
                                <div class="w-full">
                                    <?= $form->field($model, 'domain', [
                                        'template' => "{input}\n{error}",
                                        'options' => ['class' => 'w-full'],
                                    ])->textInput([
                                        'class' => 'input input-bordered join-item w-full focus:outline-none', 
                                        'placeholder' => 'ejemplo-empresa'
                                    ])->label(false) ?>
                                </div>
                                
                                <select name="DynamicModel[extension]" class="select select-bordered join-item">
                                    <option value=".com">.com</option>
                                    <option value=".co">.co</option>
                                    <option value=".org">.org</option>
                                    <option value=".net">.net</option>
                                    <option value=".com.co">.com.co</option>
                                </select>
                            </div>
                            <p class="text-xs text-base-content/50 ml-1">Escribe solo el nombre (sin www ni http).</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg shadow-xl">
                    Continuar al Resumen
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 ml-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                    </svg>
                </button>

                <?php ActiveForm::end(); ?>
            </div>

            <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-xl sticky top-8 border border-base-200">
                    <div class="card-body">
                        <h2 class="card-title text-lg border-b border-base-200 pb-2 mb-2">Resumen del Pedido</h2>
                        
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-bold text-primary"><?= Html::encode($product->name) ?></h3>
                                <p class="text-xs text-base-content/60"><?= nl2br(Html::encode($product->description)) ?></p>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-lg"><?= Yii::$app->formatter->asCurrency($product->price) ?></span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-sm opacity-60 mb-6">
                            <span>Dominio</span>
                            <span>--</span>
                        </div>

                        <div class="divider my-0"></div>

                        <div class="flex justify-between items-center mt-4 text-xl font-extrabold">
                            <span>Total Hoy:</span>
                            <span class="text-primary"><?= Yii::$app->formatter->asCurrency($product->price) ?></span>
                        </div>
                        
                        <div class="mt-2 text-xs text-center opacity-50">
                            * Precios no incluyen impuestos si aplican.
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="py-6 text-center space-y-2 opacity-60">
                            <div class="flex justify-center gap-4">
                                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                            <p class="text-xs">Pago 100% Seguro y Encriptado</p>
                        </div>
                    </div>
                </div>
                
            </div>

        </div>
    </div>
</div>