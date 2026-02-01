<?php

namespace app\models;

use Yii;

use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "customers".
 *
 * @property int $id
 * @property string|null $document_type
 * @property string $document_number NIT o Cedula
 * @property string $business_name Razón Social
 * @property string|null $trade_name Nombre comercial
 * @property string|null $contact_name
 * @property string|null $contact_position
 * @property string $email
 * @property string $primary_phone Móvil para WhatsApp
 * @property string|null $secondary_phone
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state_province
 * @property string|null $status
 * @property string|null $notes
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $user_id
 *
 * @property InventoryBatches[] $inventoryBatches
 */
class Customers extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const DOCUMENT_TYPE_NIT = 'NIT';
    const DOCUMENT_TYPE_CC = 'CC';
    const DOCUMENT_TYPE_RUT = 'RUT';
    const DOCUMENT_TYPE_PASSPORT = 'PASSPORT';
    const DOCUMENT_TYPE_OTHER = 'OTHER';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PROSPECT = 'prospect';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'customers';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'), // O simplemente no poner 'value' si usas int en DB
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['trade_name', 'contact_name', 'contact_position', 'secondary_phone', 'address', 'city', 'state_province', 'notes'], 'default', 'value' => null],
            [['document_type'], 'default', 'value' => 'NIT'],
            [['status'], 'default', 'value' => 'active'],
            [['document_type', 'status', 'notes'], 'string'],
            [['document_number', 'business_name', 'email', 'primary_phone'], 'required'],
            [['created_at', 'updated_at', 'user_id'], 'safe'],
            [['document_number', 'primary_phone', 'secondary_phone'], 'string', 'max' => 50],
            [['business_name', 'trade_name', 'contact_name', 'email', 'address'], 'string', 'max' => 255],
            [['contact_position', 'city', 'state_province'], 'string', 'max' => 100],
            ['document_type', 'in', 'range' => array_keys(self::optsDocumentType())],
            ['status', 'in', 'range' => array_keys(self::optsStatus())],
            [['document_number'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'document_type' => 'Tipo de Documento',
            'document_number' => 'Número de Documento',
            'business_name' => 'Razón Social',
            'trade_name' => 'Nombre Comercial',
            'contact_name' => 'Nombre del Contacto',
            'contact_position' => 'Cargo',
            'email' => 'Correo Electrónico',
            'primary_phone' => 'Teléfono Principal',
            'secondary_phone' => 'Teléfono Secundario',
            'address' => 'Dirección',
            'city' => 'Ciudad',
            'state_province' => 'Departamento / Estado',
            'status' => 'Estado',
            'notes' => 'Notas / Observaciones',
            'created_at' => 'Fecha de Creación',
            'updated_at' => 'Última Actualización',
        ];
    }

    /**
     * Gets query for [[InventoryBatches]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInventoryBatches()
    {
        return $this->hasMany(InventoryBatches::class, ['customer_id' => 'id']);
    }


    /**
     * column document_type ENUM value labels
     * @return string[]
     */
    public static function optsDocumentType()
    {
        return [
            self::DOCUMENT_TYPE_NIT => 'NIT',
            self::DOCUMENT_TYPE_CC => 'CC',
            self::DOCUMENT_TYPE_RUT => 'RUT',
            self::DOCUMENT_TYPE_PASSPORT => 'PASSPORT',
            self::DOCUMENT_TYPE_OTHER => 'OTHER',
        ];
    }

    /**
     * column status ENUM value labels
     * @return string[]
     */
    public static function optsStatus()
    {
        return [
            self::STATUS_ACTIVE => 'active',
            self::STATUS_INACTIVE => 'inactive',
            self::STATUS_PROSPECT => 'prospect',
        ];
    }

    /**
     * @return string
     */
    public function displayDocumentType()
    {
        return self::optsDocumentType()[$this->document_type];
    }

    /**
     * @return bool
     */
    public function isDocumentTypeNit()
    {
        return $this->document_type === self::DOCUMENT_TYPE_NIT;
    }

    public function setDocumentTypeToNit()
    {
        $this->document_type = self::DOCUMENT_TYPE_NIT;
    }

    /**
     * @return bool
     */
    public function isDocumentTypeCc()
    {
        return $this->document_type === self::DOCUMENT_TYPE_CC;
    }

    public function setDocumentTypeToCc()
    {
        $this->document_type = self::DOCUMENT_TYPE_CC;
    }

    /**
     * @return bool
     */
    public function isDocumentTypeRut()
    {
        return $this->document_type === self::DOCUMENT_TYPE_RUT;
    }

    public function setDocumentTypeToRut()
    {
        $this->document_type = self::DOCUMENT_TYPE_RUT;
    }

    /**
     * @return bool
     */
    public function isDocumentTypePassport()
    {
        return $this->document_type === self::DOCUMENT_TYPE_PASSPORT;
    }

    public function setDocumentTypeToPassport()
    {
        $this->document_type = self::DOCUMENT_TYPE_PASSPORT;
    }

    /**
     * @return bool
     */
    public function isDocumentTypeOther()
    {
        return $this->document_type === self::DOCUMENT_TYPE_OTHER;
    }

    public function setDocumentTypeToOther()
    {
        $this->document_type = self::DOCUMENT_TYPE_OTHER;
    }

    /**
     * @return string
     */
    public function displayStatus()
    {
        return self::optsStatus()[$this->status];
    }

    /**
     * @return bool
     */
    public function isStatusActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function setStatusToActive()
    {
        $this->status = self::STATUS_ACTIVE;
    }

    /**
     * @return bool
     */
    public function isStatusInactive()
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function setStatusToInactive()
    {
        $this->status = self::STATUS_INACTIVE;
    }

    /**
     * @return bool
     */
    public function isStatusProspect()
    {
        return $this->status === self::STATUS_PROSPECT;
    }

    public function setStatusToProspect()
    {
        $this->status = self::STATUS_PROSPECT;
    }

    public function getServices()
    {
        return $this->hasMany(CustomerServices::class, ['customer_id' => 'id']);
    }

}
