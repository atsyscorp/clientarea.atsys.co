<?php
use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;

$this->title = 'Gestionar Dominio: ' . ($model->domain ?: 'Sin asignar');
$this->params['breadcrumbs'][] = ['label' => 'Mis Servicios', 'url' => ['/customer-services/index']];
$this->params['breadcrumbs'][] = $this->title;

// $isUsingOurDNS is passed from the controller (boolean)
?>
<div class="domains-manage">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
        
        <div>
            <?php if ($isUsingOurDNS): ?>
                <?= Html::a('<i class="fas fa-list-ul mr-1"></i> Registros DNS (Zonas)', ['dns', 'id' => $model->id], ['class' => 'btn btn-secondary shadow-lg text-white']) ?>
            <?php else: ?>
                <div class="tooltip tooltip-bottom" data-tip="Debes guardar los NameServers locales (ns1/ns2.atsys.co) abajo para poder gestionar las zonas desde aquí.">
                    <button class="btn btn-disabled"><i class="fas fa-lock mr-1"></i> Registros DNS Bloqueados</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-base-100 shadow-xl rounded-box p-6">
            <h2 class="text-xl font-bold mb-4 border-b pb-2">Información del Dominio</h2>
            
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table table-zebra w-full'],
                'attributes' => [
                    'domain',
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => $model->getStatusHtml(),
                    ],
                    'start_date:date',
                    'next_due_date:date',
                    [
                        'label' => 'Producto Base',
                        'value' => $model->product ? $model->product->name : '-',
                    ],
                ],
            ]) ?>
        </div>
        
        <div class="bg-base-100 shadow-xl rounded-box p-6">
            <h2 class="text-xl font-bold mb-4 border-b pb-2">Servidores de Nombres (NameServers)</h2>
            <div class="text-sm mb-4 text-base-content/70">
                Para habilitar la gestión de Zonas DNS desde esta plataforma, los NameServers principales deben apuntar exactamente a:<br>
                <b>ns1.atsys.co</b> y <b>ns2.atsys.co</b>.
            </div>
            
            <?php $form = ActiveForm::begin([
                'action' => ['update-ns', 'id' => $model->id],
                'method' => 'post',
            ]); ?>
            
            <div class="form-control mb-2">
                <label class="label"><span class="label-text">NameServer 1</span></label>
                <?= Html::textInput('CustomerServices[ns1]', $model->ns1, ['class' => 'input input-bordered w-full input-sm', 'placeholder' => 'ns1.atsys.co']) ?>
            </div>
            <div class="form-control mb-2">
                <label class="label"><span class="label-text">NameServer 2</span></label>
                <?= Html::textInput('CustomerServices[ns2]', $model->ns2, ['class' => 'input input-bordered w-full input-sm', 'placeholder' => 'ns2.atsys.co']) ?>
            </div>
            <div class="form-control mb-2">
                <label class="label"><span class="label-text">NameServer 3</span></label>
                <?= Html::textInput('CustomerServices[ns3]', $model->ns3, ['class' => 'input input-bordered w-full input-sm', 'placeholder' => 'ns3.atsys.co']) ?>
            </div>
            <div class="form-control mb-4">
                <label class="label"><span class="label-text">NameServer 4</span></label>
                <?= Html::textInput('CustomerServices[ns4]', $model->ns4, ['class' => 'input input-bordered w-full input-sm', 'placeholder' => 'ns4.atsys.co']) ?>
            </div>
            
            <div class="mt-4 text-right">
                <?= Html::submitButton('Guardar NameServers', ['class' => 'btn btn-primary text-white btn-sm']) ?>
            </div>
            
            <?php ActiveForm::end(); ?>
        </div>
    </div>
    
    <?php if (Yii::$app->user->identity->isAdmin): ?>
    <div class="mt-8 border-t-4 border-error pt-6">
        <h2 class="text-xl font-bold mb-4 border-b pb-2 text-primary">⚙️ Herramientas de Administrador (API Proveedor)</h2>
        <div class="alert alert-warning shadow-sm mb-4">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span>Solo tú (Admin) puedes ver esta sección. Permite forzar el registro o renovación del dominio consumiendo saldo del proveedor real.</span>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm mb-4">Ejecuta acciones directamente en el proveedor de dominios. Solo para administradores.</p>
                <?= Html::beginForm(['domains/api-action', 'id' => $model->id], 'post', ['class' => 'space-y-4']) ?>
                
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Acción a realizar</span></label>
                    <?= Html::dropDownList('api_action', 'renew', [
                        'renew' => 'Renovar Dominio',
                        'register' => 'Forzar Registro (Si no se registró antes)',
                    ], ['class' => 'select select-bordered w-full']) ?>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Años (1-10)</span></label>
                    <?= Html::input('number', 'years', 1, ['class' => 'input input-bordered w-full', 'min' => 1, 'max' => 10]) ?>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Cupón (Opcional)</span></label>
                    <div class="flex gap-2">
                        <?= Html::textInput('coupon', '', ['class' => 'input input-bordered flex-1', 'id' => 'admin_coupon', 'placeholder' => 'Ej: DOMINIO2024']) ?>
                        <button type="button" id="btn-admin-price" class="btn btn-outline btn-secondary">Cotizar</button>
                    </div>
                </div>

                <div id="admin-price-result" class="mt-4 hidden p-3 rounded-lg text-sm"></div>

                <div class="mt-4">
                    <?= Html::submitButton('Ejecutar Acción en Proveedor', [
                        'class' => 'btn btn-primary text-white w-full',
                        'data' => ['confirm' => '¿Estás seguro de ejecutar esta acción en la API del proveedor? Esto podría generar cargos en la cuenta mayorista.']
                    ]) ?>
                </div>
                
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>
    
    <?php
    $csrfParam = Yii::$app->request->csrfParam;
    $csrfToken = Yii::$app->request->csrfToken;
    $domainName = $model->domain;
    
    $js = <<<JS
        const btnAdminPrice = document.getElementById('btn-admin-price');
        const adminPriceResult = document.getElementById('admin-price-result');
        
        if (btnAdminPrice) {
            btnAdminPrice.addEventListener('click', async function() {
                const action = document.querySelector('select[name="api_action"]').value;
                const years = document.querySelector('input[name="years"]').value;
                const coupon = document.getElementById('admin_coupon').value.trim();
                
                btnAdminPrice.classList.add('loading');
                btnAdminPrice.disabled = true;
                adminPriceResult.classList.add('hidden');
                
                try {
                    const formData = new FormData();
                    formData.append('$csrfParam', '$csrfToken');
                    formData.append('domain', '$domainName');
                    formData.append('action', action.toUpperCase());
                    formData.append('years', years);
                    if (coupon) formData.append('coupon', coupon);

                    const res = await fetch('/domains/get-pricing', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await res.json();

                    adminPriceResult.classList.remove('hidden');
                    if (result.success) {
                        adminPriceResult.classList.remove('bg-error', 'text-error-content');
                        adminPriceResult.classList.add('bg-success', 'text-success-content');
                        
                        let html = '<strong>Costo (' + years + ' año/s):</strong> ' + result.data.finalPrice + ' ' + result.data.currency;
                        if (result.data.promoPrice !== null) {
                            html += '<br><span class="text-xs">¡Cupón válido! Precio original: <del>' + result.data.regularPrice + ' ' + result.data.currency + '</del></span>';
                        }
                        adminPriceResult.innerHTML = html;
                    } else {
                        adminPriceResult.classList.remove('bg-success', 'text-success-content');
                        adminPriceResult.classList.add('bg-error', 'text-error-content');
                        adminPriceResult.innerHTML = '<strong>Error API:</strong> ' + result.message;
                    }
                } catch (error) {
                    adminPriceResult.classList.remove('hidden');
                    adminPriceResult.classList.add('bg-error', 'text-error-content');
                    adminPriceResult.innerHTML = '<strong>Error de red:</strong> No se pudo conectar con el servidor.';
                } finally {
                    btnAdminPrice.classList.remove('loading');
                    btnAdminPrice.disabled = false;
                }
            });
        }
    JS;
    $this->registerJs($js, \yii\web\View::POS_END);
    ?>
    <?php endif; ?>
</div>
