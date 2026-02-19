<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\WorkOrders */
/* @var $form yii\widgets\ActiveForm */

// A. Cargamos la librería desde la nube (Versión 6, estable y ligera)
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js', [
    'position' => \yii\web\View::POS_HEAD
]);

// B. Inicializamos el editor sobre el ID 'ticket-message-editor'
$js = <<<JS
document.addEventListener("DOMContentLoaded", function() {
    tinymce.remove('#ticket-message-editor'); // Limpieza preventiva por si usas Pjax
    tinymce.init({
        selector: '#ticket-message-editor', // Debe coincidir con el ID de arriba
        height: 300,
        menubar: false, // Sin menú superior (Archivo, Editar...)
        statusbar: false, // Sin barra inferior
        language: 'es', // Intenta cargar español, si falla usará inglés
        plugins: 'lists link autolink', // Plugins básicos
        toolbar: 'bold italic underline | bullist numlist | link | removeformat', // Herramientas limpias
        skin: 'oxide', // Tema claro estándar
        content_css: 'default',
        branding: false, // Quitar marca "Powered by TinyMCE"
        setup: function (editor) {
            // Esto asegura que el valor se guarde en el textarea al enviar el formulario
            editor.on('change', function () {
                editor.save();
            });
        }
    });
});
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>

<div class="card bg-base-100 shadow-xl border border-base-200">
    <div class="card-body">

        <?php $form = ActiveForm::begin(); ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <?php if (isset($customers)): ?>
            <div class="form-control w-full">
                <label class="label"><span class="label-text font-bold">Cliente</span></label>
                <?= $form->field($model, 'customer_id', ['template' => '{input}{error}'])->dropDownList(
                    ArrayHelper::map($customers, 'id', 'business_name'),
                    ['prompt' => 'Seleccione un cliente...', 'class' => 'select select-bordered w-full']
                ) ?>
            </div>
            <?php endif; ?>

            <div class="form-control w-full">
                <label class="label"><span class="label-text font-bold">Inversión Total ($)</span></label>
                <?= $form->field($model, 'total_cost', ['template' => '{input}{error}'])->textInput([
                    'type' => 'number', 
                    'step' => '0.01', 
                    'class' => 'input input-bordered w-full font-mono text-lg',
                    'placeholder' => '0.00'
                ]) ?>
            </div>

            <div class="form-control w-full md:col-span-2">
                <label class="label"><span class="label-text font-bold">Título del Proyecto</span></label>
                <?= $form->field($model, 'title', ['template' => '{input}{error}'])->textInput([
                    'class' => 'input input-bordered w-full',
                    'placeholder' => 'Ej: Desarrollo de API Rest para App Móvil'
                ]) ?>
            </div>

            <div class="form-control w-full md:col-span-2">
                <label class="label">
                    <span class="label-text font-bold">Detalle de Requerimientos y Alcance</span>
                    <span class="label-text-alt opacity-70">Sé lo más específico posible</span>
                </label>
                <?= $form->field($model, 'requirements', ['template' => '{input}{error}'])
                
                ->textarea([
                    'rows' => 10, 
                    'class' => 'textarea textarea-bordered w-full h-64 font-mono text-sm leading-relaxed',
                    'placeholder' => "1. Desarrollo de Login...\n2. Panel administrativo...\n3. Integración con pasarela..."
                ]) ?>
            </div>

            <div class="form-control w-full md:col-span-2">
                <label class="label"><span class="label-text font-bold">Notas o Condiciones Especiales</span></label>
                <?= $form->field($model, 'notes', ['template' => '{input}{error}'])->textarea([
                    'rows' => 3, 
                    'class' => 'textarea textarea-bordered w-full',
                    'placeholder' => 'Ej: El pago se realizará 50% anticipo y 50% contra entrega.'
                ]) ?>
            </div>

        </div>

        <div class="card-actions justify-end mt-8 border-t border-base-200 pt-6">
            <?= Html::submitButton($model->isNewRecord ? 'Crear Orden y Enviar' : 'Guardar cambios', ['class' => 'btn btn-primary text-white px-8']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>