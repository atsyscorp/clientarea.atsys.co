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

    private $_cachedAccessToken = null;
    private $_folderCache = [];

    /**
     * Sube un archivo a Google Drive.
     * Si no hay credenciales configuradas, hace fallback al hosting local de forma segura.
     * @param \yii\web\UploadedFile $file
     * @param string|null $subfolderName
     * @param string $category 'work-orders' o 'tickets'
     * @return string|null URL del archivo (Drive o Local)
     */
    public function upload($file, $subfolderName = null, $category = 'work-orders')
    {
        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->refreshToken)) {
            Yii::warning("Google Drive credentials not configured. Falling back to local storage.");
            return $this->uploadLocal($file, $subfolderName, $category);
        }

        try {
            // 1. Obtener Token de Acceso
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                Yii::error("Failed to obtain Google Access Token. Using local fallback.");
                return $this->uploadLocal($file, $subfolderName, $category);
            }

            // 1.5. Resolver ID de carpeta padre si se requiere subcarpeta
            $parentFolderId = $this->folderId;
            if (!empty($subfolderName) && !empty($parentFolderId)) {
                $cacheKey = $parentFolderId . '_' . $subfolderName;
                if (isset($this->_folderCache[$cacheKey])) {
                    $parentFolderId = $this->_folderCache[$cacheKey];
                } else {
                    $subFolderId = $this->findFolder($subfolderName, $parentFolderId, $accessToken);
                    if (!$subFolderId) {
                        $subFolderId = $this->createFolder($subfolderName, $parentFolderId, $accessToken);
                    }
                    if ($subFolderId) {
                        $this->_folderCache[$cacheKey] = $subFolderId;
                        $parentFolderId = $subFolderId;
                    }
                }
            }

            // Si el archivo supera 5MB, usar subida resumible para evitar límites de multipart y optimizar memoria
            $fileSize = ($file->tempName && file_exists($file->tempName)) ? filesize($file->tempName) : $file->size;
            if ($fileSize > 5 * 1024 * 1024) {
                $resumableUrl = $this->uploadResumable($file, $parentFolderId, $accessToken);
                if ($resumableUrl) {
                    return $resumableUrl;
                }
            }

            // 2. Subida Multipart estándar para archivos <= 5MB (Metadatos + Contenido)
            $metadata = [
                'name' => $file->name,
                'parents' => !empty($parentFolderId) ? [$parentFolderId] : []
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300 && isset($result['id'])) {
                $fileId = $result['id'];

                // 3. Compartir para que cualquier persona con el link pueda ver el archivo
                $this->makeFilePublic($fileId, $accessToken);

                // Devolvemos el enlace web de visualización
                return $result['webViewLink'] ?? "https://drive.google.com/file/d/{$fileId}/view?usp=drivesdk";
            }

            Yii::error("Google Drive upload API failed (HTTP {$httpCode}): " . $response);

        } catch (\Exception $e) {
            Yii::error("Exception during Google Drive upload: " . $e->getMessage());
        }

        return $this->uploadLocal($file, $subfolderName, $category);
    }

    /**
     * Sube un archivo a Google Drive usando Resumable Upload (para archivos grandes hasta 50MB+)
     */
    private function uploadResumable($file, $parentFolderId, $accessToken)
    {
        try {
            $metadata = [
                'name' => $file->name,
                'parents' => !empty($parentFolderId) ? [$parentFolderId] : []
            ];

            $fileSize = ($file->tempName && file_exists($file->tempName)) ? filesize($file->tempName) : $file->size;
            $mimeType = !empty($file->type) ? $file->type : 'application/octet-stream';

            // 1. Iniciar sesión resumible
            $initHeaders = [
                "Authorization: Bearer {$accessToken}",
                "Content-Type: application/json; charset=UTF-8",
                "X-Upload-Content-Type: {$mimeType}",
                "X-Upload-Content-Length: {$fileSize}"
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&fields=id,webViewLink');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($metadata));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $initHeaders);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);

            if ($httpCode !== 200) {
                Yii::error("Failed to initiate Google Drive resumable upload (HTTP {$httpCode}): " . substr($response, $headerSize));
                return null;
            }

            $headers = substr($response, 0, $headerSize);
            if (!preg_match('/location:\s*(https?:\/\/[^\r\n]+)/i', $headers, $matches)) {
                Yii::error("Google Drive resumable upload did not return Location header.");
                return null;
            }

            $uploadSessionUrl = trim($matches[1]);

            // 2. Subir el archivo mediante PUT a la URL de sesión
            $fp = fopen($file->tempName, 'rb');
            if (!$fp) {
                Yii::error("Failed to open temp file for resumable upload: {$file->tempName}");
                return null;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $uploadSessionUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_INFILE, $fp);
            curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);
            curl_setopt($ch, CURLOPT_UPLOAD, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: {$mimeType}",
                "Content-Length: {$fileSize}"
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutos

            $putResponse = curl_exec($ch);
            $putHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (is_resource($fp)) {
                fclose($fp);
            }

            $result = json_decode($putResponse, true);
            if (($putHttpCode === 200 || $putHttpCode === 201) && isset($result['id'])) {
                $fileId = $result['id'];
                $this->makeFilePublic($fileId, $accessToken);
                return $result['webViewLink'] ?? "https://drive.google.com/file/d/{$fileId}/view?usp=drivesdk";
            }

            Yii::error("Google Drive resumable upload PUT failed (HTTP {$putHttpCode}): " . $putResponse);
        } catch (\Exception $e) {
            Yii::error("Exception in Google Drive uploadResumable: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Obtiene un token de acceso fresco usando el Refresh Token.
     */
    private function getAccessToken()
    {
        if ($this->_cachedAccessToken !== null) {
            return $this->_cachedAccessToken;
        }

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
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['access_token'])) {
            $this->_cachedAccessToken = $result['access_token'];
            return $this->_cachedAccessToken;
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
     * Busca una carpeta por nombre dentro de una carpeta padre en Google Drive.
     */
    private function findFolder($name, $parentFolderId, $accessToken)
    {
        $q = "mimeType = 'application/vnd.google-apps.folder' and name = '" . str_replace("'", "\\'", $name) . "' and '{$parentFolderId}' in parents and trashed = false";
        $url = 'https://www.googleapis.com/drive/v3/files?q=' . urlencode($q) . '&fields=files(id)';

        $headers = [
            "Authorization: Bearer {$accessToken}",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($httpCode === 200 && !empty($result['files'])) {
            return $result['files'][0]['id'];
        }

        return null;
    }

    /**
     * Crea una carpeta en Google Drive.
     */
    private function createFolder($name, $parentFolderId, $accessToken)
    {
        $url = 'https://www.googleapis.com/drive/v3/files?fields=id';
        $payload = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
        ];
        if (!empty($parentFolderId)) {
            $payload['parents'] = [$parentFolderId];
        }

        $headers = [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && isset($result['id'])) {
            // Hacemos pública la carpeta creada también para evitar problemas de visualización
            $this->makeFilePublic($result['id'], $accessToken);
            return $result['id'];
        }

        return null;
    }

    /**
     * Método de respaldo para guardar el archivo localmente en el servidor
     */
    private function uploadLocal($file, $subfolderName = null, $category = 'work-orders')
    {
        $safeCategory = preg_replace('/[^a-zA-Z0-9_\-]/', '', $category) ?: 'work-orders';
        $path = '@webroot/uploads/' . $safeCategory;
        $safeSubfolder = '';
        if (!empty($subfolderName)) {
            $safeSubfolder = preg_replace('/[^a-zA-Z0-9_\-]/', '', $subfolderName);
            if (!empty($safeSubfolder)) {
                $path .= '/' . $safeSubfolder;
            }
        }

        $uploadsDir = Yii::getAlias($path);
        if (!is_dir($uploadsDir)) {
            FileHelper::createDirectory($uploadsDir, 0777);
        }

        $prefix = ($safeCategory === 'tickets') ? 'tkt_' : 'wo_';
        $filename = uniqid($prefix, true) . '.' . $file->extension;
        $filepath = $uploadsDir . '/' . $filename;

        if ($file->saveAs($filepath)) {
            $webPath = '/uploads/' . $safeCategory . '/';
            if (!empty($safeSubfolder)) {
                $webPath .= $safeSubfolder . '/';
            }
            return Yii::$app->request->hostInfo . $webPath . $filename;
        }

        Yii::error("Failed to save local file upload.");
        return null;
    }
}
