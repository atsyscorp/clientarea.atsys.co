<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\HtmlPurifier;

/* @var $this yii\web\View */
/* @var $model app\models\WorkOrders */

function formatMessage($text, $dark = false)
{

    if (strpos($text, '<p') === false && strpos($text, '<div') === false && strpos($text, '<br') === false) {
        $text = nl2br($text);
    }

    // 1. Configuramos Purifier para convertir URLs en enlaces y párrafos
    $config = function ($conf) {
        $conf->set('HTML.TargetBlank', true);
        $conf->set('AutoFormat.Linkify', true);
        $conf->set('HTML.Allowed', 'p,b,strong,i,em,u,ul,ol,li,table,thead,tbody,th,td,img[src|alt|width|height],br,span[style],div,h1,h2,h3,h4,h5,h6,a[href|target]');
    };

    // 2. Limpiamos el HTML (Aquí ya es seguro)
    //$cleanHtml = HtmlPurifier::process($text, $config);
    $cleanHtml = HtmlPurifier::process($text);

    // 3. Definimos tus clases (DaisyUI / Tailwind)
    $cssClass = $dark ? 'link link-white underline' : 'link link-primary underline';

    // 4. INYECCIÓN: Reemplazamos <a por <a class="..."
    // Como el HTML ya está purificado, es seguro manipularlo
    return str_replace('<a ', '<a class="' . $cssClass . '" ', $cleanHtml);

}

$this->title = $model->code . ' - ' . $model->title;
$isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
$newUpdate = new \app\models\WorkOrderUpdates();



if ($model->is_request == 1) {
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
                editor.on('change', function () {
                    editor.save();
                });
            }
        });
    });
    JS;
    $this->registerJs($js, \yii\web\View::POS_END);
} else {
    $isForeign = in_array($model->currency, ['USD', 'EUR']);
    $isUsd = $model->currency === 'USD';
    $isEur = $model->currency === 'EUR';
    $currencySuffix = ' ' . $model->currency;

    // Si es USD o EUR, tomamos el valor pre-calculado, de lo contrario el costo base
    $displayTotal = $isForeign ? ($model->total_cost_usd ?? round($model->total_cost / $model->exchange_rate, 2)) : $model->total_cost;
}
?>

<div class="max-w-4xl mx-auto my-8">

    <div class="flex justify-between items-center mb-6 no-print">
        <div>
            <?= Html::a('← Volver', ['index'], ['class' => 'btn btn-ghost btn-sm']) ?>
        </div>
        <div class="flex gap-2">

            <?= Html::a('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg> Descargar PDF', ['pdf', 'id' => $model->id], ['class' => 'btn btn-outline btn-sm', 'target' => '_blank']) ?>

            <?php if ($isAdmin): ?>
                <?= Html::a(
                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg> Enviar al Cliente',
                    ['send', 'id' => $model->id],
                    [
                        'class' => 'btn btn-primary btn-sm text-white',
                        'data' => ['confirm' => '¿Enviar esta orden por correo al cliente? Se adjuntará el PDF.', 'method' => 'post']
                    ]
                ) ?>
            <?php endif; ?>
        </div>
    </div>

    <div
        class="bg-base-100 shadow-2xl rounded-xl p-8 md:p-12 border border-base-200 print:shadow-none print:border-none">

        <div class="flex flex-col md:flex-row justify-between items-start border-b border-base-300 pb-8 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-primary tracking-tight">
                    <?= ($model->is_request == 1) ? 'SOLICITUD DE ' : '' ?>ORDEN DE TRABAJO
                </h1>
                <div class="text-sm opacity-60 mt-1 uppercase tracking-widest">Requerimientos de Desarrollo</div>
            </div>
            <div class="text-right mt-4 md:mt-0">
                <div class="text-2xl font-mono font-bold"><?= $model->code ?></div>
                <div class="mt-2 flex items-center justify-end gap-1">
                    <?= $model->getStatusHtml() ?>
                    <?php if ($model->has_service_contract): ?>
                        <span class="badge badge-info text-white font-bold ml-1">Contrato de Servicios</span>
                    <?php endif; ?>
                </div>
                <div class="text-sm opacity-60 mt-1">Fecha: <?= Yii::$app->formatter->asDate($model->created_at) ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
            <div>
                <h3 class="text-xs font-bold uppercase opacity-50 mb-2">Cliente</h3>
                <div class="font-bold text-lg"><?= Html::encode($model->customer->name ?? 'Cliente') ?></div>
                <div class="text-sm opacity-70"><?= Html::encode($model->customer->email ?? '') ?></div>
                <div class="text-sm opacity-70"><?= Html::encode($model->customer->document_number ?? '') ?></div>
            </div>
            <?php if ($model->is_request == 0) { ?>
                <div class="md:text-right">
                    <h3 class="text-xs font-bold uppercase opacity-50 mb-2">Proveedor</h3>
                    <div class="font-bold text-lg">Arkitech Systems SAS</div>
                    <div class="text-sm opacity-70">ATSYS - Desarrollo de Software</div>
                    <div class="text-sm opacity-70">Trascendemos</div>
                </div>
            <?php } ?>
        </div>

        <div class="mb-8">
            <h2 class="text-xl font-bold mb-2">Proyecto: <?= Html::encode($model->title) ?></h2>
        </div>

        <div class="bg-base-200/30 rounded-lg p-6 mb-8 border border-base-200">
            <h3 class="font-bold text-sm uppercase opacity-50 mb-4 border-b border-base-300 pb-2">Alcance y
                Requerimientos</h3>

            <div class="prose max-w-none text-justify">
                <?= formatMessage($model->requirements) ?>
            </div>
        </div>

        <?php if (!empty($model->attachment_url)): ?>
            <div class="bg-base-100 p-4 rounded-lg border border-primary/20 flex items-center justify-between shadow-sm mb-8 no-print">
                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-primary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <div>
                        <span class="font-bold text-sm block">Archivo adjunto de requerimientos</span>
                        <span class="text-xs opacity-60">Almacenado de forma segura en la nube</span>
                    </div>
                </div>
                <a href="<?= Html::encode($model->attachment_url) ?>" target="_blank" class="btn btn-primary btn-sm text-white gap-2">
                    Abrir en nueva ventana ↗
                </a>
            </div>
        <?php endif; ?>

        <?php
        if ($model->is_request == 1) {

            if ($isAdmin):

                ?>
                <?php $form = ActiveForm::begin([
                    'action' => ['work-orders/approve-request', 'id' => $model->id]
                ]); ?>
                <div class="bg-base-200/30 rounded-lg p-6 mb-8 border border-base-200">
                    <h3 class="font-bold text-sm uppercase opacity-50 mb-4 border-b border-base-300 pb-2">Definición de Alcance
                        y Cotización</h3>

                    <div class="prose max-w-none text-justify mb-6">
                        <?= $form->field($model, 'requirements', ['template' => '{input}{error}'])
                            ->textarea([
                                'id' => 'workorders-requirements', // Importante para el TinyMCE
                                'rows' => 10,
                                'class' => 'textarea textarea-bordered w-full h-64 font-mono text-sm leading-relaxed',
                                'placeholder' => "1. Desarrollo de Login...\n2. Panel administrativo...\n3. Integración con pasarela...",
                                'value' => ''
                            ]) ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 border-t border-base-300 pt-6">
                        <div class="flex items-center">
                            <label class="label cursor-pointer justify-start gap-4">
                                <?= $form->field($model, 'has_service_contract')->checkbox(['class' => 'checkbox checkbox-primary'], false)->label(false) ?>
                                <span class="label-text font-bold">Incluye Contrato de Servicio (Evita vencimiento)</span>
                            </label>
                        </div>
                        <div class="flex flex-col items-end">
                            <div class="w-full md:w-2/3">
                                <label class="label">
                                    <span class="label-text font-bold text-lg">Inversión Total a Cotizar</span>
                                </label>
                                <div class="join w-full">
                                    <span class="join-item btn btn-active pointer-events-none">$</span>
                                    <?= $form->field($model, 'total_cost', [
                                        'template' => '{input}',
                                        'options' => ['tag' => false]
                                    ])->textInput([
                                                'type' => 'number',
                                                'step' => '0.01',
                                                'class' => 'input input-bordered join-item w-full text-lg font-bold text-primary',
                                                'placeholder' => '0.00'
                                            ]) ?>
                                </div>
                                <?= Html::error($model, 'total_cost', ['class' => 'text-error text-sm mt-1']) ?>
                            </div>
                        </div>
                    </div>

                    <div class="border-t-2 border-dashed border-base-300 pt-8 mt-8 text-center no-print">
                        <h3 class="text-lg font-bold mb-4">¿Aprobar solicitud?</h3>
                        <p class="text-sm mb-6 max-w-2xl mx-auto opacity-70">
                            Al aprobar, esta solicitud pasará a ser una Orden de Trabajo oficial con el prefijo OT-. El cliente
                            recibirá un correo con el PDF adjunto detallando los requerimientos y la inversión asignada.
                        </p>

                        <div class="flex justify-center gap-4">
                            <?= Html::submitButton('✓ Aprobar y Enviar Cotización', [
                                'class' => 'btn btn-primary text-white px-8',
                                'data' => ['confirm' => '¿Confirmas la creación de la orden y el envío del correo al cliente con el costo estipulado?']
                            ]) ?>

                            <?= Html::a('✕ Rechazar', ['reject-request', 'id' => $model->id], [
                                'class' => 'btn btn-outline btn-error px-6',
                                'data' => ['confirm' => '¿Deseas rechazar esta solicitud? Se eliminará de inmediato.', 'method' => 'post']
                            ]) ?>
                        </div>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
                <?php

            else:

                ?>
                <div class="border-t-2 border-dashed border-base-300 pt-8 mt-8 text-center no-print">
                    <p class="text-sm mb-6 max-w-2xl mx-auto opacity-70">
                        Esta orden se encuentra bajo revisión o está por ser revisada por el equipo de trabajo de ATSYS.
                        Recibirás un email con la órden de trabajo lista con la propuesta para que la puedas aprobar.
                    </p>
                </div>
                <?php

            endif;

        } else {

            ?>

            <?php if (!empty($model->original_request)): ?>
                <div class="mb-8 text-sm italic opacity-70">
                    <strong>Solicitud original:</strong> <?= formatMessage($model->original_request) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($model->notes)): ?>
                <div class="mb-8 text-sm italic opacity-70">
                    <strong>Notas:</strong> <?= Html::encode($model->notes) ?>
                </div>
            <?php endif; ?>

            <div class="flex justify-end mb-12">
                <div class="w-full bg-base-200 p-4 rounded-lg">
                    <div class="flex justify-between items-center text-lg font-bold">
                        <span>Inversión:</span>
                        <span class="text-primary">
                            <?= Yii::$app->formatter->asCurrency($displayTotal) . $currencySuffix ?>
                            <?php if ($isForeign && !empty($model->exchange_rate)): ?>
                                <dt style="font-size:12px; color:#000; font-weight:normal; line-height:12px;">Tasa de cambio
                                    pactada (TRM)
                                    : <?= Yii::$app->formatter->asCurrency($model->exchange_rate) ?>
                                    COP
                                </dt>
                                <dt style="font-size:12px; color:#000; font-weight:normal; line-height:12px; color:red;">Este
                                    valor no incluye comisión adicional por pasarela PayPal</dt>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (!$isAdmin && $model->status == \app\models\WorkOrders::STATUS_PENDING): ?>
                <div class="border-t-2 border-dashed border-base-300 pt-8 mt-8 text-center no-print">
                    <h3 class="text-lg font-bold mb-4">Aprobación del Cliente</h3>
                    <p class="text-sm mb-6 max-w-2xl mx-auto opacity-70">
                        Al aprobar esta orden de trabajo, confirmas que los requerimientos descritos arriba son correctos y
                        autorizas el inicio del desarrollo bajo los costos estipulados.
                    </p>

                    <div class="flex justify-center gap-4 flex-wrap">
                        <?= Html::a('📄 Ver Propuesta (PDF)', ['pdf', 'id' => $model->id], [
                            'class' => 'btn btn-outline btn-secondary px-6',
                            'target' => '_blank'
                        ]) ?>

                        <?= Html::a('✓ Aprobar e Iniciar', ['approve', 'id' => $model->id], [
                            'class' => 'btn btn-primary text-white px-8',
                            'data' => ['confirm' => '¿Estás seguro de aprobar esta orden? Esto autoriza el inicio del trabajo.', 'method' => 'post']
                        ]) ?>

                        <?= Html::a('✕ Rechazar / Solicitar Cambios', ['reject', 'id' => $model->id], [
                            'class' => 'btn btn-outline btn-error px-6',
                            'data' => ['confirm' => '¿Deseas rechazar esta orden?', 'method' => 'post']
                        ]) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($isAdmin && $model->status == \app\models\WorkOrders::STATUS_PENDING): ?>
                <div class="bg-base-200 p-3 rounded-xl">
                    <div class="text-sm italic">
                        El cliente aún no ha aprobado esta orden, vuelve cuando hayas recibido una notificación.
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($model->status == \app\models\WorkOrders::STATUS_APPROVED): ?>
                <div
                    class="mt-8 text-center border-2 border-success border-dashed p-4 rounded-xl opacity-80 rotate-1 max-w-xs mx-auto">
                    <div class="text-success font-bold text-xl uppercase">APROBADO DIGITALMENTE</div>
                    <div class="text-xs text-success">Fecha: <?= Yii::$app->formatter->asDatetime($model->updated_at) ?></div>
                </div>
                <?php if ($isAdmin): ?>
                    <div class="mb-4">
                        <?php if ($model->down_payment_sent_at === null): ?>

                            <?php if (!$model->is_preapproved): ?>
                            <div class="flex gap-2 justify-center">
                                <?= \yii\helpers\Html::a('Generar Cobro 50%', ['generate-payment', 'id' => $model->id, 'percentage' => 50], [
                                    'class' => 'btn btn-primary gap-2',
                                    'data' => [
                                        'confirm' => '¿Generar cobro del 50% y enviar correo?',
                                        'method' => 'post',
                                    ]
                                ]) ?>
                                <?= \yii\helpers\Html::a('Generar Cobro 100%', ['generate-payment', 'id' => $model->id, 'percentage' => 100], [
                                    'class' => 'btn btn-secondary gap-2',
                                    'data' => [
                                        'confirm' => '¿Generar cobro del 100% y enviar correo?',
                                        'method' => 'post',
                                    ]
                                ]) ?>
                            </div>
                            <?php endif; ?>

                        <?php else: ?>

                            <div class="alert alert-success shadow-sm inline-flex w-auto py-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex flex-col text-left">
                                    <span class="font-bold text-sm">Cobro Solicitado</span>
                                    <span class="text-xs">Enviado el:
                                        <?= Yii::$app->formatter->asDatetime($model->down_payment_sent_at) ?></span>
                                </div>
                            </div>

                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        <?php } ?>

    </div>

    <?php if (in_array($model->status, [
        \app\models\WorkOrders::STATUS_APPROVED,
        \app\models\WorkOrders::STATUS_NOT_COMPLETED,
        \app\models\WorkOrders::STATUS_PARTIAL
    ])): ?>
        <div class="divider my-10">LÍNEA DE TIEMPO / AVANCES</div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2">
                <ul class="timeline timeline-snap-icon max-md:timeline-compact timeline-vertical">
                    <?php
                    // Obtenemos los avances
                    $query = \app\models\WorkOrderUpdates::find()->where(['work_order_id' => $model->id]);

                    // Si NO es admin, solo mostrar los visibles
                    if (!$isAdmin) {
                        $query->andWhere(['is_visible' => 1]);
                    }

                    $updates = $query->orderBy(['created_at' => SORT_DESC])->all();
                    ?>

                    <?php if (empty($updates)): ?>
                        <div class="text-center opacity-50 py-10">
                            <p>Aún no hay reportes de avance en este proyecto.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($updates as $index => $update): ?>
                            <li>
                                <div class="timeline-middle">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                        class="h-5 w-5 text-primary">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div
                                    class="timeline-<?= $index % 2 == 0 ? 'start' : 'end' ?> mb-10 bg-base-100 p-4 rounded-box shadow-sm border border-base-200 w-full">
                                    <time class="font-mono italic text-xs opacity-50 block mb-1">
                                        <?= Yii::$app->formatter->asDatetime($update->created_at) ?>
                                        <?php if ($isAdmin && !$update->is_visible): ?>
                                            <span class="badge badge-xs badge-ghost ml-2">Privado 🔒</span>
                                        <?php endif; ?>
                                    </time>
                                    <div class="text-sm text-justify">
                                        <?= nl2br(\yii\helpers\Html::encode($update->description)) ?>
                                    </div>

                                    <?php if ($update->allow_reply == 1): ?>
                                        <?php if (!empty($update->client_reply)): ?>
                                            <div class="bg-base-200 p-3 rounded-lg border-l-4 border-primary mt-4">
                                                <div class="text-xs font-bold text-primary mb-1">Respuesta del cliente:</div>
                                                <div class="text-sm italic text-justify">
                                                    <?= nl2br(\yii\helpers\Html::encode($update->client_reply)) ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <?php //if(!$isAdmin) { ?>
                                            <div class="bg-base-200 p-3 rounded-lg border-l-4 border-primary mt-4">
                                                <div class="text-xs font-bold text-primary mb-2">Este avance requiere tu respuesta:</div>
                                                <div class="text-sm text-justify">
                                                    <?= \yii\helpers\Html::beginForm(['work-orders/add-reply', 'id' => $model->id], 'post') ?>
                                                    <?= \yii\helpers\Html::hiddenInput('update_id', $update->id) ?>
                                                    <?= \yii\helpers\Html::textarea('reply', '', [
                                                        'class' => 'textarea textarea-bordered w-full',
                                                        'rows' => 2,
                                                        'placeholder' => 'Tu respuesta (solo podrás enviarla una vez)...',
                                                        'required' => true
                                                    ]) ?>
                                                    <?= \yii\helpers\Html::submitButton('Enviar Respuesta', [
                                                        'class' => 'btn btn-primary btn-sm mt-2',
                                                        'data' => ['confirm' => '¿Estás seguro de enviar esta respuesta? No podrás modificarla después.']
                                                    ]) ?>
                                                    <?= \yii\helpers\Html::endForm() ?>
                                                </div>
                                            </div>
                                            <?php //} ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <hr class="bg-primary" />
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if ($isAdmin && !in_array($model->status, [
                \app\models\WorkOrders::STATUS_COMPLETED,
                \app\models\WorkOrders::STATUS_NOT_COMPLETED,
                \app\models\WorkOrders::STATUS_PARTIAL
            ])): ?>
                <div>
                    <div class="card bg-base-200 shadow-inner">
                        <div class="card-body p-5">
                            <h3 class="font-bold text-lg mb-4">Registrar Avance</h3>

                            <?php $form = \yii\widgets\ActiveForm::begin(['action' => ['add-update', 'id' => $model->id]]); ?>

                            <?= $form->field($newUpdate, 'description')->textarea([
                                'rows' => 4,
                                'class' => 'textarea textarea-bordered w-full',
                                'placeholder' => 'Describe qué se trabajó hoy...'
                            ])->label(false) ?>

                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-4">
                                    <?= $form->field($newUpdate, 'is_visible')->checkbox(['class' => 'checkbox checkbox-sm checkbox-primary'], false)->label(false) ?>
                                    <span class="label-text">Visible para el Cliente</span>
                                </label>
                            </div>

                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-4">
                                    <?= $form->field($newUpdate, 'notify_email')->checkbox(['class' => 'checkbox checkbox-sm checkbox-secondary'], false)->label(false) ?>
                                    <span class="label-text">Notificar por Email</span>
                                </label>
                            </div>

                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-4">
                                    <?= $form->field($newUpdate, 'allow_reply')->checkbox([
                                        'class' => 'checkbox checkbox-sm checkbox-accent',
                                        'onclick' => 'if(this.checked === true) { if(document.getElementById("workorderupdates-is_visible").checked == false) { document.getElementById("workorderupdates-is_visible").checked = true; } if(document.getElementById("workorderupdates-notify_email").checked == false) { document.getElementById("workorderupdates-notify_email").checked = true; } alert("Para que el cliente pueda responder se activará automáticamente las opciones de notificar por email y hacer visible este avance para el cliente."); }'
                                    ], false)->label(false) ?>
                                    <span class="label-text font-bold">Solicitar respuesta / aclaración del cliente</span>
                                </label>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary btn-sm w-full">Publicar en Bitácora</button>
                            </div>

                            <?php \yii\widgets\ActiveForm::end(); ?>
                        </div>
                    </div>

                    <div class="alert alert-info shadow-sm mt-4 text-xs">
                        <span>💡 <strong>Tip:</strong> Usa "Visible" para logros completados. Usa notas privadas (sin check)
                            para tus propios recordatorios técnicos.</span>
                    </div>
                 <?php endif; ?>

        </div><!-- end grid -->
    <?php endif; ?><!-- end timeline block -->

    <?php if ($model->status === \app\models\WorkOrders::STATUS_APPROVED && $isAdmin): ?>
        <div class="card bg-base-100 shadow-xl border border-success/30 mt-8">
            <div class="card-body">
                <h3 class="card-title text-success flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Finalizar Orden de Trabajo
                </h3>
                <p class="text-base-content/70">
                    Si el trabajo técnico ha concluido, puedes cerrar esta orden. Esto cambiará el estado a "Finalizado".
                </p>

                <?= Html::beginForm(['close', 'id' => $model->id], 'post', ['class' => 'mt-4']) ?>

                <div class="form-control mb-4">
                    <label class="cursor-pointer label justify-start gap-4">
                        <input type="checkbox" name="notify_client" value="1" checked class="checkbox checkbox-success" />
                        <span class="label-text font-medium">Enviar notificación por correo al cliente ("Trabajo
                            Terminado")</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-success text-white w-full md:w-auto gap-2"
                    onclick="return confirm('¿Confirmas que el trabajo está terminado?');">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                    </svg>
                    Cerrar Orden y Finalizar
                </button>

                <?= Html::endForm() ?>
            </div>
        </div>

        <!-- Panel de Pausa por Inactividad del Cliente -->
        <div class="card bg-base-100 shadow-xl border border-warning/30 mt-6">
            <div class="card-body">
                <h3 class="card-title text-warning flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Pausar por Falta de Respuesta
                </h3>
                <p class="text-base-content/70 text-sm">
                    Usa esta opción cuando el cliente no ha respondido o no ha mostrado interés en continuar. La orden quedará bloqueada pero puede <strong>retomarse en cualquier momento</strong>.
                </p>

                <?= Html::beginForm(['pause', 'id' => $model->id], 'post', ['class' => 'mt-4 space-y-4']) ?>

                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Tipo de Cierre</span></label>
                    <select name="pause_type" class="select select-bordered select-warning w-full md:w-auto" required>
                        <option value="<?= \app\models\WorkOrders::STATUS_NOT_COMPLETED ?>">&#128994; No Finalizada &mdash; Sin respuesta del cliente</option>
                        <option value="<?= \app\models\WorkOrders::STATUS_PARTIAL ?>">&#128993; Parcialmente Finalizada &mdash; Avance parcial entregado</option>
                    </select>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Motivo interno del cierre <span class="opacity-50 font-normal">(solo lo ve el admin)</span></span></label>
                    <textarea name="pause_reason" class="textarea textarea-bordered textarea-warning w-full" rows="3"
                        placeholder="Ej: El cliente no respondió en 30 días después de la cotización."></textarea>
                </div>

                <div class="form-control">
                    <label class="cursor-pointer label justify-start gap-4">
                        <input type="checkbox" name="notify_client" value="1" class="checkbox checkbox-warning" />
                        <span class="label-text">Notificar al cliente que la orden fue pausada temporalmente</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-warning text-white gap-2"
                    onclick="return confirm('¿Confirmas que deseas pausar esta orden?');">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Pausar Orden
                </button>

                <?= Html::endForm() ?>
            </div>
        </div>
    <?php endif; ?>

    <?php
    $pausedStatuses = [\app\models\WorkOrders::STATUS_NOT_COMPLETED, \app\models\WorkOrders::STATUS_PARTIAL];
    if (in_array($model->status, $pausedStatuses)):
        $isPaused = true;
        $pauseLabel = $model->status === \app\models\WorkOrders::STATUS_NOT_COMPLETED
            ? 'No Finalizada'
            : 'Parcialmente Finalizada';
        $pauseColor = $model->status === \app\models\WorkOrders::STATUS_NOT_COMPLETED ? 'alert-error' : 'alert-warning';
    ?>
        <!-- Banner de orden pausada: visible para todos -->
        <div class="alert <?= $pauseColor ?> shadow-lg mt-8">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <h3 class="font-bold">Esta orden está pausada temporalmente</h3>
                <?php if ($isAdmin && !empty($model->pause_reason)): ?>
                    <div class="text-sm mt-1"><strong>Motivo:</strong> <?= \yii\helpers\Html::encode($model->pause_reason) ?></div>
                    <div class="text-xs opacity-70 mt-0.5">Pausada el: <?= Yii::$app->formatter->asDatetime($model->completed_at) ?></div>
                <?php else: ?>
                    <div class="text-sm mt-1">Esta orden fue pausada temporalmente. Contáctenos para retomarla.</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isAdmin): ?>
            <!-- Botón de reactivación solo para admin -->
            <div class="mt-4 flex justify-end">
                <?= Html::beginForm(['resume', 'id' => $model->id], 'post') ?>
                    <button type="submit" class="btn btn-primary gap-2"
                        onclick="return confirm('¿Confirmas que deseas retomar esta orden? Volverá al estado Aprobada.');">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z" />
                        </svg>
                        Retomar Orden de Trabajo
                    </button>
                <?= Html::endForm() ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>