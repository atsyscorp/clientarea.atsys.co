<?php

namespace app\models;

use Yii;
use yii\web\UploadedFile;

/**
 * This is the model class for table "ticket_replies".
 *
 * @property int $id
 * @property int $ticket_id
 * @property int|null $user_id ID del usuario admin que responde (Null si es el cliente)
 * @property string|null $sender_type
 * @property string $message
 * @property string|null $created_at
 *
 * @property Tickets $ticket
 * @property User $user
 */
class TicketReplies extends \yii\db\ActiveRecord
{

    /**
     * @var UploadedFile
     */
    public $attachmentFile;

    /**
     * @var UploadedFile[]
     */
    public $attachmentFiles = [];

    /**
     * ENUM field values
     */
    const SENDER_TYPE_ADMIN = 'admin';
    const SENDER_TYPE_CUSTOMER = 'customer';
    const SENDER_TYPE_SYSTEM = 'system';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ticket_replies';
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (preg_match('/<img[^>]*src="data:image[^>]*>/i', $this->message)) {
                $this->addError('message', 'No se permiten imágenes incrustadas (Base64).');
                return false;
            }
            return true;
        }
        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            $ticket = $this->ticket;
            $ticket->updated_at = date('Y-m-d H:i:s');
            $ticket->save(false);
        }
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Si este mensaje tiene adjuntos locales antiguos, los limpiamos
        if (!empty($this->attachment)) {
            $lines = preg_split('/[\r\n]+/', trim($this->attachment));
            foreach ($lines as $line) {
                $raw = trim($line);
                if (!preg_match('#^https?://#i', $raw) && !empty($raw)) {
                    $filePath = Yii::getAlias('@webroot') . '/' . ltrim($raw, '/');
                    if (file_exists($filePath) && is_file($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'default', 'value' => null],
            [['sender_type'], 'default', 'value' => 'customer'],
            [['ticket_id', 'message'], 'required'],
            [['ticket_id', 'user_id'], 'integer'],
            [['sender_type', 'message'], 'string'],
            [['created_at'], 'safe'],
            ['sender_type', 'in', 'range' => array_keys(self::optsSenderType())],
            [['ticket_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tickets::class, 'targetAttribute' => ['ticket_id' => 'id']],
            [['attachmentFile'], 'file', 
                'skipOnEmpty' => true, 
                'maxSize' => 1024 * 1024 * 50, // Máximo 50MB
                'tooBig' => 'El archivo "{file}" supera el límite máximo de 50MB.',
                'checkExtensionByMimeType' => false,
            ],
            [['attachmentFiles'], 'file', 
                'skipOnEmpty' => true, 
                'maxFiles' => 10,
                'maxSize' => 1024 * 1024 * 50, // Máximo 50MB
                'tooBig' => 'El archivo "{file}" supera el límite máximo de 50MB.',
                'checkExtensionByMimeType' => false,
            ],
        ];
    }

    /**
     * Devuelve una lista estructurada de los archivos adjuntos.
     * Soporta URLs completas de Google Drive, múltiples enlaces por línea, JSON o rutas locales antiguas.
     * @return array Array con elementos ['url' => ..., 'name' => ..., 'is_drive' => bool]
     */
    public function getAttachmentList()
    {
        if (empty($this->attachment)) {
            return [];
        }

        $raw = trim($this->attachment);

        // 1. Intentar decodificar si viene en formato JSON
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $list = [];
            foreach ($decoded as $idx => $item) {
                if (is_array($item) && !empty($item['url'])) {
                    $url = $item['url'];
                    $name = !empty($item['name']) ? $item['name'] : self::extractFilenameFromAttachmentUrl($url, $idx + 1);
                    $list[] = [
                        'url' => $url,
                        'name' => $name,
                        'is_drive' => self::isGoogleDriveUrl($url),
                    ];
                } elseif (is_string($item) && !empty($item)) {
                    $list[] = [
                        'url' => $item,
                        'name' => self::extractFilenameFromAttachmentUrl($item, $idx + 1),
                        'is_drive' => self::isGoogleDriveUrl($item),
                    ];
                }
            }
            if (!empty($list)) {
                return $list;
            }
        }

        // 2. Procesar enlaces separados por salto de línea
        $lines = preg_split('/[\r\n]+/', $raw);
        $list = [];
        $index = 1;
        foreach ($lines as $line) {
            $url = trim($line);
            if ($url === '') {
                continue;
            }

            $name = self::extractFilenameFromAttachmentUrl($url, $index);

            // Si es una ruta local relativa antigua (ej: uploads/tickets/1/archivo.png)
            if (!preg_match('#^https?://#i', $url)) {
                $fullUrl = Yii::getAlias('@web') . '/' . ltrim($url, '/');
            } else {
                $fullUrl = $url;
            }

            $list[] = [
                'url' => $fullUrl,
                'name' => $name,
                'is_drive' => self::isGoogleDriveUrl($url),
            ];
            $index++;
        }

        return $list;
    }

    /**
     * Extrae el nombre representativo del archivo adjunto a partir de su URL o ruta.
     * @param string $url
     * @param int $defaultIndex
     * @return string
     */
    public static function extractFilenameFromAttachmentUrl($url, $defaultIndex = 1)
    {
        // 1. Fragmento #filename=...
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        if (!empty($fragment)) {
            parse_str($fragment, $fragParams);
            if (!empty($fragParams['filename'])) {
                return rawurldecode($fragParams['filename']);
            }
            // Si el fragmento contiene directamente un nombre con extensión
            if (strpos($fragment, '=') === false && preg_match('/\.[a-zA-Z0-9]{2,5}$/', $fragment)) {
                return rawurldecode($fragment);
            }
        }

        // 2. Parámetros query ?filename=...
        $query = parse_url($url, PHP_URL_QUERY);
        if (!empty($query)) {
            parse_str($query, $queryParams);
            if (!empty($queryParams['filename'])) {
                return rawurldecode($queryParams['filename']);
            }
        }

        // 3. Si la URL contiene un nombre de archivo en la ruta
        $path = parse_url($url, PHP_URL_PATH);
        if (!empty($path)) {
            $basename = basename($path);
            if (preg_match('/^\d+_(.+)$/', $basename, $m)) {
                return $m[1];
            }
            if (preg_match('/^tkt_[a-z0-9\.]+_(.+)$/i', $basename, $m)) {
                return $m[1];
            }
            if ($basename !== 'view' && preg_match('/\.[a-zA-Z0-9]{2,5}$/', $basename)) {
                return $basename;
            }
        }

        if (self::isGoogleDriveUrl($url)) {
            return 'Documento en Drive ' . $defaultIndex;
        }

        return 'Archivo adjunto ' . $defaultIndex;
    }

    /**
     * Verifica si una URL corresponde a Google Drive
     * @param string $url
     * @return bool
     */
    public static function isGoogleDriveUrl($url)
    {
        return (strpos($url, 'drive.google.com') !== false || strpos($url, 'docs.google.com') !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ticket_id' => 'Ticket ID',
            'user_id' => 'User ID',
            'sender_type' => 'Sender Type',
            'message' => 'Message',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Ticket]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTicket()
    {
        return $this->hasOne(Tickets::class, ['id' => 'ticket_id']);
    }


    /**
     * column sender_type ENUM value labels
     * @return string[]
     */
    public static function optsSenderType()
    {
        return [
            self::SENDER_TYPE_ADMIN => 'admin',
            self::SENDER_TYPE_CUSTOMER => 'customer',
            self::SENDER_TYPE_SYSTEM => 'system',
        ];
    }

    /**
     * @return string
     */
    public function displaySenderType()
    {
        return self::optsSenderType()[$this->sender_type];
    }

    /**
     * @return bool
     */
    public function isSenderTypeAdmin()
    {
        return $this->sender_type === self::SENDER_TYPE_ADMIN;
    }

    public function setSenderTypeToAdmin()
    {
        $this->sender_type = self::SENDER_TYPE_ADMIN;
    }

    /**
     * @return bool
     */
    public function isSenderTypeCustomer()
    {
        return $this->sender_type === self::SENDER_TYPE_CUSTOMER;
    }

    public function setSenderTypeToCustomer()
    {
        $this->sender_type = self::SENDER_TYPE_CUSTOMER;
    }

    /**
     * @return bool
     */
    public function isSenderTypeSystem()
    {
        return $this->sender_type === self::SENDER_TYPE_SYSTEM;
    }

    public function setSenderTypeToSystem()
    {
        $this->sender_type = self::SENDER_TYPE_SYSTEM;
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        // Asegúrate de que User::class apunte a tu modelo de usuarios correcto
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
