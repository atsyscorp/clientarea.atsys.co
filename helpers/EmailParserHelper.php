<?php

namespace app\helpers;

use Yii;
use yii\helpers\HtmlPurifier;

class EmailParserHelper
{
    /**
     * Extrae únicamente el cuerpo del mensaje nuevo de una respuesta recibida por correo,
     * eliminando el historial citado, encabezados de clientes de correo (Gmail, Outlook, etc.)
     * y la estructura de la plantilla de la aplicación ATSYS.
     *
     * @param string|null $rawBody Contenido original (HTML o texto plano) recibido en el webhook.
     * @return string Contenido limpio con únicamente la respuesta del usuario.
     */
    public static function extractReplyBody($rawBody): string
    {
        if ($rawBody === null || trim($rawBody) === '') {
            return '';
        }

        $body = trim($rawBody);

        // 1. Corte prioritario por separadores explícitos (Reply-Above-This-Line)
        $body = self::stripByExplicitSeparators($body);

        // 2. Corte por contenedores HTML estándar de clientes de correo
        $body = self::stripByClientHtmlContainers($body);

        // 3. Corte por marcadores y elementos específicos de la plantilla ATSYS
        $body = self::stripByAtsysTemplateMarkers($body);

        // 4. Corte por cabeceras de cita típicas (Gmail, Outlook, Apple Mail en HTML o texto)
        $body = self::stripByQuoteHeaders($body);

        // 5. Corte de bloques de citas con prefijos ">" en texto plano
        $body = self::stripPlainEmailQuotes($body);

        // 6. Limpieza de etiquetas estructurales huérfanas y espacios vacíos finales
        $cleaned = self::cleanTrailingNoise($body);

        // Salvaguarda: si la limpieza borró absolutamente todo el contenido visible,
        // retornamos el texto original para no perder nunca el mensaje del cliente.
        $textCheck = trim(html_entity_decode(strip_tags($cleaned), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($textCheck === '') {
            return $rawBody;
        }

        return $cleaned;
    }

    /**
     * Limpia el mensaje para visualización en las vistas (retrocompatible para respuestas
     * antiguas ya guardadas en la base de datos).
     *
     * @param string|null $message
     * @return string
     */
    public static function cleanReplyForDisplay($message): string
    {
        if (empty($message)) {
            return '';
        }

        return self::extractReplyBody($message);
    }

    /**
     * Separa el correo si contiene delimitadores explícitos (como los inyectados en ticket_reply.php).
     */
    protected static function stripByExplicitSeparators(string $text): string
    {
        $patterns = [
            '/<!--\s*ATSYS_REPLY_SEPARATOR\s*-->/i',
            '/<(?:div|span|p)[^>]*class=["\'][^"\']*ticket-reply-separator[^"\']*["\'][^>]*>[\s\S]*?<\/(?:div|span|p)>/i',
            '/<(?:div|span|p)[^>]*id=["\']ticket-reply-separator["\'][^>]*>[\s\S]*?<\/(?:div|span|p)>/i',
            '/(?:[-_—]{2,}\s*)?(?:Por favor\s+)?(?:responda|escriba(?:\s+su\s+respuesta)?)\s+por\s+encima\s+de\s+esta\s+l[ií]nea(?:\s*[-_—]{2,})?/iu',
            '/(?:[-_—]{2,}\s*)?(?:Please\s+)?(?:reply|write(?:\s+your\s+reply)?)\s+above\s+this\s+line(?:\s*[-_—]{2,})?/iu',
        ];

        foreach ($patterns as $pattern) {
            $parts = preg_split($pattern, $text, 2);
            if (count($parts) === 2 && trim(strip_tags($parts[0])) !== '') {
                $text = $parts[0];
                break;
            }
        }

        return $text;
    }

    /**
     * Corta antes de contenedores HTML conocidos de clientes de correo.
     */
    protected static function stripByClientHtmlContainers(string $text): string
    {
        $containerPatterns = [
            // Gmail
            '/<div[^>]*\bclass=["\'][^"\']*\bgmail_quote\b[^"\']*["\']/i',
            '/<blockquote[^>]*\bclass=["\'][^"\']*\bgmail_quote\b[^"\']*["\']/i',
            '/<div[^>]*\bclass=["\'][^"\']*\bgmail_attr\b[^"\']*["\']/i',
            
            // Outlook / Office 365 / Hotmail
            '/<div[^>]*\bid=["\']divRplyFwdMsg["\']/i',
            '/<div[^>]*\bid=["\']appendonsend["\']/i',
            '/<div[^>]*\bclass=["\'][^"\']*\bOutlookQuote\b[^"\']*["\']/i',
            '/<hr[^>]*\bid=["\']stopSpelling["\']/i',
            '/<table[^>]*\bid=["\']outlook-table["\']/i',

            // Apple Mail / Thunderbird / Yahoo
            '/<blockquote[^>]*\btype=["\']cite["\']/i',
            '/<div[^>]*\bclass=["\'][^"\']*\bmoz-cite-prefix\b[^"\']*["\']/i',
            '/<div[^>]*\bclass=["\'][^"\']*\byahoo_quoted\b[^"\']*["\']/i',
            '/<div[^>]*\bid=["\']yiv\d+yqt\d+["\']/i',

            // Plantilla ATSYS propia (clases internas)
            '/<div[^>]*\bclass=["\'][^"\']*\bticket-reply\b[^"\']*["\']/i',
        ];

        foreach ($containerPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                $cutPos = $matches[0][1];
                $before = substr($text, 0, $cutPos);
                if (trim(strip_tags($before)) !== '') {
                    $text = $before;
                }
            }
        }

        return $text;
    }

    /**
     * Corta si el correo contiene frases o elementos distintivos de las plantillas ATSYS.
     */
    protected static function stripByAtsysTemplateMarkers(string $text): string
    {
        $atsysMarkers = [
            // Textos de ticket_reply.php
            'Se ha incluido la siguiente respuesta al ticket',
            '¿Qué tal fue la atención recibida en este mensaje?',
            'Calificar Atención del Servicio',
            'feedback/rate?ticket_id=',
            
            // Textos de newTicket-html.php / adminNewTicket-html.php
            '¡Hemos recibido tu solicitud!',
            'Se ha creado un nuevo ticket de soporte con el siguiente código',
            'Detalle de tu mensaje:',
            'Aviso: No tienes cuenta de cliente',

            // Textos de layouts/html.php
            'atsys-email-customer-tpl.png',
            'ATSYS - Trascendemos',
            'Este es un mensaje automático, por favor no responder directamente',
        ];

        $earliestPos = null;

        foreach ($atsysMarkers as $marker) {
            $pos = stripos($text, $marker);
            if ($pos !== false) {
                if ($earliestPos === null || $pos < $earliestPos) {
                    $earliestPos = $pos;
                }
            }
        }

        if ($earliestPos !== null) {
            // Buscamos si justo antes de este marcador hay una etiqueta de apertura contenedora
            // (por ejemplo: <p>, <div>, <table>, <hr>) dentro de un rango de 300 caracteres hacia atrás
            $cutPos = $earliestPos;
            $lookbackStart = max(0, $earliestPos - 350);
            $lookbackChunk = substr($text, $lookbackStart, $earliestPos - $lookbackStart);

            // Buscar si antes había una tabla envolvente de plantilla o div
            if (preg_match('/(<table[^>]*>|<div[^>]*class=["\'][^"\']*ticket-reply[^"\']*["\'][^>]*>|<hr[^>]*>|<div[^>]*style=["\'][^"\']*background-color:[^"\']*padding:[^"\']*["\'][^>]*>)/i', $lookbackChunk, $m, PREG_OFFSET_CAPTURE)) {
                $cutPos = $lookbackStart + $m[0][1];
            } elseif (preg_match('/<p[^>]*>\s*$/i', substr($text, 0, $earliestPos))) {
                // Si el marcador empezaba dentro de un <p>
                $pPos = strripos(substr($text, 0, $earliestPos), '<p');
                if ($pPos !== false) {
                    $cutPos = $pPos;
                }
            }

            $candidate = substr($text, 0, $cutPos);
            if (trim(strip_tags($candidate)) !== '') {
                $text = $candidate;
            }
        }

        return $text;
    }

    /**
     * Corta por cabeceras típicas de citas en correos (Gmail, Outlook, etc.).
     */
    protected static function stripByQuoteHeaders(string $text): string
    {
        $headerPatterns = [
            // "----- Mensaje original -----" o "----- Original Message -----"
            '/(?:<p[^>]*>|<div[^>]*>)?\s*[-_—]{3,}\s*(?:Mensaje original|Original Message)\s*[-_—]{3,}(?:<\/p>|<\/div>)?/iu',

            // "El vie, 16 oct 2026 ... escribió:" / "On Fri, Oct 16, 2026 ... wrote:"
            '/(?:<p[^>]*>|<div[^>]*>)?\s*(?:El|On)\s+[^<\n\r]{6,160}?\s+(?:escribi[oó]|wrote)\s*:\s*(?:<\/p>|<\/div>|<br\s*\/?>)?/iu',

            // Bloques de cabeceras de Outlook: "De: ... Enviado el: ..." o "From: ... Sent: ..."
            '/(?:<p[^>]*>|<div[^>]*>|<font[^>]*>)?\s*(?:<b>\s*)?(?:De|From)\s*:\s*(?:<\/b>\s*)?[^<\n\r]{3,120}?(?:<br\s*\/?>|\n)\s*(?:<b>\s*)?(?:Enviado el|Sent|Fecha|Date)\s*:\s*/iu',
            
            // En texto plano puro:
            '/(?:^|\n)\s*(?:[-_—]{3,}\s*(?:Mensaje original|Original Message)\s*[-_—]{3,})/iu',
            '/(?:^|\n)\s*(?:El|On)\s+[^\n\r]{6,160}?\s+(?:escribi[oó]|wrote)\s*:\s*$/miu',
            '/(?:^|\n)\s*(?:De|From)\s*:\s*[^\n\r]{3,100}?\n\s*(?:Enviado el|Sent|Fecha|Date)\s*:\s*/iu',
        ];

        foreach ($headerPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                $cutPos = $matches[0][1];

                // Si justo antes del match hay un contenedor divisor (ej. <div style="...border-top:..."> o <hr>)
                $lookback = substr($text, 0, $cutPos);
                if (preg_match('/(<div[^>]*style=["\'][^"\']*border(?:-top)?:[^"\']*["\'][^>]*>|<hr[^>]*>)\s*$/i', $lookback, $divMatch, PREG_OFFSET_CAPTURE)) {
                    $cutPos = $divMatch[0][1];
                }

                $before = substr($text, 0, $cutPos);
                if (trim(strip_tags($before)) !== '') {
                    $text = $before;
                    break;
                }
            }
        }

        return $text;
    }

    /**
     * Elimina bloques de citas con prefijos ">" en texto plano.
     */
    protected static function stripPlainEmailQuotes(string $text): string
    {
        // Si no parece tener HTML complejo
        if (strpos($text, '<div') === false && strpos($text, '<table') === false) {
            $lines = explode("\n", $text);
            $cleanLines = [];
            $inQuoteBlock = false;

            foreach ($lines as $line) {
                $trimmed = trim($line);
                // Si la línea empieza con ">" o "&gt;"
                if (strpos($trimmed, '>') === 0 || strpos($trimmed, '&gt;') === 0) {
                    $inQuoteBlock = true;
                    continue;
                }

                // Si ya empezó un bloque de citas y encuentra líneas vacías intermedias, las ignora
                if ($inQuoteBlock && $trimmed === '') {
                    continue;
                }

                $inQuoteBlock = false;
                $cleanLines[] = $line;
            }

            return implode("\n", $cleanLines);
        }

        return $text;
    }

    /**
     * Elimina saltos de línea finales, etiquetas HTML vacías y ruidos estructurales.
     */
    protected static function cleanTrailingNoise(string $text): string
    {
        // Eliminar posibles etiquetas html, head, body envolventes si están incompletas por el corte
        $text = preg_replace('/<\/?(html|head|body)[^>]*>/i', '', $text);

        // Eliminar etiquetas de namespaces Office (ej: <o:p></o:p>)
        $text = preg_replace('/<\/?\w+:[^>]*>/i', '', $text);

        // Limpiar saltos de línea y párrafos vacíos al final de forma iterativa
        $prev = '';
        while ($prev !== $text) {
            $prev = $text;
            $text = preg_replace('/(\s*<br\s*\/?>\s*)+$/i', '', $text);
            $text = preg_replace('/(\s*<p[^>]*>(\s*|&nbsp;|<br\s*\/?>)*<\/p>\s*)+$/i', '', $text);
            $text = preg_replace('/(\s*<div[^>]*>(\s*|&nbsp;|<br\s*\/?>)*<\/div>\s*)+$/i', '', $text);
            $text = preg_replace('/(\s*&nbsp;\s*)+$/i', '', $text);
            $text = trim($text);
        }

        return $text;
    }
}
