<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "ticket_spam_blacklist".
 *
 * @property int $id
 * @property string $email
 * @property string|null $reason
 * @property string $created_at
 */
class TicketSpamBlacklist extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ticket_spam_blacklist';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email'], 'required', 'message' => 'El correo electrónico es obligatorio.'],
            [['email'], 'email', 'message' => 'El formato del correo electrónico no es válido.'],
            [['email'], 'unique', 'message' => 'Este correo electrónico ya está registrado en la lista negra.'],
            [['reason'], 'string', 'max' => 255],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'Correo Electrónico',
            'reason' => 'Motivo / Notas',
            'created_at' => 'Fecha de Registro',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = date('Y-m-d H:i:s');
            }
            $this->email = strtolower(trim($this->email));
            return true;
        }
        return false;
    }
}
