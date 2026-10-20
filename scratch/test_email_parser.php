<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../helpers/EmailParserHelper.php';

use app\helpers\EmailParserHelper;

$testCases = [
    'Caso 1: Gmail con gmail_quote y plantilla ATSYS' => [
        'input' => '<div dir="ltr">Hola equipo de ATSYS, ya adjunté el comprobante solicitado.<br>Quedo atento a la confirmación.</div><br><div class="gmail_quote"><div dir="ltr" class="gmail_attr">El jue, 15 oct 2026 a las 14:00, ATSYS Soporte (&lt;<a href="mailto:soporte@atsys.co">soporte@atsys.co</a>&gt;) escribió:<br></div><blockquote class="gmail_quote" style="margin:0px 0px 0px 0.8ex;border-left:1px solid rgb(204,204,204);padding-left:1ex"><div class="ticket-reply"><p>Se ha incluido la siguiente respuesta al ticket #TKT-12345:</p><div style="background-color: #f9f9f9;">Hola, por favor envíanos el comprobante.</div><div style="text-align: center;"><a href="https://clientarea.atsys.co/feedback/rate?ticket_id=TKT-12345">⭐ Calificar Atención del Servicio</a></div></div></blockquote></div>',
        'expected_contains' => 'ya adjunté el comprobante solicitado',
        'expected_not_contains' => 'Calificar Atención del Servicio',
    ],

    'Caso 2: Outlook con divRplyFwdMsg y stopSpelling' => [
        'input' => '<html><head></head><body><div style="font-family: Calibri, Arial, sans-serif; font-size: 11pt;">Buenas tardes, ya realizamos el cambio de DNS.<br>Por favor verificar.</div><div id="divRplyFwdMsg" dir="ltr"><font face="Calibri" color="#000000"><b>De:</b> ATSYS Soporte &lt;soporte@atsys.co&gt;<br><b>Enviado el:</b> viernes, 16 de octubre de 2026 8:00<br><b>Para:</b> cliente@empresa.com<br><b>Asunto:</b> [Ticket #TKT-99881] Actualización</font><div>&nbsp;</div></div><div class="ticket-reply"><p>Se ha incluido la siguiente respuesta al ticket #TKT-99881:</p></div></body></html>',
        'expected_contains' => 'ya realizamos el cambio de DNS',
        'expected_not_contains' => 'divRplyFwdMsg',
    ],

    'Caso 3: Apple Mail con blockquote cite' => [
        'input' => '<body dir="auto">Muchas gracias por la rápida atención. Todo funcionando ok.<br><br><blockquote type="cite">El 16 oct 2026, a las 7:15, ATSYS Soporte &lt;soporte@atsys.co&gt; escribió:<br><br><div class="ticket-reply"><p>Se ha incluido la siguiente respuesta al ticket #TKT-55443:</p></div></blockquote></body>',
        'expected_contains' => 'Muchas gracias por la rápida atención',
        'expected_not_contains' => 'ticket #TKT-55443',
    ],

    'Caso 4: Delimitador explícito de corte' => [
        'input' => '<p>Por favor proceder con el reinicio del servidor.</p><br><div class="ticket-reply-separator">-- Escriba su respuesta por encima de esta línea --</div><p>Se ha incluido la siguiente respuesta al ticket #TKT-11223:</p>',
        'expected_contains' => 'Por favor proceder con el reinicio del servidor',
        'expected_not_contains' => 'Se ha incluido la siguiente respuesta',
    ],

    'Caso 5: Mensaje plano con "El 15 de oct. escribió:"' => [
        'input' => "Todo correcto, pueden cerrar el caso.\n\nEl vie, 16 oct 2026 a las 9:30, ATSYS Soporte <soporte@atsys.co> escribió:\n> Se ha incluido la siguiente respuesta al ticket #TKT-001:\n> ¿Se solucionó el problema?",
        'expected_contains' => 'Todo correcto, pueden cerrar el caso',
        'expected_not_contains' => '¿Se solucionó el problema?',
    ],

    'Caso 6: Mensaje simple sin historial (no debe romperse)' => [
        'input' => '<p>Hola, necesito ayuda con mi cuenta de correo corporativo.</p>',
        'expected_contains' => 'Hola, necesito ayuda con mi cuenta de correo corporativo',
        'expected_not_contains' => 'null',
    ],

    'Caso 7: Plantilla ATSYS sin cabecera de cliente' => [
        'input' => '<div>Confirmado el soporte. Gracias.</div><table border="0"><tr><td><img src="https://static.atsys.co/img/email/atsys-email-customer-tpl.png"></td></tr><tr><td><div class="ticket-reply"><p>Se ha incluido la siguiente respuesta al ticket #TKT-88776:</p><a href="https://clientarea.atsys.co/feedback/rate">⭐ Calificar Atención del Servicio</a></div></td></tr></table>',
        'expected_contains' => 'Confirmado el soporte',
        'expected_not_contains' => 'Calificar Atención del Servicio',
    ],

    'Caso 8: Outlook MsoNormal con línea divisoria border-top' => [
        'input' => '<p class="MsoNormal">Hola, adjunto los datos solicitados.<o:p></o:p></p><div style="border:none;border-top:solid #E1E1E1 1.0pt;padding:3.0pt 0cm 0cm 0cm"><p class="MsoNormal"><b>De:</b> Soporte ATSYS &lt;soporte@atsys.co&gt;<br><b>Enviado el:</b> viernes, 16 de octubre de 2026<br><b>Para:</b> Cliente<br><b>Asunto:</b> Ticket</p></div><p>Mensaje anterior</p>',
        'expected_contains' => 'Hola, adjunto los datos solicitados',
        'expected_not_contains' => 'border-top',
    ],
];

echo "=== INICIANDO PRUEBAS DE EMAIL PARSER ===\n\n";
$allPassed = true;

foreach ($testCases as $name => $test) {
    $result = EmailParserHelper::extractReplyBody($test['input']);
    
    $containsPass = (strpos($result, $test['expected_contains']) !== false);
    $notContainsPass = ($test['expected_not_contains'] === 'null' || strpos($result, $test['expected_not_contains']) === false);

    if ($containsPass && $notContainsPass) {
        echo "✅ PASS: $name\n";
        echo "   Resultado limpio: " . substr(strip_tags($result), 0, 80) . "\n\n";
    } else {
        $allPassed = false;
        echo "❌ FAIL: $name\n";
        echo "   Contiene esperado: " . ($containsPass ? 'SI' : 'NO (' . $test['expected_contains'] . ')') . "\n";
        echo "   NO contiene esperado: " . ($notContainsPass ? 'SI' : 'NO, todavía contiene (' . $test['expected_not_contains'] . ')') . "\n";
        echo "   Resultado completo: " . $result . "\n\n";
    }
}

if ($allPassed) {
    echo "🎉 TODAS LAS PRUEBAS PASARON SATISFACTORIAMENTE.\n";
} else {
    echo "⚠️ ALGUNAS PRUEBAS FALLARON.\n";
    exit(1);
}
