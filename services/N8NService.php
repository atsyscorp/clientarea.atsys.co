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
        $client = new Client();
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