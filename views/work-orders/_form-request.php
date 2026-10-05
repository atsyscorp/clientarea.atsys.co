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
document.addEventListener("DOMContentLoaded", function() {
    const isDarkMode = document.documentElement.classList.contains('dark');
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
            editor.on('change keyup NodeChange', function () {
                editor.save();
            });
        }
    });

    const form = document.getElementById('request-work-order-form');
    if (form) {
        form.addEventListener('submit', function() {
            tinymce.triggerSave();
        });
    }
});
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>

<div class="card bg-base-100 shadow-xl border border-base-200">
    <div class="card-body">

        <?php $form = ActiveForm::begin([
            'id' => 'request-work-order-form',
            'options' => ['enctype' => 'multipart/form-data']
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