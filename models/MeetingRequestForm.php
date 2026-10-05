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
     * Valida que la fecha sugerida no sea pasada ni fin de semana (si aplica)
     */
    public function validateFutureDate($attribute, $params)
    {
        if ($this->hasErrors()) {
            return;
        }

        $today = date('Y-m-d');
        if ($this->requested_date < $today) {
            $this->addError($attribute, 'Por favor selecciona una fecha presente o futura.');
            return;
        }

        $timestamp = strtotime($this->requested_date);
        $dayOfWeek = date('w', $timestamp); // 0=Domingo, 6=Sábado
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            $this->addError($attribute, 'Las reuniones se agendan de lunes a viernes en horario laboral.');
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
