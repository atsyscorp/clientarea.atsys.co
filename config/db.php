<?php

require_once __DIR__ . '/env.php';

return [
    'class' => 'yii\db\Connection',
    'dsn' => env('DB_DSN', 'mysql:host=localhost;dbname=atclient_app'),
    'username' => env_required('DB_USERNAME'),
    'password' => env_required('DB_PASSWORD'),
    'charset' => 'utf8mb4',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
    'on afterOpen' => function ($event) {
        $event->sender->createCommand("SET time_zone = '-05:00';")->execute();
    },
];
