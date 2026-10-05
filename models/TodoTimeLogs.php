<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model class for table "{{%todo_time_logs}}".
 *
 * @property int $id
 * @property int $todo_id
 * @property int $user_id
 * @property string $start_time
 * @property string|null $end_time
 * @property int $duration_seconds
 * @property string|null $description
 * @property int $is_running
 * @property string $created_at
 *
 * @property Todos $todo
 * @property User $user
 */
class TodoTimeLogs extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%todo_time_logs}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['todo_id', 'user_id', 'start_time'], 'required'],
            [['todo_id', 'user_id', 'duration_seconds', 'is_running'], 'integer'],
            [['start_time', 'end_time', 'created_at'], 'safe'],
            [['description'], 'string'],
            [['duration_seconds'], 'default', 'value' => 0],
            [['is_running'], 'default', 'value' => 0],
            [['todo_id'], 'exist', 'skipOnError' => true, 'targetClass' => Todos::class, 'targetAttribute' => ['todo_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
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

        if ($insert && empty($this->created_at)) {
            $this->created_at = date('Y-m-d H:i:s');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'todo_id' => 'Tarea',
            'user_id' => 'Usuario',
            'start_time' => 'Hora Inicio',
            'end_time' => 'Hora Fin',
            'duration_seconds' => 'Duración (segundos)',
            'description' => 'Detalle del Trabajo',
            'is_running' => 'En Ejecución',
            'created_at' => 'Registrado el',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTodo()
    {
        return $this->hasOne(Todos::class, ['id' => 'todo_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Formatea la duración en horas, minutos y segundos.
     * @param int|null $seconds
     * @return string
     */
    public function getFormattedDuration()
    {
        $sec = (int) $this->duration_seconds;
        if ($this->is_running && !empty($this->start_time)) {
            $sec = max(0, time() - strtotime($this->start_time));
        }

        return static::formatSeconds($sec);
    }

    /**
     * Helper estático para dar formato a segundos
     * @param int $seconds
     * @return string
     */
    public static function formatSeconds($seconds)
    {
        $seconds = (int) $seconds;
        if ($seconds <= 0) {
            return '0m';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        if ($minutes > 0 || $hours > 0) {
            $parts[] = "{$minutes}m";
        }
        if ($hours === 0 && $remainingSeconds > 0) {
            $parts[] = "{$remainingSeconds}s";
        }

        return !empty($parts) ? implode(' ', $parts) : '0m';
    }
}
