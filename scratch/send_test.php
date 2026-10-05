<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
$config = require __DIR__ . '/../config/console.php';
$application = new yii\console\Application($config);

$html = '<h2>Prueba de Entregabilidad</h2>
<p>Hola, esta es una prueba de la plataforma ATSYS para verificar SPF, DKIM y DMARC.</p>
<a href="https://clientarea.atsys.co/login" style="background-color: #134C42; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Acceder a la plataforma</a>
<br><p>Saludos cordiales.</p>';

$message = Yii::$app->mailer->compose()
    ->setFrom('clientarea@atsys.co')
    ->setTo('ruizorlando1983@gmail.com')
    ->setSubject('Prueba de Entregabilidad y Botones')
    ->setHtmlBody($html);

try { 
    $result = $message->send(); 
    echo $result ? "Enviado con éxito\n" : "Fallo al enviar (sin excepcion)\n";
} catch (\Throwable $e) { 
    echo "ERROR EXCEPTION: " . $e->getMessage() . "\n";
}
