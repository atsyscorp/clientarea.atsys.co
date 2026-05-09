<?php

?>
<div class="announcement-email">
    <h3><?= $model->title ?></h3>
    <p>Se ha publicado un nuevo comunicado de ATSYS para todos nuestros clientes</p>

    <div>
        <?php
        // Se agrega un resumen del comunicado
        if (isset($model->content)) {
            $short = substr($model->content, 0, 100);
            echo $short;
            if (strlen($model->content) > 100) {
                echo "...";
            }
        }

        ?>
    </div>
    <p>
        <a href="<?php echo Yii::$app->urlManager->createAbsoluteUrl(['announcements/view', 'id' => $model->id]); ?>"
            style="background-color: #134C42; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">
            Ver comunicado
        </a>
    </p>

</div>