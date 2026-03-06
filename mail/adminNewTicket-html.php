<?php
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

/* @var $this yii\web\View */
/* @var $ticket app\models\Tickets */
/* @var $message string */
/* @var $user app\models\User */

$adminUrl = Yii::$app->urlManager->createAbsoluteUrl(['tickets/view', 'id' => $ticket->id]);
$formatMessage = function($text, $dark = false) {
    if (strpos($text, '<p') === false && strpos($text, '<div') === false && strpos($text, '<br') === false) {
        $text = nl2br($text);
    }

    $config = function ($conf) {
        $conf->set('HTML.TargetBlank', true);
        $conf->set('AutoFormat.Linkify', true);
        $conf->set('HTML.Allowed', 'p,b,strong,i,em,u,ul,ol,li,table,thead,tbody,th,td,img[src|alt|width|height],br,span[style],div,h1,h2,h3,h4,h5,h6,a[href|target]');
    };

    $cleanHtml = HtmlPurifier::process($text, $config);
    $cssClass = $dark ? 'link link-white underline' : 'link link-primary underline';

    return str_replace('<a ', '<a class="' . $cssClass . '" ', $cleanHtml);
};
?>
<div style="font-family: Arial, sans-serif; color: #333;">
    <h3 style="color: #d97706;">🔔 Nuevo Ticket de Soporte</h3>
    
    <p>El cliente <strong><?= Html::encode($user->username) ?></strong> (<?= Html::encode($user->email) ?>) ha abierto un ticket.</p>
    
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

    <p>
        <a href="<?= $adminUrl ?>" style="background-color: #134C42; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Ir a responder &rarr;</a>
    </p>
</div>