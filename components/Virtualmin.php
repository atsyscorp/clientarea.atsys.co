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
        $client = new \yii\httpclient\Client();
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
            // Virtualmin devuelve success: 1 si todo salió bien
            return [
                'success' => ($response->data['status'] === 'success'), 
                'message' => $response->data['output'] ?? '',
                'data' => $response->data['data'] ?? null
            ];
        }

        return ['success' => false, 'message' => 'Error de conexión HTTP'];
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