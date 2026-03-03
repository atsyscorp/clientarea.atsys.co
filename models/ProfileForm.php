<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\User;
use borales\extensions\phoneInput\PhoneInputValidator;

class ProfileForm extends Model
{
    public $username;
    public $email;
    public $password;
    public $confirm_password;
    //public $mobile;
    //public $otp;

    private $_user;

    public function __construct($user, $config = [])
    {
        $this->_user = $user;
        $this->username = $user->username; // O 'name', según tu tabla
        $this->email = $user->email;
        parent::__construct($config);
    }

    public function rules()
    {
        return [
            [['email'], 'required'],
            ['email', 'email'],
            
            // Validar que el email no lo use otro usuario
            ['email', 'unique', 'targetClass' => User::class, 'filter' => ['!=', 'id', $this->_user->id], 'message' => 'Este correo ya está registrado.'],

            /*
            ['mobile', 'trim'],
            ['mobile', 'required', 'message' => 'El número de celular es obligatorio.'],
            [['mobile'], PhoneInputValidator::className(), 'message' => 'El número de celular no es válido.'],
            */

            // Contraseñas (Opcional, solo si escribe algo)
            ['password', 'string', 'min' => 6],
            ['confirm_password', 'compare', 'compareAttribute' => 'password', 'message' => 'Las contraseñas no coinciden.'],

            /*
            ['otp', 'required', 'message' => 'El código de verificación es obligatorio.', 'when' => function ($model) {
                return Yii::$app->session->has('whatsapp_otp');
            }],
            */
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'Nombre Completo',
            'email' => 'Correo Electrónico',
            'password' => 'Nueva Contraseña (Opcional)',
            'confirm_password' => 'Confirmar Contraseña',
        ];
    }

    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $user = $this->_user;
        $user->email = $this->email;

        // Solo cambiamos la clave si el usuario escribió una nueva
        if (!empty($this->password)) {
            $user->setPassword($this->password);
        }

        /*
        if($user->mobile != $this->mobile) {

            $codigoOtp = (string)rand(100000, 999999);
            Yii::$app->session->set('whatsapp_otp', $codigoOtp);
            Yii::$app->session->set('whatsapp_mobile', $this->mobile);

            $job = new \app\jobs\WhatsappJob([
                'phone' => $this->mobile,
                'message' => $codigoOtp,
                'webhookUrl' => 'https://n8n.atsys.co/webhook/atsys-otp-alert'
            ]);
            Yii::$app->queue->push($job);

        }
        */

        return $user->save();
    }

    protected function sendEmail($user)
    {
        Yii::$app->mailer->compose(
            ['html' => 'emailVerify-html', 'text' => 'emailVerify-text'],
            ['user' => $user]
        )
        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name ])
        ->setTo($this->email)
        ->setBcc(Yii::$app->params['adminEmail'])
        ->setSubject('Confirma tu registro en ' . Yii::$app->name)
        ->send();

        /*
        $job = new \app\jobs\WhatsappJob([
            'phone' => $this->mobile,
            'message' => $user->verification_token,
            'webhookUrl' => 'https://n8n.atsys.co/webhook/atsys-clientarea-alert'
        ]);
        Yii::$app->queue->push($job);
        */

        return true;
    }
}