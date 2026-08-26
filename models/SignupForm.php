<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\User;

use app\components\TurnstileValidator;

/**
 * SignupForm es el modelo detrás del formulario de registro.
 */
class SignupForm extends Model
{
    public $email;
    public $password;
    public $password_repeat; // Campo para confirmar contraseña
    public $captcha;

    /**
     * @var bool Indica si el correo de verificación salió bien. La cuenta se crea
     * igual aunque el envío falle; el controlador usa esto para el mensaje.
     */
    public $emailSent = false;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // Validaciones para email
            ['email', 'trim'],
            ['email', 'required', 'message' => 'El correo electrónico es obligatorio.'],
            ['email', 'email', 'message' => 'El formato del correo no es válido.'],
            ['email', 'string', 'max' => 255],
            ['email', 'validateEmail'],

            ['password', 'required', 'message' => 'La contraseña es obligatoria.'],
            ['password', 'string', 'min' => 6, 'message' => 'La contraseña debe tener al menos 6 caracteres.'],

            ['password_repeat', 'required', 'message' => 'Por favor, confirma la contraseña.'],
            ['password_repeat', 'compare', 'compareAttribute' => 'password', 'message' => 'Las contraseñas no coinciden.'],

            [['captcha'], 'string'],
            [['captcha'], TurnstileValidator::class, 'message' => 'Por favor, confirma que no eres un robot.'],
        ];
    }

    /**
     * Valida que el correo electrónico no pertenezca a una cuenta activa existente.
     */
    public function validateEmail($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $existingUser = User::findOne(['email' => $this->email]);
            if ($existingUser && $existingUser->status === User::STATUS_ACTIVE) {
                $this->addError($attribute, 'Este correo electrónico ya está registrado.');
            }
        }
    }

    /**
     * Nombres de las etiquetas de los atributos
     */
    public function attributeLabels()
    {
        return [
            'email' => 'Correo Electrónico',
            'password' => 'Contraseña',
            'password_repeat' => 'Confirmar Contraseña',
        ];
    }

    public function signup()
    {
        if (!$this->validate()) {
            return false;
        }

        // Buscar si ya existía un registro inactivo previo con este email
        $user = User::findOne(['email' => $this->email, 'status' => User::STATUS_INACTIVE]);
        if (!$user) {
            $user = new User();
            $emailPrefix = explode('@', $this->email)[0];
            $cleanPrefix = preg_replace('/[^a-zA-Z0-9]/', '', $emailPrefix);
            $user->username = $cleanPrefix . '_' . rand(1000, 9999);
            $user->email = $this->email;
        }

        $user->setPassword($this->password);
        $user->generateAuthKey();

        // IMPORTANTE: Estado INACTIVO y generar token
        $user->status = User::STATUS_INACTIVE;
        $user->generateEmailVerificationToken();

        // Si se guarda, enviamos el email
        if ($user->save(false)) {
            $this->emailSent = (bool) $this->sendEmail($user);
            return true;
        }
        return false;
    }

    protected function sendEmail($user)
    {
        try {
            return Yii::$app->mailer->compose(
                ['html' => 'emailVerify-html', 'text' => 'emailVerify-text'],
                ['user' => $user]
            )
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setTo($this->email)
                ->setBcc(Yii::$app->params['adminEmail'])
                ->setSubject('Confirma tu registro en ' . Yii::$app->name)
                ->send();
        } catch (\Throwable $e) {
            Yii::error("Fallo al enviar correo de verificación a {$this->email}: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}