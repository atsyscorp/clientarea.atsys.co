<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
new yii\console\Application($config);

$nc = new app\services\NamecheapService();
$domains = app\models\CustomerServices::find()->joinWith("product p")->where(["p.type"=>"domain"])->all();
foreach($domains as $d) {
    echo "DOMAIN: " . $d->domain . "\n";
    $params = $nc->getBaseParams("namecheap.domains.dns.getList");
    $parts = explode(".", $d->domain, 2);
    $params["SLD"] = $parts[0];
    $params["TLD"] = $parts[1];
    try {
        $res = $nc->executeRequest($params);
        print_r($res);
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
