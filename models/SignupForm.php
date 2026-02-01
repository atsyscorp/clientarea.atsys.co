<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\User; // Asegúrate de que este sea el namespace de tu tabla de usuarios

/**
 * SignupForm es el modelo detrás del formulario de registro.
 */
class SignupForm extends Model
{
    public $email;
    public $password;
    public $password_repeat; // Campo para confirmar contraseña

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
            ['email', 'unique', 'targetClass' => '\app\models\User', 'message' => 'Este correo electrónico ya está registrado.'],

            ['password', 'required', 'message' => 'La contraseña es obligatoria.'],
            ['password', 'string', 'min' => 6, 'message' => 'La contraseña debe tener al menos 6 caracteres.'],

            ['password_repeat', 'required', 'message' => 'Por favor, confirma la contraseña.'],
            ['password_repeat', 'compare', 'compareAttribute' => 'password', 'message' => 'Las contraseñas no coinciden.'],
        ];
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
            return null;
        }
        
        $user = new User();

        $emailPrefix = explode('@', $this->email)[0];
        $cleanPrefix = preg_replace('/[^a-zA-Z0-9]/', '', $emailPrefix);

        $user->username = $cleanPrefix . '_' . rand(1000, 9999);
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        
        // IMPORTANTE: Estado INACTIVO y generar token
        $user->status = User::STATUS_INACTIVE; 
        $user->generateEmailVerificationToken();
        
        // Si se guarda, enviamos el email
        return $user->save() && $this->sendEmail($user);
    }

    protected function sendEmail($user)
    {
        return Yii::$app->mailer->compose(
            ['html' => 'emailVerify-html', 'text' => 'emailVerify-text'],
            ['user' => $user]
        )
        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name ])
        ->setTo($this->email)
        ->setBcc(Yii::$app->params['adminEmail'])
        ->setSubject('Confirma tu registro en ' . Yii::$app->name)
        ->send();
    }
}