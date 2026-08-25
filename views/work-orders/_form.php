<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\WorkOrders */
/* @var $form yii\widgets\ActiveForm */

// Asegurar que siempre tengamos la lista de clientes
if (!isset($customers) || empty($customers)) {
    $customers = \app\models\Customers::find()->orderBy('business_name')->all();
}

// Cargar proyectos del cliente asociado a la orden server-side
$projectsList = [];
if ($model->customer_id) {
    $projects = \app\models\Projects::find()
        ->where(['customer_id' => $model->customer_id, 'status' => \app\models\Projects::STATUS_ACTIVE])
        ->orderBy(['is_default' => SORT_DESC, 'name' => SORT_ASC])
        ->all();

    // Si el cliente no tiene proyectos aún, creamos el proyecto principal por defecto
    if (empty($projects)) {
        $custObj = \app\models\Customers::findOne($model->customer_id);
        if ($custObj) {
            $name = !empty($custObj->trade_name) ? $custObj->trade_name : $custObj->business_name;
            $defaultProject = new \app\models\Projects();
            $defaultProject->customer_id = $custObj->id;
            $defaultProject->code = 'PRJ-' . str_pad($custObj->id, 4, '0', STR_PAD_LEFT) . '-DEF';
            $defaultProject->name = 'Proyecto Principal - ' . $name;
            $defaultProject->business_name = $custObj->business_name;
            $defaultProject->document_number = $custObj->document_number;
            $defaultProject->address = $custObj->address;
            $defaultProject->is_default = 1;
            $defaultProject->status = \app\models\Projects::STATUS_ACTIVE;
            if ($defaultProject->save(false)) {
                $projects = [$defaultProject];
            }
        }
    }

    foreach ($projects as $p) {
        $projectsList[$p->id] = $p->code . ' - ' . $p->getDisplayName();
    }

    // Si el modelo aún no tiene project_id asignado, pre-seleccionar el proyecto predeterminado
    if (!$model->project_id && !empty($projects)) {
        $defaultProj = null;
        foreach ($projects as $p) {
            if ($p->is_default) {
                $defaultProj = $p;
                break;
            }
        }
        $model->project_id = $defaultProj ? $defaultProj->id : $projects[0]->id;
    }
}

// A. Cargamos la librería desde la nube (Versión 6, estable y ligera)
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js', [
    'position' => \yii\web\View::POS_HEAD
]);

// B. Inicializamos el editor y la lógica de proyectos y TRM
$projectsListUrl = Url::to(['/projects/list-by-customer']);
$js = <<<JS
document.addEventListener("DOMContentLoaded", function() {
    // --- LÓGICA DE TINYMCE ---
    const isDarkMode = document.documentElement.classList.contains('dark');
    tinymce.remove('#workorders-requirements'); // Limpieza preventiva
    tinymce.init({
        selector: '#workorders-requirements', 
        height: 300,
        menubar: false, 
        statusbar: false, 
        language: 'es', 
        plugins: 'lists link autolink fullscreen', 
        toolbar: 'bold italic underline | bullist numlist | link | removeformat | fullscreen', 
        skin: isDarkMode ? 'oxide-dark' : 'oxide', 
        content_css: isDarkMode ? 'dark' : 'default',
        branding: false, 
        setup: function (editor) {
            editor.on('change', function () {
                editor.save();
            });
        }
    });

    // --- LÓGICA DE PROYECTOS POR CLIENTE ---
    const customerSelect = document.getElementById('workorders-customer_id');
    const projectSelect = document.getElementById('workorders-project_id');
    const initialProjectId = "<?= (string)$model->project_id ?>";
    const projectsApiUrl = "{$projectsListUrl}";

    function loadProjects(customerId, selectedProjectId) {
        if (!projectSelect) return;
        if (!customerId) {
            projectSelect.innerHTML = '<option value="">-- Seleccione un cliente primero --</option>';
            return;
        }
        
        projectSelect.innerHTML = '<option value="">Cargando proyectos...</option>';
        const sep = projectsApiUrl.indexOf('?') !== -1 ? '&' : '?';
        fetch(projectsApiUrl + sep + 'customer_id=' + encodeURIComponent(customerId))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.projects.length > 0) {
                    projectSelect.innerHTML = '';
                    let matched = false;
                    data.projects.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.id;
                        opt.textContent = p.code + ' - ' + p.name;
                        if (selectedProjectId && String(p.id) === String(selectedProjectId)) {
                            opt.selected = true;
                            matched = true;
                        } else if (!selectedProjectId && !matched && p.is_default) {
                            opt.selected = true;
                            matched = true;
                        }
                        projectSelect.appendChild(opt);
                    });
                    if (!matched && projectSelect.options.length > 0) {
                        projectSelect.options[0].selected = true;
                    }
                } else {
                    projectSelect.innerHTML = '<option value="">-- No hay proyectos registrados --</option>';
                }
            })
            .catch(err => {
                console.error('Error cargando proyectos:', err);
                projectSelect.innerHTML = '<option value="">-- Error al cargar proyectos --</option>';
            });
    }

    if (customerSelect) {
        customerSelect.addEventListener('change', function() {
            loadProjects(this.value, null);
        });
        // Si el selector no tiene opciones previas cargadas por PHP, cargar vía AJAX
        if (customerSelect.value && projectSelect && projectSelect.options.length <= 1) {
            loadProjects(customerSelect.value, initialProjectId);
        }
    } else if (projectSelect) {
        const currentCustId = "<?= (string)$model->customer_id ?>";
        if (currentCustId && projectSelect.options.length <= 1) {
            loadProjects(currentCustId, initialProjectId);
        }
    }

    // --- LÓGICA DE MONEDA Y TRM ---
    const currencySelect = document.getElementById('workorders-currency');
    const trmContainer = document.getElementById('trm-container');
    const trmInput = document.getElementById('workorders-exchange_rate');

    function toggleTrm() {
        if (!currencySelect || !trmContainer || !trmInput) return;
        if (currencySelect.value === 'USD' || currencySelect.value === 'EUR') {
            trmContainer.style.display = 'block';
            trmContainer.classList.add('animate-fade-in');
        } else {
            trmContainer.style.display = 'none';
            trmInput.value = '';
        }
    }

    if (currencySelect) {
        currencySelect.addEventListener('change', toggleTrm);
    }
});
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>

<div class="card bg-base-100 shadow-xl border border-base-200">
    <div class="card-body">

        <?php $form = ActiveForm::begin(['id' => 'work-order-form']); ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="form-control w-full">
                <label class="label"><span class="label-text font-bold">Cliente</span></label>
                <?= $form->field($model, 'customer_id', ['template' => '{input}{error}'])->dropDownList(
                    ArrayHelper::map($customers, 'id', 'business_name'),
                    ['prompt' => 'Seleccione un cliente...', 'class' => 'select select-bordered w-full', 'id' => 'workorders-customer_id']
                ) ?>
            </div>

            <!-- Proyecto / Filial -->
            <div class="form-control w-full">
                <label class="label"><span class="label-text font-bold">Proyecto / Empresa Filial</span></label>
                <?= $form->field($model, 'project_id', ['template' => '{input}{error}'])->dropDownList(
                    $projectsList,
                    [
                        'prompt' => empty($projectsList) ? '-- No hay proyectos registrados --' : '-- Seleccione un Proyecto --',
                        'class' => 'select select-bordered w-full',
                        'id' => 'workorders-project_id'
                    ]
                ) ?>
            </div>

            <!-- Contrato Relacionado -->
            <?php
            $contractsQuery = \app\models\Contracts::find();
            if ($model->customer_id) {
                $contractsQuery->andWhere(['customer_id' => $model->customer_id]);
            }
            $contractsList = ArrayHelper::map($contractsQuery->all(), 'id', function($c) {
                return $c->code . ' - ' . $c->title;
            });
            ?>
            <div class="form-control w-full md:col-span-2">
                <label class="label"><span class="label-text font-bold">Contrato Relacionado (Opcional)</span></label>
                <?= $form->field($model, 'contract_id', ['template' => '{input}{error}'])->dropDownList(
                    $contractsList,
                    ['prompt' => '-- Sin Contrato Vinculado --', 'class' => 'select select-bordered w-full']
                ) ?>
            </div>


            <!-- Porcentaje de Avance -->
            <div class="form-control w-full md:col-span-2">
                <div class="flex items-center justify-between p-4 bg-info/10 rounded-xl border border-info/20">
                    <div>
                        <span class="font-bold text-sm block text-info">Porcentaje de Avance Físico / Técnico</span>
                        <span class="text-xs opacity-70">Permite registrar el % de progreso completado de esta orden.</span>
                    </div>
                    <div class="w-32">
                        <?= $form->field($model, 'progress_percentage', ['template' => '{input}{error}'])->textInput([
                            'type' => 'number',
                            'step' => '0.1',
                            'min' => '0',
                            'max' => '100',
                            'class' => 'input input-bordered input-info w-full text-right font-mono font-bold',
                            'placeholder' => '0.0%'
                        ]) ?>
                    </div>
                </div>
            </div>

            <!-- Nueva Agrupación: Costo, Moneda y TRM -->
            <div class="form-control w-full md:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border border-base-200 p-4 rounded-xl bg-base-50">
                    
                    <div class="form-control w-full">
                        <label class="label"><span class="label-text font-bold">Inversión Total</span></label>
                        <?= $form->field($model, 'total_cost', ['template' => '{input}{error}'])->textInput([
                            'type' => 'number', 
                            'step' => '0.01', 
                            'class' => 'input input-bordered w-full font-mono text-lg',
                            'placeholder' => '0.00'
                        ]) ?>
                    </div>

                    <div class="form-control w-full">
                        <label class="label"><span class="label-text font-bold">Moneda</span></label>
                        <?= $form->field($model, 'currency', ['template' => '{input}{error}'])->dropDownList([
                            'COP' => 'Pesos (COP)',
                            'USD' => 'Dólares (USD)',
                            'EUR' => 'Euros (EUR)'
                        ], [
                            'id' => 'workorders-currency',
                            'class' => 'select select-bordered w-full text-lg'
                        ]) ?>
                    </div>

                    <div class="form-control w-full" id="trm-container" style="<?= in_array($model->currency, ['USD', 'EUR']) ? '' : 'display: none;' ?>">
                        <label class="label"><span class="label-text font-bold text-primary">Tasa de Cambio (TRM)</span></label>
                        <?= $form->field($model, 'exchange_rate', ['template' => '{input}{error}'])->textInput([
                            'id' => 'workorders-exchange_rate',
                            'type' => 'number', 
                            'step' => '0.01', 
                            'class' => 'input input-bordered input-primary w-full font-mono text-lg',
                            'placeholder' => 'Ej: 3950.00'
                        ]) ?>
                    </div>
                </div>
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
                    'id' => 'workorders-requirements', // Agregado el ID explícito para TinyMCE
                    'rows' => 10, 
                    'class' => 'textarea textarea-bordered w-full h-64 font-mono text-sm leading-relaxed',
                    'placeholder' => "1. Desarrollo de Login...\n2. Panel administrativo...\n3. Integración con pasarela..."
                ]) ?>
            </div>

            <div class="form-control w-full md:col-span-2">
                <div class="flex items-center gap-4 p-4 bg-base-200/50 rounded-xl border border-base-200">
                    <?= $form->field($model, 'has_service_contract')->checkbox(['class' => 'checkbox checkbox-primary'], false)->label(false) ?>
                    <div>
                        <span class="font-bold text-sm block">¿Incluye contrato de servicios?</span>
                        <span class="text-xs opacity-70">Al marcar esta opción, la orden no vencerá automáticamente tras 5 días de inactividad.</span>
                    </div>
                </div>
            </div>

            <?php if ($model->isNewRecord): ?>
            <div class="form-control w-full md:col-span-2">
                <div class="flex items-center gap-4 p-4 bg-primary/10 rounded-xl border border-primary/20">
                    <?= $form->field($model, 'is_preapproved')->checkbox(['class' => 'checkbox checkbox-primary'], false)->label(false) ?>
                    <div>
                        <span class="font-bold text-sm block text-primary">Pre-Aprobar Orden de Trabajo</span>
                        <span class="text-xs opacity-70">Al marcar esta opción, la orden se creará en estado Aprobada de inmediato y no se enviará el correo de confirmación de apertura al cliente. Permite usar la bitácora directamente.</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$model->isNewRecord && $model->ticket_id && $model->status == \app\models\WorkOrders::STATUS_DRAFT): ?>
            <div class="form-control w-full md:col-span-2">
                <label class="label"><span class="label-text font-bold">Estado final de la Orden</span></label>
                <div class="flex flex-col gap-3 border border-base-200 p-4 rounded-xl bg-base-50">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="WorkOrders[ticket_action]" value="draft" checked class="radio radio-primary mt-1" />
                        <div>
                            <span class="font-bold text-sm block">Mantener como Borrador</span>
                            <span class="text-xs opacity-70">Guarda los cambios pero la orden permanecerá en estado borrador.</span>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="WorkOrders[ticket_action]" value="send" class="radio radio-primary mt-1" />
                        <div>
                            <span class="font-bold text-sm block text-primary">Enviar para aprobación al cliente</span>
                            <span class="text-xs opacity-70">La orden cambiará a estado Pendiente, se generará el PDF y se enviará por correo al cliente.</span>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="WorkOrders[ticket_action]" value="preapprove" class="radio radio-primary mt-1" />
                        <div>
                            <span class="font-bold text-sm block text-success">Pre-Aprobar Orden de Trabajo</span>
                            <span class="text-xs opacity-70">La orden cambiará a estado Aprobada de inmediato. No se enviará correo al cliente.</span>
                        </div>
                    </label>
                </div>
            </div>
            <?php endif; ?>

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