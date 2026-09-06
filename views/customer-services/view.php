<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\CustomerServices $model */

$this->title = 'Detalle de Hosting: ' . $model->domain;
$this->params['breadcrumbs'][] = ['label' => 'Servicios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->domain;

$product = $model->product;
$server = $model->server ?? ($product ? $product->server : null);
?>
<div class="customer-services-view fade-in">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-3xl font-bold text-base-content"><?= Html::encode($model->domain) ?></h1>
                <?= $model->getStatusHtml() ?>
            </div>
            <p class="text-base-content/60 mt-1">
                Plan: <span class="font-semibold text-primary"><?= Html::encode($product->name) ?></span>
                <?php if ($server): ?>
                    | Servidor: <span class="font-semibold"><?= Html::encode($server->name) ?> (<?= Html::encode($server->type) ?>)</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <?= $this->render('_add_to_calendar', ['model' => $model, 'btnClass' => 'btn-primary btn-sm']) ?>
            <?= Html::a('← Volver', ['index'], ['class' => 'btn btn-ghost btn-sm']) ?>
            <?= Html::a('Soporte', ['/tickets/create', 'service_id' => $model->id, 'subject' => 'Consulta sobre: ' . ($model->domain ?? $model->product->name)], ['class' => 'btn btn-outline btn-sm']) ?>
        </div>
    </div>
    <!-- Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Server credentials card -->
        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-6 flex flex-col justify-between">
                <div>
                    <h2 class="card-title text-lg border-b border-base-200 pb-3 mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
                        Credenciales del Servicio
                    </h2>

                    <div class="space-y-4">
                        <div class="form-control w-full">
                            <label class="label p-1"><span class="label-text text-xs opacity-60">Dominio / URL</span></label>
                            <div class="flex items-center gap-2 justify-between bg-base-200 p-2 rounded-lg text-sm">
                                <span class="font-mono truncate select-all"><?= Html::encode($model->domain) ?></span>
                                <a href="http://<?= Html::encode($model->domain) ?>" target="_blank" class="btn btn-ghost btn-square btn-xs text-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                </a>
                            </div>
                        </div>

                        <?php if ($model->username_service): ?>
                        <div class="form-control w-full">
                            <label class="label p-1"><span class="label-text text-xs opacity-60">Usuario del Panel</span></label>
                            <div class="join w-full">
                                <input type="text" value="<?= Html::encode($model->username_service) ?>" class="input input-bordered input-sm join-item w-full bg-base-200 font-mono text-sm" readonly id="usr-inp" />
                                <button class="btn btn-square btn-sm join-item" onclick="navigator.clipboard.writeText(document.getElementById('usr-inp').value)" type="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($model->password_service): ?>
                        <div class="form-control w-full">
                            <label class="label p-1"><span class="label-text text-xs opacity-60">Contraseña del Panel</span></label>
                            <div class="join w-full">
                                <input type="password" value="<?= Html::encode($model->password_service) ?>" class="input input-bordered input-sm join-item w-full bg-base-200 font-mono text-sm" readonly id="pwd-inp" />
                                <button class="btn btn-square btn-sm join-item text-base-content/60" onclick="togglePasswordVisibility()" type="button">
                                    <svg id="pwd-eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </button>
                                <button class="btn btn-square btn-sm join-item" onclick="navigator.clipboard.writeText(document.getElementById('pwd-inp').value)" type="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="divider my-2"></div>

                        <div class="flex justify-between text-xs opacity-60">
                            <span>Próxima Renovación:</span>
                            <span class="font-semibold font-mono"><?= Yii::$app->formatter->asDate($model->next_due_date, 'long') ?></span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid <?= ($server && $server->hostname) ? 'grid-cols-2' : 'grid-cols-1' ?> gap-3">
                    <a href="http://<?= Html::encode($model->domain) ?>" target="_blank" class="btn btn-outline btn-sm shadow-sm gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-.778.099-1.533.284-2.253" /></svg>
                        Sitio Web
                    </a>
                    <?php if ($server && $server->hostname): ?>
                        <?php
                        $panelPort = '10000';
                        switch ($server->type) {
                            case 'cyberpanel':
                                $panelPort = '8090';
                                break;
                            case 'virtualmin':
                                $panelPort = '10000';
                                break;
                            case 'cpanel':
                                $panelPort = '2083';
                                break;
                            case 'plesk':
                                $panelPort = '8443';
                                break;
                        }
                        ?>
                        <a href="https://<?= Html::encode($server->hostname) ?>:<?= $panelPort ?>" target="_blank" class="btn btn-primary btn-sm shadow-md gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
                            Panel de Control
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function togglePasswordVisibility() {
    const input = document.getElementById('pwd-inp');
    const icon = document.getElementById('pwd-eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"; />';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />';
    }
}
</script>
