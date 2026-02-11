<?php
/* @var $this \yii\web\View */
/* @var $title string */
/* @var $content string */
/* @var $color string */

// Por defecto usamos el color primario si no se define
$headerColor = isset($color) ? $color : '#134C42';
?>
<div style="font-family: 'Helvetica', 'Arial', sans-serif; padding: 20px;">
    
    <h2 style="color: <?= $headerColor ?>; margin-top: 0;">
        <?= $title ?>
    </h2>

    <div style="font-size: 14px; line-height: 1.6; color: #333; background-color: #f9fafb; padding: 15px; border-radius: 8px; border: 1px solid #e5e7eb;">
        <?= $content ?>
    </div>
</div>