<?php

namespace app\helpers;

use Yii;
use app\models\CustomerServices;

class CalendarHelper
{
    /**
     * Determina si un servicio es elegible para agregar a calendario.
     * Solo aplica si el servicio está activo (status = 1) y faltan entre 0 y $maxDays días para el vencimiento.
     *
     * @param CustomerServices $service
     * @param int $maxDays
     * @return bool
     */
    public static function isEligible($service, int $maxDays = 90): bool
    {
        if (!$service || (int)$service->status !== 1) {
            return false;
        }

        $daysLeft = self::getDaysUntilDue($service);
        if ($daysLeft === null) {
            return false;
        }

        return ($daysLeft >= 0 && $daysLeft <= $maxDays);
    }

    /**
     * Calcula los días restantes hasta la fecha de vencimiento.
     *
     * @param CustomerServices $service
     * @return int|null
     */
    public static function getDaysUntilDue($service): ?int
    {
        if (empty($service->next_due_date)) {
            return null;
        }

        try {
            $today = new \DateTime(date('Y-m-d'));
            $dueDate = new \DateTime(substr($service->next_due_date, 0, 10));
            $diff = $today->diff($dueDate);
            return $diff->invert ? -$diff->days : $diff->days;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Obtiene el título descriptivo para el evento de calendario.
     */
    public static function getEventTitle($service): string
    {
        $domain = $service->domain ?: 'Servicio ATSYS';
        $productName = ($service->product && !empty($service->product->name)) ? $service->product->name : 'Servicio';
        return "Renovación: {$domain} ({$productName}) - ATSYS";
    }

    /**
     * Obtiene la descripción detallada del evento.
     */
    public static function getEventDescription($service): string
    {
        $domain = $service->domain ?: 'N/A';
        $productName = ($service->product && !empty($service->product->name)) ? $service->product->name : 'Servicio';
        
        $priceText = '';
        if ($service->product) {
            $priceVal = ($service->product->type === 'hosting') 
                ? $service->product->price 
                : ($service->product->price_renewal ?: $service->product->price);
            if ($priceVal > 0) {
                $priceText = "• Valor Estimado de Renovación: " . Yii::$app->formatter->asCurrency($priceVal) . "\n";
            }
        }

        $dateFormatted = Yii::$app->formatter->asDate($service->next_due_date, 'long');
        $renewUrl = "https://clientarea.atsys.co/customer-services/";

        return "Recordatorio de renovación de servicio en ATSYS.\n\n"
            . "• Servicio: {$domain}\n"
            . "• Plan: {$productName}\n"
            . $priceText
            . "• Fecha Límite: {$dateFormatted}\n\n"
            . "Renueva a tiempo desde tu área de clientes:\n"
            . "{$renewUrl}\n\n"
            . "Si ya realizaste el pago, puedes ignorar este recordatorio.";
    }

    /**
     * Genera la URL para Google Calendar (evento de día completo).
     */
    public static function getGoogleCalendarUrl($service): string
    {
        $dueDateStr = substr($service->next_due_date, 0, 10);
        $start = date('Ymd', strtotime($dueDateStr));
        $end = date('Ymd', strtotime($dueDateStr . ' +1 day'));

        $params = [
            'action' => 'TEMPLATE',
            'text' => self::getEventTitle($service),
            'dates' => "{$start}/{$end}",
            'details' => self::getEventDescription($service),
            'location' => 'Área de Clientes ATSYS (https://clientarea.atsys.co)',
            'sf' => 'true',
            'output' => 'xml'
        ];

        return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
    }

    /**
     * Genera la URL para Outlook Live / Hotmail Web.
     */
    public static function getOutlookLiveUrl($service): string
    {
        $dueDateStr = substr($service->next_due_date, 0, 10);

        $params = [
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => self::getEventTitle($service),
            'body' => self::getEventDescription($service),
            'startdt' => $dueDateStr,
            'enddt' => $dueDateStr,
            'allday' => 'true',
            'location' => 'Área de Clientes ATSYS (https://clientarea.atsys.co)'
        ];

        return 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query($params);
    }

    /**
     * Genera la URL para Office 365 Web.
     */
    public static function getOffice365Url($service): string
    {
        $dueDateStr = substr($service->next_due_date, 0, 10);

        $params = [
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => self::getEventTitle($service),
            'body' => self::getEventDescription($service),
            'startdt' => $dueDateStr,
            'enddt' => $dueDateStr,
            'allday' => 'true',
            'location' => 'Área de Clientes ATSYS (https://clientarea.atsys.co)'
        ];

        return 'https://outlook.office.com/calendar/0/deeplink/compose?' . http_build_query($params);
    }

    /**
     * Genera la URL para Yahoo Calendar.
     */
    public static function getYahooCalendarUrl($service): string
    {
        $dueDateStr = substr($service->next_due_date, 0, 10);
        $st = date('Ymd', strtotime($dueDateStr));
        $et = date('Ymd', strtotime($dueDateStr . ' +1 day'));

        $params = [
            'v' => '60',
            'view' => 'd',
            'type' => '20',
            'title' => self::getEventTitle($service),
            'st' => $st,
            'et' => $et,
            'desc' => self::getEventDescription($service),
            'in_loc' => 'Área de Clientes ATSYS (https://clientarea.atsys.co)'
        ];

        return 'https://calendar.yahoo.com/?' . http_build_query($params);
    }

    /**
     * Escapa cadenas para formato iCalendar RFC 5545.
     */
    private static function escapeIcsText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(';', '\;', $text);
        $text = str_replace(',', '\,', $text);
        $text = str_replace(["\r\n", "\n", "\r"], "\\n", $text);
        return $text;
    }

    /**
     * Genera el contenido de un archivo iCalendar (.ics) estándar con alarmas programadas.
     * Compatible con Apple Calendar, iCal, iPhone, iPad, Outlook de escritorio, etc.
     */
    public static function generateIcsContent($service): string
    {
        $dueDateStr = substr($service->next_due_date, 0, 10);
        $startDate = date('Ymd', strtotime($dueDateStr));
        $endDate = date('Ymd', strtotime($dueDateStr . ' +1 day'));
        $nowUtc = gmdate('Ymd\THis\Z');
        $uid = 'service-renewal-' . $service->id . '-' . strtotime($service->created_at ?: 'now') . '@clientarea.atsys.co';

        $title = self::escapeIcsText(self::getEventTitle($service));
        $description = self::escapeIcsText(self::getEventDescription($service));
        $location = self::escapeIcsText('Área de Clientes ATSYS (https://clientarea.atsys.co)');
        $url = 'https://clientarea.atsys.co/customer-services/';

        $domain = $service->domain ?: 'Servicio';

        $icsLines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//ATSYS//Area de Clientes//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$nowUtc}",
            "DTSTART;VALUE=DATE:{$startDate}",
            "DTEND;VALUE=DATE:{$endDate}",
            "SUMMARY:{$title}",
            "DESCRIPTION:{$description}",
            "LOCATION:{$location}",
            "URL:{$url}",
            'STATUS:CONFIRMED',
            'TRANSP:TRANSPARENT',
            // Alarma 7 días antes
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            "DESCRIPTION:Recordatorio: Tu servicio {$domain} vence en 7 días",
            'TRIGGER:-P7D',
            'END:VALARM',
            // Alarma 3 días antes
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            "DESCRIPTION:Recordatorio: Tu servicio {$domain} vence en 3 días",
            'TRIGGER:-P3D',
            'END:VALARM',
            // Alarma el mismo día a las 09:00 AM (disparador al inicio de fecha)
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            "DESCRIPTION:🚨 HOY vence tu servicio {$domain} en ATSYS",
            'TRIGGER:-PT0M',
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR'
        ];

        return implode("\r\n", $icsLines) . "\r\n";
    }
}
