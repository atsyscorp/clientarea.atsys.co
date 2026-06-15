<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Este es el modelo para la tabla "system_settings".
 *
 * @property int $id
 * @property string $category
 * @property string $key
 * @property string|null $value
 * @property string $label
 * @property string|null $description
 * @property string $type
 * @property string|null $updated_at
 */
class SystemSettings extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'system_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category', 'key', 'label'], 'required'],
            [['value'], 'string'],
            [['updated_at'], 'safe'],
            [['category', 'type'], 'string', 'max' => 50],
            [['key'], 'string', 'max' => 100],
            [['label', 'description'], 'string', 'max' => 255],
            [['key'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category' => 'Categoría',
            'key' => 'Clave',
            'value' => 'Valor',
            'label' => 'Nombre',
            'description' => 'Descripción',
            'type' => 'Tipo',
            'updated_at' => 'Última Actualización',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->updated_at = date('Y-m-d H:i:s');
            return true;
        }
        return false;
    }

    /**
     * Carga todas las configuraciones del sistema dinámicamente en Yii::$app->params.
     * Si la tabla no existe, la crea automáticamente y la puebla con valores iniciales.
     */
    public static function loadToParams()
    {
        try {
            $db = Yii::$app->db;
            if ($db->getTableSchema('system_settings', true) === null) {
                // Crear tabla si no existe (autoinstalación)
                $db->createCommand()->createTable('system_settings', [
                    'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                    'category' => 'VARCHAR(50) NOT NULL',
                    'key' => 'VARCHAR(100) NOT NULL UNIQUE',
                    'value' => 'TEXT NULL',
                    'label' => 'VARCHAR(255) NOT NULL',
                    'description' => 'VARCHAR(255) NULL',
                    'type' => "VARCHAR(30) NOT NULL DEFAULT 'text'",
                    'updated_at' => 'DATETIME NULL',
                ])->execute();

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

                $wompiPubKey = $params['wmpi_pubKey'] ?? 'pub_prod_UbGVrJOt3EZ6xBKQaPy8lah9pFQchr0T';
                $wompiIntegrity = $params['wmpi_integrity'] ?? 'prod_integrity_qGF2hvg6bUCrUAY2qEK7yefE5soM5JZ0';
                $paypalClientId = $params['paypalClientId'] ?? '';
                $paypalSecret = $params['paypalSecret'] ?? '';
                $paypalMode = $params['paypalMode'] ?? 'live';
                $whoisKey = $params['whois']['key'] ?? '7f041f7fd72736886ea4bfffa0e8dcec9e32fde4069065bde0b18622310bf0be';

                $googleDrive = $params['googleDrive'] ?? [];
                $gdClientId = $googleDrive['clientId'] ?? '';
                $gdClientSecret = $googleDrive['clientSecret'] ?? '';
                $gdRefreshToken = $googleDrive['refreshToken'] ?? '';
                $gdFolderId = $googleDrive['folderId'] ?? '1W-TKUf_2kQ5JbNc9X61iAulweFJ_Zq48';

                // Sembrar valores iniciales
                $db->createCommand()->batchInsert('system_settings',
                    ['category', 'key', 'value', 'label', 'description', 'type'],
                    [
                        [
                            'tickets', 
                            'ticket_hours_to_close', 
                            '48', 
                            'Límite de Horas para Cerrar Ticket', 
                            'Tiempo límite de inactividad del cliente antes de cerrar automáticamente un ticket (en horas).', 
                            'number'
                        ],
                        [
                            'tickets', 
                            'ticket_max_pending', 
                            '4', 
                            'Límite de Tickets Pendientes', 
                            'Número máximo de tickets en proceso (abiertos o en progreso) que un cliente puede tener antes de restringir la creación de nuevos tickets.', 
                            'number'
                        ],
                        [
                            'paypal', 
                            'paypalClientId', 
                            $paypalClientId, 
                            'PayPal Client ID', 
                            'Identificador del cliente para la pasarela de PayPal.', 
                            'text'
                        ],
                        [
                            'paypal', 
                            'paypalSecret', 
                            $paypalSecret, 
                            'PayPal Secret Key', 
                            'Clave secreta para la pasarela de PayPal.', 
                            'password'
                        ],
                        [
                            'paypal', 
                            'paypalMode', 
                            $paypalMode, 
                            'PayPal Mode', 
                            'Modo de ejecución para PayPal: sandbox (pruebas) o live (producción).', 
                            'text'
                        ],
                        [
                            'wompi', 
                            'wmpi_pubKey', 
                            $wompiPubKey, 
                            'Wompi Public Key', 
                            'Clave pública de conexión a la pasarela Wompi.', 
                            'text'
                        ],
                        [
                            'wompi', 
                            'wmpi_integrity', 
                            $wompiIntegrity, 
                            'Wompi Integrity Secret', 
                            'Clave de integridad (secreto) para la firma de Wompi.', 
                            'password'
                        ],
                        [
                            'whois',
                            'whois_key',
                            $whoisKey,
                            'Whois JSON API Key',
                            'Clave de API de WhoisJSON para la consulta de disponibilidad de dominios.',
                            'password'
                        ],
                        [
                            'google_drive',
                            'google_drive_client_id',
                            $gdClientId,
                            'Google Drive Client ID',
                            'ID del cliente de Google APIs con acceso a Drive.',
                            'text'
                        ],
                        [
                            'google_drive',
                            'google_drive_client_secret',
                            $gdClientSecret,
                            'Google Drive Client Secret',
                            'Clave secreta del cliente de Google APIs.',
                            'password'
                        ],
                        [
                            'google_drive',
                            'google_drive_refresh_token',
                            $gdRefreshToken,
                            'Google Drive Refresh Token',
                            'Token de refresco OAuth2 para generar Access Tokens de Google.',
                            'password'
                        ],
                        [
                            'google_drive',
                            'google_drive_folder_id',
                            $gdFolderId,
                            'Google Drive Folder ID',
                            'ID de la carpeta en Google Drive donde se subirán los archivos de órdenes de trabajo.',
                            'text'
                        ],
                        [
                            'tickets',
                            'ticket_hours_sla',
                            '24',
                            'Horas Límite de SLA',
                            'Tiempo límite de respuesta para ATSYS antes de disparar una alerta de riesgo SLA (en horas).',
                            'number'
                        ],
                        [
                            'tickets',
                            'n8n_admin_push_url',
                            'https://n8n-new.atsys.co/webhook/send-admin-push',
                            'Webhook N8N Admin Push',
                            'URL del Webhook de N8N utilizado para enviar notificaciones Push a los dispositivos de los administradores.',
                            'text'
                        ]
                    ]
                )->execute();
            }

            $settings = self::find()->all();
            foreach ($settings as $setting) {
                if ($setting->key === 'whois_key') {
                    Yii::$app->params['whois']['key'] = $setting->value;
                } elseif (strpos($setting->key, 'google_drive_') === 0) {
                    $propMap = [
                        'google_drive_client_id' => 'clientId',
                        'google_drive_client_secret' => 'clientSecret',
                        'google_drive_refresh_token' => 'refreshToken',
                        'google_drive_folder_id' => 'folderId',
                    ];
                    
                    if (isset($propMap[$setting->key])) {
                        $prop = $propMap[$setting->key];
                        // Inyectar en el componente googleDrive si está registrado
                        if (Yii::$app->has('googleDrive')) {
                            try {
                                Yii::$app->get('googleDrive')->$prop = $setting->value;
                            } catch (\Exception $ex) {
                                Yii::error("Error setting Google Drive property: " . $ex->getMessage());
                            }
                        }
                        
                        // Convertir a la estructura camelCase esperada en Yii::$app->params
                        $paramKey = str_replace('google_drive_', '', $setting->key);
                        if ($paramKey === 'client_id') $paramKey = 'clientId';
                        elseif ($paramKey === 'client_secret') $paramKey = 'clientSecret';
                        elseif ($paramKey === 'refresh_token') $paramKey = 'refreshToken';
                        elseif ($paramKey === 'folder_id') $paramKey = 'folderId';
                        
                        Yii::$app->params['googleDrive'][$paramKey] = $setting->value;
                    }
                } else {
                    Yii::$app->params[$setting->key] = $setting->value;
                }
            }
        } catch (\Exception $e) {
            Yii::error("Error al cargar o inicializar configuraciones dinámicas: " . $e->getMessage(), __METHOD__);
        }
    }
}
