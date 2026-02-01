<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "order_items".
 *
 * @property int $id
 * @property int $order_id
 * @property int $service_id
 * @property string $service_name
 * @property string $action_type
 * @property string|null $domain_name
 * @property int|null $period
 * @property float $unit_price
 * @property float $total
 *
 * @property Orders $order
 */
class OrderItems extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const ACTION_TYPE_REGISTER = 'register';
    const ACTION_TYPE_RENEW = 'renew';
    const ACTION_TYPE_TRANSFER = 'transfer';
    const ACTION_TYPE_PENALTY = 'penalty';
    const ACTION_TYPE_HOSTING_SETUP = 'hosting_setup';
    const ACTION_TYPE_PAYMENT = 'payment';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_items';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['domain_name'], 'default', 'value' => null],
            [['action_type'], 'default', 'value' => 'register'],
            [['period'], 'default', 'value' => 1],
            [['order_id', 'service_id', 'service_name', 'unit_price', 'total'], 'required'],
            [['order_id', 'service_id', 'period'], 'integer'],
            [['action_type'], 'string'],
            [['unit_price', 'total'], 'number'],
            [['service_name', 'domain_name'], 'string', 'max' => 255],
            ['action_type', 'in', 'range' => array_keys(self::optsActionType())],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Orders::class, 'targetAttribute' => ['order_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'service_id' => 'Service ID',
            'service_name' => 'Service Name',
            'action_type' => 'Action Type',
            'domain_name' => 'Domain Name',
            'period' => 'Period',
            'unit_price' => 'Unit Price',
            'total' => 'Total',
        ];
    }

    /**
     * Gets query for [[Order]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Orders::class, ['id' => 'order_id']);
    }

    /**
     * Gets query for [[Products]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Products::class, ['id' => 'service_id']);
    }

    /**
     * column action_type ENUM value labels
     * @return string[]
     */
    public static function optsActionType()
    {
        return [
            self::ACTION_TYPE_REGISTER => 'register',
            self::ACTION_TYPE_RENEW => 'renew',
            self::ACTION_TYPE_TRANSFER => 'transfer',
            self::ACTION_TYPE_PENALTY => 'penalty',
            self::ACTION_TYPE_HOSTING_SETUP => 'hosting_setup',
            self::ACTION_TYPE_PAYMENT => 'payment',
        ];
    }

    /**
     * @return string
     */
    public function displayActionType()
    {
        return self::optsActionType()[$this->action_type];
    }

    /**
     * @return bool
     */
    public function isActionTypeRegister()
    {
        return $this->action_type === self::ACTION_TYPE_REGISTER;
    }

    public function setActionTypeToRegister()
    {
        $this->action_type = self::ACTION_TYPE_REGISTER;
    }

    /**
     * @return bool
     */
    public function isActionTypeRenew()
    {
        return $this->action_type === self::ACTION_TYPE_RENEW;
    }

    public function setActionTypeToRenew()
    {
        $this->action_type = self::ACTION_TYPE_RENEW;
    }

    /**
     * @return bool
     */
    public function isActionTypeTransfer()
    {
        return $this->action_type === self::ACTION_TYPE_TRANSFER;
    }

    public function setActionTypeToTransfer()
    {
        $this->action_type = self::ACTION_TYPE_TRANSFER;
    }

    /**
     * @return bool
     */
    public function isActionTypePenalty()
    {
        return $this->action_type === self::ACTION_TYPE_PENALTY;
    }

    public function setActionTypeToPenalty()
    {
        $this->action_type = self::ACTION_TYPE_PENALTY;
    }
    
    /** 
     * @return bool 
     */ 
    public function isActionTypeHostingsetup() 
    { 
        return $this->action_type === self::ACTION_TYPE_HOSTING_SETUP; 
    } 
 
    public function setActionTypeToHostingsetup() 
    { 
        $this->action_type = self::ACTION_TYPE_HOSTING_SETUP; 
    } 
 
    /** 
     * @return bool 
     */ 
    public function isActionTypePayment() 
    { 
        return $this->action_type === self::ACTION_TYPE_PAYMENT; 
    } 
    
    public function setActionTypeToPayment() 
    { 
        $this->action_type = self::ACTION_TYPE_PAYMENT; 
    }
}
