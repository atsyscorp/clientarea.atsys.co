<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=clie_app',
    'username' => 'clie_app',
    'password' => 'ySkUvcI3czC8Cheg',
    'charset' => 'utf8mb4',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
    'on afterOpen' => function ($event) {
        $event->sender->createCommand("SET time_zone = '-05:00';")->execute();
    },
];
