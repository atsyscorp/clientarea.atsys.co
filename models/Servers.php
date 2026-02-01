<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "servers".
 *
 * @property int $id
 * @property string $name
 * @property string $hostname
 * @property string $ip_address
 * @property string|null $username
 * @property string|null $auth_token
 * @property string|null $type
 * @property int|null $current_accounts
 * @property int|null $max_accounts
 * @property int|null $is_active
 * @property string|null $created_at
 *
 * @property Products[] $products
 */
class Servers extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const TYPE_CYBERPANEL = 'cyberpanel';
    const TYPE_CPANEL = 'cpanel';
    const TYPE_VIRTUALMIN = 'virtualmin';
    const TYPE_PLESK = 'plesk';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'servers';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['auth_token', 'created_at'], 'default', 'value' => null],
            [['username'], 'default', 'value' => 'admin'],
            [['type'], 'default', 'value' => 'cyberpanel'],
            [['current_accounts'], 'default', 'value' => 0],
            [['max_accounts'], 'default', 'value' => 100],
            [['is_active'], 'default', 'value' => 1],
            [['name', 'hostname', 'ip_address'], 'required'],
            [['auth_token', 'type'], 'string'],
            [['current_accounts', 'max_accounts', 'is_active'], 'integer'],
            [['created_at'], 'safe'],
            [['name', 'username'], 'string', 'max' => 50],
            [['hostname'], 'string', 'max' => 100],
            [['ip_address'], 'string', 'max' => 45],
            ['type', 'in', 'range' => array_keys(self::optsType())],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'hostname' => 'Hostname',
            'ip_address' => 'Ip Address',
            'username' => 'Username',
            'auth_token' => 'Auth Token',
            'type' => 'Type',
            'current_accounts' => 'Current Accounts',
            'max_accounts' => 'Max Accounts',
            'is_active' => 'Is Active',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Products]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(Products::class, ['server_id' => 'id']);
    }


    /**
     * column type ENUM value labels
     * @return string[]
     */
    public static function optsType()
    {
        return [
            self::TYPE_CYBERPANEL => 'cyberpanel',
            self::TYPE_CPANEL => 'cpanel',
            self::TYPE_VIRTUALMIN => 'virtualmin',
            self::TYPE_PLESK => 'plesk',
        ];
    }

    /**
     * @return string
     */
    public function displayType()
    {
        return self::optsType()[$this->type];
    }

    /**
     * @return bool
     */
    public function isTypeCyberpanel()
    {
        return $this->type === self::TYPE_CYBERPANEL;
    }

    public function setTypeToCyberpanel()
    {
        $this->type = self::TYPE_CYBERPANEL;
    }

    /**
     * @return bool
     */
    public function isTypeCpanel()
    {
        return $this->type === self::TYPE_CPANEL;
    }

    public function setTypeToCpanel()
    {
        $this->type = self::TYPE_CPANEL;
    }

    /**
     * @return bool
     */
    public function isTypeVirtualmin()
    {
        return $this->type === self::TYPE_VIRTUALMIN;
    }

    public function setTypeToVirtualmin()
    {
        $this->type = self::TYPE_VIRTUALMIN;
    }

    /**
     * @return bool
     */
    public function isTypePlesk()
    {
        return $this->type === self::TYPE_PLESK;
    }

    public function setTypeToPlesk()
    {
        $this->type = self::TYPE_PLESK;
    }
}
