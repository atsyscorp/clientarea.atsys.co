<?php
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

// Helper para formato en caso de no ser pasado por parámetro
if (!isset($formatMessage)) {
    $formatMessage = function ($text, $dark = false) {
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
        $cssClass = $dark ? 'link link-white underline' : 'link link-primary underline';

        return str_replace('<a ', '<a class="' . $cssClass . '" ', $cleanHtml);
    };
}

$isSupport = ($reply->sender_type === 'admin');
$alignment = $isSupport ? 'chat-end' : 'chat-start';
$darkLink = $isSupport ? true : false;
$bubbleColor = $isSupport ? 'chat-bubble-primary text-primary-content' : 'dark:bg-base-300 dark:text-base-content bg-base-100 text-base-content border border-base-300';

$name = 'Usuario Desconocido';
$badgeRol = '';
$avatar = '👤';

if ($isSupport) {
    $name = ($model->department === 'support') ? 'Soporte' : (($model->department === 'sales') ? 'Comercial' : 'ATSYS');
    $avatar = '🛡️';
    $badgeRol = '<span class="badge badge-primary badge-xs ml-2">' . ucfirst($name) . '</span>';
} else {
    $senderUser = $reply->user;

    if ($senderUser) {
        $name = Html::encode($senderUser->contact_name ?? $senderUser->email);

        if ($senderUser->getIsSubAccount()) {
            $badgeRol = '<span class="badge badge-ghost badge-xs ml-2 text-base-content/60">Delegado</span>';
        } else {
            $badgeRol = '<span class="badge badge-neutral badge-xs ml-2 font-bold">Titular</span>';
        }
    } else {
        $name = $model->customer ? (
            $model->customer->contact_name == $model->customer->business_name ?
            $model->customer->contact_name :
            $model->customer->contact_name . ' (' . $model->customer->business_name . ')'
        ) : $model->email;
    }
}
?>
<div class="chat <?= $alignment ?> reply-item" data-reply-id="<?= $reply->id ?>">
    <div class="chat-header text-xs opacity-50 mb-1 flex items-center">
        <?= $name ?>
        <?= $badgeRol ?>
        <time class="text-xs opacity-50 ml-2">
            <?= Yii::$app->formatter->asRelativeTime($reply->created_at) ?>
        </time>
    </div>
    <div class="chat-image avatar placeholder">
        <div
            class="w-8 rounded-full bg-base-300 text-center flex items-center justify-center text-xs cursor-default select-none">
            <span><?= $avatar ?></span>
        </div>
    </div>
    <div class="chat-bubble <?= $bubbleColor ?> shadow-sm">
        <?= $formatMessage($reply->message, $darkLink) ?>

        <?php if (!empty($reply->attachment)): ?>
            <div class="mt-3 pt-4 border-t border-white/10">
                <a href="<?= Yii::getAlias('@web') . '/' . $reply->attachment ?>" target="_blank"
                    class="btn btn-xs btn-outline gap-2 bg-base-100 text-base-content border-0 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                    </svg>
                    Ver Archivo Adjunto
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
