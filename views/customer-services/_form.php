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

        <div id="domain-fields-container" class="grid grid-cols-1 gap-4 transition-all duration-300 hidden md:col-span-2">
            <div class="divider text-xs font-bold opacity-50">OPCIONES DE REGISTRO DE DOMINIO</div>
            <div class="form-control">
                <label class="cursor-pointer label justify-start gap-3 bg-base-200 p-4 rounded-lg">
                    <?= Html::checkbox('register_domain_api', false, ['class' => 'checkbox checkbox-primary', 'id' => 'register_domain_api']) ?>
                    <div>
                        <span class="label-text font-bold text-lg block">Registrar con el Proveedor Automáticamente</span>
                        <span class="text-xs opacity-70">Se registrará el dominio (1 año) usando la API y los datos del cliente.</span>
                    </div>
                </label>
            </div>
            
            <div id="domain-coupon-container" class="bg-base-100 border border-base-200 rounded-lg p-4" style="display: none;">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="form-control flex-1">
                        <label class="label-text font-bold mb-1">Cupón (Opcional)</label>
                        <?= Html::textInput('domain_coupon', '', ['class' => 'input input-bordered w-full', 'id' => 'domain_coupon', 'placeholder' => 'Ej: DOMINIO2024']) ?>
                    </div>
                    <div class="form-control">
                        <button type="button" id="btn-check-price" class="btn btn-outline btn-secondary">Validar Costo API</button>
                    </div>
                </div>
                
                <div id="price-result-container" class="mt-4 hidden p-3 rounded-lg text-sm">
                    <!-- Pricing results will be injected here -->
                </div>
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
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;

$js = <<<JS
    const productSelect = document.getElementById('customerservices-product_id');
    const infraContainer = document.getElementById('infra-fields-container');
    const domainContainer = document.getElementById('domain-fields-container');
    const registerDomainCheckbox = document.getElementById('register_domain_api');
    const couponContainer = document.getElementById('domain-coupon-container');
    const domainInput = document.getElementById('customerservices-domain');
    const btnCheckPrice = document.getElementById('btn-check-price');
    const priceResult = document.getElementById('price-result-container');
    const couponInput = document.getElementById('domain_coupon');

    function toggleFields() {
        const selectedText = productSelect.options[productSelect.selectedIndex].text.toLowerCase();
        
        if (selectedText.includes('hosting')) {
            infraContainer.classList.remove('hidden');
            infraContainer.classList.add('opacity-100', 'translate-y-0');
        } else {
            infraContainer.classList.add('hidden');
            infraContainer.classList.remove('opacity-100', 'translate-y-0');
        }

        if (selectedText.includes('dominio') || selectedText.includes('domain')) {
            domainContainer.classList.remove('hidden');
        } else {
            domainContainer.classList.add('hidden');
        }
    }

    async function fetchPrice() {
        const domain = domainInput.value.trim();
        if (!domain) {
            priceResult.classList.remove('hidden', 'bg-success', 'text-success-content');
            priceResult.classList.add('bg-error', 'text-error-content');
            priceResult.innerHTML = '<strong>Aviso:</strong> Escribe un dominio para ver el costo.';
            return;
        }

        const coupon = couponInput.value.trim();
        
        btnCheckPrice.classList.add('loading');
        btnCheckPrice.disabled = true;
        priceResult.classList.remove('hidden');
        priceResult.innerHTML = '<span class="loading loading-spinner loading-sm"></span> Consultando costo...';
        priceResult.classList.remove('bg-error', 'bg-success', 'text-error-content', 'text-success-content');
        priceResult.classList.add('bg-base-200');

        try {
            const formData = new FormData();
            formData.append('$csrfParam', '$csrfToken');
            formData.append('domain', domain);
            formData.append('action', 'REGISTER');
            formData.append('years', 1);
            if (coupon) formData.append('coupon', coupon);

            const res = await fetch('/domains/get-pricing', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();

            priceResult.classList.remove('bg-base-200');
            if (result.success) {
                priceResult.classList.remove('bg-error', 'text-error-content');
                priceResult.classList.add('bg-success', 'text-success-content');
                
                let html = '<strong>Costo de Registro (1 año):</strong> ' + result.data.finalPrice + ' ' + result.data.currency;
                if (result.data.promoPrice !== null) {
                    html += '<br><span class="text-xs font-bold bg-white text-success px-2 py-1 rounded mt-1 inline-block">¡Cupón válido! Precio original: <del>' + result.data.regularPrice + ' ' + result.data.currency + '</del></span>';
                }
                priceResult.innerHTML = html;
            } else {
                priceResult.classList.remove('bg-success', 'text-success-content');
                priceResult.classList.add('bg-error', 'text-error-content');
                priceResult.innerHTML = '<strong>Error API:</strong> ' + result.message;
            }
        } catch (error) {
            priceResult.classList.remove('bg-base-200', 'bg-success', 'text-success-content');
            priceResult.classList.add('bg-error', 'text-error-content');
            priceResult.innerHTML = '<strong>Error de red:</strong> No se pudo conectar con el servidor.';
        } finally {
            btnCheckPrice.classList.remove('loading');
            btnCheckPrice.disabled = false;
        }
    }

    registerDomainCheckbox.addEventListener('change', function() {
        couponContainer.style.display = this.checked ? 'block' : 'none';
        if (this.checked && domainInput.value.trim() !== '') {
            fetchPrice();
        } else if (!this.checked) {
            priceResult.classList.add('hidden');
        }
    });
    
    // Auto fetch when domain loses focus if checkbox is checked
    domainInput.addEventListener('blur', function() {
        if (registerDomainCheckbox.checked && this.value.trim() !== '') {
            fetchPrice();
        }
    });

    btnCheckPrice.addEventListener('click', fetchPrice);

    productSelect.addEventListener('change', toggleFields);
    toggleFields();
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>