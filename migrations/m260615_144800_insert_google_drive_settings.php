<?php

use yii\db\Migration;

/**
 * Class m260615_144800_insert_google_drive_settings
 */
class m260615_144800_insert_google_drive_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Intentar leer params.php para obtener los valores actuales
        $paramsFile = Yii::getAlias('@app/config/params.php');
        $params = [];
        if (file_exists($paramsFile)) {
            try {
                $params = require $paramsFile;
            } catch (\Exception $e) {
                // Silenciar
            }
        }

        $googleDrive = $params['googleDrive'] ?? [];
        $clientId = $googleDrive['clientId'] ?? '';
        $clientSecret = $googleDrive['clientSecret'] ?? '';
        $refreshToken = $googleDrive['refreshToken'] ?? '';
        $folderId = $googleDrive['folderId'] ?? '1W-TKUf_2kQ5JbNc9X61iAulweFJ_Zq48';

        // Insertar configuraciones de Google Drive en la tabla system_settings
        $this->batchInsert('{{%system_settings}}',
            ['category', 'key', 'value', 'label', 'description', 'type'],
            [
                [
                    'google_drive',
                    'google_drive_client_id',
                    $clientId,
                    'Google Drive Client ID',
                    'ID del cliente de Google APIs con acceso a Drive.',
                    'text'
                ],
                [
                    'google_drive',
                    'google_drive_client_secret',
                    $clientSecret,
                    'Google Drive Client Secret',
                    'Clave secreta del cliente de Google APIs.',
                    'password'
                ],
                [
                    'google_drive',
                    'google_drive_refresh_token',
                    $refreshToken,
                    'Google Drive Refresh Token',
                    'Token de refresco OAuth2 para generar Access Tokens de Google.',
                    'password'
                ],
                [
                    'google_drive',
                    'google_drive_folder_id',
                    $folderId,
                    'Google Drive Folder ID',
                    'ID de la carpeta en Google Drive donde se subirán los archivos de órdenes de trabajo.',
                    'text'
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%system_settings}}', ['key' => [
            'google_drive_client_id',
            'google_drive_client_secret',
            'google_drive_refresh_token',
            'google_drive_folder_id'
        ]]);
    }
}
