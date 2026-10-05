<?php

namespace app\services;

use yii\httpclient\Client;
use yii\helpers\Json;

class N8NService
{
    private $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('N8N_BASE_URL', 'https://n8n-new.atsys.co'), '/');
    }

    /**
     * Cliente HTTP configurado con CurlTransport y timeouts explícitos
     * @param int $timeout Segundos máximos de espera
     * @return Client
     */
    protected function getHttpClient($timeout = 10)
    {
        return new Client([
            'transport' => 'yii\httpclient\CurlTransport',
            'requestConfig' => [
                'options' => [
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT => $timeout,
                ],
            ],
        ]);
    }

    /**
     * Envía una alerta a n8n para notificar por WhatsApp
     * @param string $phone Número de destino (ej: "573001234567")
     * @param string $message El contenido de la alerta
     * @return bool
     */
    public function sendWhatsappAlert($phone, $message)
    {
        $client = $this->getHttpClient(10);
        $webhookUrl = env('N8N_WEBHOOK_URL', $this->baseUrl . '/webhook/atsys-clientarea-alert');

        try {
            $response = $client->createRequest()
                ->setMethod('POST')
                ->setUrl($webhookUrl)
                ->setData([
                    'phone' => $phone,
                    'message' => $message,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'source' => 'ATSYS-ClientArea'
                ])
                ->send();

            return $response->isOk;
        } catch (\Exception $e) {
            \Yii::error("Error enviando alerta WhatsApp a n8n: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Envía una solicitud a n8n para programar una reunión en Google Calendar con Google Meet
     *
     * @param array $params Datos de la reunión:
     *   - cliente_nombre: (string) Nombre del cliente
     *   - cliente_email: (string) Correo al que llegará la invitación de Google Calendar
     *   - asunto: (string) Título de la reunión
     *   - inicio: (string) Fecha/hora inicio en formato ISO 8601 (ej: 2026-09-28T10:00:00-05:00)
     *   - fin: (string) Fecha/hora fin en formato ISO 8601 (ej: 2026-09-28T10:45:00-05:00)
     *   - descripcion: (string, opcional) Notas o detalles de la sesión
     *   - client_id: (int|null, opcional) ID del cliente en ClientArea
     * @return array|false Devuelve array con 'meet_url', 'event_id', etc. o false en fallo
     */
    public function scheduleMeeting(array $params)
    {
        $client = $this->getHttpClient(15);
        $webhookUrl = $this->baseUrl . '/webhook/crear-reunion';

        try {
            $payload = array_merge([
                'timestamp' => date('c'),
                'source' => 'ATSYS-ClientArea',
            ], $params);

            $response = $client->createRequest()
                ->setMethod('POST')
                ->setUrl($webhookUrl)
                ->setFormat(Client::FORMAT_JSON)
                ->setData($payload)
                ->send();

            if ($response->isOk) {
                return $response->data ?: Json::decode($response->content);
            }

            \Yii::warning(
                "n8n scheduleMeeting falló con estado {$response->statusCode}: {$response->content}",
                __METHOD__
            );
            return false;
        } catch (\Exception $e) {
            \Yii::error("Excepción programando reunión vía n8n: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}