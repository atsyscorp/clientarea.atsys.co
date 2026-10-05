<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\Html;

/**
 * Model class for table "{{%meetings}}".
 *
 * @property int $id
 * @property int|null $customer_id
 * @property string $client_name
 * @property string $client_email
 * @property string $title
 * @property string|null $description
 * @property string $start_time
 * @property string $end_time
 * @property string|null $google_event_id
 * @property string|null $meet_url
 * @property string|null $calendar_html_link
 * @property string|null $notes
 * @property string $status
 * @property int|null $created_by
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Customers|null $customer
 * @property User|null $creator
 */
class Meetings extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELED = 'canceled';
    const STATUS_REJECTED = 'rejected';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%meetings}}';
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
            [['client_name', 'client_email', 'title', 'start_time', 'end_time'], 'required', 'message' => 'Este campo es obligatorio.'],
            [['client_email'], 'email', 'message' => 'Ingresa un correo electrónico válido.'],
            [['customer_id', 'created_by'], 'integer'],
            [['description', 'notes'], 'string'],
            [['start_time', 'end_time'], 'safe'],
            [['client_name', 'title', 'google_event_id'], 'string', 'max' => 255],
            [['meet_url', 'calendar_html_link'], 'string', 'max' => 500],
            [['status'], 'string', 'max' => 30],
            [['status'], 'default', 'value' => self::STATUS_SCHEDULED],
            [['status'], 'in', 'range' => array_keys(self::optsStatus())],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customers::class, 'targetAttribute' => ['customer_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'customer_id' => 'Cliente Registrado',
            'client_name' => 'Nombre del Asistente',
            'client_email' => 'Correo Electrónico',
            'title' => 'Asunto de la Reunión',
            'description' => 'Agenda / Descripción',
            'start_time' => 'Fecha y Hora de Inicio',
            'end_time' => 'Fecha y Hora de Finalización',
            'google_event_id' => 'ID Evento Google',
            'meet_url' => 'Enlace Google Meet',
            'calendar_html_link' => 'Ver en Google Calendar',
            'notes' => 'Minuta / Transcripción de Gemini',
            'status' => 'Estado',
            'created_by' => 'Agendado por',
            'created_at' => 'Fecha de Creación',
            'updated_at' => 'Última Actualización',
        ];
    }

    /**
     * Opciones de estado
     */
    public static function optsStatus()
    {
        return [
            self::STATUS_PENDING => 'Pendiente de Aprobación',
            self::STATUS_SCHEDULED => 'Programada',
            self::STATUS_COMPLETED => 'Realizada',
            self::STATUS_CANCELED => 'Cancelada',
            self::STATUS_REJECTED => 'Rechazada',
        ];
    }

    /**
     * Badge visual para el estado (DaisyUI)
     */
    public function getStatusBadge()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return '<span class="badge badge-warning text-white gap-1 text-xs font-semibold shadow-sm"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" /></svg> Pendiente por Aprobar</span>';
            case self::STATUS_COMPLETED:
                return '<span class="badge badge-success text-white gap-1 text-xs font-semibold"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg> Realizada</span>';
            case self::STATUS_CANCELED:
                return '<span class="badge badge-error text-white gap-1 text-xs font-semibold"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" /></svg> Cancelada</span>';
            case self::STATUS_REJECTED:
                return '<span class="badge badge-ghost text-base-content/70 gap-1 text-xs font-semibold"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" /></svg> Rechazada</span>';
            case self::STATUS_SCHEDULED:
            default:
                if ($this->isPast()) {
                    return '<span class="badge badge-warning text-white gap-1 text-xs font-semibold"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" /></svg> Pendiente por cerrar</span>';
                }
                return '<span class="badge badge-info text-white gap-1 text-xs font-semibold"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg> Programada</span>';
        }
    }

    /**
     * Duración en minutos
     */
    public function getDurationMinutes()
    {
        if (!$this->start_time || !$this->end_time) {
            return 30;
        }
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);
        return max(15, round(($end - $start) / 60));
    }

    /**
     * ¿Es una reunión pasada?
     */
    public function isPast()
    {
        return strtotime($this->end_time ?: $this->start_time) < time();
    }

    /**
     * ¿Es una reunión de hoy?
     */
    public function isToday()
    {
        return date('Y-m-d', strtotime($this->start_time)) === date('Y-m-d');
    }

    /**
     * Relación con el cliente registrado
     */
    public function getCustomer()
    {
        return $this->hasOne(Customers::class, ['id' => 'customer_id']);
    }

    /**
     * Relación con el usuario que creó la reunión
     */
    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }
}
