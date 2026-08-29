<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Gestionar DNS: ' . $model->domain;
$this->params['breadcrumbs'][] = ['label' => 'Dominios', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->domain, 'url' => ['manage', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'DNS';

$recordTypes = ['A', 'AAAA', 'CNAME', 'NS', 'MX', 'TXT', 'SPF', 'CAA', 'SRV'];

$ttlPresets = [
    60 => '1 Minuto',
    300 => '5 Minutos',
    1800 => '30 Minutos',
    3600 => '1 Hora',
    10800 => '3 Horas',
    18000 => '5 Horas',
    21600 => '6 Horas',
    43200 => '12 Horas',
    57600 => '16 Horas',
    64800 => '18 Horas',
    86400 => '24 Horas'
];
?>
<div class="domains-dns">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
        
        <div>
            <?= Html::a('<i class="fas fa-arrow-left"></i> Volver al Dominio', ['manage', 'id' => $model->id], ['class' => 'btn btn-outline']) ?>
        </div>
    </div>

    <?php if (!$zoneExists): ?>
        <div class="alert alert-error shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <div>
                    <h3 class="font-bold">¡La zona DNS no existe en el servidor!</h3>
                    <div class="text-sm">Ha fallado la lectura de la zona. Puede que este dominio no tenga plan de hosting asignado ni se haya inicializado como dominio parqueado.</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-base-100 shadow-xl rounded-box p-6 relative">
        <?php if (!$zoneExists): ?>
            <div class="absolute inset-0 bg-base-100 bg-opacity-70 z-10 flex items-center justify-center rounded-box backdrop-blur-sm">
                <div class="text-center">
                    <i class="fas fa-lock text-4xl text-base-content/30 mb-2"></i>
                    <p class="font-bold text-base-content/60">Gestor Bloqueado</p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php $form = ActiveForm::begin([
            'action' => ['dns-save', 'id' => $model->id],
            'method' => 'post',
            'id' => 'dns-form'
        ]); ?>

        <div class="overflow-x-auto mb-4" style="overflow: visible;">
            <table class="table table-compact w-full" id="dns-table">
                <thead>
                    <tr>
                        <th style="min-width: 150px;">Host (@ para raíz)</th>
                        <th style="min-width: 120px;">Tipo de Registro</th>
                        <th style="min-width: 200px;">Valor / Destino</th>
                        <th style="min-width: 100px;">Prioridad MX</th>
                        <th style="min-width: 180px;">TTL</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr class="dns-row">
                            <td><input type="text" name="DnsRecords[0][host]" class="input input-bordered input-sm w-full" value="@" required></td>
                            <td>
                                <select name="DnsRecords[0][record_type]" class="select select-bordered select-sm w-full">
                                    <?php foreach($recordTypes as $t): ?>
                                        <option value="<?= $t ?>"><?= $t ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="DnsRecords[0][address]" class="input input-bordered input-sm w-full" required></td>
                            <td><input type="number" name="DnsRecords[0][mx_pref]" class="input input-bordered input-sm w-full" value="10" style="display: none;"></td>
                            <td>
                                <select class="select select-bordered select-sm w-full ttl-preset mb-1" onchange="toggleCustomTtl(this, 0)">
                                    <?php foreach($ttlPresets as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= $val == 1800 ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                    <option value="custom">Personalizado</option>
                                </select>
                                <input type="number" name="DnsRecords[0][ttl]" id="ttl-input-0" class="input input-bordered input-sm w-full ttl-input" value="1800" style="display: none;" oninput="validateTtl(this)">
                            </td>
                            <td><button type="button" class="btn btn-error btn-xs remove-row">Eliminar</button></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $index => $record): 
                            $currentTtl = $record['TTL'] ?? 1800;
                            $isCustomTtl = !array_key_exists($currentTtl, $ttlPresets);
                        ?>
                            <tr class="dns-row">
                                <td><input type="text" name="DnsRecords[<?= $index ?>][host]" class="input input-bordered input-sm w-full" value="<?= Html::encode($record['Name'] ?? '') ?>" required></td>
                                <td>
                                    <select name="DnsRecords[<?= $index ?>][record_type]" class="select select-bordered select-sm w-full">
                                        <?php 
                                        $currentType = $record['Type'] ?? '';
                                        $mergedTypes = $recordTypes;
                                        if ($currentType && !in_array($currentType, $mergedTypes)) {
                                            $mergedTypes[] = $currentType;
                                        }
                                        foreach($mergedTypes as $t): ?>
                                            <option value="<?= $t ?>" <?= $currentType === $t ? 'selected' : '' ?>><?= $t ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="DnsRecords[<?= $index ?>][address]" class="input input-bordered input-sm w-full" value="<?= Html::encode($record['Address'] ?? '') ?>" required></td>
                                <td><input type="number" name="DnsRecords[<?= $index ?>][mx_pref]" class="input input-bordered input-sm w-full" value="<?= Html::encode($record['MXPref'] ?? '') ?>" <?= ($record['Type'] ?? '') === 'MX' ? '' : 'style="display: none;"' ?>></td>
                                <td>
                                    <select class="select select-bordered select-sm w-full ttl-preset mb-1" onchange="toggleCustomTtl(this, <?= $index ?>)">
                                        <?php foreach($ttlPresets as $val => $label): ?>
                                            <option value="<?= $val ?>" <?= (!$isCustomTtl && $val == $currentTtl) ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                        <option value="custom" <?= $isCustomTtl ? 'selected' : '' ?>>Personalizado</option>
                                    </select>
                                    <input type="number" name="DnsRecords[<?= $index ?>][ttl]" id="ttl-input-<?= $index ?>" class="input input-bordered input-sm w-full ttl-input" value="<?= Html::encode($currentTtl) ?>" style="<?= $isCustomTtl ? '' : 'display: none;' ?>" oninput="validateTtl(this)">
                                </td>
                                <td><button type="button" class="btn btn-error btn-xs remove-row">Eliminar</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex justify-between mt-4">
            <button type="button" class="btn btn-outline btn-primary btn-sm" id="add-row">
                <i class="fas fa-plus mr-1"></i> Añadir Registro
            </button>
            <?= Html::submitButton('<i class="fas fa-save mr-1"></i> Guardar Cambios', ['class' => 'btn btn-primary text-white']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$recordTypesOptions = '';
foreach($recordTypes as $t) {
    $recordTypesOptions .= "<option value=\"{$t}\">{$t}</option>";
}
$ttlOptions = '';
foreach($ttlPresets as $val => $label) {
    $ttlOptions .= "<option value=\"{$val}\" " . ($val == 1800 ? 'selected' : '') . ">{$label}</option>";
}
$ttlOptions .= '<option value="custom">Personalizado</option>';

$script = <<< JS
let rowCount = document.querySelectorAll('.dns-row').length;

// Globally accessible function for TTL logic
window.toggleCustomTtl = function(selectElem, index) {
    let inputElem = document.getElementById('ttl-input-' + index);
    if (selectElem.value === 'custom') {
        inputElem.style.display = 'block';
        // Set an empty or minimum value when switched to custom
        if (parseInt(inputElem.value) < 86400) {
            inputElem.value = 86400;
        }
    } else {
        inputElem.style.display = 'none';
        inputElem.value = selectElem.value;
        // Remove error classes since preset is chosen
        inputElem.classList.remove('input-error');
    }
};

window.validateTtl = function(inputElem) {
    // Only allow numbers
    inputElem.value = inputElem.value.replace(/[^0-9]/g, '');
    
    // Highlight if under 86400
    if (inputElem.value !== '' && parseInt(inputElem.value) < 86400) {
        inputElem.classList.add('input-error');
    } else {
        inputElem.classList.remove('input-error');
    }
};

document.getElementById('add-row').addEventListener('click', function() {
    let tbody = document.querySelector('#dns-table tbody');
    let html = `
        <tr class="dns-row">
            <td><input type="text" name="DnsRecords[\${rowCount}][host]" class="input input-bordered input-sm w-full" value="" required></td>
            <td>
                <select name="DnsRecords[\${rowCount}][record_type]" class="select select-bordered select-sm w-full">
                    {$recordTypesOptions}
                </select>
            </td>
            <td><input type="text" name="DnsRecords[\${rowCount}][address]" class="input input-bordered input-sm w-full" required></td>
            <td><input type="number" name="DnsRecords[\${rowCount}][mx_pref]" class="input input-bordered input-sm w-full" value="10" style="display: none;"></td>
            <td>
                <select class="select select-bordered select-sm w-full ttl-preset mb-1" onchange="toggleCustomTtl(this, \${rowCount})">
                    {$ttlOptions}
                </select>
                <input type="number" name="DnsRecords[\${rowCount}][ttl]" id="ttl-input-\${rowCount}" class="input input-bordered input-sm w-full ttl-input" value="1800" style="display: none;" oninput="validateTtl(this)">
            </td>
            <td><button type="button" class="btn btn-error btn-xs remove-row">Eliminar</button></td>
        </tr>
    `;
    tbody.insertAdjacentHTML('beforeend', html);
    rowCount++;
});

document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('remove-row')) {
        if (confirm('¿Deseas quitar este registro? Recuerda que el cambio solo será efectivo en el servidor cuando hagas clic en "Guardar Cambios".')) {
            e.target.closest('tr').remove();
        }
    }
});

document.addEventListener('change', function(e) {
    if (e.target && e.target.name && e.target.name.includes('[record_type]')) {
        let mxInput = e.target.closest('tr').querySelector('input[name$="[mx_pref]"]');
        if (mxInput) {
            mxInput.style.display = e.target.value === 'MX' ? 'block' : 'none';
        }
    }
});

// Sync any existing form submissions on submit
document.getElementById('dns-form').addEventListener('submit', function(e) {
    let hasError = false;
    let recordsSet = new Set();
    
    // Check TTL values
    document.querySelectorAll('.ttl-input').forEach(function(input) {
        let select = input.closest('td').querySelector('.ttl-preset');
        if (select && select.value === 'custom') {
            if (input.value === '' || parseInt(input.value) < 86400) {
                input.classList.add('input-error');
                hasError = true;
            }
        }
    });
    
    if (hasError) {
        e.preventDefault();
        alert('Por favor, corrige los valores de TTL personalizados. Deben ser mayores o iguales a 86400.');
        return;
    }

    // Check for duplicates
    let rows = document.querySelectorAll('.dns-row');
    for (let i = 0; i < rows.length; i++) {
        let host = rows[i].querySelector('input[name$="[host]"]').value.trim().toLowerCase();
        let type = rows[i].querySelector('select[name$="[record_type]"]').value;
        let address = rows[i].querySelector('input[name$="[address]"]').value.trim().toLowerCase();
        
        let uniqueKey = host + '|' + type + '|' + address;
        if (type === 'MX') {
            let mx = rows[i].querySelector('input[name$="[mx_pref]"]').value.trim();
            uniqueKey += '|' + mx;
        }

        if (recordsSet.has(uniqueKey)) {
            e.preventDefault();
            alert('Error: Tienes registros duplicados idénticos (Host: ' + host + ', Tipo: ' + type + ', Valor: ' + address + '). Por favor, elimina los duplicados antes de guardar.');
            return;
        }
        recordsSet.add(uniqueKey);
    }
});
JS;
$this->registerJs($script, \yii\web\View::POS_END);
?>
