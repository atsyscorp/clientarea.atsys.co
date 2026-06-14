<?php
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

/* @var $reply object */
/* @var $ticket object */
?>

<div class="ticket-reply">
    <p>Se ha incluido la siguiente respuesta al ticket #<?= $ticket->ticket_code ?>:</p>
    
    <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #ccc;">
        <?= HtmlPurifier::process($reply->message) ?>
    </div>
</div>