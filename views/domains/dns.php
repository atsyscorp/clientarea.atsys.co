<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Gestionar DNS: ' . $model->domain;
$this->params['breadcrumbs'][] = ['label' => 'Dominios', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->domain, 'url' => ['manage', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'DNS';

$recordTypes = ['A', 'AAAA', 'CNAME', 'MX', 'MXE', 'TXT', 'URL', 'URL301', 'FRAME'];
?>
<div class="domains-dns">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
        
        <div>
            <?= Html::a('Volver al Dominio', ['manage', 'id' => $model->id], ['class' => 'btn btn-outline']) ?>
            <?= Html::a('Sincronizar Registros', ['dns-sync', 'id' => $model->id], [
                'class' => 'btn btn-secondary text-white',
                'data' => [
                    'confirm' => 'Esto sobreescribirá los registros locales con los que están actualmente en el proveedor. ¿Continuar?',
                    'method' => 'post',
                ],
            ]) ?>
        </div>
    </div>

    <div class="bg-base-100 shadow-xl rounded-box p-6">
        
        <?php $form = ActiveForm::begin([
            'action' => ['dns-save', 'id' => $model->id],
            'method' => 'post',
            'id' => 'dns-form'
        ]); ?>

        <div class="overflow-x-auto mb-4">
            <table class="table table-compact w-full" id="dns-table">
                <thead>
                    <tr>
                        <th>Host (@ para raíz)</th>
                        <th>Tipo de Registro</th>
                        <th>Valor / Destino</th>
                        <th>Prioridad MX</th>
                        <th>TTL</th>
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
                            <td><input type="number" name="DnsRecords[0][mx_pref]" class="input input-bordered input-sm w-full" value="10"></td>
                            <td><input type="number" name="DnsRecords[0][ttl]" class="input input-bordered input-sm w-full" value="1800"></td>
                            <td><button type="button" class="btn btn-error btn-xs remove-row">Eliminar</button></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $index => $record): ?>
                            <tr class="dns-row">
                                <td><input type="text" name="DnsRecords[<?= $index ?>][host]" class="input input-bordered input-sm w-full" value="<?= Html::encode($record->host) ?>" required></td>
                                <td>
                                    <select name="DnsRecords[<?= $index ?>][record_type]" class="select select-bordered select-sm w-full">
                                        <?php foreach($recordTypes as $t): ?>
                                            <option value="<?= $t ?>" <?= $record->record_type === $t ? 'selected' : '' ?>><?= $t ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="DnsRecords[<?= $index ?>][address]" class="input input-bordered input-sm w-full" value="<?= Html::encode($record->address) ?>" required></td>
                                <td><input type="number" name="DnsRecords[<?= $index ?>][mx_pref]" class="input input-bordered input-sm w-full" value="<?= Html::encode($record->mx_pref) ?>"></td>
                                <td><input type="number" name="DnsRecords[<?= $index ?>][ttl]" class="input input-bordered input-sm w-full" value="<?= Html::encode($record->ttl) ?>"></td>
                                <td><button type="button" class="btn btn-error btn-xs remove-row">Eliminar</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex justify-between mt-4">
            <button type="button" class="btn btn-accent btn-sm text-white" id="add-row">Añadir Registro</button>
            <?= Html::submitButton('Guardar Cambios', ['class' => 'btn btn-primary text-white']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$recordTypesOptions = '';
foreach($recordTypes as $t) {
    $recordTypesOptions .= "<option value=\"{$t}\">{$t}</option>";
}
$script = <<< JS
let rowCount = $('.dns-row').length;

$('#add-row').click(function() {
    let html = `
        <tr class="dns-row">
            <td><input type="text" name="DnsRecords[\${rowCount}][host]" class="input input-bordered input-sm w-full" value="" required></td>
            <td>
                <select name="DnsRecords[\${rowCount}][record_type]" class="select select-bordered select-sm w-full">
                    {$recordTypesOptions}
                </select>
            </td>
            <td><input type="text" name="DnsRecords[\${rowCount}][address]" class="input input-bordered input-sm w-full" required></td>
            <td><input type="number" name="DnsRecords[\${rowCount}][mx_pref]" class="input input-bordered input-sm w-full" value="10"></td>
            <td><input type="number" name="DnsRecords[\${rowCount}][ttl]" class="input input-bordered input-sm w-full" value="1800"></td>
            <td><button type="button" class="btn btn-error btn-xs remove-row">Eliminar</button></td>
        </tr>
    `;
    $('#dns-table tbody').append(html);
    rowCount++;
});

$(document).on('click', '.remove-row', function() {
    $(this).closest('tr').remove();
});
JS;
$this->registerJs($script);
?>
