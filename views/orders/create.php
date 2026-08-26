<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\Customers;
use app\models\Products;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */

$this->title = 'Registrar Nueva Órden de Pago';
$this->params['breadcrumbs'][] = ['label' => 'Órdenes de Pago', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Preparar los productos para usarlos en el JS
$products = Products::find()->where(['status' => 1])->all();
$productsJson = [];
foreach($products as $p) {
    $productsJson[] = ['id' => $p->id, 'name' => Html::encode($p->name), 'price' => floatval($p->price)];
}
?>
<div class="orders-create container mx-auto px-4 py-8">

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-base-content"><?= Html::encode($this->title) ?></h1>
        <p class="text-base-content/70 mt-2">Genera una orden de pago seleccionando productos o ingresando conceptos personalizados.</p>
    </div>

    <div class="card bg-base-100 shadow-xl border border-base-200 w-full max-w-4xl">
        <div class="card-body">
            <?php $form = ActiveForm::begin(['id' => 'dynamic-order-form']); ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="form-control">
                    <?= $form->field($model, 'customer_id')->dropDownList(
                        ArrayHelper::map(Customers::find()->orderBy('business_name')->all(), 'id', function($model) {
                            return $model->business_name . ' (' . $model->email . ')';
                        }),
                        ['prompt' => 'Selecciona un cliente...', 'class' => 'select select-bordered w-full', 'required' => true]
                    )->label('Cliente', ['class' => 'label font-bold text-sm']) ?>
                </div>
                
                <div class="form-control">
                    <?= $form->field($model, 'currency')->dropDownList([
                        'COP' => 'COP (Pesos)',
                        'USD' => 'USD (Dólares)',
                        'EUR' => 'EUR (Euros)'
                    ], ['class' => 'select select-bordered w-full'])->label('Moneda', ['class' => 'label font-bold text-sm']) ?>
                </div>
            </div>

            <div class="divider">Ítems a Facturar</div>

            <div class="mb-6">
                <div id="items-container" class="space-y-3">
                    <!-- Dynamic rows go here -->
                </div>
                <button type="button" onclick="addItemRow()" class="btn btn-sm btn-outline mt-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Agregar Ítem
                </button>
            </div>

            <div class="flex flex-col items-end mb-6">
                <div class="text-lg text-base-content/70 font-semibold">Total Orden:</div>
                <div class="text-4xl font-extrabold text-primary" id="order-total-display">0.00</div>
            </div>

            <div class="form-control mt-4">
                <?= Html::submitButton('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> Generar Órden', ['class' => 'btn btn-primary w-full gap-2 text-white font-bold text-lg']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<script>
const products = <?= json_encode($productsJson) ?>;
let itemIndex = 0;

function addItemRow() {
    const rowId = itemIndex++;
    
    let productOptions = '<option value="">Selecciona un producto...</option>';
    products.forEach(p => {
        productOptions += `<option value="${p.id}" data-price="${p.price}">${p.name}</option>`;
    });

    const rowHtml = `
        <div class="flex flex-col md:flex-row gap-3 items-start md:items-end bg-base-200/50 p-4 rounded-xl border border-base-300 item-row" id="row-${rowId}">
            <div class="form-control w-full md:w-1/4">
                <label class="label text-xs font-bold py-1">Tipo de Ítem</label>
                <select name="items[${rowId}][type]" class="select select-bordered select-sm w-full" onchange="toggleItemType(${rowId})">
                    <option value="custom">Concepto Personalizado</option>
                    <option value="product">Producto del Catálogo</option>
                </select>
            </div>
            
            <div class="form-control w-full md:w-2/4" id="container-custom-${rowId}">
                <label class="label text-xs font-bold py-1">Descripción</label>
                <input type="text" name="items[${rowId}][description]" class="input input-bordered input-sm w-full" placeholder="Ej: Abono a cuenta..." required>
            </div>
            
            <div class="form-control w-full md:w-2/4 hidden" id="container-product-${rowId}">
                <label class="label text-xs font-bold py-1">Producto</label>
                <select name="items[${rowId}][product_id]" class="select select-bordered select-sm w-full" onchange="updatePrice(${rowId})">
                    ${productOptions}
                </select>
            </div>
            
            <div class="form-control w-full md:w-1/4">
                <label class="label text-xs font-bold py-1">Precio Unitario</label>
                <input type="number" name="items[${rowId}][amount]" class="input input-bordered input-sm w-full item-amount" step="0.01" min="0" required onchange="calcTotal()" onkeyup="calcTotal()">
            </div>
            
            <button type="button" class="btn btn-sm btn-error btn-square mt-2 md:mt-0" onclick="removeRow(${rowId})" title="Eliminar ítem">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            </button>
        </div>
    `;
    document.getElementById('items-container').insertAdjacentHTML('beforeend', rowHtml);
}

function removeRow(rowId) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 1) {
        document.getElementById(`row-${rowId}`).remove();
        calcTotal();
    } else {
        alert("La orden debe tener al menos un ítem.");
    }
}

function toggleItemType(rowId) {
    const type = document.querySelector(`select[name="items[${rowId}][type]"]`).value;
    const customContainer = document.getElementById(`container-custom-${rowId}`);
    const productContainer = document.getElementById(`container-product-${rowId}`);
    const descInput = document.querySelector(`input[name="items[${rowId}][description]"]`);
    const prodSelect = document.querySelector(`select[name="items[${rowId}][product_id]"]`);
    
    if (type === 'custom') {
        customContainer.classList.remove('hidden');
        productContainer.classList.add('hidden');
        descInput.required = true;
        prodSelect.required = false;
        // Optionally, reset description if it was a product name
        // descInput.value = '';
    } else {
        customContainer.classList.add('hidden');
        productContainer.classList.remove('hidden');
        descInput.required = false;
        prodSelect.required = true;
        updatePrice(rowId);
    }
}

function updatePrice(rowId) {
    const prodSelect = document.querySelector(`select[name="items[${rowId}][product_id]"]`);
    const selectedOption = prodSelect.options[prodSelect.selectedIndex];
    if (selectedOption && selectedOption.value !== "") {
        const price = selectedOption.getAttribute('data-price');
        document.querySelector(`input[name="items[${rowId}][amount]"]`).value = price;
        
        // Populate hidden description or keep it valid
        const descInput = document.querySelector(`input[name="items[${rowId}][description]"]`);
        descInput.value = selectedOption.text; 
        calcTotal();
    }
}

function calcTotal() {
    let total = 0;
    document.querySelectorAll('.item-amount').forEach(el => {
        const val = parseFloat(el.value);
        if(!isNaN(val)) total += val;
    });
    
    // Formatear como moneda (COP/USD)
    document.getElementById('order-total-display').innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Ensure at least one row exists
document.addEventListener("DOMContentLoaded", () => {
    addItemRow();
    
    // Prevent form submission if no items
    document.getElementById('dynamic-order-form').addEventListener('submit', function(e) {
        if(document.querySelectorAll('.item-row').length === 0) {
            e.preventDefault();
            alert("Debes agregar al menos un ítem a la orden.");
        }
    });
});
</script>