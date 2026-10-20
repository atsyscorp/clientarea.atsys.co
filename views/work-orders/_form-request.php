<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\WorkOrders */
/* @var $form yii\widgets\ActiveForm */
/* @var $projects app\models\Projects[] */

// A. Cargamos la librería desde la nube (Versión 6, estable y ligera)
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js', [
    'position' => \yii\web\View::POS_HEAD
]);

// B. Inicializamos el editor sobre el ID 'workorders-requirements'
$js = <<<JS
window.AutoDraft = window.AutoDraft || {
    save: function(k, d) { try { if (!k) return false; localStorage.setItem(k, JSON.stringify({ data: d, timestamp: Date.now() })); return true; } catch (e) { return false; } },
    load: function(k, m) { try { if (!k) return null; const r = localStorage.getItem(k); if (!r) return null; const i = JSON.parse(r); if (Date.now() - i.timestamp > (typeof m === 'number' ? m : 7) * 86400000) { this.clear(k); return null; } return i; } catch (e) { return null; } },
    clear: function(k) { try { if (k) localStorage.removeItem(k); } catch (e) {} },
    formatTime: function(t) { try { const d = new Date(t), now = new Date(), isToday = d.toDateString() === now.toDateString(), s = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); return isToday ? 'hoy a las ' + s : d.toLocaleDateString([], { day: '2-digit', month: 'short' }) + ' ' + s; } catch (e) { return ''; } }
};

document.addEventListener("DOMContentLoaded", function() {
    const isDarkMode = document.documentElement.classList.contains('dark');
    const form = document.getElementById('request-work-order-form');
    const isNewRecord = form ? form.getAttribute('data-is-new') === '1' : true;
    const userId = form ? (form.getAttribute('data-user-id') || '0') : '0';
    const draftKey = `draft_workorder_request_u\${userId}`;
    const draftNotice = document.getElementById('request-wo-draft-notice');
    const draftTimeSpan = document.getElementById('request-wo-draft-time');
    const discardBtn = document.getElementById('btn-discard-request-wo-draft');
    const autosaveStatus = document.getElementById('request-wo-autosave-status');

    const titleInput = document.getElementById('workorders-title');
    const projectSelect = document.getElementById('workorders-project_id');

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
        autosaveStatus.className = `text-[11px] font-normal transition-opacity duration-300 \${success ? 'text-success' : 'text-base-content/50'}`;
        autosaveStatus.style.opacity = '1';
        if (text) {
            statusFadeTimer = setTimeout(() => {
                autosaveStatus.style.opacity = '0';
            }, 3000);
        }
    }

    let debounceTimer = null;
    function triggerSaveDraft() {
        if (!window.AutoDraft || !isNewRecord) return;

        clearTimeout(debounceTimer);
        setStatus('Guardando borrador...', false);
        debounceTimer = setTimeout(() => {
            let requirementsContent = '';
            if (typeof tinymce !== 'undefined') {
                const inst = tinymce.get('workorders-requirements');
                if (inst) requirementsContent = inst.getContent();
            }
            if (!requirementsContent) {
                const ta = document.getElementById('workorders-requirements');
                if (ta) requirementsContent = ta.value;
            }

            const titleVal = titleInput ? titleInput.value.trim() : '';
            const projectVal = projectSelect ? projectSelect.value : '';

            const hasReq = !isContentEmpty(requirementsContent);
            const hasTitle = titleVal.length > 0;

            if (hasReq || hasTitle) {
                const draftData = {
                    title: titleVal,
                    requirements: requirementsContent,
                    project_id: projectVal
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

    if (isNewRecord) {
        if (titleInput) {
            titleInput.addEventListener('input', triggerSaveDraft);
        }
        if (projectSelect) {
            projectSelect.addEventListener('change', triggerSaveDraft);
        }
    }

    tinymce.remove('#workorders-requirements'); // Limpieza preventiva por si usas Pjax
    tinymce.init({
        selector: '#workorders-requirements', // Debe coincidir con el ID de arriba
        height: 300,
        menubar: false, // Sin menú superior (Archivo, Editar...)
        statusbar: false, // Sin barra inferior
        language: 'es', // Intenta cargar español, si falla usará inglés
        plugins: 'lists link autolink fullscreen', // Plugins básicos
        toolbar: 'bold italic underline | bullist numlist | link | removeformat | fullscreen', // Herramientas limpias
        skin: isDarkMode ? 'oxide-dark' : 'oxide',
        content_css: isDarkMode ? 'dark' : 'default',
        branding: false, // Quitar marca "Powered by TinyMCE"
        setup: function (editor) {
            // Esto asegura que el valor se guarde en el textarea al enviar el formulario
            editor.on('change keyup NodeChange input', function () {
                editor.save();
                triggerSaveDraft();
            });

            editor.on('init', function () {
                if (window.AutoDraft && isNewRecord) {
                    const savedDraft = window.AutoDraft.load(draftKey);
                    if (savedDraft && savedDraft.data) {
                        const data = (typeof savedDraft.data === 'string')
                            ? { requirements: savedDraft.data }
                            : savedDraft.data;

                        let restoredAny = false;

                        if (data.title && titleInput && !titleInput.value.trim()) {
                            titleInput.value = data.title;
                            restoredAny = true;
                        }

                        if (data.project_id && projectSelect && !projectSelect.value) {
                            projectSelect.value = data.project_id;
                            restoredAny = true;
                        }

                        if (data.requirements && !isContentEmpty(data.requirements) && isContentEmpty(editor.getContent())) {
                            editor.setContent(data.requirements);
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
        }
    });

    if (discardBtn) {
        const newDiscardBtn = discardBtn.cloneNode(true);
        discardBtn.parentNode.replaceChild(newDiscardBtn, discardBtn);

        newDiscardBtn.addEventListener('click', function () {
            if (confirm('¿Deseas descartar este borrador? Se eliminarán los datos que has redactado.')) {
                if (typeof tinymce !== 'undefined') {
                    const inst = tinymce.get('workorders-requirements');
                    if (inst) {
                        inst.setContent('');
                        inst.save();
                    }
                }
                const textarea = document.getElementById('workorders-requirements');
                if (textarea) textarea.value = '';

                if (titleInput) titleInput.value = '';

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

    if (form) {
        form.addEventListener('submit', function() {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
            if (window.AutoDraft && isNewRecord) {
                window.AutoDraft.clear(draftKey);
            }
            if (draftNotice) {
                draftNotice.classList.add('hidden');
            }
        });
    }
});
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>

<div class="card bg-base-100 shadow-xl border border-base-200">
    <div class="card-body">

        <!-- Barra superior con indicador de estado -->
        <div class="flex justify-between items-center mb-2">
            <span class="text-xs text-base-content/60 font-semibold uppercase tracking-wider">Formulario de Solicitud</span>
            <span id="request-wo-autosave-status" class="text-[11px] text-base-content/50 font-normal transition-opacity duration-300"></span>
        </div>

        <!-- Aviso de borrador restaurado con opción de descartar -->
        <div id="request-wo-draft-notice" class="hidden alert alert-warning/10 text-xs py-2.5 px-3.5 rounded-xl border border-warning/30 shadow-sm mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4 text-warning shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <span>Se restauró un borrador no enviado (<span id="request-wo-draft-time" class="font-medium"></span>).</span>
            </div>
            <button type="button" id="btn-discard-request-wo-draft" class="btn btn-ghost btn-xs text-error hover:bg-error/10 font-bold gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-3.5 h-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                Descartar borrador
            </button>
        </div>

        <?php $form = ActiveForm::begin([
            'id' => 'request-work-order-form',
            'options' => [
                'enctype' => 'multipart/form-data',
                'data-is-new' => $model->isNewRecord ? '1' : '0',
                'data-user-id' => !Yii::$app->user->isGuest ? Yii::$app->user->id : 0,
            ]
        ]); ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <?php if (!empty($projects) && count($projects) > 1): ?>
                <div class="form-control w-full md:col-span-2">
                    <label class="label">
                        <span class="label-text font-bold">Proyecto / Filial <span class="text-error">*</span></span>
                        <span class="label-text-alt opacity-70">Selecciona el proyecto o filial al que corresponde esta solicitud</span>
                    </label>
                    <?= $form->field($model, 'project_id', ['template' => '{input}{error}'])->dropDownList(
                        ArrayHelper::map($projects, 'id', function($p) {
                            $label = $p->name;
                            if (!empty($p->business_name) && $p->business_name !== $p->name) {
                                $label .= ' (' . $p->business_name . ')';
                            }
                            return $label;
                        }),
                        [
                            'class' => 'select select-bordered w-full',
                            'id' => 'workorders-project_id'
                        ]
                    ) ?>
                </div>
            <?php else: ?>
                <?= $form->field($model, 'project_id')->hiddenInput(['value' => $model->project_id])->label(false) ?>
                <?php if (!empty($projects) && count($projects) === 1): ?>
                    <div class="form-control w-full md:col-span-2">
                        <label class="label">
                            <span class="label-text font-bold">Proyecto / Filial</span>
                        </label>
                        <div class="p-3 bg-base-200/60 rounded-lg text-sm font-medium flex items-center gap-2 border border-base-300">
                            <span class="badge badge-primary badge-sm">Predeterminado</span>
                            <span>📌 <?= Html::encode($projects[0]->name) ?><?= (!empty($projects[0]->business_name) && $projects[0]->business_name !== $projects[0]->name) ? ' (' . Html::encode($projects[0]->business_name) . ')' : '' ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="form-control w-full md:col-span-2">
                <label class="label">
                    <span class="label-text font-bold">Título de la Solicitud <span class="text-error">*</span></span>
                    <span class="label-text-alt opacity-70">Describe brevemente qué requieres</span>
                </label>
                <?= $form->field($model, 'title', ['template' => '{input}{error}'])->textInput([
                    'class' => 'input input-bordered w-full',
                    'placeholder' => 'Ej: Desarrollo de API Rest para App Móvil'
                ]) ?>
            </div>

            <div class="form-control w-full md:col-span-2">
                <label class="label">
                    <span class="label-text font-bold">Detalle de Requerimientos <span class="text-error">*</span></span>
                    <span class="label-text-alt opacity-70">Indica tu solicitud en detalle. Procura ser lo más específic@ posible.</span>
                </label>
                <?= $form->field($model, 'requirements', ['template' => '{input}{error}'])                
                ->textarea([
                    'rows' => 10, 
                    'class' => 'textarea textarea-bordered w-full h-64 font-mono text-sm leading-relaxed',
                    'placeholder' => "1. Desarrollo de Login...\n2. Panel administrativo...\n3. Integración con pasarela..."
                ]) ?>
            </div>
            
            <div class="form-control w-full md:col-span-2 mt-4">
                <label class="label">
                    <span class="label-text font-bold">Archivo Adjunto (Opcional)</span>
                    <span class="label-text-alt opacity-70">Puedes subir imágenes, requerimientos en Word/Excel, PDFs o archivos ZIP/RAR (hasta 15MB). El archivo se cargará directamente en Google Drive.</span>
                </label>
                <?= $form->field($model, 'attachmentFile', ['template' => '{input}{error}'])->fileInput([
                    'class' => 'file-input file-input-bordered file-input-primary w-full'
                ]) ?>
            </div>
        </div>

        <div class="card-actions justify-end mt-8 border-t border-base-200 pt-6">
            <?= Html::submitButton($model->isNewRecord ? 'Solicitar' : 'Guardar cambios', ['class' => 'btn btn-primary text-white px-8']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>