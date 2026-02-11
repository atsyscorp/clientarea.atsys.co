<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

class WorkOrders extends \yii\db\ActiveRecord
{
    // Constantes de Estado...
    const STATUS_DRAFT = 0;
    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECTED = 3;
    const STATUS_COMPLETED = 4;

    public static function tableName()
    {
        return 'work_orders';
    }

    public function rules()
    {
        return [
            // Valores por defecto
            [['notes', 'down_payment_sent_at', 'created_at', 'updated_at'], 'default', 'value' => null],
            [['total_cost'], 'default', 'value' => 0.00],
            [['status'], 'default', 'value' => 0],
            
            // CORRECCIÓN AQUÍ: Quitamos 'code' de required
            [['customer_id', 'title', 'requirements'], 'required'],
            
            // Tipos de datos
            [['customer_id', 'status'], 'integer'],
            [['requirements', 'notes'], 'string'],
            [['total_cost'], 'number'],
            [['down_payment_sent_at', 'created_at', 'updated_at', 'completed_at'], 'safe'],
            
            // Validaciones de longitud
            [['code'], 'string', 'max' => 50],
            [['title'], 'string', 'max' => 255],
            
            // Integridad referencial
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customers::class, 'targetAttribute' => ['customer_id' => 'id']],
        ];
    }

    // Generador de Código Automático
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord && empty($this->code)) {
                // Generar consecutivo basado en el último ID para evitar duplicados si borras registros
                // Buscamos el último registro creado
                $lastOrder = self::find()->orderBy(['id' => SORT_DESC])->one();
                
                // Si existe tomamos su ID + 1, si no existe empezamos en 1
                // (Usar count() es peligroso si borras órdenes intermedias)
                $nextId = $lastOrder ? ($lastOrder->id + 1) : 1;
                
                $this->code = 'OT-' . date('Y') . '-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }
            return true;
        }
        return false;
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

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'customer_id' => 'Cliente',
            'code' => 'Código (OT)',
            'title' => 'Título del Proyecto',
            'requirements' => 'Detalle de Requerimientos',
            'notes' => 'Notas Adicionales',
            'total_cost' => 'Inversión Total',
            'status' => 'Estado',
            'down_payment_sent_at' => 'Anticipo enviado el',
            'created_at' => 'Fecha Creación',
        ];
    }

    // ... (Tus relaciones y getStatusHtml siguen igual) ...
    public function getCustomer()
    {
        return $this->hasOne(Customers::class, ['id' => 'customer_id']);
    }

    public function getStatusHtml()
    {
        $statusMap = [
            self::STATUS_DRAFT => ['label' => 'Borrador', 'class' => 'badge-ghost'],
            self::STATUS_PENDING => ['label' => 'Pendiente', 'class' => 'badge-warning'],
            self::STATUS_APPROVED => ['label' => 'Aprobada', 'class' => 'badge-success text-white'],
            self::STATUS_REJECTED => ['label' => 'Rechazada', 'class' => 'badge-error text-white'],
            self::STATUS_COMPLETED => ['label' => 'Finalizada', 'class' => 'badge-info text-white'],
        ];

        $s = $statusMap[$this->status] ?? ['label' => 'Desconocido', 'class' => 'badge-ghost'];
        return "<span class='badge {$s['class']} font-bold'>{$s['label']}</span>";
    }
}