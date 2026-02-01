<?php

namespace app\components;

use Yii;
use yii\base\Component;
use app\models\Servers;

class CyberPanel extends Component
{
    /**
     * Crea una cuenta de hosting completa (Usuario + Website)
     * * @param int $serverId ID del servidor en tu BD
     * @param string $domain Dominio a crear
     * @param string $package Nombre del paquete en CyberPanel (ej: 'Default')
     * @param string $ownerEmail Email del cliente
     * @param string $password Contraseña para el panel
     * @param string $username Nombre de usuario deseado (ej: cliente_atsys)
     * @return array Resultado ['success' => bool, 'message' => string]
     */
    public static function createAccount($serverId, $domain, $package, $ownerEmail, $password, $username)
    {
        $server = Servers::findOne($serverId);
        if (!$server) {
            return ['success' => false, 'message' => 'Servidor no encontrado en BD.'];
        }

        // 1. URL Base de la API
        $baseUrl = "https://{$server->hostname}:8090/api/";

        // 2. Datos comunes de autenticación (Admin Token/Pass)
        // CyberPanel suele usar el usuario admin y su clave para la API
        $authData = [
            'adminUser' => $server->username, // Generalmente 'admin'
            'adminPass' => $server->auth_token // Aquí va la contraseña del admin o token
        ];

        // ---------------------------------------------------------
        // PASO A: Crear el Sitio Web (CyberPanel crea el usuario implícitamente si se configura o se usa ACL)
        // ---------------------------------------------------------
        // Nota: La API 'createWebsite' de CyberPanel es la más robusta.
        $payload = array_merge($authData, [
            'domainName' => $domain,
            'ownerEmail' => $ownerEmail,
            'packageName' => $package,
            'websiteOwner' => $username, // El usuario que será dueño
            'ownerPassword' => $password, // Su contraseña
            'phpSelection' => 'PHP 8.1', // Versión por defecto (puedes parametrizarla)
            'acl' => 'user' // Define permisos (user/reseller/admin)
        ]);

        $result = self::sendRequest($baseUrl . 'createWebsite', $payload);

        // Analizar respuesta
        if ($result['status'] == 1) {
            return ['success' => true, 'message' => 'Cuenta creada exitosamente.'];
        } else {
            // Manejo de errores comunes (ej: "Domain already exists")
            return ['success' => false, 'message' => 'Error API: ' . ($result['error_message'] ?? json_encode($result))];
        }
    }

    /**
     * Suspende un sitio web (Por falta de pago)
     */
    public static function suspendAccount($serverId, $domain)
    {
        $server = Servers::findOne($serverId);
        if (!$server) return false;

        $baseUrl = "https://{$server->hostname}:8090/api/";
        
        $payload = [
            'adminUser' => $server->username,
            'adminPass' => $server->auth_token,
            'websiteName' => $domain,
            'state' => 'Suspend' // O 'Unsuspend' según la acción
        ];

        $result = self::sendRequest($baseUrl . 'submitWebsiteStatus', $payload);
        Yii::error($result);
        return ($result['websiteStatus'] == 1);
    }

    /**
     * Reactiva un sitio web (Tras renovación)
     */
    public static function unsuspendAccount($serverId, $domain)
    {
        $server = Servers::findOne($serverId);
        if (!$server) return false;

        $baseUrl = "https://{$server->hostname}:8090/api/";
        
        $payload = [
            'adminUser' => $server->username,
            'adminPass' => $server->auth_token,
            'websiteName' => $domain,
            'state' => 'Unsuspend'
        ];

        $result = self::sendRequest($baseUrl . 'submitWebsiteStatus', $payload);
        Yii::error($result);
        return ($result['websiteStatus'] == 1);
    }

    /**
     * Función auxiliar para cURL
     */
    private static function sendRequest($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Deshabilitar verificación SSL si usas self-signed en tu VPS (común en CyberPanel)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Decodificar JSON
        $json = json_decode($response, true);
        
        if (!$json) {
            return ['status' => 0, 'error_message' => "Error de conexión HTTP $httpCode: $response"];
        }

        return $json;
    }
}