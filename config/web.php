<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'atsys-clientarea-app',
    'name' => 'Área de clientes ATSYS',
    'language' => 'es-CO',
    'sourceLanguage' => 'en-US',
    'timeZone' => 'America/Bogota',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'vPb_geIlTPAEC4eeCFvViGPCqeIpbkh-',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => false,
            'transport' => [
                'scheme' => 'smtp',
                'host' => 'nexus03.atsys.co',
                'username' => 'soporte@atsys.co',
                'password' => 'rcdu88120kcfrmash',
                'port' => 587,
                'options' => [
                    'verify_peer' => 0,
                ],
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'signup' => 'site/signup',
                'login' => 'site/login',
                'logout' => 'site/logout',
                'profile' => 'site/profile'
            ],
        ],
        'i18n' => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages'
                ]    
            ]  
        ],
        'assetManager' => [
            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'js' => [],
                ],
                'yii\bootstrap5\BootstrapAsset' => [
                    'css' => [],
                    'js' => [],
                ],
                'yii\bootstrap5\BootstrapPluginAsset' => [
                    'js' => [],
                ],
            ],
        ],
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'locale' => 'es-CO', // Forzar localía
            'defaultTimeZone' => 'America/Bogota',
            'dateFormat' => 'long', // Formato por defecto
        ],
    ],
    'params' => $params,
    'container' => [
        'definitions' => [
            \yii\grid\GridView::class => [
                'tableOptions' => ['class' => 'table table-zebra w-full'], // Clases de DaisyUI
                'pager' => [
                    'options' => ['class' => 'join'], // Paginación DaisyUI
                    'linkOptions' => ['class' => 'join-item btn btn-sm'],
                ],
            ],
        ],
    ],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

$config['container']['definitions'] = [
    \yii\widgets\ActiveForm::class => [
        'errorCssClass' => 'input-error',
        'successCssClass' => 'input-success',
        'fieldConfig' => [
            'template' => "
                <div class='form-control w-full'>
                    <label class='label'>{label}</label>
                    {input}
                    <label class='label'>
                        <span class='label-text-alt text-error'>{error}</span>
                    </label>
                </div>
            ",
            'labelOptions' => ['class' => 'label-text font-bold'], 
            'inputOptions' => ['class' => 'input input-bordered w-full'], 
            'errorOptions' => ['class' => 'text-error text-xs'],
        ],
    ],

    \yii\grid\GridView::class => [
        'tableOptions' => ['class' => 'table table-zebra w-full'],
        'layout' => "{items}\n<div class='mt-4 flex justify-between'>{summary}{pager}</div>",
        'pager' => [
            'class' => \yii\widgets\LinkPager::class,
            'options' => ['class' => 'join'],
            'linkContainerOptions' => ['class' => 'join-item'],
            'linkOptions' => ['class' => 'join-item btn btn-sm btn-outline'],
            'disabledListItemSubTagOptions' => ['class' => 'join-item btn btn-sm btn-disabled'],
            'activePageCssClass' => 'btn-active',
        ],
    ],
];

return $config;
