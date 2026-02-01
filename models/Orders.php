<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "orders".
 *
 * @property int $id
 * @property string $code
 * @property int $customer_id
 * @property float $subtotal
 * @property float|null $tax
 * @property float $total
 * @property int|null $status 0: Pendiente, 1: Pagado, 2: Activo, 3: Cancelado
 * @property string|null $payment_method
 * @property string|null $transaction_ref
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Customers $customer
 * @property OrderItems[] $orderItems
 */
class Orders extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'orders';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['payment_method', 'transaction_ref', 'created_at', 'updated_at'], 'default', 'value' => null],
            [['tax'], 'default', 'value' => 0.00],
            [['status'], 'default', 'value' => 0],
            [['code', 'customer_id', 'subtotal', 'total'], 'required'],
            [['customer_id', 'status'], 'integer'],
            [['subtotal', 'tax', 'total'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['code'], 'string', 'max' => 30],
            [['payment_method'], 'string', 'max' => 50],
            [['transaction_ref'], 'string', 'max' => 255],
            [['code'], 'unique'],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customers::class, 'targetAttribute' => ['customer_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'customer_id' => 'Customer ID',
            'subtotal' => 'Subtotal',
            'tax' => 'Tax',
            'total' => 'Total',
            'status' => 'Status',
            'payment_method' => 'Payment Method',
            'transaction_ref' => 'Transaction Ref',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
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
     * Gets query for [[OrderItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItems()
    {
        return $this->hasMany(OrderItems::class, ['order_id' => 'id']);
    }

}
