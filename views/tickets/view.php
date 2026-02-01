<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Tickets $model */

$this->title = $model->ticket_code . ' - ' . $model->subject;

// Verificamos si es admin
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;

// Helper para links
function formatMessage($text, $dark=false) {
    $text = Html::encode($text);
    $text = preg_replace(
        '/(https?:\/\/[^\s]+)/', 
        '<a href="$1" target="_blank" class="link link-'.(($dark) ? 'white' : 'primary').' underline break-all">$1</a>', 
        $text
    );
    return nl2br($text);
}

// --- LOGICA DE VISUALIZACIÓN (Mapeos) ---

// 1. Estados
$statusLabels = [
    'open' => ['text' => 'ABIERTO', 'color' => 'badge-error text-white'],
    'answered' => ['text' => 'RESPONDIDO', 'color' => 'badge-success text-white'],
    'closed' => ['text' => 'CERRADO', 'color' => 'badge-neutral text-white'],
];
$st = strtolower($model->status);
$currentStatus = $statusLabels[$st] ?? ['text' => strtoupper($st), 'color' => 'bg-ghost'];

// 2. Prioridades (Tus colores personalizados intactos)
$priorityLabels = [
    //'low' => ['text' => 'Baja', 'color' => 'badge-ghost'],
    'medium' => ['text' => 'Media', 'color' => 'badge-success text-white'],
    'high' => ['text' => 'Alta', 'color' => 'badge-warning'],
    'critical' => ['text' => 'Urgente', 'color' => 'badge-error text-white'],
];
$pr = strtolower($model->priority);
$currentPriority = $priorityLabels[$pr] ?? ['text' => ucfirst($pr), 'color' => 'bg-ghost'];

?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 flex flex-col gap-4">
        
        <div class="card bg-base-100 shadow-xl flex-grow border border-base-200">
            <div class="card-body border-b border-base-200 pb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="card-title text-2xl font-bold break-words">
                            <?= Html::encode($model->subject) ?>
                        </h2>
                        <p class="text-sm text-base-content/60 mt-1">
                            Ticket <span class="font-mono bg-base-200 px-1 rounded select-all"><?= $model->ticket_code ?></span>
                        </p>
                    </div>
                    <div class="badge <?= $currentStatus['color'] ?> badge-lg font-bold p-4">
                        <?= $currentStatus['text'] ?>
                    </div>
                </div>
            </div>

            <div class="card-body bg-base-200/30 max-h-[600px] overflow-y-auto p-4 space-y-6">
                
                <?php if (empty($model->ticketReplies)): ?>
                    <div class="alert alert-info shadow-sm bg-blue-50 text-blue-900 border-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Este es el inicio de la conversación.</span>
                    </div>
                <?php else: ?>
                    
                    <?php foreach ($model->ticketReplies as $reply): ?>
                        <?php 
                            $isSupport = ($reply->sender_type === 'admin'); 
                            $alignment = $isSupport ? 'chat-end' : 'chat-start';
                            $darkLink = $isSupport ? true : false;
                            $bubbleColor = $isSupport ? 'chat-bubble-primary text-primary-content' : 'bg-white text-base-content border border-base-300';
                            
                            $name = $isSupport ? 'Soporte ATSYS' : ($model->customer ? (
                                $model->customer->contact_name . ' ('.$model->customer->business_name.')'
                            ) : $model->email);
                            $avatar = $isSupport ? '🛡️' : '👤';
                        ?>

                        <div class="chat <?= $alignment ?>">
                            <div class="chat-header text-xs opacity-50 mb-1">
                                <?= $name ?>
                                <time class="text-xs opacity-50 ml-1">
                                    <?= Yii::$app->formatter->asRelativeTime($reply->created_at ?? date('Y-m-d H:i:s')) ?>
                                </time>
                            </div>
                            <div class="chat-image avatar placeholder">
                                <div class="w-8 rounded-full bg-base-300 text-center flex items-center justify-center text-xs cursor-default select-none">
                                    <span><?= $avatar ?></span>
                                </div>
                            </div>
                            
                            <div class="chat-bubble <?= $bubbleColor ?> shadow-sm">
                                <?= formatMessage($reply->message, $darkLink) ?>

                                <?php if (!empty($reply->attachment)): ?>
                                    <div class="mt-3 pt-4 border-t border-white/10">
                                        <a href="<?= Yii::getAlias('@web') . '/' . $reply->attachment ?>" target="_blank" class="btn btn-xs btn-outline gap-2 bg-base-100 text-base-content border-0 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                                            Ver Archivo Adjunto
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>
                
                <div id="chat-bottom"></div>
            </div>

            <div class="card-body border-t border-base-200 bg-base-100 pt-6">
                
                <?php $form = ActiveForm::begin([
                    'action' => ['reply', 'id' => $model->id],
                    'options' => ['enctype' => 'multipart/form-data'] 
                ]); ?>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-bold">
                                <?php if($model->status === 'closed'): ?>
                                    <span class="text-warning flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                                        Responder reabrirá este ticket
                                    </span>
                                <?php else: ?>
                                    <?=(Yii::$app->user->identity->isAdmin) ? 'Responder al cliente' : 'Agregar respuesta' ?>
                                <?php endif; ?>
                            </span>
                        </label>
                        
                        <?= Html::textarea('TicketReplies[message]', '', [
                            'class' => 'textarea textarea-bordered h-24 w-full focus:textarea-primary text-base',
                            'placeholder' => 'Escribe tu respuesta aquí...',
                            'required' => true
                        ]) ?>
                    </div>

                    <div class="flex flex-col md:flex-row justify-between items-start mt-4 gap-4">
                        
                        <div class="form-control w-full md:w-auto">
                            <label class="btn btn-outline btn-primary gap-2 w-full md:w-auto cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                </svg>
                                <span id="file-name-display">Adjuntar archivo</span>
                                
                                <?= Html::fileInput('TicketReplies[attachmentFile]', null, [
                                    'class' => 'hidden',
                                    'accept' => '.jpg,.jpeg,.png,.pdf,.zip,.rar',
                                    'onchange' => "
                                        let name = this.files[0] ? this.files[0].name : 'Adjuntar archivo';
                                        if(name.length > 20) name = name.substring(0, 17) + '...';
                                        document.getElementById('file-name-display').innerText = name;
                                    "
                                ]) ?>
                            </label>
                            <label class="label pb-0 justify-center md:justify-start">
                                <span class="label-text-alt text-base-content/50">Max: 10MB</span>
                            </label>
                        </div>

                        <div class="w-full md:w-auto text-right">
                            <button type="submit" class="btn btn-primary gap-2 text-white px-8 shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                                </svg>
                                Enviar Respuesta
                            </button>
                        </div>
                    </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-4">
        
        <?php if ($model->status !== 'closed'): ?>
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-5">
                    <h3 class="card-title text-xs uppercase font-bold tracking-wider mb-2 opacity-50">Acciones</h3>
                    
                    <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> Marcar como Resuelto', 
                        ['close', 'id' => $model->id], 
                        [
                            'class' => 'btn btn-outline btn-success btn-block gap-2',
                            'data-method' => 'post',
                            'data-confirm' => '¿Confirmas que el problema ha sido resuelto? El ticket cambiará a estado Cerrado.'
                        ]
                    ) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
        <div class="card bg-base-100 shadow-xl border-l-4 border-error">
            <div class="card-body p-5">
                <h3 class="card-title text-xs uppercase text-error font-bold tracking-wider mb-2">Zona de Peligro</h3>
                <div class="flex flex-col gap-2">
                    <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg> Eliminar Ticket', 
                        ['delete', 'id' => $model->id], 
                        [
                            'class' => 'btn btn-outline btn-error btn-sm justify-start w-full',
                            'data' => [
                                'confirm' => '¿ESTÁS SEGURO? Esta acción es irreversible.',
                                'method' => 'post',
                            ],
                        ]
                    ) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-5">
                <h3 class="card-title text-lg mb-4">Información</h3>
                
                <div class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <tbody>
                            <tr>
                                <th class="text-base-content/60 font-normal pl-0">Prioridad:</th>
                                <td class="text-right pr-0">
                                    <div class="badge <?= $currentPriority['color'] ?> badge-sm font-semibold border-0">
                                        <?= $currentPriority['text'] ?>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <th class="text-base-content/60 font-normal pl-0">Fuente:</th>
                                <td class="text-right pr-0">
                                    <div class="badge badge-outline gap-1 badge-sm uppercase text-xs">
                                        <?= $model->source ?>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <th class="text-base-content/60 font-normal pl-0">Creado:</th>
                                <td class="text-right pr-0 text-sm">
                                    <?= Yii::$app->formatter->asDate($model->created_at, 'short') ?>
                                </td>
                            </tr>

                            <tr>
                                <th class="text-base-content/60 font-normal pl-0 pt-3 align-top">Email:</th>
                                <td class="text-right pr-0 pt-3">
                                    <?= Html::a($model->email, 'mailto:'.$model->email, [
                                        'class' => 'link link-hover text-sm break-all inline-block text-right'
                                    ]) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?= Html::a('← Volver a la lista', ['index'], ['class' => 'btn btn-ghost btn-block text-base-content/60']) ?>
    </div>

</div>