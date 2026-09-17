<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use dosamigos\tinymce\TinyMce;

/** @var yii\web\View $this */
/** @var app\models\Tickets $model */
/** @var array $customers */
/** @var array $delegates */

$this->title = 'Abrir Nuevo Ticket';

// Verificamos si es admin
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;

// Registrar delegados iniciales en JS
$delegatesData = [];
if (!$isAdmin && !empty($delegates)) {
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

// A. Cargamos la librería desde la nube (Versión 6, estable y ligera)
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js', [
    'position' => \yii\web\View::POS_HEAD
]);

// B. Inicializamos el editor sobre el ID 'tickets-message'
$js = <<<'JS'
function initCreateTicketEditor() {
    if (typeof tinymce === 'undefined') return;

    const getCsrf = () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const param = document.querySelector('meta[name="csrf-param"]')?.getAttribute('content');
        return { token, param };
    };

    const isDarkMode = document.documentElement.classList.contains('dark');
    tinymce.remove('#tickets-message'); // Limpieza preventiva por si usas Pjax
    tinymce.init({
        selector: '#tickets-message', // Debe coincidir con el ID de arriba
        height: 300,
        menubar: false, // Sin menú superior (Archivo, Editar...)
        statusbar: false, // Sin barra inferior
        language: 'es', // Intenta cargar español, si falla usará inglés
        plugins: 'lists link autolink fullscreen image code', // Plugins básicos
        toolbar: 'bold italic underline | bullist numlist | link image | removeformat | fullscreen | blockquote', // Herramientas limpias
        skin: isDarkMode ? 'oxide-dark' : 'oxide',
        content_css: isDarkMode ? 'dark' : 'default',
        content_style: `
            body { font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; line-height: 1.5; }
            p { margin: 0 0 0.75rem 0; }
            p:last-child { margin-bottom: 0; }
            ul { list-style-type: disc; margin-left: 1.25rem; margin-bottom: 0.75rem; }
            ol { list-style-type: decimal; margin-left: 1.25rem; margin-bottom: 0.75rem; }
            li { margin-bottom: 0.25rem; }
            blockquote { border-left: 3px solid #ccc; margin: 0.5rem 0; padding-left: 0.75rem; font-style: italic; }
        `,
        branding: false, // Quitar marca "Powered by TinyMCE"
        setup: function (editor) {
            // Esto asegura que el valor se guarde en el textarea al enviar el formulario
            editor.on('change keyup NodeChange', function () {
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
            xhr.open('POST', '/tickets/upload-image');

            const csrf = getCsrf();
            if (csrf.token) {
                xhr.setRequestHeader("X-CSRF-Token", csrf.token);
            }

            xhr.upload.onprogress = (e) => {
                progress(e.loaded / e.total * 100);
            };

            xhr.onload = () => {
                // Si el status no es 200 (OK)
                if (xhr.status < 200 || xhr.status >= 300) {
                    reject('Error del servidor (Código: ' + xhr.status + ')');
                    return;
                }

                const json = JSON.parse(xhr.responseText);

                // Si el backend envió un mensaje de error específico (ej. "Archivo muy pesado")
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

    const createForm = document.getElementById('create-ticket-form');
    if (createForm) {
        createForm.addEventListener('submit', function () {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener("DOMContentLoaded", initCreateTicketEditor);
} else {
    initCreateTicketEditor();
}
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>

<div class="flex justify-center items-start min-h-[calc(100vh-10rem)] py-6">

    <div class="card w-full max-w-2xl bg-base-100 shadow-xl">
        <div class="card-body">

            <div class="flex items-center gap-4 border-b border-base-200 pb-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-7 h-7">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                </div>
                <div>
                    <h1 class="card-title text-2xl font-bold">Nuevo Ticket</h1>
                    <p class="text-base-content/60 text-sm">Describe tu solicitud y te responderemos a la brevedad.</p>
                </div>
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'create-ticket-form',
                'options' => ['class' => 'space-y-4', 'enctype' => 'multipart/form-data'],
            ]); ?>

            <?php if ($isAdmin): ?>
                <div class="bg-base-200 p-4 rounded-lg border border-base-300 mb-2">
                    <div class="flex items-center gap-2 mb-2 text-primary font-bold text-sm uppercase tracking-wide">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
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

            <div class="form-control">
                <label class="label">
                    <span class="label-text font-bold">¿A qué área va dirigida tu solicitud?</span>
                </label>
                <?= $form->field($model, 'department')->dropDownList(
                    \app\models\Tickets::getDepartmentList(),
                    [
                        'class' => 'select select-bordered w-full',
                        'prompt' => 'Seleccione un departamento...', 'aria-label' => 'Departamento',
                        'options' => [
                            Yii::$app->request->get('department', 0) => [
                                'selected' => true
                            ]
                        ]
                    ]
                )->label(false) ?>
            </div>

            <?php if ($isAdmin):
                echo $form->field($model, 'priority')->dropDownList([
                    'medium' => 'Medio',
                    'high' => 'Alto',
                    'critical' => 'Urgente'
                ], ['class' => 'select select-bordered w-full']);
            endif; ?>

            <?= $form->field($model, 'subject', [
                'template' => "{label}\n<div class=\"relative\">{input}<div class=\"absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-base-content/40\"><svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\" class=\"w-5 h-5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z\" /></svg></div></div>\n{error}",
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
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                    </svg>

                    <span id="create-file-btn-text">Adjuntar archivos (Opcional)</span>

                    <?= Html::fileInput('Tickets[attachmentFiles][]', null, [
                        'class' => 'hidden',
                        'id' => 'create-attachment-input',
                        'multiple' => true,
                        'onchange' => "handleCreateFilesSelected(this)"
                    ]) ?>
                </label>
                <label class="label pb-0 justify-center md:justify-start">
                    <span class="label-text-alt text-base-content/60">Máx: 50MB por archivo • Múltiples permitidos</span>
                </label>

                <!-- Previsualización de archivos seleccionados -->
                <div id="create-files-preview-container" class="hidden mt-2 flex flex-col gap-1 max-w-md">
                    <div class="flex items-center justify-between text-xs font-semibold text-base-content/70 mb-1">
                        <span id="create-files-count">0 archivos seleccionados</span>
                        <button type="button" onclick="clearCreateFiles()" class="text-error hover:underline text-xs flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            Quitar
                        </button>
                    </div>
                    <div id="create-files-chips" class="flex flex-wrap gap-1.5"></div>
                </div>
            </div>

            <div class="card-actions justify-end mt-6 pt-4 border-t border-base-200">
                <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-ghost']) ?>

                <button type="submit" class="btn btn-primary gap-2 text-white shadow-lg shadow-primary/30">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
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
        }
        
        // Cargar los delegados dinámicamente si es admin
        loadCustomerDelegates(val);
    }

    function loadCustomerDelegates(customerId) {
        if (!customerId || customerId == '9999') {
            window.ticketDelegates = [];
            return;
        }
        
        fetch('/tickets/get-delegates?customer_id=' + customerId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.delegates) {
                    window.ticketDelegates = data.delegates;
                } else {
                    window.ticketDelegates = [];
                }
            })
            .catch(err => {
                console.error('Error fetching delegates:', err);
                window.ticketDelegates = [];
            });
    }

    // Ejecutar al cargar la página (por si falla la validación y recarga, mantener el estado)
    document.addEventListener("DOMContentLoaded", function () {
        const currentVal = document.getElementById('tickets-customer_id').value;
        toggleEmailField(currentVal);
    });

    function formatCreateFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        else return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function handleCreateFilesSelected(input) {
        const previewContainer = document.getElementById('create-files-preview-container');
        const chipsContainer = document.getElementById('create-files-chips');
        const btnText = document.getElementById('create-file-btn-text');
        const countText = document.getElementById('create-files-count');

        if (!previewContainer || !chipsContainer) return;
        chipsContainer.innerHTML = '';
        const files = input.files;
        const maxSizeBytes = 50 * 1024 * 1024; // 50MB

        if (!files || files.length === 0) {
            if (btnText) btnText.innerText = 'Adjuntar archivos (Opcional)';
            previewContainer.classList.add('hidden');
            return;
        }

        let hasOversized = false;
        let oversizedNames = [];

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (file.size > maxSizeBytes) {
                hasOversized = true;
                oversizedNames.push('• ' + file.name + ' (' + formatCreateFileSize(file.size) + ')');
            }
        }

        if (hasOversized) {
            alert('El siguiente archivo supera el límite máximo permitido de 50MB:\n\n' + oversizedNames.join('\n') + '\n\nPor favor selecciona archivos de hasta 50MB cada uno.');
            input.value = '';
            if (btnText) btnText.innerText = 'Adjuntar archivos (Opcional)';
            previewContainer.classList.add('hidden');
            return;
        }

        if (btnText) btnText.innerText = files.length === 1 ? '1 archivo listo' : files.length + ' archivos listos';
        if (countText) countText.innerText = files.length === 1 ? '1 archivo seleccionado' : files.length + ' archivos seleccionados';

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const chip = document.createElement('div');
            chip.className = 'badge badge-sm badge-outline gap-1 py-2 px-2.5 max-w-full text-xs font-normal';
            chip.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-3.5 h-3.5 text-primary shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg>' +
                '<span class="truncate max-w-[150px]" title="' + file.name + '">' + file.name + '</span>' +
                '<span class="opacity-60 text-[10px]">(' + formatCreateFileSize(file.size) + ')</span>';
            chipsContainer.appendChild(chip);
        }

        previewContainer.classList.remove('hidden');
    }

    function clearCreateFiles() {
        const input = document.getElementById('create-attachment-input');
        if (input) input.value = '';
        const btnText = document.getElementById('create-file-btn-text');
        if (btnText) btnText.innerText = 'Adjuntar archivos (Opcional)';
        const previewContainer = document.getElementById('create-files-preview-container');
        if (previewContainer) previewContainer.classList.add('hidden');
        const chipsContainer = document.getElementById('create-files-chips');
        if (chipsContainer) chipsContainer.innerHTML = '';
    }
</script>