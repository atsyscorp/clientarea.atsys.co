<?php
$file = 'views/tickets/view.php';
$content = file_get_contents($file);

// Replace the start of the Información block
$search = '<div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-5">
                <h3 class="card-title text-lg mb-4">Información</h3>';

$replace = '<div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="card-title text-lg mb-0">Información</h3>
                    <?php if ($isAdmin): ?>
                        <button type="button" onclick="document.getElementById(\'edit_ticket_modal\').showModal()" class="btn btn-ghost btn-xs text-primary gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" /></svg>
                            Editar
                        </button>
                    <?php endif; ?>
                </div>';

if (strpos($content, 'edit_ticket_modal') === false) {
    $content = str_replace($search, $replace, $content);
    
    // Add the modal at the end of the file
    $modalHtml = <<<'HTML'

<?php if ($isAdmin): ?>
    <?php
    $allCustomers = \yii\helpers\ArrayHelper::map(
        \app\models\Customers::find()->where(['status' => 'active'])->orderBy('business_name')->all(),
        'id',
        'business_name'
    );
    $allCustomers[9999] = 'No es cliente / Externo';
    ?>
    <dialog id="edit_ticket_modal" class="modal">
        <div class="modal-box max-w-md">
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>
            <h3 class="font-bold text-lg mb-4 text-primary">Editar Ticket</h3>

            <?php $editForm = ActiveForm::begin([
                'action' => ['edit', 'id' => $model->id],
                'method' => 'post',
                'options' => ['class' => 'space-y-4']
            ]); ?>

            <div class="form-control">
                <label class="label pb-1">
                    <span class="label-text text-sm font-bold">Cliente</span>
                </label>
                <?= Html::activeDropDownList($model, 'customer_id', $allCustomers, [
                    'class' => 'select select-bordered select-sm w-full focus:select-primary',
                    'prompt' => 'Seleccione el cliente...'
                ]) ?>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label pb-1">
                        <span class="label-text text-sm font-bold">Prioridad</span>
                    </label>
                    <?= Html::activeDropDownList($model, 'priority', [
                        \app\models\Tickets::PRIORITY_LOW => 'Baja',
                        \app\models\Tickets::PRIORITY_MEDIUM => 'Media',
                        \app\models\Tickets::PRIORITY_HIGH => 'Alta',
                        \app\models\Tickets::PRIORITY_CRITICAL => 'Urgente',
                    ], [
                        'class' => 'select select-bordered select-sm w-full focus:select-primary'
                    ]) ?>
                </div>

                <div class="form-control">
                    <label class="label pb-1">
                        <span class="label-text text-sm font-bold">Fuente</span>
                    </label>
                    <?= Html::activeDropDownList($model, 'source', [
                        \app\models\Tickets::SOURCE_WEB => 'Web',
                        \app\models\Tickets::SOURCE_EMAIL => 'Email',
                        \app\models\Tickets::SOURCE_WHATSAPP => 'WhatsApp',
                    ], [
                        'class' => 'select select-bordered select-sm w-full focus:select-primary'
                    ]) ?>
                </div>
            </div>

            <div class="form-control">
                <label class="label pb-1">
                    <span class="label-text text-sm font-bold">Departamento</span>
                </label>
                <?= Html::activeDropDownList($model, 'department', \app\models\Tickets::getDepartmentList(), [
                    'class' => 'select select-bordered select-sm w-full focus:select-primary'
                ]) ?>
            </div>
            
            <div class="alert alert-info shadow-sm text-xs p-3 mt-4 flex gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Estos cambios se guardarán de forma silenciosa sin enviar notificaciones al cliente.</span>
            </div>

            <div class="modal-action mt-6">
                <button type="button" onclick="document.getElementById('edit_ticket_modal').close()" class="btn btn-ghost btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm gap-2 shadow-md">
                    Guardar Cambios
                </button>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
        <form method="dialog" class="modal-backdrop"><button>cerrar</button></form>
    </dialog>
<?php endif; ?>
HTML;

    $content = str_replace('<?php if ($isAdmin && !$model->merged_into_id): ?>', $modalHtml . "\n\n<?php if (\$isAdmin && !\$model->merged_into_id): ?>", $content);

    file_put_contents($file, $content);
    echo "views/tickets/view.php patched.\n";
} else {
    echo "Already patched.\n";
}
