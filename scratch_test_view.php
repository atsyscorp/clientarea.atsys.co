<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
$config = require __DIR__ . '/config/web.php';
$app = new yii\web\Application($config);
$app->request->setUrl('/'); // mock url
$reply = app\models\TicketReplies::find()->orderBy(['id' => SORT_DESC])->one();
$ticket = $reply->ticket;
try {
    $html = Yii::$app->view->renderFile('@app/views/tickets/view-reply.php', [
        'reply' => $reply,
        'model' => $ticket,
    ]);
    echo "Render successful! Length: " . strlen($html);
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
