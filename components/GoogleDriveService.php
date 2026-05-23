<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\helpers\FileHelper;

class GoogleDriveService extends Component
{
    public $clientId;
    public $clientSecret;
    public $refreshToken;
    public $folderId;

    /**
     * Sube un archivo a Google Drive.
     * Si no hay credenciales configuradas, hace fallback al hosting local de forma segura.
     * @param \yii\web\UploadedFile $file
     * @return string URL del archivo (Drive o Local)
     */
    public function upload($file)
    {
        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->refreshToken)) {
            Yii::warning("Google Drive credentials not configured. Falling back to local storage.");
            return $this->uploadLocal($file);
        }

        try {
            // 1. Obtener Token de Acceso
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                Yii::error("Failed to obtain Google Access Token. Using local fallback.");
                return $this->uploadLocal($file);
            }

            // 2. Subida Multipart (Metadatos + Contenido)
            $metadata = [
                'name' => $file->name,
                'parents' => !empty($this->folderId) ? [$this->folderId] : []
            ];

            $boundary = '-------' . md5(time());
            $multipartData = "--{$boundary}\r\n" .
                "Content-Type: application/json; charset=UTF-8\r\n\r\n" .
                json_encode($metadata) . "\r\n" .
                "--{$boundary}\r\n" .
                "Content-Type: {$file->type}\r\n\r\n" .
                file_get_contents($file->tempName) . "\r\n" .
                "--{$boundary}--";

            $headers = [
                "Authorization: Bearer {$accessToken}",
                "Content-Type: multipart/related; boundary={$boundary}",
                "Content-Length: " . strlen($multipartData)
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,webViewLink');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $multipartData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300 && isset($result['id'])) {
                $fileId = $result['id'];

                // 3. Compartir para que cualquier persona con el link pueda ver el archivo
                $this->makeFilePublic($fileId, $accessToken);

                // Devolvemos el enlace web de visualización
                return $result['webViewLink'] ?? "https://drive.google.com/open?id={$fileId}";
            }

            Yii::error("Google Drive upload API failed (HTTP {$httpCode}): " . $response);

        } catch (\Exception $e) {
            Yii::error("Exception during Google Drive upload: " . $e->getMessage());
        }

        return $this->uploadLocal($file);
    }

    /**
     * Obtiene un token de acceso fresco usando el Refresh Token.
     */
    private function getAccessToken()
    {
        $params = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['access_token'])) {
            return $result['access_token'];
        }

        Yii::error("Failed to refresh Google token (HTTP {$httpCode}): " . $response);
        return null;
    }

    /**
     * Da permiso de lector a cualquiera con el enlace.
     */
    private function makeFilePublic($fileId, $accessToken)
    {
        $payload = [
            'role' => 'reader',
            'type' => 'anyone'
        ];

        $headers = [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/drive/v3/files/{$fileId}/permissions");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Método de respaldo para guardar el archivo localmente en el servidor
     */
    private function uploadLocal($file)
    {
        $uploadsDir = Yii::getAlias('@webroot/uploads/work-orders');
        if (!is_dir($uploadsDir)) {
            FileHelper::createDirectory($uploadsDir, 0777);
        }

        $filename = uniqid('wo_', true) . '.' . $file->extension;
        $filepath = $uploadsDir . '/' . $filename;

        if ($file->saveAs($filepath)) {
            // Devolvemos la URL absoluta o relativa para web
            return Yii::$app->request->hostInfo . '/uploads/work-orders/' . $filename;
        }

        Yii::error("Failed to save local file upload.");
        return null;
    }
}
