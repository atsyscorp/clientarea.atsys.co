<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\HtmlPurifier;

/** @var yii\web\View $this */
/** @var app\models\Tickets $model */

$this->title = $model->ticket_code . ' - ' . $model->subject;

// Verificamos si es admin
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;

// Registrar delegados del cliente del ticket en JS
$delegatesData = [];
if ($model->customer && $model->customer->user_id) {
    $delegates = \app\models\User::find()
        ->where([
            'or',
            ['id' => $model->customer->user_id],
            ['parent_id' => $model->customer->user_id]
        ])
        ->andWhere(['status' => \app\models\User::STATUS_ACTIVE])
        ->all();
    foreach ($delegates as $delegate) {
        $delegatesData[] = [
            'id' => $delegate->id,
            'contact_name' => $delegate->contact_name,
            'username' => $delegate->username,
            'email' => $delegate->email,
        ];
    }
}
$this->registerJs('window.ticketDelegates = ' . json_encode($delegatesData) . ';', \yii\web\View::POS_HEAD);

// Helper para links
$formatMessage = function ($text, $dark = false) {
    if (strpos($text, '<p') === false && strpos($text, '<div') === false && strpos($text, '<br') === false) {
        $text = nl2br($text);
    }

    $config = function ($conf) {
        $conf->set('HTML.TargetBlank', true);
        $conf->set('AutoFormat.Linkify', true);
        $conf->set('HTML.Allowed', 'p,b,strong,i,em,u,ul,ol,li,table,thead,tbody,th,td,img[src|alt|width|height],br,span[style|class|data-email],div,h1,h2,h3,h4,h5,h6,a[href|target]');
        
        $def = $conf->getHTMLDefinition(true);
        if ($def) {
            $def->addAttribute('span', 'data-email', 'Text');
        }
    };

    $cleanHtml = HtmlPurifier::process($text, $config);
    $cssClass = $dark ? 'link link-white underline' : 'link link-primary underline';

    return str_replace('<a ', '<a class="' . $cssClass . '" ', $cleanHtml);
};

// --- LOGICA DE VISUALIZACIÓN ---

// 1. Estados
$statusLabels = [
    'open' => ['text' => 'ABIERTO', 'color' => 'badge-error text-white'],
    'in_progress' => ['text' => 'EN PROGRESO', 'color' => 'badge-info text-white'],
    'answered' => ['text' => 'RESPONDIDO', 'color' => 'badge-success text-white'],
    'closed' => ['text' => 'CERRADO', 'color' => 'badge-neutral text-white'],
];
$st = strtolower($model->status);
$currentStatus = $statusLabels[$st] ?? ['text' => strtoupper($st), 'color' => 'bg-ghost'];

// 2. Prioridades
$priorityLabels = [
    'medium' => ['text' => 'Media', 'color' => 'badge-success text-white'],
    'high' => ['text' => 'Alta', 'color' => 'badge-warning'],
    'critical' => ['text' => 'Urgente', 'color' => 'badge-error text-white'],
];
$pr = strtolower($model->priority);
$currentPriority = $priorityLabels[$pr] ?? ['text' => ucfirst($pr), 'color' => 'bg-ghost'];

// A. Cargamos TinyMCE
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js', [
    'position' => \yii\web\View::POS_HEAD
]);

// B. Inicializamos el editor
$js = <<<'JS'
document.addEventListener("DOMContentLoaded", function() {

    const currentHtmlTheme = document.documentElement.getAttribute('data-theme');
    const isDarkMode = currentHtmlTheme === 'dark';

    const getCsrf = () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const param = document.querySelector('meta[name="csrf-param"]')?.getAttribute('content');
        return { token, param };
    };

    tinymce.remove('#ticket-message-editor'); 
    tinymce.init({
        selector: '#ticket-message-editor',
        height: 300,
        menubar: false,
        statusbar: false,
        language: 'es',
        skin: isDarkMode ? 'oxide-dark' : 'oxide',
        content_css: isDarkMode ? 'dark' : 'default',
        plugins: 'lists link autolink fullscreen image code',
        toolbar: 'bold italic underline | bullist numlist | link image | removeformat | fullscreen | blockquote',
        branding: false,
        setup: function (editor) {
            editor.on('change', function () {
                editor.save();
            });

            // Registrar autocompleter para menciones con @
            editor.ui.registry.addAutocompleter('delegates', {
                trigger: '@',
                minChars: 0,
                columns: 1,
                fetch: function (pattern) {
                    return new Promise(function (resolve) {
                        const list = window.ticketDelegates || [];
                        const matches = list.filter(function (user) {
                            const name = (user.contact_name || user.username || '').toLowerCase();
                            const email = (user.email || '').toLowerCase();
                            const pat = pattern.toLowerCase();
                            return name.includes(pat) || email.includes(pat);
                        }).map(function (user) {
                            const name = user.contact_name || user.username || 'Delegado';
                            return {
                                value: user.email,
                                text: name,
                                meta: { email: user.email, name: name }
                            };
                        });
                        resolve(matches);
                    });
                },
                onAction: function (autocompleteApi, rng, value, meta) {
                    const mentionHtml = `<span class="mention font-bold text-primary" data-email="${meta.email}">@${meta.name}</span>&nbsp;`;
                    editor.selection.setRng(rng);
                    editor.insertContent(mentionHtml);
                    autocompleteApi.hide();
                }
            });
        },
        paste_data_images: true,
        automatic_uploads: true,
        paste_preprocess: (plugin, args) => {
            if (args.content.indexOf('src="data:image') !== -1) {
                args.content = args.content.replace(/<img[^>]*src="data:image[^>]*>/gi, ' [Imagen bloqueada: Por favor usa el botón de subir imagen] ');
                alert("No se permite pegar imágenes directamente. Por favor, usa la opción de 'Insertar Imagen'.");
            }
        },
        images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.withCredentials = false;
            var data = {};
            xhr.open('POST', '/tickets/upload-image', true);

            const csrf = getCsrf();
            if (csrf.token) {
                xhr.setRequestHeader("X-CSRF-Token", csrf.token);
            }

            xhr.upload.onprogress = (e) => {
                progress(e.loaded / e.total * 100);
            };

            xhr.onload = () => {
                if (xhr.status < 200 || xhr.status >= 300) {
                    reject('Error del servidor (Código: ' + xhr.status + ')');
                    return;
                }

                const json = JSON.parse(xhr.responseText);

                if (json && json.error) {
                    reject(json.error); 
                    return;
                }

                if (!json || typeof json.location != 'string') {
                    reject('Respuesta del servidor inválida');
                    return;
                }

                resolve(json.location);
            };

            xhr.onerror = () => {
                reject('Error de red o conexión fallida.');
            };

            const formData = new FormData();
            if (csrf.param) {
                formData.append(csrf.param, csrf.token);
            }
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            xhr.send(formData);
        })
    });

});
JS;
$this->registerJs($js, \yii\web\View::POS_END);

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
                            Ticket <span
                                class="font-mono bg-base-200 px-1 rounded select-all"><?= $model->ticket_code ?></span>
                        </p>
                    </div>
                    <div class="badge <?= $currentStatus['color'] ?> badge-lg font-bold p-4">
                        <?= $currentStatus['text'] ?>
                    </div>
                </div>
            </div>

            <div class="card-body bg-base-200/30 max-h-[600px] overflow-y-auto p-4 space-y-6">

                <?php
                // Clonamos el arreglo de respuestas para no afectar el modelo original
                $replies = $model->ticketReplies;
                ?>

                <?php if (empty($replies)): ?>
                    <div class="alert alert-info shadow-sm bg-blue-50 text-blue-900 border-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            class="stroke-current shrink-0 w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Este es el inicio de la conversación. Aún no hay mensajes registrados.</span>
                    </div>
                <?php else: ?>

                    <?php
                    // EXTRAEMOS EL PRIMER MENSAJE (El requerimiento original)
                    $firstReply = array_shift($replies);
                    if ($firstReply) {
                        echo $this->render('_reply', [
                            'reply' => $firstReply,
                            'model' => $model,
                            'formatMessage' => $formatMessage
                        ]);
                    }
                    ?>

                    <?php if (!empty($replies)): ?>
                        <div class="divider text-xs opacity-30 my-2">Respuestas</div>
                    <?php endif; ?>

                    <?php foreach ($replies as $reply): ?>
                        <?= $this->render('_reply', [
                            'reply' => $reply,
                            'model' => $model,
                            'formatMessage' => $formatMessage
                        ]) ?>
                    <?php endforeach; ?>

                <?php endif; ?>

                <div id="chat-bottom"></div>
            </div>

            <div class="card-body border-t border-base-200 bg-base-100 pt-6">

                <?php
                $existingFeedback = \app\models\ServiceFeedback::find()
                    ->where(['ticket_id' => $model->ticket_code])
                    ->orWhere(['ticket_id' => (string)$model->id])
                    ->one();
                ?>

                <?php if ($model->status === 'closed'): ?>
                    <?php if ($existingFeedback): ?>
                        <div class="alert alert-success shadow-sm bg-success/10 border border-success/20 text-success-content p-4 rounded-2xl mb-4">
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <h4 class="font-bold text-sm">¡Gracias por evaluar este servicio!</h4>
                                    <p class="text-xs opacity-80 mt-0.5">
                                        Calificaste la atención con <?= $existingFeedback->getRatingStarsHtml() ?> 
                                        (<?= Yii::$app->formatter->asDatetime($existingFeedback->created_at, 'php:d M Y, h:i a') ?>).
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-gradient-to-r from-emerald-500/10 to-teal-500/10 border border-emerald-500/30 p-5 rounded-2xl mb-6 shadow-sm">
                            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-base text-base-content">Este ticket ha sido cerrado</h4>
                                        <p class="text-xs text-base-content/70 mt-0.5">¿Cómo calificas la atención y la solución brindada por nuestro equipo?</p>
                                    </div>
                                </div>
                                <a href="<?= \yii\helpers\Url::to(['/feedback/rate', 'ticket_id' => $model->ticket_code]) ?>" class="btn btn-primary gap-2 rounded-xl shadow-md shrink-0 w-full md:w-auto">
                                    ⭐ Calificar Atención
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($model->isLocked() && !$isAdmin): ?>
                    <div class="alert alert-neutral shadow-sm bg-base-200 border-base-300 text-base-content/70">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Este ticket se encuentra cerrado.</span>
                    </div>
                <?php elseif (!$model->canCustomerReply($isAdmin)): ?>
                    <div class="alert alert-warning shadow-sm bg-warning/10 border border-warning/20 text-warning-content p-4 rounded-2xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <h4 class="font-bold text-sm">Límite de respuestas consecutivas alcanzado</h4>
                            <p class="text-xs opacity-90 mt-0.5">Has enviado 3 respuestas sin recibir atención o respuesta de nuestro equipo. Por favor espera a que un agente responda o ponga en proceso tu ticket para poder enviar más mensajes.</p>
                        </div>
                    </div>
                <?php else: ?>

                <?php $form = ActiveForm::begin([
                    'action' => ['reply', 'id' => $model->id],
                    'options' => ['enctype' => 'multipart/form-data']
                ]); ?>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-bold">
                            <?php if ($model->status === 'closed'): ?>
                                <span class="text-warning flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                        class="w-4 h-4">
                                        <path fill-rule="evenodd"
                                            d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Responder reabrirá este ticket
                                </span>
                            <?php else: ?>
                                <?= (Yii::$app->user->identity->isAdmin) ? 'Responder al cliente' : 'Agregar respuesta' ?>
                            <?php endif; ?>
                        </span>
                    </label>

                    <?= Html::textarea('TicketReplies[message]', '', [
                        'class' => 'textarea textarea-bordered h-24 w-full focus:textarea-primary text-base',
                        'placeholder' => 'Escribe tu respuesta aquí...',
                        'id' => 'ticket-message-editor',
                        'required' => true
                    ]) ?>
                </div>

                <div class="flex flex-col md:flex-row justify-between items-start mt-4 gap-4">

                    <div class="form-control w-full md:w-auto">
                        <label class="btn btn-outline btn-primary gap-2 w-full md:w-auto cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
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
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                            Enviar Respuesta
                        </button>
                    </div>
                </div>

                <?php ActiveForm::end(); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-4">

        <?php if ($model->status !== 'closed'): ?>
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-5">
                    <h3 class="card-title text-xs uppercase font-bold tracking-wider mb-2 opacity-50">Acciones</h3>

                    <?php if ($isAdmin): ?>
                        <?php if ($model->status !== 'in_progress'): ?>
                            <?= Html::a(
                                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> Tomar / En Progreso',
                                ['in-progress', 'id' => $model->id],
                                [
                                    'class' => 'btn btn-outline btn-info btn-block gap-2 mb-3',
                                    'data-method' => 'post',
                                ]
                            ) ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?= Html::a(
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> Marcar como Resuelto',
                        ['close', 'id' => $model->id],
                        [
                            'class' => 'btn btn-outline btn-success btn-block gap-2',
                            'data-method' => 'post',
                            'data-confirm' => '¿Confirmas que el problema ha sido resuelto? El ticket cambiará a estado Cerrado.'
                        ]
                    ) ?>

                    <?php if ($isAdmin): ?>
                        <?= Html::a(
                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg> Cerrar y Bloquear Respuestas',
                            ['close', 'id' => $model->id, 'lock' => 1],
                            [
                                'class' => 'btn btn-outline btn-error btn-block gap-2 mt-3',
                                'data-method' => 'post',
                                'data-confirm' => '¿Confirmas que deseas cerrar el ticket y bloquear las respuestas del cliente?'
                            ]
                        ) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($isAdmin): ?>
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body p-5">
                    <h3 class="card-title text-xs uppercase font-bold tracking-wider mb-2 opacity-50">Acciones de Bloqueo</h3>
                    <?php if ($model->isLocked()): ?>
                        <div class="badge badge-warning gap-1 mb-3 p-3 font-semibold text-xs w-full justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                            Respuestas bloqueadas para cliente
                        </div>
                        <?= Html::a(
                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg> Desbloquear Respuestas',
                            ['toggle-lock', 'id' => $model->id],
                            [
                                'class' => 'btn btn-outline btn-info btn-block gap-2',
                                'data-method' => 'post',
                            ]
                        ) ?>
                    <?php else: ?>
                        <?= Html::a(
                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg> Bloquear Respuestas',
                            ['toggle-lock', 'id' => $model->id],
                            [
                                'class' => 'btn btn-outline btn-warning btn-block gap-2',
                                'data-method' => 'post',
                            ]
                        ) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <div class="card bg-base-100 shadow-xl border border-base-200 mb-4">
                <div class="card-body p-5">
                    <h3 class="card-title text-xs uppercase font-bold tracking-wider mb-2 opacity-50">Administración</h3>
                    <?= Html::a(
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg> Generar Orden de Trabajo',
                        ['work-orders/create-from-ticket', 'ticket_id' => $model->id],
                        [
                            'class' => 'btn btn-primary btn-block gap-2 text-white shadow-md',
                            'data' => [
                                'confirm' => '¿Deseas generar una orden de trabajo a partir de este ticket?',
                                'method' => 'post'
                            ]
                        ]
                    ) ?>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl border border-base-200 border-l-4 border-error mb-4">
                <div class="card-body p-5">
                    <h3 class="card-title text-xs uppercase text-error font-bold tracking-wider mb-2">Zona de Peligro</h3>
                    <div class="flex flex-col gap-2">
                        <?= Html::a(
                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg> Eliminar Ticket',
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

        <?php 
        $relatedWorkOrders = $model->workOrders; 
        if (!empty($relatedWorkOrders)): 
        ?>
            <div class="card bg-base-100 shadow-xl border border-base-200 mb-4">
                <div class="card-body p-5">
                    <h3 class="card-title text-xs uppercase font-bold tracking-wider mb-2 opacity-50">Órdenes de Trabajo</h3>
                    <div class="flex flex-col gap-2">
                        <?php foreach ($relatedWorkOrders as $wo): ?>
                            <div class="flex justify-between items-center bg-base-200/50 p-2 rounded-lg border border-base-300">
                                <div class="min-w-0 flex-1 mr-2">
                                    <?= Html::a(
                                        Html::encode($wo->code),
                                        ['work-orders/view', 'id' => $wo->id],
                                        ['class' => 'font-bold link link-primary text-sm']
                                    ) ?>
                                    <span class="block text-xs opacity-65 truncate" title="<?= Html::encode($wo->title) ?>"><?= Html::encode($wo->title) ?></span>
                                </div>
                                <div class="shrink-0">
                                    <?= $wo->getStatusHtml() ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                                <th class="text-base-content/60 font-normal pl-0">Departamento:</th>
                                <td class="text-right pr-0">
                                    <?= $model->getDepartmentLabelShort() ?>
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
                                    <?= Yii::$app->formatter->asDatetime($model->created_at, 'medium') ?>
                                </td>
                            </tr>

                            <tr>
                                <th class="text-base-content/60 font-normal pl-0 pt-3 align-top">Email:</th>
                                <td class="text-right pr-0 pt-3">
                                    <?= Html::a($model->email, 'mailto:' . $model->email, [
                                        'class' => 'link link-hover text-sm break-all inline-block text-right'
                                    ]) ?>
                                </td>
                            </tr>

                            <?php if (!empty($model->cc_emails)): ?>
                                <tr>
                                    <th class="text-base-content/60 font-normal pl-0 pt-3 align-top">En copia (CC):</th>
                                    <td class="text-right pr-0 pt-3">
                                        <div class="flex flex-col gap-1 items-end">
                                            <?php foreach (array_map('trim', explode(',', $model->cc_emails)) as $ccEmail): ?>
                                                <span class="badge badge-sm badge-neutral font-semibold select-all"><?= Html::encode($ccEmail) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?= Html::a('← Volver a la lista', ['index'], ['class' => 'btn btn-ghost btn-block text-base-content/60']) ?>
    </div>

</div>

<?php
// GLightbox for ticket images
$this->registerCssFile('https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js', ['position' => \yii\web\View::POS_END]);

$jsLightbox = <<<JS
document.addEventListener("DOMContentLoaded", function() {
    const images = document.querySelectorAll('.chat-bubble img');
    
    images.forEach(img => {
        if (img.parentElement && img.parentElement.classList.contains('glightbox')) return;
        
        img.classList.add('max-w-full', 'rounded', 'shadow-sm', 'transition-all', 'duration-300', 'group-hover:brightness-75');
        
        const wrapper = document.createElement('div');
        wrapper.className = 'relative group inline-block cursor-pointer my-2 max-w-full align-middle';
        
        const link = document.createElement('a');
        link.href = img.src;
        link.className = 'glightbox block';
        link.setAttribute('data-gallery', 'ticket-images');
        
        const iconDiv = document.createElement('div');
        iconDiv.className = 'absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none rounded';
        iconDiv.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="w-10 h-10 drop-shadow-md"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" /></svg>';
        
        img.parentNode.insertBefore(wrapper, img);
        link.appendChild(img);
        link.appendChild(iconDiv);
        wrapper.appendChild(link);
    });
    
    if (typeof GLightbox !== 'undefined') {
        GLightbox({
            selector: '.glightbox',
            touchNavigation: true,
            loop: true,
            zoomable: true
        });
    }
});
JS;
$this->registerJs($jsLightbox, \yii\web\View::POS_END);

$jsPolling = <<<JS
document.addEventListener("DOMContentLoaded", function() {
    setInterval(function() {
        const replyItems = document.querySelectorAll('.reply-item');
        let lastReplyId = 0;
        if (replyItems.length > 0) {
            const lastItem = replyItems[replyItems.length - 1];
            lastReplyId = lastItem.getAttribute('data-reply-id') || 0;
        }

        fetch(`/tickets/get-new-replies?id={$model->id}&lastReplyId=\${lastReplyId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            if (html.trim() !== '') {
                const chatBottom = document.getElementById('chat-bottom');
                chatBottom.insertAdjacentHTML('beforebegin', html);
                
                // Scroll to bottom smoothly
                const chatContainer = chatBottom.parentElement;
                chatContainer.scrollTo({
                    top: chatContainer.scrollHeight,
                    behavior: 'smooth'
                });

                // Re-init glightbox for new images
                const newImages = chatContainer.querySelectorAll('.chat-bubble img:not(.max-w-full)');
                if (newImages.length > 0) {
                    newImages.forEach(img => {
                        if (img.parentElement && img.parentElement.classList.contains('glightbox')) return;
                        img.classList.add('max-w-full', 'rounded', 'shadow-sm', 'transition-all', 'duration-300', 'group-hover:brightness-75');
                        const wrapper = document.createElement('div');
                        wrapper.className = 'relative group inline-block cursor-pointer my-2 max-w-full align-middle';
                        const link = document.createElement('a');
                        link.href = img.src;
                        link.className = 'glightbox block';
                        link.setAttribute('data-gallery', 'ticket-images');
                        const iconDiv = document.createElement('div');
                        iconDiv.className = 'absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none rounded';
                        iconDiv.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="w-10 h-10 drop-shadow-md"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" /></svg>';
                        img.parentNode.insertBefore(wrapper, img);
                        link.appendChild(img);
                        link.appendChild(iconDiv);
                        wrapper.appendChild(link);
                    });
                    if (typeof GLightbox !== 'undefined') {
                        GLightbox({
                            selector: '.glightbox',
                            touchNavigation: true,
                            loop: true,
                            zoomable: true
                        });
                    }
                }
            }
        })
        .catch(err => console.error('Error fetching new replies:', err));
    }, 10000); // 10 seconds
});
JS;
$this->registerJs($jsPolling, \yii\web\View::POS_END);
?>