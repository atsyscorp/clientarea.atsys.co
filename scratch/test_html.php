<?php
$html = '<div style="text-align: center;"><a href="https://atsys.co/login" style="padding: 10px; background-color: blue; color: white;">Iniciar sesión</a></div><p>Other text</p>';

// 1. Encuentra el primer botón (<a> que tenga 'padding' y 'background-color')
$mainButtonUrl = null;
$newHtml = preg_replace_callback('/(<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*style=["\'][^"\']*(?:padding|background-color)[^"\']*["\'][^>]*>.*?<\/a>)/is', function($matches) use (&$mainButtonUrl) {
    if ($mainButtonUrl === null) {
        $mainButtonUrl = $matches[2];
        $fallback = '<p style="margin:12px 0 24px 0; font-size:13px; color:#666666; text-align:center;">' .
                    'Si el botón no funciona, copia este enlace en tu navegador:<br />' .
                    '<a href="' . $mainButtonUrl . '" style="color:#666666;">' . $mainButtonUrl . '</a></p>';
        return $matches[1] . "\n" . $fallback;
    }
    return $matches[1];
}, $html);

echo $newHtml;
