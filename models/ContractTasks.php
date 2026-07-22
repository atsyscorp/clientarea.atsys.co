<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "contract_tasks".
 *
 * @property int $id
 * @property int $contract_id
 * @property int|null $work_order_id
 * @property string $title
 * @property string|null $description
 * @property float $weight_percentage
 * @property float $progress_percentage
 * @property int $status 0: Pendiente, 1: En Progreso, 2: Completada
 * @property string|null $due_date
 * @property string|null $created_at
 *
 * @property Contracts $contract
 * @property WorkOrders|null $workOrder
 */
class ContractTasks extends ActiveRecord
{
    const STATUS_PENDING = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;

    public static function tableName()
    {
        return 'contract_tasks';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['contract_id', 'title'], 'required'],
            [['contract_id', 'work_order_id', 'status'], 'integer'],
            [['weight_percentage', 'progress_percentage'], 'number'],
            [['description'], 'string'],
            [['due_date', 'created_at'], 'safe'],
            [['title'], 'string', 'max' => 255],

            [['weight_percentage'], 'default', 'value' => 0.00],
            [['progress_percentage'], 'default', 'value' => 0.00],
            [['status'], 'default', 'value' => self::STATUS_PENDING],

            [['contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => Contracts::class, 'targetAttribute' => ['contract_id' => 'id']],
            [['work_order_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkOrders::class, 'targetAttribute' => ['work_order_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'contract_id' => 'Contrato',
            'work_order_id' => 'Orden de Trabajo Relacionada',
            'title' => 'Título de la Tarea / Hito',
            'description' => 'Descripción',
            'weight_percentage' => 'Peso en el Contrato (%)',
            'progress_percentage' => '% de Avance',
            'status' => 'Estado',
            'due_date' => 'Fecha Límite',
            'created_at' => 'Fecha de Creación',
        ];
    }

    public function getContract()
    {
        return $this->hasOne(Contracts::class, ['id' => 'contract_id']);
    }

    public function getWorkOrder()
    {
        return $this->hasOne(WorkOrders::class, ['id' => 'work_order_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($this->contract) {
            $this->contract->recalculateProgress();
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();
        if ($this->contract) {
            $this->contract->recalculateProgress();
        }
    }
}
