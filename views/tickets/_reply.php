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
        <div class="ticket-message-content">
            <?= $formatMessage($reply->message, $darkLink) ?>
        </div>

        <?php 
        $attachments = $reply->getAttachmentList(); 
        $totalAttachments = count($attachments);
        $getFileIcon = function($filename) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4 shrink-0 text-amber-400"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>';
            }
            if ($ext === 'pdf') {
                return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4 shrink-0 text-red-400"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>';
            }
            if (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) {
                return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4 shrink-0 text-yellow-500"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>';
            }
            return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4 shrink-0 text-sky-400"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>';
        };
        ?>
        <?php if ($totalAttachments > 0): ?>
            <div class="mt-3 pt-3 border-t <?= $isSupport ? 'border-white/20' : 'border-base-300 dark:border-base-content/10' ?>">
                <div class="rounded-lg p-3 <?= $isSupport ? 'bg-black/20 border border-white/15' : 'bg-base-200/90 dark:bg-base-300/70 border border-base-300 shadow-sm' ?>">
                    <div class="flex items-center justify-between gap-2 mb-2 pb-1.5 border-b <?= $isSupport ? 'border-white/10' : 'border-base-300/60' ?>">
                        <div class="flex items-center gap-1.5 text-xs font-bold <?= $isSupport ? 'text-white' : 'text-base-content' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                            </svg>
                            <span><?= ($totalAttachments === 1) ? 'Archivo adjunto:' : 'Archivos adjuntos (' . $totalAttachments . '):' ?></span>
                        </div>
                        <span class="badge badge-xs <?= $isSupport ? 'badge-neutral bg-white/20 text-white border-0' : 'badge-ghost' ?>">
                            <?= ($totalAttachments === 1) ? '1 archivo' : $totalAttachments . ' archivos' ?>
                        </span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <?php foreach ($attachments as $att): ?>
                            <a href="<?= Html::encode($att['url']) ?>" target="_blank" rel="noopener noreferrer"
                               class="group flex items-center justify-between gap-3 p-2 rounded-md transition-all text-xs <?= $isSupport ? 'bg-white/10 hover:bg-white/20 text-white' : 'bg-base-100 hover:bg-base-200 text-base-content border border-base-200 hover:border-base-300 shadow-xs' ?>"
                               title="<?= Html::encode($att['name']) ?>">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <?= $getFileIcon($att['name']) ?>
                                    <span class="font-medium truncate">
                                        <?= Html::encode($att['name']) ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0 font-semibold text-[11px] opacity-80 group-hover:opacity-100 group-hover:underline">
                                    <span><?= !empty($att['is_drive']) ? 'Abrir en Drive' : 'Ver archivo' ?></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
