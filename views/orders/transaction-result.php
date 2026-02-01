<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $order app\models\Orders */
/* @var $wompiData array */

$this->title = 'Resultado de la Transacción';
$status = $wompiData['status']; // APPROVED, DECLINED, ERROR
?>

<div class="min-h-screen bg-base-200 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="card w-full max-w-lg bg-base-100 shadow-xl">
        <div class="card-body text-center items-center">
            
            <?php if ($status == 'APPROVED'): ?>
                <div class="w-24 h-24 bg-success/20 rounded-full flex items-center justify-center mb-4 animate-bounce">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-12 h-12 text-success">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="card-title text-3xl font-bold text-success mb-2">¡Pago Exitoso!</h2>
                <p class="text-base-content/70">Tu orden <strong><?= $order->code ?></strong> ha sido procesada correctamente.</p>
                
                <div class="divider">DETALLES</div>
                
                <div class="w-full text-sm space-y-2 mb-6">
                    <div class="flex justify-between">
                        <span>Referencia Wompi:</span>
                        <span class="font-mono"><?= $wompiData['id'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Método:</span>
                        <span><?= $wompiData['payment_method_type'] ?></span>
                    </div>
                    <div class="flex justify-between font-bold text-lg">
                        <span>Total Pagado:</span>
                        <span><?= Yii::$app->formatter->asCurrency($order->total) ?></span>
                    </div>
                </div>

                <div class="alert alert-info shadow-sm text-left text-xs mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Tus servicios se están aprovisionando. Recibirás un correo con los accesos en breves momentos.</span>
                </div>

                <?= Html::a('Ir a mis Servicios', ['/customer-services'], ['class' => 'btn btn-primary btn-wide']) ?>

            <?php elseif ($status == 'DECLINED'): ?>
                <div class="w-24 h-24 bg-error/20 rounded-full flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-12 h-12 text-error">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <h2 class="card-title text-3xl font-bold text-error mb-2">Pago Rechazado</h2>
                <p class="text-base-content/70">El banco ha declinado la transacción.</p>
                
                <div class="py-4 text-sm opacity-60">
                    Motivo: <?= $wompiData['status_message'] ?? 'Fondos insuficientes o bloqueo de seguridad' ?>
                </div>

                <?= Html::a('Intentar Pagar de Nuevo', ['view', 'id' => $order->id], ['class' => 'btn btn-outline btn-error btn-wide']) ?>

            <?php else: ?>
                <div class="w-24 h-24 bg-warning/20 rounded-full flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-12 h-12 text-warning">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="card-title text-3xl font-bold text-warning mb-2">Procesando...</h2>
                <p class="text-base-content/70">Tu pago está en estado: <strong><?= $status ?></strong>.</p>
                <p class="text-xs mt-2">Por favor espera unos minutos y revisa tu historial.</p>
                
                <?= Html::a('Volver a la Orden', ['view', 'id' => $order->id], ['class' => 'btn btn-ghost mt-4']) ?>

            <?php endif; ?>
            
        </div>
    </div>
</div>