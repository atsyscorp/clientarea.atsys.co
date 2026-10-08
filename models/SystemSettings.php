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
                        ],
                        [
                            'work_orders',
                            'work_order_expiration_days',
                            '5',
                            'Días de Vigencia de Órdenes de Trabajo',
                            'Número de días calendario de vigencia para una orden de trabajo pendiente antes de expirar por inactividad o falta de aprobación.',
                            'number'
                        ],
                        [
                            'meetings',
                            'meetings_schedule',
                            json_encode(self::getDefaultMeetingSchedule(), JSON_UNESCAPED_UNICODE),
                            'Horario de Atención para Citas',
                            'Configuración de días hábiles y horarios de atención al cliente para solicitudes de reuniones virtuales (estilo WhatsApp Business).',
                            'schedule'
                        ]
                    ]
                )->execute();
            }

            // Asegurar que la configuración del horario de citas exista si la tabla ya fue creada
            $hasSchedule = self::find()->where(['key' => 'meetings_schedule'])->exists();
            if (!$hasSchedule) {
                try {
                    $defaultScheduleJson = json_encode(self::getDefaultMeetingSchedule(), JSON_UNESCAPED_UNICODE);
                    $db->createCommand()->insert('system_settings', [
                        'category' => 'meetings',
                        'key' => 'meetings_schedule',
                        'value' => $defaultScheduleJson,
                        'label' => 'Horario de Atención para Citas',
                        'description' => 'Configuración de días hábiles y horarios de atención al cliente para solicitudes de reuniones virtuales (estilo WhatsApp Business).',
                        'type' => 'schedule',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ])->execute();
                } catch (\Exception $exSchedule) {
                    Yii::error("Error auto-inserting meetings_schedule: " . $exSchedule->getMessage());
                }
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

    /**
     * Retorna la plantilla predeterminada del horario comercial de atención (estilo WhatsApp).
     * Claves: 1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado, 0=Domingo.
     * Mismo índice que date('w') en PHP y getDay() en JavaScript.
     *
     * @return array
     */
    public static function getDefaultMeetingSchedule()
    {
        return [
            '1' => ['enabled' => true,  'start' => '08:00', 'end' => '17:00', 'name' => 'Lunes',     'short' => 'Lun'],
            '2' => ['enabled' => true,  'start' => '08:00', 'end' => '17:00', 'name' => 'Martes',    'short' => 'Mar'],
            '3' => ['enabled' => true,  'start' => '08:00', 'end' => '17:00', 'name' => 'Miércoles', 'short' => 'Mié'],
            '4' => ['enabled' => true,  'start' => '08:00', 'end' => '17:00', 'name' => 'Jueves',    'short' => 'Jue'],
            '5' => ['enabled' => true,  'start' => '08:00', 'end' => '17:00', 'name' => 'Viernes',   'short' => 'Vie'],
            '6' => ['enabled' => false, 'start' => '08:00', 'end' => '12:00', 'name' => 'Sábado',    'short' => 'Sáb'],
            '0' => ['enabled' => false, 'start' => '08:00', 'end' => '12:00', 'name' => 'Domingo',   'short' => 'Dom'],
        ];
    }

    /**
     * Obtiene el horario de atención configurado en el sistema para citas y reuniones.
     * Si no existe o tiene datos inválidos, retorna el horario predeterminado.
     *
     * @return array
     */
    public static function getMeetingSchedule()
    {
        $raw = Yii::$app->params['meetings_schedule'] ?? null;
        if (empty($raw)) {
            try {
                $row = self::findOne(['key' => 'meetings_schedule']);
                if ($row && !empty($row->value)) {
                    $raw = $row->value;
                }
            } catch (\Exception $e) {
                // Silenciar
            }
        }

        $default = self::getDefaultMeetingSchedule();

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($default as $dayKey => $data) {
                    if (isset($decoded[$dayKey]) && is_array($decoded[$dayKey])) {
                        $default[$dayKey]['enabled'] = !empty($decoded[$dayKey]['enabled']);
                        if (!empty($decoded[$dayKey]['start'])) {
                            $default[$dayKey]['start'] = substr($decoded[$dayKey]['start'], 0, 5);
                        }
                        if (!empty($decoded[$dayKey]['end'])) {
                            $default[$dayKey]['end'] = substr($decoded[$dayKey]['end'], 0, 5);
                        }
                    }
                }
                return $default;
            }
        } elseif (is_array($raw)) {
            foreach ($default as $dayKey => $data) {
                if (isset($raw[$dayKey]) && is_array($raw[$dayKey])) {
                    $default[$dayKey]['enabled'] = !empty($raw[$dayKey]['enabled']);
                    if (!empty($raw[$dayKey]['start'])) {
                        $default[$dayKey]['start'] = substr($raw[$dayKey]['start'], 0, 5);
                    }
                    if (!empty($raw[$dayKey]['end'])) {
                        $default[$dayKey]['end'] = substr($raw[$dayKey]['end'], 0, 5);
                    }
                }
            }
            return $default;
        }

        return $default;
    }

    /**
     * Calcula la fecha hábil más próxima disponible según el horario configurado.
     * Si el día de hoy ya superó la hora límite de cierre o no está habilitado,
     * busca y retorna el siguiente día hábil disponible.
     *
     * @param string|null $fromDate Fecha base en formato Y-m-d (por defecto hoy)
     * @return array ['date' => 'Y-m-d', 'dayOfWeek' => int, 'config' => array, 'isToday' => bool, 'isPastCutoff' => bool]
     */
    public static function getNextAvailableBusinessDate($fromDate = null)
    {
        $schedule = self::getMeetingSchedule();
        $todayStr = date('Y-m-d');
        $baseDate = $fromDate ?: $todayStr;
        $currentTime = date('H:i');

        // Evaluar si la fecha base es hoy
        if ($baseDate === $todayStr) {
            $todayDow = (string)date('w');
            $todayConfig = $schedule[$todayDow] ?? null;

            if ($todayConfig && !empty($todayConfig['enabled'])) {
                $endTime = $todayConfig['end'] ?? '17:00';
                // Si la hora actual es menor que la hora límite, hoy sigue estando disponible
                if ($currentTime < $endTime) {
                    return [
                        'date' => $todayStr,
                        'dayOfWeek' => (int)$todayDow,
                        'config' => $todayConfig,
                        'isToday' => true,
                        'isPastCutoff' => false,
                    ];
                }
            }
        }

        // Si hoy ya pasó la hora límite o no es día hábil, avanzar hasta 14 días
        $baseTimestamp = strtotime($baseDate);
        for ($i = 1; $i <= 14; $i++) {
            $nextTimestamp = strtotime("+$i days", $baseTimestamp);
            $nextDate = date('Y-m-d', $nextTimestamp);
            $nextDow = (string)date('w', $nextTimestamp);
            $nextConfig = $schedule[$nextDow] ?? null;

            if ($nextConfig && !empty($nextConfig['enabled'])) {
                return [
                    'date' => $nextDate,
                    'dayOfWeek' => (int)$nextDow,
                    'config' => $nextConfig,
                    'isToday' => false,
                    'isPastCutoff' => true,
                ];
            }
        }

        // Respaldo de seguridad
        return [
            'date' => date('Y-m-d', strtotime('+1 day')),
            'dayOfWeek' => (int)date('w', strtotime('+1 day')),
            'config' => $schedule['1'] ?? ['start' => '08:00', 'end' => '17:00', 'name' => 'Lunes'],
            'isToday' => false,
            'isPastCutoff' => true,
        ];
    }
}
