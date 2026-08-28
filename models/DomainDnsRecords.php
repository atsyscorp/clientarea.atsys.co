<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "domain_dns_records".
 *
 * @property int $id
 * @property int $customer_service_id
 * @property string $host
 * @property string $record_type
 * @property string $address
 * @property int|null $mx_pref
 * @property int|null $ttl
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property CustomerServices $customerService
 */
class DomainDnsRecords extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'domain_dns_records';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['customer_service_id', 'host', 'record_type', 'address'], 'required'],
            [['customer_service_id', 'mx_pref', 'ttl'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['host', 'address'], 'string', 'max' => 255],
            [['record_type'], 'string', 'max' => 10],
            [['customer_service_id'], 'exist', 'skipOnError' => true, 'targetClass' => CustomerServices::class, 'targetAttribute' => ['customer_service_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'customer_service_id' => 'Servicio',
            'host' => 'Host',
            'record_type' => 'Tipo',
            'address' => 'Valor / Destino',
            'mx_pref' => 'Prioridad MX',
            'ttl' => 'TTL',
            'created_at' => 'Creado',
            'updated_at' => 'Actualizado',
        ];
    }

    /**
     * Gets query for [[CustomerService]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomerService()
    {
        return $this->hasOne(CustomerServices::class, ['id' => 'customer_service_id']);
    }
}
