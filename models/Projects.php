<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "projects".
 *
 * @property int $id
 * @property int $customer_id
 * @property string $code
 * @property string $name
 * @property string|null $business_name
 * @property string|null $document_number
 * @property string|null $address
 * @property int $is_default
 * @property int $status 1: Activo, 0: Inactivo
 * @property string|null $notes
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Customers $customer
 * @property WorkOrders[] $workOrders
 */
class Projects extends ActiveRecord
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    public static function tableName()
    {
        return 'projects';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['business_name', 'document_number', 'address', 'notes', 'created_at', 'updated_at'], 'default', 'value' => null],
            [['is_default'], 'default', 'value' => 0],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],

            [['customer_id', 'name'], 'required'],
            [['customer_id', 'is_default', 'status'], 'integer'],
            [['address', 'notes'], 'string'],
            [['created_at', 'updated_at'], 'safe'],

            [['code'], 'string', 'max' => 50],
            [['name', 'business_name'], 'string', 'max' => 255],
            [['document_number'], 'string', 'max' => 50],

            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customers::class, 'targetAttribute' => ['customer_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'customer_id' => 'Cliente',
            'code' => 'Código de Proyecto',
            'name' => 'Nombre del Proyecto / Frente',
            'business_name' => 'Razón Social (Filial/Empresa)',
            'document_number' => 'NIT / Documento Identificación',
            'address' => 'Dirección de la Sede/Empresa',
            'is_default' => 'Proyecto Predeterminado',
            'status' => 'Estado',
            'notes' => 'Notas u Observaciones',
            'created_at' => 'Fecha de Creación',
            'updated_at' => 'Última Actualización',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && empty($this->code)) {
            $lastId = (new \yii\db\Query())->from(self::tableName())->max('id') ?: 0;
            $this->code = 'PRJ-' . str_pad($this->customer_id, 4, '0', STR_PAD_LEFT) . '-' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
        }

        return true;
    }

    public function getCustomer()
    {
        return $this->hasOne(Customers::class, ['id' => 'customer_id']);
    }

    public function getWorkOrders()
    {
        return $this->hasMany(WorkOrders::class, ['project_id' => 'id']);
    }

    public function getStatusLabel()
    {
        return $this->status == self::STATUS_ACTIVE ? 'Activo' : 'Inactivo';
    }

    public function getDisplayName()
    {
        if (!empty($this->business_name) && $this->business_name !== $this->name) {
            return $this->name . ' (' . $this->business_name . ')';
        }
        return $this->name;
    }
}
