<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "contracts".
 *
 * @property int $id
 * @property int $customer_id
 * @property string $code
 * @property string $title
 * @property string|null $description
 * @property float|null $total_amount
 * @property string $currency
 * @property string|null $start_date
 * @property string|null $end_date
 * @property int $status 0: Borrador, 1: Activo, 2: Suspendido, 3: Finalizado, 4: Cancelado
 * @property float $progress_percentage
 * @property int $progress_mode 0: Automático, 1: Manual
 * @property string|null $contract_file
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Customers $customer
 * @property WorkOrders[] $workOrders
 * @property ContractTasks[] $tasks
 * @property ContractDocuments[] $documents
 */
class Contracts extends ActiveRecord
{
    public $attachmentFile;

    const STATUS_DRAFT = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_SUSPENDED = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_CANCELLED = 4;

    const PROGRESS_MODE_AUTO = 0;
    const PROGRESS_MODE_MANUAL = 1;

    public static function tableName()
    {
        return 'contracts';
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
            [['description', 'contract_file', 'start_date', 'end_date', 'created_at', 'updated_at'], 'default', 'value' => null],
            [['total_amount', 'progress_percentage'], 'default', 'value' => 0.00],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['progress_mode'], 'default', 'value' => self::PROGRESS_MODE_AUTO],
            [['currency'], 'default', 'value' => 'COP'],

            [['customer_id', 'title'], 'required'],

            [['customer_id', 'status', 'progress_mode'], 'integer'],
            [['total_amount', 'progress_percentage'], 'number'],
            [['description', 'contract_file'], 'string'],
            [['start_date', 'end_date', 'created_at', 'updated_at'], 'safe'],

            [['code'], 'string', 'max' => 50],
            [['title'], 'string', 'max' => 255],
            [['currency'], 'string', 'max' => 3],
            [['currency'], 'in', 'range' => ['COP', 'USD', 'EUR']],

            [['attachmentFile'], 'file',
                'skipOnEmpty' => true,
                'extensions' => 'pdf, doc, docx, zip, rar, png, jpg, jpeg',
                'maxSize' => 1024 * 1024 * 25, // 25MB
                'checkExtensionByMimeType' => false,
            ],

            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customers::class, 'targetAttribute' => ['customer_id' => 'id']],
        ];
    }

    public static function generateNextCode()
    {
        $year = date('Y');
        $prefix = 'ATC-' . $year . '-';
        
        // Buscar el último contrato generado en el año actual
        $lastContract = self::find()
            ->where(['like', 'code', $prefix . '%', false])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $nextNum = 1;
        if ($lastContract && preg_match('/ATC-\d{4}-(\d+)/', $lastContract->code, $matches)) {
            $nextNum = intval($matches[1]) + 1;
        }

        return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord && empty($this->code)) {
                $this->code = self::generateNextCode();
            }
            return true;
        }
        return false;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'customer_id' => 'Cliente',
            'code' => 'Código de Contrato',
            'title' => 'Título / Objeto del Contrato',
            'description' => 'Descripción / Condiciones',
            'total_amount' => 'Monto Total',
            'currency' => 'Moneda',
            'start_date' => 'Fecha de Inicio',
            'end_date' => 'Fecha de Vencimiento',
            'status' => 'Estado',
            'progress_percentage' => '% de Avance',
            'progress_mode' => 'Modo de Cálculo de Avance',
            'contract_file' => 'Archivo de Contrato (PDF/Doc)',
            'attachmentFile' => 'Adjuntar Documento del Contrato (PDF)',
            'created_at' => 'Fecha de Registro',
            'updated_at' => 'Última Actualización',
        ];
    }

    public function getCustomer()
    {
        return $this->hasOne(Customers::class, ['id' => 'customer_id']);
    }

    public function getWorkOrders()
    {
        return $this->hasMany(WorkOrders::class, ['contract_id' => 'id']);
    }

    public function getTasks()
    {
        return $this->hasMany(ContractTasks::class, ['contract_id' => 'id']);
    }

    public function getDocuments()
    {
        return $this->hasMany(ContractDocuments::class, ['contract_id' => 'id']);
    }

    public static function getStatusOptions()
    {
        return [
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_ACTIVE => 'Activo / En Ejecución',
            self::STATUS_SUSPENDED => 'Suspendido / Pausado',
            self::STATUS_COMPLETED => 'Finalizado / Entregado',
            self::STATUS_CANCELLED => 'Cancelado',
        ];
    }

    public function getStatusHtml()
    {
        $statusMap = [
            self::STATUS_DRAFT => ['label' => 'Borrador', 'class' => 'badge-ghost'],
            self::STATUS_ACTIVE => ['label' => 'Activo', 'class' => 'badge-success text-white font-bold'],
            self::STATUS_SUSPENDED => ['label' => 'Pausado', 'class' => 'badge-warning font-bold'],
            self::STATUS_COMPLETED => ['label' => 'Finalizado', 'class' => 'badge-info text-white font-bold'],
            self::STATUS_CANCELLED => ['label' => 'Cancelado', 'class' => 'badge-error text-white'],
        ];

        $s = $statusMap[$this->status] ?? ['label' => 'Desconocido', 'class' => 'badge-ghost'];
        return "<span class='badge {$s['class']}'>{$s['label']}</span>";
    }

    public function getProgressColorClass()
    {
        $progress = floatval($this->progress_percentage);
        if ($progress >= 100) {
            return 'progress-success';
        } elseif ($progress >= 75) {
            return 'progress-info';
        } elseif ($progress >= 25) {
            return 'progress-warning';
        } else {
            return 'progress-error';
        }
    }

    public function recalculateProgress()
    {
        if ($this->progress_mode == self::PROGRESS_MODE_MANUAL) {
            return;
        }

        $tasks = $this->tasks;
        if (!empty($tasks)) {
            $totalWeight = 0;
            $weightedProgress = 0;
            foreach ($tasks as $task) {
                $w = floatval($task->weight_percentage);
                $p = floatval($task->progress_percentage);
                if ($w > 0) {
                    $totalWeight += $w;
                    $weightedProgress += ($p * $w) / 100;
                } else {
                    $weightedProgress += $p;
                    $totalWeight += 100;
                }
            }

            if ($totalWeight > 0) {
                $finalProgress = ($weightedProgress / ($totalWeight > 100 ? $totalWeight : 100)) * 100;
                $this->progress_percentage = min(100, max(0, round($finalProgress, 2)));
            }
        } else {
            $workOrders = $this->workOrders;
            if (!empty($workOrders)) {
                $totalWO = count($workOrders);
                $accumulatedProgress = 0;
                foreach ($workOrders as $wo) {
                    if (floatval($wo->progress_percentage) > 0) {
                        $accumulatedProgress += floatval($wo->progress_percentage);
                    } else {
                        // Derivar del estado de la OT si no tiene % explícito
                        if ($wo->status == WorkOrders::STATUS_COMPLETED) {
                            $accumulatedProgress += 100;
                        } elseif ($wo->status == WorkOrders::STATUS_APPROVED) {
                            $accumulatedProgress += 50;
                        } elseif ($wo->status == WorkOrders::STATUS_PARTIAL) {
                            $accumulatedProgress += 50;
                        }
                    }
                }
                $this->progress_percentage = min(100, max(0, round($accumulatedProgress / $totalWO, 2)));
            }
        }

        // Si llega a 100 y estaba activo, sugerir o actualizar a completado si aplica
        $this->save(false, ['progress_percentage', 'updated_at']);
    }
}
