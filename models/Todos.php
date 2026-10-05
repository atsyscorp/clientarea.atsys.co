<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * Model class for table "{{%todos}}".
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $priority
 * @property string $status
 * @property string|null $due_date
 * @property string|null $reminder_at
 * @property int $reminder_sent
 * @property int|null $customer_id
 * @property int|null $assigned_to
 * @property int $created_by
 * @property int|null $estimated_minutes
 * @property int $total_time_spent
 * @property string|null $completed_at
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Customers|null $customer
 * @property User|null $assignedToUser
 * @property User $createdByUser
 * @property TodoTimeLogs[] $timeLogs
 * @property TodoChecklistItems[] $checklistItems
 * @property TodoComments[] $comments
 */
class Todos extends ActiveRecord
{
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%todos}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required', 'message' => 'El título de la tarea es obligatorio.'],
            [['description'], 'string'],
            [['priority', 'status'], 'string', 'max' => 20],
            [['priority'], 'default', 'value' => self::PRIORITY_MEDIUM],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['priority'], 'in', 'range' => array_keys(self::optsPriority())],
            [['status'], 'in', 'range' => array_keys(self::optsStatus())],
            [['customer_id', 'assigned_to', 'created_by', 'estimated_minutes', 'total_time_spent', 'reminder_sent'], 'integer'],
            [['total_time_spent'], 'default', 'value' => 0],
            [['reminder_sent'], 'default', 'value' => 0],
            [['estimated_minutes'], 'default', 'value' => 0],
            [['due_date', 'reminder_at', 'completed_at', 'created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customers::class, 'targetAttribute' => ['customer_id' => 'id']],
            [['assigned_to'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['assigned_to' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        if ($insert && empty($this->created_at)) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Título de la Tarea',
            'description' => 'Descripción / Notas',
            'priority' => 'Prioridad',
            'status' => 'Estado',
            'due_date' => 'Fecha Límite (Entrega)',
            'reminder_at' => 'Recordatorio (Fecha y Hora)',
            'reminder_sent' => 'Recordatorio Enviado',
            'customer_id' => 'Cliente Asociado',
            'assigned_to' => 'Responsable Asignado',
            'created_by' => 'Creado Por',
            'estimated_minutes' => 'Tiempo Estimado (Minutos)',
            'total_time_spent' => 'Tiempo Total Invertido',
            'completed_at' => 'Fecha de Finalización',
            'created_at' => 'Fecha de Creación',
            'updated_at' => 'Última Actualización',
        ];
    }

    /**
     * @return array
     */
    public static function optsPriority()
    {
        return [
            self::PRIORITY_LOW => 'Baja',
            self::PRIORITY_MEDIUM => 'Media',
            self::PRIORITY_HIGH => 'Alta',
            self::PRIORITY_URGENT => 'Urgente',
        ];
    }

    /**
     * @return array
     */
    public static function optsStatus()
    {
        return [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_IN_PROGRESS => 'En Progreso',
            self::STATUS_COMPLETED => 'Completada',
            self::STATUS_CANCELLED => 'Cancelada',
        ];
    }

    /**
     * Clase CSS de badge según prioridad
     * @return string
     */
    public function getPriorityBadgeClass()
    {
        switch ($this->priority) {
            case self::PRIORITY_URGENT:
                return 'badge-error text-white font-bold animate-pulse';
            case self::PRIORITY_HIGH:
                return 'badge-warning font-semibold text-warning-content';
            case self::PRIORITY_MEDIUM:
                return 'badge-info font-medium text-info-content';
            case self::PRIORITY_LOW:
            default:
                return 'badge-ghost text-base-content/70';
        }
    }

    /**
     * Clase CSS de badge según estado
     * @return string
     */
    public function getStatusBadgeClass()
    {
        switch ($this->status) {
            case self::STATUS_COMPLETED:
                return 'badge-success text-white font-semibold';
            case self::STATUS_IN_PROGRESS:
                return 'badge-primary text-white font-semibold';
            case self::STATUS_CANCELLED:
                return 'badge-neutral line-through opacity-70';
            case self::STATUS_PENDING:
            default:
                return 'badge-warning font-semibold text-warning-content';
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customers::class, ['id' => 'customer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAssignedToUser()
    {
        return $this->hasOne(User::class, ['id' => 'assigned_to']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimeLogs()
    {
        return $this->hasMany(TodoTimeLogs::class, ['todo_id' => 'id'])->orderBy(['id' => SORT_DESC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChecklistItems()
    {
        return $this->hasMany(TodoChecklistItems::class, ['todo_id' => 'id'])->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(TodoComments::class, ['todo_id' => 'id'])->orderBy(['id' => SORT_DESC]);
    }

    /**
     * Devuelve el cronómetro activo para esta tarea (si existe).
     * @param int|null $userId
     * @return TodoTimeLogs|null
     */
    public function getActiveTimer($userId = null)
    {
        $query = TodoTimeLogs::find()
            ->where(['todo_id' => $this->id, 'is_running' => 1]);

        if ($userId !== null) {
            $query->andWhere(['user_id' => $userId]);
        }

        return $query->one();
    }

    /**
     * Recalcula y persiste el tiempo total invertido sumando todos los logs finalizados.
     */
    public function recalculateTotalTime()
    {
        $total = (int) TodoTimeLogs::find()
            ->where(['todo_id' => $this->id, 'is_running' => 0])
            ->sum('duration_seconds');

        $this->total_time_spent = $total;
        $this->save(false, ['total_time_spent', 'updated_at']);
    }

    /**
     * Obtiene el tiempo total formateado (ej. 2h 30m).
     * @return string
     */
    public function getFormattedTotalTime()
    {
        return TodoTimeLogs::formatSeconds($this->total_time_spent);
    }

    /**
     * Calcula el porcentaje de avance de la tarea.
     * @return int
     */
    public function getCompletionPercentage()
    {
        $items = $this->checklistItems;
        $total = count($items);

        if ($total > 0) {
            $done = 0;
            foreach ($items as $item) {
                if ($item->is_completed) {
                    $done++;
                }
            }
            return (int) round(($done / $total) * 100);
        }

        if ($this->status === self::STATUS_COMPLETED) {
            return 100;
        } elseif ($this->status === self::STATUS_IN_PROGRESS) {
            return 50;
        }

        return 0;
    }

    /**
     * Determina si la tarea está vencida.
     * @return bool
     */
    public function getIsOverdue()
    {
        if (empty($this->due_date)) {
            return false;
        }
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            return false;
        }

        return strtotime($this->due_date) < time();
    }

    /**
     * Procesa recordatorios pendientes generando notificaciones del sistema.
     * @param int|null $userId Si se proporciona, filtra las tareas asignadas o creadas por ese admin.
     * @return int Cantidad de recordatorios procesados
     */
    public static function processPendingReminders($userId = null)
    {
        $now = date('Y-m-d H:i:s');
        $query = static::find()
            ->where(['not in', 'status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]])
            ->andWhere(['reminder_sent' => 0])
            ->andWhere(['not', ['reminder_at' => null]])
            ->andWhere(['<=', 'reminder_at', $now]);

        if ($userId !== null) {
            $query->andWhere([
                'or',
                ['assigned_to' => $userId],
                ['and', ['assigned_to' => null], ['created_by' => $userId]],
            ]);
        }

        /** @var Todos[] $todos */
        $todos = $query->all();
        $processed = 0;

        foreach ($todos as $todo) {
            $targetUserId = $todo->assigned_to ?: $todo->created_by;
            if (!$targetUserId) {
                continue;
            }

            $title = "⏰ Recordatorio de Tarea: " . $todo->title;
            $body = "Tienes una tarea pendiente para revisión: \"{$todo->title}\".";
            if (!empty($todo->due_date)) {
                $body .= " Fecha límite: " . date('d/m/Y H:i', strtotime($todo->due_date)) . ".";
            }
            if ($todo->customer) {
                $body .= " Cliente: " . ($todo->customer->trade_name ?: $todo->customer->business_name) . ".";
            }

            $link = Url::to(['/todos/view', 'id' => $todo->id]);

            Notifications::create($targetUserId, $title, $body, $link, Notifications::TYPE_WARNING);

            $todo->reminder_sent = 1;
            $todo->save(false, ['reminder_sent', 'updated_at']);
            $processed++;
        }

        return $processed;
    }
}
