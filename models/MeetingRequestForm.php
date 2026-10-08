<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\components\TurnstileValidator;

/**
 * MeetingRequestForm modelo para la solicitud pública de reuniones.
 * Incluye protección anti-bot multicapa: Cloudflare Turnstile + Honeypot + validación de fecha.
 */
class MeetingRequestForm extends Model
{
    public $name;
    public $email;
    public $company;
    public $phone;
    public $title;
    public $description;
    public $requested_date;
    public $requested_time;
    public $duration_minutes = 30;

    /**
     * Campo Honeypot (trampa para bots).
     * Oculto en la interfaz mediante CSS. Si contiene algún valor, se descarta la solicitud.
     */
    public $website;

    /**
     * Token de verificación de Cloudflare Turnstile.
     */
    public $captcha;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'email', 'title', 'requested_date', 'requested_time'], 'required', 'message' => '{attribute} es obligatorio.'],
            ['name', 'string', 'max' => 150],
            ['company', 'string', 'max' => 150],
            ['phone', 'string', 'max' => 50],
            ['title', 'string', 'max' => 200],
            ['description', 'string', 'max' => 2000],
            ['email', 'trim'],
            ['email', 'email', 'message' => 'El formato del correo electrónico no es válido.'],
            ['email', 'string', 'max' => 150],
            ['duration_minutes', 'in', 'range' => [15, 30, 45, 60, 90]],

            // Validación de fecha no retroactiva
            ['requested_date', 'validateFutureDate'],

            // Honeypot: Debe estar estrictamente vacío
            ['website', 'validateHoneypot'],

            // Cloudflare Turnstile
            [['captcha'], 'string'],
            [['captcha'], TurnstileValidator::class, 'message' => 'Por favor, confirma que no eres un robot.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'name' => 'Tu Nombre Completo',
            'email' => 'Correo Electrónico',
            'company' => 'Empresa / Negocio (Opcional)',
            'phone' => 'Teléfono o WhatsApp (Opcional)',
            'title' => 'Asunto de la Reunión',
            'description' => 'Temas a tratar / Detalles de tu consulta',
            'requested_date' => 'Fecha Sugerida',
            'requested_time' => 'Hora Sugerida',
            'duration_minutes' => 'Duración Estimada',
            'website' => 'Sitio Web', // Trampa
            'captcha' => 'Verificación de Seguridad',
        ];
    }

    /**
     * Valida que la fecha y hora sugeridas se encuentren dentro del horario comercial activo (estilo WhatsApp).
     */
    public function validateFutureDate($attribute, $params)
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $today = date('Y-m-d');
        if ($this->requested_date < $today) {
            $this->addError($attribute, 'Por favor selecciona una fecha presente o futura.');
            return;
        }

        $schedule = SystemSettings::getMeetingSchedule();
        $timestamp = strtotime($this->requested_date);
        $dayOfWeek = (string)date('w', $timestamp); // 0=Domingo, 1=Lunes, ...
        $dayConfig = $schedule[$dayOfWeek] ?? null;

        if (!$dayConfig || empty($dayConfig['enabled'])) {
            $dayName = $dayConfig['name'] ?? 'el día seleccionado';
            $nextAvailable = SystemSettings::getNextAvailableBusinessDate();
            $formattedNext = date('d/m/Y', strtotime($nextAvailable['date']));
            $this->addError($attribute, "Los días {$dayName} no contamos con atención para reuniones. Siguiente día hábil disponible: {$formattedNext}.");
            return;
        }

        $currentTime = date('H:i');
        $startTime = $dayConfig['start'] ?? '08:00';
        $endTime = $dayConfig['end'] ?? '17:00';

        // Si la solicitud es para el día de hoy, verificar si ya se superó la hora límite
        if ($this->requested_date === $today) {
            if ($currentTime >= $endTime) {
                $nextAvailable = SystemSettings::getNextAvailableBusinessDate();
                $formattedNext = date('d/m/Y', strtotime($nextAvailable['date']));
                $this->addError($attribute, "La jornada de atención para hoy ha concluido (hora límite: {$endTime}). Te invitamos a seleccionar el siguiente día hábil ({$formattedNext}).");
                return;
            }

            // Validar si la hora seleccionada ya transcurrió hoy
            if (!empty($this->requested_time) && $this->requested_time <= $currentTime) {
                $this->addError('requested_time', 'La hora seleccionada ya ha transcurrido. Por favor selecciona una hora posterior.');
                return;
            }
        }

        // Validar que la hora seleccionada esté dentro de la franja de apertura y cierre del día
        if (!empty($this->requested_time)) {
            if ($this->requested_time < $startTime || $this->requested_time > $endTime) {
                $this->addError('requested_time', "La hora seleccionada ({$this->requested_time}) está fuera del horario de atención de los {$dayConfig['name']} ({$startTime} a {$endTime}).");
                return;
            }
        }
    }

    /**
     * Validación silenciosa del Honeypot
     */
    public function validateHoneypot($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            // Un bot llenó el campo oculto
            $this->addError($attribute, 'Solicitud no válida.');
        }
    }

    /**
     * Comprueba si el remitente es un cliente existente en plataforma
     * @return Customers|null
     */
    public function findRegisteredCustomer()
    {
        // 1. Buscar directamente en Customers por email
        $customer = Customers::find()->where(['email' => $this->email])->one();
        if ($customer) {
            return $customer;
        }

        // 2. Buscar por usuario asociado
        $user = User::find()->where(['email' => $this->email])->one();
        if ($user) {
            $ownerId = $user->parent_id ?: $user->id;
            return Customers::findOne(['user_id' => $ownerId]);
        }

        return null;
    }
}
