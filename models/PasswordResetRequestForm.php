<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\User;

/**
 * Password reset request form
 */
class PasswordResetRequestForm extends Model
{
    public $email;

    public function rules()
    {
        return [
            ['email', 'trim'],
            ['email', 'required', 'message' => 'El correo es obligatorio.'],
            ['email', 'email', 'message' => 'Formato de correo inválido.'],
            ['email', 'exist',
                'targetClass' => '\app\models\User',
                'filter' => ['status' => User::STATUS_ACTIVE], // Solo usuarios activos
                'message' => 'No encontramos un usuario con este correo.'
            ],
        ];
    }

    /**
     * Envía el correo con el enlace
     */
    public function sendEmail()
    {
        /* @var $user User */
        $user = User::findOne([
            'status' => User::STATUS_ACTIVE,
            'email' => $this->email,
        ]);

        if (!$user) {
            return false;
        }
        
        // Verificamos si ya tiene un token válido para no generar uno nuevo a cada rato
        if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
            $user->generatePasswordResetToken();
            if (!$user->save()) {
                return false;
            }
        }

        // Generamos el link de forma segura usando el UrlManager
        // Esto crea: tudominio.com/site/reset-password?token=XYZ
        $resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token]);

        try {
            return Yii::$app->mailer->compose(['html' => 'passwordResetToken-html'], ['user' => $user, 'link' => $resetLink])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                ->setTo($this->email)
                ->setSubject('Restablecer contraseña - ' . Yii::$app->name)
                ->send();
        } catch (\Throwable $e) {
            Yii::error("Error enviando correo de restablecimiento a {$this->email}: " . $e->getMessage());
            return false;
        }
    }
}