<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $products app\models\Products[] */

$this->title = 'Nuestros Planes y Servicios';
$selectedCurrency = Yii::$app->session->get('currency', 'COP');
?>

<div class="py-12 px-4 sm:px-6 lg:px-8 min-h-screen bg-base-200">
    
    <div class="flex justify-end max-w-7xl mx-auto mb-6 no-print">
        <div class="join border border-base-300 bg-base-100 shadow-sm p-1 rounded-xl">
            <span class="join-item px-4 flex items-center text-xs font-bold uppercase tracking-wider opacity-60">Moneda:</span>
            <a href="?currency=COP" class="join-item btn btn-sm <?= $selectedCurrency === 'COP' ? 'btn-primary text-white' : 'btn-ghost' ?>">🇨🇴 COP</a>
            <a href="?currency=USD" class="join-item btn btn-sm <?= $selectedCurrency === 'USD' ? 'btn-primary text-white' : 'btn-ghost' ?>">🇺🇸 USD</a>
            <a href="?currency=EUR" class="join-item btn btn-sm <?= $selectedCurrency === 'EUR' ? 'btn-primary text-white' : 'btn-ghost' ?>">🇪🇺 EUR</a>
        </div>
    </div>
    
    <div class="text-center mb-12">
        <h1 class="text-4xl font-extrabold text-base-content sm:text-5xl sm:tracking-tight lg:text-6xl">
            Elige el plan perfecto para ti
        </h1>
        <p class="mt-5 max-w-xl mx-auto text-xl text-base-content/60">
            Infraestructura de alto rendimiento controlada y administrada por expertos.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 max-w-7xl mx-auto">
        
        <?php foreach ($products as $product): ?>
        <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300 border border-base-200">
            
            <div class="card-body items-center text-center">
                <h2 class="card-title text-2xl font-bold text-primary">
                    <?= Html::encode($product->name) ?>
                </h2>
                
                <div class="my-4">
                    <span class="text-4xl font-extrabold text-base-content">
                        <?= Yii::$app->formatter->asCurrency($product->getConvertedPrice($selectedCurrency), $selectedCurrency) ?>
                    </span>
                    <span class="text-base font-medium text-base-content/50">
                        /<?= $product->billing_cycle == 'monthly' ? 'mes' : ($product->billing_cycle == 'yearly' ? 'año' : 'único') ?>
                    </span>
                </div>

                <div class="text-sm text-base-content/70 mb-6 min-h-[60px]">
                    <?= nl2br(Html::encode($product->description)) ?>
                </div>

                <div class="card-actions justify-center w-full mt-auto">
                    <?= Html::a('Contratar Ahora', [
                        'shop/configure', 
                        'id' => $product->id,
                        'domain' => Yii::$app->request->get('domain'),
                        'extension' => Yii::$app->request->get('extension'),
                        'action' => Yii::$app->request->get('action')
                    ], [
                        'class' => 'btn btn-primary w-full shadow-lg shadow-primary/30',
                    ]) ?>
                </div>
            </div>
            
            </div>
        <?php endforeach; ?>

        <?php if (empty($products)): ?>
            <div class="col-span-full text-center text-gray-500 py-20">
                <p class="text-xl">No hay productos disponibles en este momento.</p>
            </div>
        <?php endif; ?>

    </div>
</div>