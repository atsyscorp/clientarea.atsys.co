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
window.AutoDraft = window.AutoDraft || {
    save: function(k, d) { try { if (!k) return false; localStorage.setItem(k, JSON.stringify({ data: d, timestamp: Date.now() })); return true; } catch (e) { return false; } },
    load: function(k, m) { try { if (!k) return null; const r = localStorage.getItem(k); if (!r) return null; const i = JSON.parse(r); if (Date.now() - i.timestamp > (typeof m === 'number' ? m : 7) * 86400000) { this.clear(k); return null; } return i; } catch (e) { return null; } },
    clear: function(k) { try { if (k) localStorage.removeItem(k); } catch (e) {} },
    formatTime: function(t) { try { const d = new Date(t), now = new Date(), isToday = d.toDateString() === now.toDateString(), s = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); return isToday ? 'hoy a las ' + s : d.toLocaleDateString([], { day: '2-digit', month: 'short' }) + ' ' + s; } catch (e) { return ''; } }
};

function initCreateTicketEditor() {
    if (typeof tinymce === 'undefined') return;

    const createForm = document.getElementById('create-ticket-form');
    const userId = createForm ? (createForm.getAttribute('data-user-id') || '0') : '0';
    const draftKey = `draft_ticket_create_u${userId}`;
    const draftNotice = document.getElementById('create-draft-notice');
    const draftTimeSpan = document.getElementById('create-draft-time');
    const discardBtn = document.getElementById('btn-discard-create-draft');
    const autosaveStatus = document.getElementById('create-autosave-status');

    const subjectInput = document.getElementById('tickets-subject');
    const deptSelect = document.querySelector('select[name="Tickets[department]"]');
    const prioritySelect = document.getElementById('priority-selector');

    function isContentEmpty(html) {
        if (!html) return true;
        const stripped = html.replace(/<[^>]+>/g, '').replace(/&nbsp;/gi, ' ').trim();
        return stripped.length === 0;
    }

    let statusFadeTimer = null;
    function setStatus(text, success) {
        if (!autosaveStatus) return;
        clearTimeout(statusFadeTimer);
        autosaveStatus.textContent = text;
        autosaveStatus.className = `text-[11px] font-normal transition-opacity duration-300 ${success ? 'text-success' : 'text-base-content/50'}`;
        autosaveStatus.style.opacity = '1';
        if (text) {
            statusFadeTimer = setTimeout(() => {
                autosaveStatus.style.opacity = '0';
            }, 3000);
        }
    }

    let debounceTimer = null;
    function triggerSaveDraft() {
        if (!window.AutoDraft) return;

        clearTimeout(debounceTimer);
        setStatus('Guardando borrador...', false);
        debounceTimer = setTimeout(() => {
            let messageContent = '';
            if (typeof tinymce !== 'undefined') {
                const inst = tinymce.get('tickets-message');
                if (inst) messageContent = inst.getContent();
            }
            if (!messageContent) {
                const ta = document.getElementById('tickets-message');
                if (ta) messageContent = ta.value;
            }

            const subjectVal = subjectInput ? subjectInput.value.trim() : '';
            const deptVal = deptSelect ? deptSelect.value : '';
            const priorityVal = prioritySelect ? prioritySelect.value : '';

            const hasMessage = !isContentEmpty(messageContent);
            const hasSubject = subjectVal.length > 0;

            if (hasMessage || hasSubject) {
                const draftData = {
                    subject: subjectVal,
                    message: messageContent,
                    department: deptVal,
                    priority: priorityVal
                };
                window.AutoDraft.save(draftKey, draftData);
                setStatus('✓ Borrador guardado localmente', true);
            } else {
                window.AutoDraft.clear(draftKey);
                if (draftNotice) draftNotice.classList.add('hidden');
                setStatus('', false);
            }
        }, 600);
    }

    // Escuchar inputs estándar
    if (subjectInput) {
        subjectInput.addEventListener('input', triggerSaveDraft);
    }
    if (deptSelect) {
        deptSelect.addEventListener('change', triggerSaveDraft);
    }
    if (prioritySelect) {
        prioritySelect.addEventListener('change', triggerSaveDraft);
    }

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
            editor.on('change keyup NodeChange input', function () {
                editor.save();
                triggerSaveDraft();
            });

            // Restaurar borrador previo cuando el editor esté listo
            editor.on('init', function () {
                if (window.AutoDraft) {
                    const savedDraft = window.AutoDraft.load(draftKey);
                    if (savedDraft && savedDraft.data) {
                        const data = (typeof savedDraft.data === 'string')
                            ? { message: savedDraft.data }
                            : savedDraft.data;

                        let restoredAny = false;

                        // Restaurar Asunto si el campo está vacío
                        if (data.subject && subjectInput && !subjectInput.value.trim()) {
                            subjectInput.value = data.subject;
                            restoredAny = true;
                        }

                        // Restaurar Departamento si no fue pasado por URL
                        if (data.department && deptSelect && (!deptSelect.value || deptSelect.value === '0')) {
                            deptSelect.value = data.department;
                            restoredAny = true;
                        }

                        // Restaurar Prioridad si fue cambiada
                        if (data.priority && prioritySelect && data.priority !== 'medium') {
                            prioritySelect.value = data.priority;
                            if (typeof window.togglePriorityWarning === 'function') {
                                window.togglePriorityWarning(data.priority);
                            }
                            restoredAny = true;
                        }

                        // Restaurar Mensaje si el editor está vacío
                        if (data.message && !isContentEmpty(data.message) && isContentEmpty(editor.getContent())) {
                            editor.setContent(data.message);
                            editor.save();
                            restoredAny = true;
                        }

                        if (restoredAny && draftNotice) {
                            draftNotice.classList.remove('hidden');
                            if (draftTimeSpan && savedDraft.timestamp) {
                                draftTimeSpan.textContent = window.AutoDraft.formatTime(savedDraft.timestamp);
                            }
                        }
                    }
                }
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

    // Descartar borrador
    if (discardBtn) {
        const newDiscardBtn = discardBtn.cloneNode(true);
        discardBtn.parentNode.replaceChild(newDiscardBtn, discardBtn);

        newDiscardBtn.addEventListener('click', function () {
            if (confirm('¿Deseas descartar este borrador? Se eliminarán los datos que has redactado.')) {
                if (typeof tinymce !== 'undefined') {
                    const inst = tinymce.get('tickets-message');
                    if (inst) {
                        inst.setContent('');
                        inst.save();
                    }
                }
                const textarea = document.getElementById('tickets-message');
                if (textarea) textarea.value = '';

                if (subjectInput) subjectInput.value = '';
                if (prioritySelect) {
                    prioritySelect.value = 'medium';
                    if (typeof window.togglePriorityWarning === 'function') {
                        window.togglePriorityWarning('medium');
                    }
                }

                if (window.AutoDraft) {
                    window.AutoDraft.clear(draftKey);
                }
                if (draftNotice) {
                    draftNotice.classList.add('hidden');
                }
                setStatus('Borrador descartado', false);
            }
        });
    }

    const createForm = document.getElementById('create-ticket-form');
    if (createForm) {
        createForm.addEventListener('submit', function () {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
            // Eliminar borrador al enviar el formulario exitosamente
            if (window.AutoDraft) {
                window.AutoDraft.clear(draftKey);
            }
            if (draftNotice) {
                draftNotice.classList.add('hidden');
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

            <div class="flex items-center justify-between border-b border-base-200 pb-4 mb-4">
                <div class="flex items-center gap-4">
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
                <span id="create-autosave-status" class="text-[11px] text-base-content/50 font-normal transition-opacity duration-300"></span>
            </div>

            <!-- Aviso de borrador restaurado con opción de descartar -->
            <div id="create-draft-notice" class="hidden alert alert-warning/10 text-xs py-2.5 px-3.5 rounded-xl border border-warning/30 shadow-sm mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4 text-warning shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>Se restauró un borrador no enviado (<span id="create-draft-time" class="font-medium"></span>).</span>
                </div>
                <button type="button" id="btn-discard-create-draft" class="btn btn-ghost btn-xs text-error hover:bg-error/10 font-bold gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    Descartar borrador
                </button>
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'create-ticket-form',
                'options' => [
                    'class' => 'space-y-4',
                    'enctype' => 'multipart/form-data',
                    'data-user-id' => !Yii::$app->user->isGuest ? Yii::$app->user->id : 0,
                ],
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
                                'id' => 'select-customer',
                                'onchange' => 'toggleEmailField(this.value)' // Disparador
                            ])->label('¿A nombre de qué cliente es el ticket?') ?>

                    <div id="user-container" class="form-control mb-3" style="display: none;">
                        <label class="label-text font-bold mb-1 flex items-center justify-between">
                            <span>¿A qué usuario se le asigna el ticket?</span>
                            <span id="user-loading-spinner" class="loading loading-spinner loading-xs text-primary hidden"></span>
                        </label>
                        <?= Html::dropDownList('Tickets[user_id]', $model->user_id ?? null, $customerUsers ?? [], [
                            'id' => 'select-ticket-user',
                            'class' => 'select select-bordered w-full focus:select-primary',
                            'prompt' => 'Seleccione el usuario...',
                        ]) ?>
                        <div id="user-hint" class="text-xs text-base-content/60 mt-1"></div>
                    </div>

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

            <?php 
                echo $form->field($model, 'priority', [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'label-text font-bold mb-1 block']
                ])->dropDownList([
                    'medium' => 'Medio',
                    'high' => 'Alto',
                    'critical' => 'Urgente'
                ], [
                    'class' => 'select select-bordered w-full',
                    'id' => 'priority-selector',
                    'onchange' => 'togglePriorityWarning(this.value)',
                    'options' => [
                        'medium' => ['selected' => true]
                    ]
                ])->label('Prioridad');
            ?>
            
            <div id="urgent-warning" class="alert alert-warning shadow-sm mt-2 hidden text-sm py-3 mb-4 rounded-xl border border-warning/30 bg-warning/10 text-warning-content">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span>Entendemos la urgencia de la solicitud. Cada ticket se revisa minuciosamente y, si determinamos que no corresponde a una urgencia crítica que afecte el servicio, nos reservamos el derecho de ajustar la prioridad para mantener los tiempos de respuesta justos.</span>
            </div>

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
        const userBlock = document.getElementById('user-container');
        const userSelect = document.getElementById('select-ticket-user');
        const userHint = document.getElementById('user-hint');

        if (!emailBlock) return;

        // Si es 9999 (Prospecto / No Registrado)
        if (val == '9999') {
            emailBlock.style.display = 'block';
            if (userBlock) userBlock.style.display = 'none';
            if (userSelect) userSelect.value = '';
            if (userHint) userHint.innerHTML = '';
            window.ticketDelegates = [];
            const emailInput = document.getElementById('tickets-email');
            if (emailInput) emailInput.focus();
            return;
        }

        // Si no hay cliente seleccionado
        if (!val) {
            emailBlock.style.display = 'none';
            if (userBlock) userBlock.style.display = 'none';
            if (userSelect) {
                userSelect.innerHTML = '<option value="">Seleccione el usuario...</option>';
                userSelect.value = '';
            }
            if (userHint) userHint.innerHTML = '';
            window.ticketDelegates = [];
            return;
        }

        // Cliente registrado normal
        emailBlock.style.display = 'none';
        if (userBlock) userBlock.style.display = 'block';
        loadCustomerDelegates(val);
    }

    function loadCustomerDelegates(customerId) {
        if (!customerId || customerId == '9999') {
            window.ticketDelegates = [];
            return;
        }

        const userSelect = document.getElementById('select-ticket-user');
        const spinner = document.getElementById('user-loading-spinner');
        const hint = document.getElementById('user-hint');

        if (spinner) spinner.classList.remove('hidden');

        fetch('/tickets/get-delegates?customer_id=' + customerId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.delegates) {
                    window.ticketDelegates = data.delegates;
                } else {
                    window.ticketDelegates = [];
                }

                if (userSelect) {
                    const currentSelectedVal = userSelect.value;
                    userSelect.innerHTML = '';

                    const users = data.users || data.delegates || [];

                    if (users.length === 0) {
                        const opt = document.createElement('option');
                        opt.value = '';
                        opt.textContent = '(Sin usuarios registrados - se usarán datos de la empresa)';
                        userSelect.appendChild(opt);
                        if (hint) {
                            hint.innerHTML = '<span class="text-warning text-xs font-semibold">⚠ Este cliente no tiene cuentas de acceso creadas aún. Se usarán los datos de contacto generales.</span>';
                        }
                    } else {
                        const defaultOpt = document.createElement('option');
                        defaultOpt.value = '';
                        defaultOpt.textContent = 'Seleccione el usuario...';
                        userSelect.appendChild(defaultOpt);

                        let ownerId = null;
                        users.forEach(function (user) {
                            const opt = document.createElement('option');
                            opt.value = user.id;
                            opt.textContent = user.display_label || (user.contact_name ? `${user.contact_name} - ${user.email}` : user.email);
                            userSelect.appendChild(opt);

                            if (user.is_owner) {
                                ownerId = user.id;
                            }
                        });

                        // Selección automática inteligente:
                        if (currentSelectedVal && users.some(u => u.id == currentSelectedVal)) {
                            userSelect.value = currentSelectedVal;
                        } else if (users.length === 1) {
                            userSelect.value = users[0].id;
                        } else if (ownerId) {
                            userSelect.value = ownerId;
                        }

                        if (hint) {
                            hint.innerHTML = `<span class="text-success text-xs font-medium">✓ ${users.length} usuario(s) disponible(s) para este cliente</span>`;
                        }
                    }
                }
            })
            .catch(err => {
                console.error('Error fetching delegates:', err);
                window.ticketDelegates = [];
                if (hint) {
                    hint.innerHTML = '<span class="text-error text-xs">Error al cargar usuarios del cliente.</span>';
                }
            })
            .finally(() => {
                if (spinner) spinner.classList.add('hidden');
            });
    }

    // Ejecutar al cargar la página (por si falla la validación y recarga, mantener el estado)
    document.addEventListener("DOMContentLoaded", function () {
        const custSelect = document.getElementById('select-customer') || document.getElementById('tickets-customer_id');
        if (custSelect && custSelect.value) {
            const val = custSelect.value;
            const emailBlock = document.getElementById('email-container');
            const userBlock = document.getElementById('user-container');
            const userSelect = document.getElementById('select-ticket-user');

            if (val == '9999') {
                if (emailBlock) emailBlock.style.display = 'block';
                if (userBlock) userBlock.style.display = 'none';
            } else {
                if (emailBlock) emailBlock.style.display = 'none';
                if (userBlock) userBlock.style.display = 'block';
                // Si el selector no tiene opciones cargadas desde PHP, disparar la carga AJAX
                if (userSelect && userSelect.options.length <= 1) {
                    loadCustomerDelegates(val);
                }
            }
        }
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

    function togglePriorityWarning(value) {
        const warning = document.getElementById('urgent-warning');
        if (!warning) return;
        
        if (value === 'critical') {
            warning.classList.remove('hidden');
        } else {
            warning.classList.add('hidden');
        }
    }

    // Run on init just in case
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('priority-selector');
        if (select) togglePriorityWarning(select.value);
    });
</script>