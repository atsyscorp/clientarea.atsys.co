<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Tickets $model */
/** @var array $customers */ // Definimos que recibimos esta variable

$this->title = 'Abrir Nuevo Ticket';

// Verificamos si es admin
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
?>

<div class="flex justify-center items-start min-h-[calc(100vh-10rem)] py-6">
    
    <div class="card w-full max-w-2xl bg-base-100 shadow-xl">
        <div class="card-body">
            
            <div class="flex items-center gap-4 border-b border-base-200 pb-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                </div>
                <div>
                    <h1 class="card-title text-2xl font-bold">Nuevo Ticket de Soporte</h1>
                    <p class="text-base-content/60 text-sm">Describe tu solicitud y te responderemos a la brevedad.</p>
                </div>
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'create-ticket-form',
                'options' => ['class' => 'space-y-4','enctype' => 'multipart/form-data'],
            ]); ?>

            <?php if ($isAdmin): ?>
                <div class="bg-base-200 p-4 rounded-lg border border-base-300 mb-2">
                    <div class="flex items-center gap-2 mb-2 text-primary font-bold text-sm uppercase tracking-wide">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Asignación Administrativa
                    </div>
                    
                    <?= $form->field($model, 'customer_id', [
                        'template' => "{label}\n{input}\n{error}",
                        'labelOptions' => ['class' => 'label-text font-bold mb-1 block'],
                    ])->dropDownList($customers ?? [], [ // Usamos $customers enviado desde el controlador
                        'prompt' => 'Seleccione el cliente...',
                        'class' => 'select select-bordered w-full focus:select-primary',
                        'onchange' => 'toggleEmailField(this.value)' // Disparador
                    ])->label('¿A nombre de qué cliente es el ticket?') ?>

                    <div id="email-container" style="display: none;">
                        <?= $form->field($model, 'email')->textInput([
                            'placeholder' => 'correo@externo.com',
                            'class' => 'input input-bordered w-full border-warning' // Borde amarillo para resaltar
                        ])->label('Email de Contacto (Externo)') ?>
                    </div>

                    <?= $form->field($model, 'source')->dropDownList([
                        'web' => 'Web / Portal',
                        'email' => 'Email',
                        'whatsapp' => 'WhatsApp'
                    ], ['class' => 'select select-bordered w-full']) ?>
                </div>

            <?php endif; ?>

            <?= $form->field($model, 'priority')->dropDownList([
                'medium' => 'Medio', 
                'high' => 'Alto', 
                //'critical' => 'Urgente'
            ], ['class' => 'select select-bordered w-full']) ?>

            <?= $form->field($model, 'subject', [
                'template' => "{label}\n<div class=\"relative\">{input}<div class=\"absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400\"><svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\" class=\"w-5 h-5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z\" /></svg></div></div>\n{error}",
                'labelOptions' => ['class' => 'label-text font-bold mb-1 block'],
                'inputOptions' => ['class' => 'input input-bordered w-full pr-10 focus:input-primary', 'placeholder' => 'Ej: Error al cargar mi perfil'],
            ])->textInput([
                'autofocus' => true,
                'value' => Yii::$app->request->get('subject', ''),
            ])->label('Asunto / Motivo') ?>

            <?= $form->field($model, 'message', [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'label-text font-bold mb-1 block'],
                'inputOptions' => [
                    'class' => 'textarea textarea-bordered w-full h-32 focus:textarea-primary text-base', 
                    'placeholder' => 'Por favor detalla lo que sucede...'
                ],
            ])->textarea()->label('Descripción Detallada') ?>

            <div class="form-control w-full md:w-auto mb-4">
                <label class="btn btn-outline btn-primary gap-2 w-full md:w-auto cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                    </svg>
                    
                    <span id="file-name-create">Adjuntar archivo (Opcional)</span>
                    
                    <?= Html::fileInput('Tickets[attachmentFile]', null, [
                        'class' => 'hidden',
                        'accept' => '.jpg,.jpeg,.png,.pdf,.zip,.rar',
                        'onchange' => "
                            let name = this.files[0] ? this.files[0].name : 'Adjuntar archivo';
                            if(name.length > 25) name = name.substring(0, 22) + '...';
                            document.getElementById('file-name-create').innerText = name;
                        "
                    ]) ?>
                </label>
            </div>

            <div class="card-actions justify-end mt-6 pt-4 border-t border-base-200">
                <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-ghost']) ?>
                
                <button type="submit" class="btn btn-primary gap-2 text-white shadow-lg shadow-primary/30">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                    Crear Ticket
                </button>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
<script>
function toggleEmailField(val) {
    const emailBlock = document.getElementById('email-container');
    
    // Si el valor es 9999, mostramos el campo
    if (val == '9999') {
        emailBlock.style.display = 'block';
        // Opcional: Poner el foco en el campo email
        document.getElementById('tickets-email').focus(); 
    } else {
        emailBlock.style.display = 'none';
        // Opcional: Limpiar el campo si se oculta para no enviar basura
        // document.getElementById('tickets-email').value = ''; 
    }
}

// Ejecutar al cargar la página (por si falla la validación y recarga, mantener el estado)
document.addEventListener("DOMContentLoaded", function() {
    // Asegúrate de que el ID del input generado por Yii sea 'tickets-customer_id' o usa el ID que pusimos 'select-customer'
    // Yii suele generar ids como 'nombredelmodelo-atributo'. Si tu modelo es Tickets:
    const currentVal = document.getElementById('tickets-customer_id').value;
    toggleEmailField(currentVal);
});
</script>