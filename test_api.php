<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$app = new \yii\console\Application($config);

$servers = \app\models\Servers::find()->where(['type' => 'virtualmin'])->all();
foreach ($servers as $server) {
    echo "Servidor: {$server->name}\n";
    $result = Yii::$app->virtualmin->sendCommandDynamic($server->username, $server->auth_token, $server->hostname, 'list-domains', ['multiline' => '', 'domain' => 'apsei.com'], 300);
    print_r($result);
}
