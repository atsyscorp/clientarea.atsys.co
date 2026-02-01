<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\User;

class ProfileForm extends Model
{
    public $username;
    public $email;
    public $password;
    public $confirm_password;

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

            // Contraseñas (Opcional, solo si escribe algo)
            ['password', 'string', 'min' => 6],
            ['confirm_password', 'compare', 'compareAttribute' => 'password', 'message' => 'Las contraseñas no coinciden.'],
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

        return $user->save();
    }
}