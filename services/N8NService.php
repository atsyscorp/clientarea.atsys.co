<?php

namespace app\services;

use yii\httpclient\Client;
use yii\helpers\Json;

class N8NService
{
    private $webhookUrl = 'https://n8n.atsys.co';

    /**
     * Envía una alerta a n8n para notificar por WhatsApp
     * @param string $phone Número de destino (ej: "573001234567")
     * @param string $message El contenido de la alerta
     * @return bool
     */
    public function sendWhatsappAlert($phone, $message)
    {
        // Timeouts explícitos: el transporte por defecto (StreamTransport) no
        // tiene límite y hereda default_socket_timeout (60s). Un n8n lento
        // colgaría el request hasta que el proxy devuelva 504.
        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport',
            'requestConfig' => [
                'options' => [
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT => 10,
                ],
            ],
        ]);
        $webhookUrl = $this->webhookUrl . '/webhook/atsys-clientarea-alert';

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
            \Yii::error("Error enviando a n8n: " . $e->getMessage());
            return false;
        }
    }

}