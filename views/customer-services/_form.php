<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $customers array Map [id => name] */
/* @var $products array Map [id => name] */
/* @var $servers array Map [id => name] */ // Nueva variable que debes pasar desde el controlador
?>

<div class="card bg-base-100 shadow-xl max-w-4xl mx-auto border border-base-200">
    <div class="card-body">

        <?php $form = ActiveForm::begin([
            'options' => ['class' => 'space-y-4'],
            'fieldConfig' => [
                'labelOptions' => ['class' => 'label-text font-bold mb-1'],
                'inputOptions' => ['class' => 'input input-bordered w-full focus:input-primary'],
                'errorOptions' => ['class' => 'text-error text-xs mt-1'],
            ]
        ]); ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div class="form-control">
                <div class="form-group">
                    <?php
                    if ($model->customer_id) {
                        echo Html::activeHiddenInput($model, 'customer_id');
                        echo '<label class="label-text font-bold mb-1">Cliente</label>';
                        echo Html::textInput('', $model->customer->business_name ?? '', ['class' => 'input input-bordered w-full bg-base-200', 'disabled' => true]);
                    } else {
                        echo $form->field($model, 'customer_id')->dropDownList($customers, ['prompt' => 'Selecciona un Cliente...']);
                    }
                    ?>
                </div>
            </div>

            <div class="form-control">
                <?= $form->field($model, 'product_id')->dropDownList($products, [
                    'prompt' => 'Selecciona el Servicio...',
                    'class' => 'select select-bordered w-full'
                ]) ?>
            </div>

            <div class="form-control">
                <?= $form->field($model, 'domain', [
                    'inputOptions' => ['placeholder' => 'Ej: atsys.co', 'class' => 'input input-bordered w-full font-mono']
                ])->label('Dominio o Referencia') ?>
            </div>

            <div class="form-control md:col-span-2">
                <?= $form->field($model, 'description_label')->textInput(['placeholder' => 'Ej: Servidor de Producción / Plan Básico']) ?>
            </div>

            <div class="divider md:col-span-2 text-xs font-bold opacity-50">CICLO DE FACTURACIÓN</div>

            <div class="form-control">
                <?= $form->field($model, 'start_date')->input('date') ?>
            </div>

            <div class="form-control">
                <?= $form->field($model, 'next_due_date')->input('date') ?>
            </div>

        </div>

        <div id="infra-fields-container" class="grid grid-cols-1 md:grid-cols-2 gap-6 transition-all duration-300">
            <div class="divider md:col-span-2 text-xs font-bold opacity-50">CREDENCIALES INTERNAS Y ESTADO</div>

            <div class="form-control">
                <?= $form->field($model, 'server_id')->dropDownList($servers, [
                    'prompt' => 'Asignación Automática',
                    'class' => 'select select-bordered w-full'
                ]) ?>
            </div>

            <div class="form-control">
                <?= $form->field($model, 'username_service')->textInput(['placeholder' => 'Usuario']) ?>
            </div>

            <div class="form-control">
                <?= $form->field($model, 'password_service')->textInput(['placeholder' => 'Contraseña']) ?>
            </div>

            <div class="form-control">
                <?= $form->field($model, 'status')->dropDownList([
                    1 => 'Activo',
                    2 => 'Suspendido',
                    0 => 'Cancelado'
                ], ['class' => 'select select-bordered w-full md:w-1/2']) ?>
            </div>
        </div>

        <div class="card-actions justify-between items-center mt-8 border-t border-base-200 pt-6">
            <label class="cursor-pointer label justify-start gap-3">
                <?= Html::checkbox('silent', false, ['class' => 'checkbox checkbox-sm']) ?>
                <span class="label-text">Crear sin avisar al cliente por correo</span>
            </label>
            <?= Html::submitButton('Asignar y Aprovisionar', ['class' => 'btn btn-primary text-white shadow-lg px-8']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
// Script ES6 para la lógica de visibilidad
$js = <<<JS
    const productSelect = document.getElementById('customerservices-product_id');
    const infraContainer = document.getElementById('infra-fields-container');

    function toggleInfraFields() {
        // Obtenemos el texto de la opción seleccionada
        const selectedText = productSelect.options[productSelect.selectedIndex].text.toLowerCase();
        console.log(selectedText);
        
        // Si el producto contiene "hosting", mostramos los campos; si no, los ocultamos
        if (selectedText.includes('hosting')) {
            infraContainer.classList.remove('hidden');
            // Opcional: añadir animación de DaisyUI/Tailwind
            infraContainer.classList.add('opacity-100', 'translate-y-0');
        } else {
            infraContainer.classList.add('hidden');
            infraContainer.classList.remove('opacity-100', 'translate-y-0');
        }
    }

    // Escuchar cambios
    productSelect.addEventListener('change', toggleInfraFields);

    // Ejecutar al cargar por si es una edición
    toggleInfraFields();
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>