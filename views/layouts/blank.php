<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use yii\helpers\Html;

AppAsset::register($this);

// Nos aseguramos de tener el meta viewport para móviles
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-theme="atsys_theme">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div>
    <?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?>
        <?php
        // Mapeo de colores de Yii2 a DaisyUI
        $alertClass = 'alert-info';
        $icon = '';
        
        switch ($type) {
            case 'success':
                $alertClass = 'alert-success text-white'; // Verde
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                break;
            case 'error':
            case 'danger':
                $alertClass = 'alert-error text-white'; // Rojo
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                break;
            case 'warning':
                $alertClass = 'alert-warning'; // Amarillo
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>';
                break;
            default:
                // Info por defecto
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
        }
        ?>
        <div class="fixed top-0 right-0 left-0 p-4 mt-4 z-50 max-w-96 mx-auto">
            <div role="alert" class="alert <?= $alertClass ?> mb-5 shadow-lg flex items-center">
                <?= $icon ?>
                <span class="font-medium"><?= $message ?></span>
                
                <button onclick="this.parentElement.style.display='none'" class="btn btn-sm btn-ghost btn-circle ml-auto">✕</button>
            </div>
        </div>
    <?php endforeach; ?>
    <?= $content ?>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>