<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "admin_tokens".
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string|null $device_info
 * @property string|null $created_at
 */
class AdminTokens extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'admin_tokens';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['device_info', 'created_at'], 'default', 'value' => null],
            [['user_id', 'token'], 'required'],
            [['user_id'], 'integer'],
            [['created_at'], 'safe'],
            [['token', 'device_info'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'token' => 'Token',
            'device_info' => 'Device Info',
            'created_at' => 'Created At',
        ];
    }

}
