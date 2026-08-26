<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\httpclient\Client; // Requiere yiisoft/yii2-httpclient

class Virtualmin extends Component
{
    public $apiUrl; // ej: 'https://nexus01.tudominio.com:10000/virtual-server/remote.cgi'
    public $apiUser;
    public $apiPassword;

    public function sendCommandDynamic($user, $pass, $host, $program, $params = [])
    {
        // Timeouts explícitos: el transporte por defecto (StreamTransport) no
        // tiene límite y hereda default_socket_timeout (60s). Un servidor
        // Virtualmin lento o caído colgaría el request web hasta el 504.
        // El margen es más amplio que en otras llamadas porque algunas
        // operaciones de aprovisionamiento tardan.
        $client = new \yii\httpclient\Client([
            'transport' => 'yii\httpclient\CurlTransport',
            'requestConfig' => [
                'options' => [
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT => 30,
                ],
            ],
        ]);
        // Construcción de la URL remota de Virtualmin
        $url = "https://{$host}:10000/virtual-server/remote.cgi?program={$program}&json=1";

        foreach ($params as $key => $value) {
            $url .= "&" . urlencode($key) . "=" . urlencode($value);
        }

        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($url)
            ->addHeaders(['Authorization' => 'Basic ' . base64_encode($user . ':' . $pass)])
            ->send();

        if ($response->isOk) {
            // Si la respuesta no es un array, puede que no se haya parseado el JSON
            if (!is_array($response->data)) {
                return [
                    'success' => false,
                    'message' => 'Respuesta no es JSON: ' . $response->content,
                    'data' => null
                ];
            }

            // Virtualmin devuelve success: 1 si todo salió bien
            return [
                'success' => (isset($response->data['status']) && $response->data['status'] === 'success'), 
                'message' => $response->data['output'] ?? $response->data['error'] ?? '',
                'data' => $response->data['data'] ?? null,
                'raw' => $response->data
            ];
        }

        return ['success' => false, 'message' => 'Error de conexión HTTP: ' . $response->statusCode . ' - ' . $response->content];
    }

    private function sendCommand($program, $params = [])
    {
        return $this->sendCommandDynamic(
            $this->apiUser,
            $this->apiPassword,
            str_replace(['https://', ':10000/virtual-server/remote.cgi'], '', $this->apiUrl), // Extrae solo el host
            $program,
            $params
        );
    }

    // Crear una nueva cuenta aislada (con PHP-FPM)
    public function createAccount($domain, $password, $plan = 'default')
    {
        return $this->sendCommand('create-domain', [
            'domain' => $domain,
            'pass' => $password,
            'plan' => $plan,
            'features-from-plan' => '' // Aplica las características del plan (FPM, SSL, etc.)
        ]);
    }

    // Suspender la cuenta
    public function suspendAccount($domain)
    {
        return $this->sendCommand('disable-domain', [
            'domain' => $domain
        ]);
    }

    // Eliminar la cuenta definitivamente
    public function deleteAccount($domain)
    {
        return $this->sendCommand('delete-domain', [
            'domain' => $domain
        ]);
    }
}