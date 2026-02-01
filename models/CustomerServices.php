<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "customer_services".
 *
 * @property int $id
 * @property int $customer_id
 * @property int $product_id
 * @property string|null $description_label
 * @property string|null $domain
 * @property string|null $username_service
 * @property string|null $password_service
 * @property string|null $start_date
 * @property string|null $next_due_date
 * @property int|null $status
 * @property string|null $created_at
 *
 * @property Customers $customer
 * @property Products $product
 */
class CustomerServices extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'customer_services';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description_label', 'domain', 'username_service', 'password_service', 'start_date', 'next_due_date'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 1],
            [['customer_id', 'product_id'], 'required'],
            [['customer_id', 'product_id', 'status'], 'integer'],
            [['start_date', 'next_due_date', 'created_at'], 'safe'],
            [['description_label', 'domain', 'password_service'], 'string', 'max' => 255],
            [['username_service'], 'string', 'max' => 100],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customers::class, 'targetAttribute' => ['customer_id' => 'id']],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Products::class, 'targetAttribute' => ['product_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'customer_id' => 'Cliente',
            'product_id' => 'Producto',
            'description_label' => 'Descripción',
            'domain' => 'Dominio',
            'username_service' => 'Usuario',
            'password_service' => 'Contraseña',
            'start_date' => 'Fecha inicio',
            'next_due_date' => 'Próxima renovación',
            'status' => 'Estado',
            'created_at' => 'Fecha creación',
        ];
    }

    /**
     * Gets query for [[Customer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customers::class, ['id' => 'customer_id']);
    }

    /**
     * Gets query for [[Product]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Products::class, ['id' => 'product_id']);
    }

    public function getStatusHtml()
    {
        $states = [
            1 => ['label' => 'Activo', 'class' => 'badge-success'],
            2 => ['label' => 'Suspendido', 'class' => 'badge-warning'],
            0 => ['label' => 'Cancelado', 'class' => 'badge-error text-white'],
        ];
        $s = $states[$this->status] ?? ['label' => 'Desconocido', 'class' => 'badge-ghost'];
        
        return "<span class='badge {$s['class']} font-bold'>{$s['label']}</span>";
    }

}
