            'on beforeSend' => function ($event) {
                $message = $event->message;
                $email = $message->getSymfonyEmail();
                if ($email && $email->getHtmlBody()) {
                    $html = $email->getHtmlBody();
                    
                    // 1. Inyectar respaldo para el primer botón
                    $mainButtonUrl = null;
                    $html = preg_replace_callback(
                        '/(<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*style=["\'][^"\']*(?:padding|background-color)[^"\']*["\'][^>]*>.*?<\/a>)/is',
                        function($matches) use (&$mainButtonUrl) {
                            if ($mainButtonUrl === null) {
                                $mainButtonUrl = $matches[2];
                                $fallback = '<p style="margin:16px 0 24px 0; font-size:13px; color:#666666; text-align:center;">' .
                                            'Si el botón no funciona, copia este enlace en tu navegador:<br />' .
                                            '<a href="' . $mainButtonUrl . '" style="color:#666666;">' . $mainButtonUrl . '</a></p>';
                                return $matches[1] . "\n" . $fallback;
                            }
                            return $matches[1];
                        },
                        $html
                    );
                    $email->html($html);

                    // 2. Generar text/plain si no existe
                    if (!$email->getTextBody()) {
                        $text = preg_replace('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '$2 ($1)', $html);
                        $text = preg_replace('/<(br|p|div|tr|td|h[1-6])\b[^>]*>/i', "\n", $text);
                        $text = strip_tags($text);
                        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
                        $text = trim(preg_replace("/\n\s*\n+/", "\n\n", $text));
                        $email->text($text);
                    }
                }
            },
