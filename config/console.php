<?php

require_once __DIR__ . '/env.php';

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'atsys-clientarea-console',
    'language' => 'es-CO',
    'timeZone' => 'America/Bogota',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        'log',
        'queue',
        function ($app) {
            \app\models\SystemSettings::loadToParams();
        }
    ],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'hostInfo' => 'https://clientarea.atsys.co',
            'baseUrl' => '/',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
        'log' => [
            'traceLevel' => 3,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'logFile' => '@runtime/logs/console.log',
                ],
            ],
        ],
        'db' => $db,
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => false,
            'transport' => [
                'scheme' => env('MAIL_SCHEME', 'smtps'),
                'host' => env('MAIL_HOST', 'nexus01.atsys.co'),
                // La consola envía como soporte@, la web como noreply@
                'username' => env_required('MAIL_CONSOLE_USERNAME'),
                'password' => env_required('MAIL_PASSWORD'),
                'port' => (int) env('MAIL_PORT', 465),
                'options' => [
                    'verify_peer' => env('MAIL_VERIFY_PEER', false) ? 1 : 0,
                ],
            ],
        ],
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'locale' => 'es-CO', // Forzar localía
            'defaultTimeZone' => 'America/Bogota',
            'dateFormat' => 'long', // Formato por defecto
        ],
        'queue' => [
            'class' => \yii\queue\db\Queue::class,
            'db' => 'db',
            'tableName' => '{{%queue}}',
            'channel' => 'default',
            'mutex' => \yii\mutex\MysqlMutex::class,
        ],
        'virtualmin' => [
            'class' => 'app\components\Virtualmin',
        ],
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
    // configuration adjustments for 'dev' environment
    // requires version `2.1.21` of yii2-debug module
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
