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
     * Envía una solicitud a n8n para programar una reunión en Google Calendar con Google Meet.
     * Puede invocarse de manera estática:
     *   N8NService::scheduleMeeting($meeting);
     * O mediante instancia:
     *   $n8n = new N8NService();
     *   $n8n->scheduleMeeting($params);
     *
     * @param array|\app\models\Meetings $params Datos de la reunión en array o modelo Meetings:
     *   - cliente_nombre: (string) Nombre del cliente
     *   - cliente_email: (string) Correo al que llegará la invitación de Google Calendar
     *   - asunto: (string) Título de la reunión
     *   - inicio: (string) Fecha/hora inicio en formato ISO 8601 (ej: 2026-09-28T10:00:00-05:00)
     *   - fin: (string) Fecha/hora fin en formato ISO 8601 (ej: 2026-09-28T10:45:00-05:00)
     *   - descripcion: (string, opcional) Notas o detalles de la sesión
     *   - client_id: (int|null, opcional) ID del cliente en ClientArea
     * @return array Devuelve array con 'success' => bool, 'meet_url', 'event_id', etc. o 'error' en caso de fallo
     */
    public static function scheduleMeeting($params)
    {
        $instance = new self();
        return $instance->processScheduleMeeting($params);
    }

    /**
     * Procesa internamente el envío del webhook a n8n para programar la reunión
     *
     * @param array|\app\models\Meetings $params
     * @return array
     */
    public function processScheduleMeeting($params)
    {
        if ($params instanceof \app\models\Meetings) {
            $startTimestamp = strtotime($params->start_time);
            $endTimestamp = strtotime($params->end_time);

            $payloadData = [
                'client_id'      => $params->customer_id,
                'cliente_nombre' => $params->client_name,
                'cliente_email'  => $params->client_email,
                'asunto'         => $params->title,
                'descripcion'    => $params->description,
                'inicio'         => date('c', $startTimestamp),
                'fin'            => date('c', $endTimestamp),
            ];
        } elseif (is_array($params)) {
            $payloadData = $params;
        } else {
            return [
                'success' => false,
                'error' => 'Parámetros no válidos proporcionados para programar la reunión.',
            ];
        }

        $client = $this->getHttpClient(15);
        $webhookUrl = $this->baseUrl . '/webhook/crear-reunion';

        try {
            $payload = array_merge([
                'timestamp' => date('c'),
                'source' => 'ATSYS-ClientArea',
            ], $payloadData);

            $response = $client->createRequest()
                ->setMethod('POST')
                ->setUrl($webhookUrl)
                ->setFormat(Client::FORMAT_JSON)
                ->setData($payload)
                ->send();

            if ($response->isOk) {
                $data = $response->data ?: Json::decode($response->content);
                if (is_array($data)) {
                    if (!isset($data['success'])) {
                        $data['success'] = !empty($data['meet_url']) || !empty($data['event_id']);
                    }
                    return $data;
                }
                return [
                    'success' => false,
                    'error' => 'La respuesta devuelta por n8n no tiene un formato válido.',
                ];
            }

            $errorMsg = "n8n scheduleMeeting falló con estado {$response->statusCode}: {$response->content}";
            \Yii::warning($errorMsg, __METHOD__);
            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        } catch (\Exception $e) {
            \Yii::error("Excepción programando reunión vía n8n: " . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envía una solicitud a n8n para actualizar una reunión existente en Google Calendar.
     *
     * @param \app\models\Meetings|array $params
     * @return array
     */
    public static function updateMeeting($params)
    {
        $instance = new self();
        return $instance->processUpdateMeeting($params);
    }

    /**
     * Procesa internamente el webhook de actualización de reunión hacia n8n
     *
     * @param \app\models\Meetings|array $params
     * @return array
     */
    public function processUpdateMeeting($params)
    {
        if ($params instanceof \app\models\Meetings) {
            $startTimestamp = strtotime($params->start_time);
            $endTimestamp = strtotime($params->end_time);

            $payloadData = [
                'action'         => 'update',
                'event_id'       => $params->google_event_id,
                'google_event_id'=> $params->google_event_id,
                'client_id'      => $params->customer_id,
                'cliente_nombre' => $params->client_name,
                'cliente_email'  => $params->client_email,
                'asunto'         => $params->title,
                'descripcion'    => $params->description,
                'inicio'         => date('c', $startTimestamp),
                'fin'            => date('c', $endTimestamp),
            ];
        } elseif (is_array($params)) {
            $payloadData = $params;
        } else {
            return [
                'success' => false,
                'error' => 'Parámetros no válidos para actualizar la reunión.',
            ];
        }

        $client = $this->getHttpClient(15);
        $webhookUrl = env('N8N_UPDATE_MEETING_WEBHOOK_URL', $this->baseUrl . '/webhook/actualizar-reunion');

        try {
            $payload = array_merge([
                'timestamp' => date('c'),
                'source' => 'ATSYS-ClientArea',
            ], $payloadData);

            $response = $client->createRequest()
                ->setMethod('POST')
                ->setUrl($webhookUrl)
                ->setFormat(Client::FORMAT_JSON)
                ->setData($payload)
                ->send();

            if ($response->isOk) {
                $data = $response->data ?: Json::decode($response->content);
                if (is_array($data)) {
                    if (!isset($data['success'])) {
                        $data['success'] = true;
                    }
                    return $data;
                }
                return ['success' => true];
            }

            $errorMsg = "n8n updateMeeting respondió con estado {$response->statusCode}: {$response->content}";
            \Yii::warning($errorMsg, __METHOD__);
            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        } catch (\Exception $e) {
            \Yii::warning("Excepción actualizando reunión vía n8n: " . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}