<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "products".
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property float|null $price
 * @property float|null $price_renewal
 * @property float|null $price_restoration
 * @property int|null $status
 * @property string|null $created_at
 * @property string|null $currency 
 * @property string|null $billing_cycle 
 * @property string|null $type 
 * @property string|null $provisioning_config 
 * @property string|null $server_package 
 *
 * @property CustomerServices[] $customerServices
 * @property Servers $server 
 */
class Products extends \yii\db\ActiveRecord
{

    /** 
    * ENUM field values 
    */ 
    const BILLING_CYCLE_ONE_TIME = 'one_time'; 
    const BILLING_CYCLE_MONTHLY = 'monthly'; 
    const BILLING_CYCLE_YEARLY = 'yearly'; 
    const TYPE_HOSTING = 'hosting'; 
    const TYPE_LICENSE = 'license'; 
    const TYPE_DEVELOPMENT = 'development'; 
    const TYPE_SUPPORT = 'support'; 
    const TYPE_DOMAIN = 'domain'; 

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'products';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description'], 'default', 'value' => null],
            [['price'], 'default', 'value' => 0.00],
            [['server_id', 'description', 'provisioning_config', 'server_package'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 1],
            [['name'], 'required'],
            [['description', 'billing_cycle', 'type'], 'string'],
            [['price', 'price_renewal', 'price_restoration'], 'number'],
            [['status'], 'integer'],
            [['created_at', 'provisioning_config'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['currency'], 'string', 'max' => 3], 
            [['server_package'], 'string', 'max' => 100], 
            ['billing_cycle', 'in', 'range' => array_keys(self::optsBillingCycle())], 
            ['type', 'in', 'range' => array_keys(self::optsType())], 
            [['server_id'], 'exist', 'skipOnError' => true, 'targetClass' => Servers::class, 'targetAttribute' => ['server_id' => 'id']], 
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Producto',
            'description' => 'Descripción',
            'price' => 'Precio Registro / Base',
            'price_renewal' => 'Precio Renovación',
            'price_restoration' => 'Precio Restauración (Multa)',
            'status' => 'Estado (Activo/Inactivo)',
            'created_at' => 'Fecha de creación',
        ];
    }

    /**
     * Gets query for [[CustomerServices]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomerServices()
    {
        return $this->hasMany(CustomerServices::class, ['product_id' => 'id']);
    }

    /** 
     * Gets query for [[Server]]. 
     * 
     * @return \yii\db\ActiveQuery 
     */ 
    public function getServer() 
    { 
        return $this->hasOne(Servers::class, ['id' => 'server_id']); 
    } 


    /** 
     * column billing_cycle ENUM value labels 
     * @return string[] 
     */ 
    public static function optsBillingCycle() 
    { 
        return [ 
            self::BILLING_CYCLE_ONE_TIME => 'one_time', 
            self::BILLING_CYCLE_MONTHLY => 'monthly', 
            self::BILLING_CYCLE_YEARLY => 'yearly', 
        ]; 
    } 

    /** 
     * column type ENUM value labels 
     * @return string[] 
     */ 
    public static function optsType() 
    { 
        return [ 
            self::TYPE_HOSTING => 'hosting', 
            self::TYPE_LICENSE => 'license', 
            self::TYPE_DEVELOPMENT => 'development', 
            self::TYPE_SUPPORT => 'support', 
            self::TYPE_DOMAIN => 'domain', 
        ]; 
    } 

    /** 
     * @return string 
     */ 
    public function displayBillingCycle() 
    { 
        return self::optsBillingCycle()[$this->billing_cycle]; 
    } 

    /** 
     * @return bool 
     */ 
    public function isBillingCycleOnetime() 
    { 
        return $this->billing_cycle === self::BILLING_CYCLE_ONE_TIME; 
    } 

    public function setBillingCycleToOnetime() 
    { 
        $this->billing_cycle = self::BILLING_CYCLE_ONE_TIME; 
    } 

    /** 
        * @return bool 
        */ 
    public function isBillingCycleMonthly() 
    { 
        return $this->billing_cycle === self::BILLING_CYCLE_MONTHLY; 
    } 

    public function setBillingCycleToMonthly() 
    { 
        $this->billing_cycle = self::BILLING_CYCLE_MONTHLY; 
    } 

    /** 
        * @return bool 
        */ 
    public function isBillingCycleYearly() 
    { 
        return $this->billing_cycle === self::BILLING_CYCLE_YEARLY; 
    } 

    public function setBillingCycleToYearly() 
    { 
        $this->billing_cycle = self::BILLING_CYCLE_YEARLY; 
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
    public function isTypeHosting() 
    { 
        return $this->type === self::TYPE_HOSTING; 
    } 

    public function setTypeToHosting() 
    { 
        $this->type = self::TYPE_HOSTING; 
    } 

    /** 
        * @return bool 
        */ 
    public function isTypeLicense() 
    { 
        return $this->type === self::TYPE_LICENSE; 
    } 

    public function setTypeToLicense() 
    { 
        $this->type = self::TYPE_LICENSE; 
    } 

    /** 
        * @return bool 
        */ 
    public function isTypeDevelopment() 
    { 
        return $this->type === self::TYPE_DEVELOPMENT; 
    } 

    public function setTypeToDevelopment() 
    { 
        $this->type = self::TYPE_DEVELOPMENT; 
    } 

    /** 
        * @return bool 
        */ 
    public function isTypeSupport() 
    { 
        return $this->type === self::TYPE_SUPPORT; 
    } 

    public function setTypeToSupport() 
    { 
        $this->type = self::TYPE_SUPPORT; 
    } 

    /** 
        * @return bool 
        */ 
    public function isTypeDomain() 
    { 
        return $this->type === self::TYPE_DOMAIN; 
    } 

    public function setTypeToDomain() 
    { 
        $this->type = self::TYPE_DOMAIN; 
    } 

}
