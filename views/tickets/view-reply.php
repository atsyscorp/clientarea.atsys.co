<?php
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

$this->title = 'Respuesta del Ticket #' . Html::encode($model->ticket_code);

// Formateador
$formatMessage = function ($text, $dark = false) {
    $text = (string)$text;
    if (strpos($text, '<p') === false && strpos($text, '<div') === false && strpos($text, '<br') === false) {
        $text = nl2br($text);
    }
    $config = function ($conf) {
        $conf->set('HTML.TargetBlank', true);
        $conf->set('AutoFormat.Linkify', true);
        $conf->set('HTML.Allowed', 'p,b,strong,i,em,u,ul,ol,li,table,thead,tbody,th,td,img[src|alt|width|height|style],br,span[style|class|data-email],div,h1,h2,h3,h4,h5,h6,a[href|target]');

        $def = $conf->getHTMLDefinition(true);
        if ($def) {
            $def->addAttribute('span', 'data-email', 'Text');
        }
    };
    $cleanHtml = HtmlPurifier::process($text, $config);
    return $cleanHtml;
};

// Estilos tipográficos extraídos de view.php
$this->registerCss("
    .ticket-message-content p {
        margin-bottom: 0.75rem;
    }
    .ticket-message-content p:last-child {
        margin-bottom: 0;
    }
    .ticket-message-content p:empty,
    .ticket-message-content p > br:only-child {
        min-height: 1.25rem;
        display: block;
    }
    .ticket-message-content p:empty::before {
        content: '\\00a0';
    }
    .ticket-message-content ul {
        list-style-type: disc;
        margin-left: 1.25rem;
        margin-bottom: 0.75rem;
    }
    .ticket-message-content ol {
        list-style-type: decimal;
        margin-left: 1.25rem;
        margin-bottom: 0.75rem;
    }
    .ticket-message-content li {
        margin-bottom: 0.25rem;
    }
    .ticket-message-content blockquote {
        border-left: 3px solid currentColor;
        opacity: 0.85;
        padding-left: 0.75rem;
        margin: 0.5rem 0 0.75rem 0;
        font-style: italic;
    }
    .ticket-message-content img {
        max-width: 100%;
        height: auto;
    }
");
?>
<div class="p-6 bg-base-100 min-h-screen text-base-content">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6 flex justify-between items-center border-b border-base-200 pb-4">
            <div>
                <h1 class="text-xl font-bold">Ticket: <?= Html::encode($model->subject) ?> (#<?= Html::encode($model->ticket_code) ?>)</h1>
                <p class="text-sm opacity-60">Mensaje enviado el <?= Yii::$app->formatter->asDatetime($reply->created_at, 'long') ?></p>
            </div>
            <button onclick="window.close()" class="btn btn-sm btn-outline">Cerrar Ventana</button>
        </div>

        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body">
                <div class="ticket-message-content">
                    <?= $formatMessage($reply->message) ?>
                </div>

                <?php 
                $attachments = $reply->getAttachmentList(); 
                if (count($attachments) > 0): 
                ?>
                    <div class="mt-6 pt-4 border-t border-base-200">
                        <h4 class="font-bold text-sm mb-3">Archivos adjuntos:</h4>
                        <div class="flex flex-col gap-2">
                            <?php foreach ($attachments as $att): ?>
                                <a href="<?= Html::encode($att['url']) ?>" target="_blank" rel="noopener noreferrer" class="link link-primary text-sm flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                    <?= Html::encode($att['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
