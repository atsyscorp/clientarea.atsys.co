<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\SystemSettings[] $settings */

$this->title = 'Configuración del Sistema';
$this->params['breadcrumbs'][] = $this->title;

// Group settings by category
$groupedSettings = [];
foreach ($settings as $setting) {
    $groupedSettings[$setting->category][] = $setting;
}

$categories = [
    'tickets' => [
        'label' => 'Tickets',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" /></svg>'
    ],
    'paypal' => [
        'label' => 'PayPal',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 10.18 12 9.09 12 9.09m0-3.636c1.172-.879 3.07-.879 4.242 0M12 5.455v3.636m0-3.636a3.375 3.375 0 100 6.75h-.008" /></svg>'
    ],
    'wompi' => [
        'label' => 'Wompi',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75-3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5h15a2.25 2.25 0 002.25-2.25V15M9 9l.008-.008L9 9.008v-.008z" /></svg>'
    ],
    'whois' => [
        'label' => 'Whois',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-.778.099-1.533.284-2.253" /></svg>'
    ],
    'google_drive' => [
        'label' => 'Google Drive',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>'
    ]
];

?>

<div class="max-w-4xl mx-auto mt-6 mb-12 px-4">
    
    <div class="text-center md:text-left mb-8">
        <h1 class="text-3xl font-extrabold text-base-content tracking-tight"><?= Html::encode($this->title) ?></h1>
        <p class="text-sm text-base-content/60 mt-1">Administra credenciales, integraciones y parámetros generales del área de clientes.</p>
    </div>

    <?php $form = ActiveForm::begin([
        'id' => 'system-settings-form',
        'options' => ['class' => 'space-y-6'],
    ]); ?>

    <!-- Horizontal Tabs Group -->
    <div class="flex flex-wrap md:flex-nowrap gap-1.5 p-1.5 bg-base-200/80 rounded-2xl border border-base-200/50 mb-8 max-w-3xl mx-auto shadow-sm">
        <?php $first = true; foreach ($categories as $catKey => $catInfo): ?>
            <button type="button" 
                    id="btn-tab-<?= $catKey ?>" 
                    onclick="switchTab(event, '<?= $catKey ?>')" 
                    class="tab-btn w-full md:w-auto flex-1 py-3 px-4 rounded-xl flex items-center justify-center gap-2 font-semibold text-sm transition-all duration-200 <?= $first ? 'bg-primary text-primary-content shadow-sm font-bold' : 'hover:bg-base-100 text-base-content/70' ?>">
                <?= $catInfo['icon'] ?>
                <span><?= Html::encode($catInfo['label']) ?></span>
            </button>
        <?php $first = false; endforeach; ?>
    </div>

    <!-- Main Content Card -->
    <div class="card bg-base-100 shadow-xl border border-base-200">
        <div class="card-body p-6 md:p-8">
            
            <!-- Category Sections -->
            <?php $first = true; foreach ($categories as $catKey => $catInfo): ?>
                <div id="section-<?= $catKey ?>" class="settings-section <?= $first ? '' : 'hidden' ?> space-y-6">
                    <div class="flex items-center gap-3 border-b border-base-200 pb-4 mb-6">
                        <span class="p-2 bg-primary/10 rounded-xl text-primary">
                            <?= $catInfo['icon'] ?>
                        </span>
                        <div>
                            <h2 class="text-xl font-bold text-base-content"><?= Html::encode($catInfo['label']) ?></h2>
                            <p class="text-xs text-base-content/50">Ajustes relativos a esta categoría.</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <?php if (isset($groupedSettings[$catKey])): ?>
                            <?php foreach ($groupedSettings[$catKey] as $setting): ?>
                                <div class="form-control w-full">
                                    <div class="flex justify-between items-start mb-1">
                                        <label class="label-text font-bold text-base-content/80"><?= Html::encode($setting->label) ?></label>
                                        <span class="badge badge-outline badge-sm font-mono text-base-content/30 opacity-70"><?= Html::encode($setting->key) ?></span>
                                    </div>
                                    
                                    <?php if ($setting->type === 'password'): ?>
                                        <div class="relative flex items-center">
                                            <?= Html::input('password', "SystemSettings[{$setting->id}][value]", $setting->value, [
                                                'class' => 'input input-bordered w-full pr-12 font-mono',
                                                'id' => "input-{$setting->id}",
                                            ]) ?>
                                            <button type="button" class="absolute right-4 text-base-content/50 hover:text-base-content" onclick="togglePassword(<?= $setting->id ?>)">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 toggle-icon-<?= $setting->id ?>"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.43 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            </button>
                                        </div>
                                    <?php elseif ($setting->type === 'number'): ?>
                                        <?= Html::input('number', "SystemSettings[{$setting->id}][value]", $setting->value, [
                                            'class' => 'input input-bordered w-full font-mono',
                                        ]) ?>
                                    <?php else: ?>
                                        <?= Html::input('text', "SystemSettings[{$setting->id}][value]", $setting->value, [
                                            'class' => 'input input-bordered w-full font-mono',
                                        ]) ?>
                                    <?php endif; ?>

                                    <?php if ($setting->description): ?>
                                        <p class="text-xs text-base-content/50 mt-1.5 leading-relaxed"><?= Html::encode($setting->description) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm opacity-50">No hay configuraciones disponibles en esta categoría.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php $first = false; endforeach; ?>

            <!-- Action Button at the Bottom -->
            <div class="flex justify-end mt-8 border-t border-base-200 pt-6">
                <?= Html::submitButton('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-2 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg> Guardar Cambios', [
                    'class' => 'btn btn-primary text-white px-8 btn-submit shadow-md rounded-xl',
                ]) ?>
            </div>

        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
$js = <<<JS
function switchTab(e, key) {
    e.preventDefault();
    
    // Toggle active classes on tab buttons
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => {
        btn.classList.remove('bg-primary', 'text-primary-content', 'shadow-sm', 'font-bold');
        btn.classList.add('hover:bg-base-100', 'text-base-content/70');
    });
    
    const activeBtn = document.getElementById('btn-tab-' + key);
    if (activeBtn) {
        activeBtn.classList.remove('hover:bg-base-100', 'text-base-content/70');
        activeBtn.classList.add('bg-primary', 'text-primary-content', 'shadow-sm', 'font-bold');
    }

    // Toggle active sections
    const sections = document.querySelectorAll('.settings-section');
    sections.forEach(sec => {
        sec.classList.add('hidden');
    });
    
    const targetSection = document.getElementById('section-' + key);
    if (targetSection) {
        targetSection.classList.remove('hidden');
    }
}

function togglePassword(id) {
    const input = document.getElementById('input-' + id);
    const icon = document.querySelector('.toggle-icon-' + id);
    if (input && input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />';
    } else if (input) {
        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.43 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />';
    }
}

// Export functions to global window context so inline onclick works
window.switchTab = switchTab;
window.togglePassword = togglePassword;
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>
