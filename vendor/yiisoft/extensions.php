<?php

$vendorDir = dirname(__DIR__);

return array (
  'yiisoft/yii2-faker' => 
  array (
    'name' => 'yiisoft/yii2-faker',
    'version' => '2.0.5.0',
    'alias' => 
    array (
      '@yii/faker' => $vendorDir . '/yiisoft/yii2-faker/src',
    ),
  ),
  'yiisoft/yii2-bootstrap5' => 
  array (
    'name' => 'yiisoft/yii2-bootstrap5',
    'version' => '2.0.51.0',
    'alias' => 
    array (
      '@yii/bootstrap5' => $vendorDir . '/yiisoft/yii2-bootstrap5/src',
    ),
    'bootstrap' => 'yii\\bootstrap5\\i18n\\TranslationBootstrap',
  ),
  'yiisoft/yii2-gii' => 
  array (
    'name' => 'yiisoft/yii2-gii',
    'version' => '2.2.7.0',
    'alias' => 
    array (
      '@yii/gii' => $vendorDir . '/yiisoft/yii2-gii/src',
    ),
  ),
  'yiisoft/yii2-symfonymailer' => 
  array (
    'name' => 'yiisoft/yii2-symfonymailer',
    'version' => '2.0.4.0',
    'alias' => 
    array (
      '@yii/symfonymailer' => $vendorDir . '/yiisoft/yii2-symfonymailer/src',
    ),
  ),
  '2amigos/yii2-tinymce-widget' => 
  array (
    'name' => '2amigos/yii2-tinymce-widget',
    'version' => '1.1.3.0',
    'alias' => 
    array (
      '@dosamigos/tinymce' => $vendorDir . '/2amigos/yii2-tinymce-widget/src',
    ),
  ),
  'borales/yii2-phone-input' => 
  array (
    'name' => 'borales/yii2-phone-input',
    'version' => '0.3.0.0',
    'alias' => 
    array (
      '@borales/extensions/phoneInput' => $vendorDir . '/borales/yii2-phone-input/src',
    ),
  ),
  'yiisoft/yii2-httpclient' => 
  array (
    'name' => 'yiisoft/yii2-httpclient',
    'version' => '2.0.16.0',
    'alias' => 
    array (
      '@yii/httpclient' => $vendorDir . '/yiisoft/yii2-httpclient/src',
    ),
  ),
  'easedevs/yii2-turnstile-validator' => 
  array (
    'name' => 'easedevs/yii2-turnstile-validator',
    'version' => '1.0.2.0',
    'alias' => 
    array (
      '@easedevs/yii2/turnstile' => $vendorDir . '/easedevs/yii2-turnstile-validator/src',
    ),
  ),
  'kartik-v/yii2-mpdf' => 
  array (
    'name' => 'kartik-v/yii2-mpdf',
    'version' => 'dev-master',
    'alias' => 
    array (
      '@kartik/mpdf' => $vendorDir . '/kartik-v/yii2-mpdf/src',
    ),
  ),
  'yiisoft/yii2-queue' => 
  array (
    'name' => 'yiisoft/yii2-queue',
    'version' => '2.3.8.0',
    'alias' => 
    array (
      '@yii/queue' => $vendorDir . '/yiisoft/yii2-queue/src',
      '@yii/queue/db' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/db',
      '@yii/queue/sqs' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/sqs',
      '@yii/queue/amqp' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/amqp',
      '@yii/queue/file' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/file',
      '@yii/queue/sync' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/sync',
      '@yii/queue/redis' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/redis',
      '@yii/queue/stomp' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/stomp',
      '@yii/queue/gearman' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/gearman',
      '@yii/queue/beanstalk' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/beanstalk',
      '@yii/queue/amqp_interop' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/amqp_interop',
    ),
  ),
  'yiisoft/yii2-debug' => 
  array (
    'name' => 'yiisoft/yii2-debug',
    'version' => '2.1.28.0',
    'alias' => 
    array (
      '@yii/debug' => $vendorDir . '/yiisoft/yii2-debug/src',
    ),
  ),
);
