<?php

namespace app\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\httpclient\Client;
use Yii;

/**
 * Clase encargada de procesar el envío a n8n en segundo plano
 */
class WhatsappJob extends BaseObject implements JobInterface
{
    public $phone;
    public $message;
    
    // URL de tu Webhook en n8n
    public $webhookUrl = 'https://n8n.atsys.co/webhook/atsys-clientarea-alert';

    /**
     * Este es el método que ejecuta el Worker de Supervisor
     */
    public function execute($queue)
    {
        $client = new Client();
        
        try {
            $response = $client->createRequest()
                ->setMethod('POST')
                ->setUrl($this->webhookUrl)
                ->setData([
                    'phone' => $this->phone,
                    'message' => $this->message,
                    'sent_at' => date('Y-m-d H:i:s'),
                ])
                ->send();

            if (!$response->isOk) {
                // Si n8n responde error, lanzamos excepción para que la cola reintente
                throw new \Exception("Error en n8n: " . $response->content);
            }

            Yii::info("WhatsApp enviado con éxito a: " . $this->phone, 'whatsapp');
            
        } catch (\Exception $e) {
            Yii::error("Fallo al enviar WhatsApp: " . $e->getMessage(), 'whatsapp');
            // Al lanzar la excepción, Yii2 Queue reintentará según tu config
            throw $e; 
        }
    }
}