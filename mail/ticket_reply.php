<?php
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

/* @var $reply app\models\TicketReplies */
/* @var $ticket app\models\Tickets */

$ticket = $ticket ?? ($reply ? $reply->ticket : null);

$formatMessage = function($text, $dark = false) {
    if (empty($text)) {
        return '';
    }
    if (strpos($text, '<p') === false && strpos($text, '<div') === false && strpos($text, '<br') === false) {
        $text = nl2br($text);
    }

    $config = function ($conf) {
        $conf->set('HTML.TargetBlank', true);
        $conf->set('AutoFormat.Linkify', true);
        $conf->set('HTML.Allowed', 'p,b,strong,i,em,u,ul,ol,li,table,thead,tbody,th,td,img[src|alt|width|height],br,span[style|class|data-email],div,h1,h2,h3,h4,h5,h6,a[href|target]');
        
        $def = $conf->getHTMLDefinition(true);
        if ($def) {
            $def->addAttribute('span', 'data-email', 'Text');
        }
    };

    $cleanHtml = HtmlPurifier::process($text, $config);

    // Apply inline style for links
    $linkColor = $dark ? '#ffffff' : '#4F46E5';
    $linkStyle = 'style="color: ' . $linkColor . '; text-decoration: underline; font-weight: 500;"';
    $cleanHtml = str_replace('<a ', '<a ' . $linkStyle . ' ', $cleanHtml);

    // Apply inline style for mentions
    $mentionStyle = 'style="font-weight: bold; color: #4F46E5;"';
    $cleanHtml = preg_replace('/class=["\']mention\s+font-bold\s+text-primary["\']/', 'class="mention" ' . $mentionStyle, $cleanHtml);

    return $cleanHtml;
};
?>

<div class="ticket-reply">
    <?php if ($ticket): ?>
        <p>Se ha incluido la siguiente respuesta al ticket #<?= Html::encode($ticket->ticket_code) ?>:</p>
    <?php else: ?>
        <p>Se ha incluido la siguiente respuesta al ticket:</p>
    <?php endif; ?>
    
    <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #ccc; font-size: 14px; line-height: 1.6; color: #333;">
        <?= $reply ? $formatMessage($reply->message) : '' ?>
    </div>

    <?php if ($ticket): ?>
        <?php 
            $rateUrl = Yii::$app->urlManager->createAbsoluteUrl(['feedback/rate', 'ticket_id' => $ticket->ticket_code]);
        ?>
        <div style="margin-top: 25px; padding: 15px; background-color: #f8fafc; border: 1px border-style: solid; border-color: #e2e8f0; border-radius: 8px; text-align: center;">
            <p style="margin: 0 0 8px 0; font-size: 13px; color: #475569; font-weight: bold;">¿Qué tal fue la atención recibida en este mensaje?</p>
            <a href="<?= $rateUrl ?>" style="display: inline-block; background-color: #134C42; color: #ffffff; padding: 8px 18px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">
                ⭐ Calificar Atención del Servicio
            </a>
        </div>
    <?php endif; ?>
</div>