<?php
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

/* @var $this yii\web\View */
/* @var $ticket app\models\Tickets */
/* @var $message string */
/* @var $user app\models\User */

$adminUrl = Yii::$app->urlManager->createAbsoluteUrl(['tickets/view', 'id' => $ticket->id]);
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
<div style="font-family: Arial, sans-serif; color: #333;">
    <h3 style="color: #d97706;">🔔 Nuevo Ticket</h3>
    
    <p>El usuario <strong><?= Html::encode($user ? $user->username : 'Usuario Externo') ?></strong> (<?= Html::encode($user ? $user->email : $ticket->email) ?>) ha abierto un ticket.</p>
    
    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Código:</strong></td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;"><?= Html::encode($ticket->ticket_code) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Asunto:</strong></td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;"><?= Html::encode($ticket->subject) ?></td>
        </tr>
    </table>

    <div style="background: #fffbeb; padding: 15px; border: 1px solid #fcd34d; border-radius: 5px; margin-bottom: 20px;">
        <strong>Mensaje:</strong><br>
        <?= $formatMessage($message) ?>
    </div>

    <?php 
    $firstReply = $ticket ? $ticket->getTicketReplies()->orderBy(['id' => SORT_ASC])->one() : null;
    $attachments = ($firstReply && method_exists($firstReply, 'getAttachmentList')) ? $firstReply->getAttachmentList() : [];
    if (!empty($attachments)): 
    ?>
        <div style="margin-bottom: 20px; padding: 12px 16px; background-color: #f8fafc; border-radius: 6px; border: 1px solid #cbd5e1;">
            <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: bold; color: #334155;">
                📎 <?= (count($attachments) === 1) ? 'Archivo adjunto:' : 'Archivos adjuntos (' . count($attachments) . '):' ?>
            </p>
            <div style="margin: 0; padding-left: 5px; font-size: 13px;">
                <?php foreach ($attachments as $att): ?>
                    <div style="margin-bottom: 6px;">
                        <a href="<?= Html::encode($att['url']) ?>" target="_blank" style="color: #134C42; font-weight: bold; text-decoration: underline;">
                            <?= Html::encode($att['name']) ?>
                        </a>
                        <span style="font-size: 11px; color: #64748b; margin-left: 6px;">
                            (<?= !empty($att['is_drive']) ? 'Abrir en Google Drive' : 'Ver archivo' ?> ↗)
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <p>
        <a href="<?= $adminUrl ?>" style="background-color: #134C42; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Ir a responder &rarr;</a>
    </p>
</div>