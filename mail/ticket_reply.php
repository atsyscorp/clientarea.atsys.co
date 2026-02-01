<?php
use yii\helpers\Html;

/* @var $reply object */
/* @var $ticket object */
?>

<div class="ticket-reply">
    <p>Se ha incluido la siguiente respuesta a su ticket:</p>
    
    <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #ccc;">
        <?= nl2br(Html::encode($reply->message)) ?>
    </div>
</div>