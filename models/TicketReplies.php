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
 */
class TicketReplies extends \yii\db\ActiveRecord
{

    /**
     * @var UploadedFile
     */
    public $attachmentFile;

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

        // Si este mensaje tiene un adjunto, lo buscamos y lo borramos
        if (!empty($this->attachment)) {
            $filePath = Yii::getAlias('@webroot') . '/uploads/tickets/'.$this->ticket_id.'/' . $this->attachment;
            
            if (file_exists($filePath)) {
                @unlink($filePath); 
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
                'extensions' => 'png, jpg, jpeg, pdf, zip, rar', 
                'maxSize' => 1024 * 1024 * 10, // Máximo 10MB
                'tooBig' => 'El archivo es muy pesado. Máximo 10MB.',
                'checkExtensionByMimeType' => false,
            ],
        ];
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
}
