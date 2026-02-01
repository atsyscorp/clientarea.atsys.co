<?php

namespace app\models;

use yii\base\InvalidArgumentException;
use yii\base\Model;
use app\models\User;

/**
 * Formulario para restablecer contraseña
 */
class ResetPasswordForm extends Model
{
    public $password;
    public $confirm_password;

    /**
     * @var \app\models\User
     */
    private $_user;

    /**
     * Crea una instancia del modelo y valida el token.
     *
     * @param string $token
     * @param array $config
     * @throws InvalidArgumentException si el token está vacío o no es válido
     */
    public function __construct($token, $config = [])
    {
        if (empty($token) || !is_string($token)) {
            throw new InvalidArgumentException('El token de restablecimiento de contraseña no puede estar vacío.');
        }

        $this->_user = User::findByPasswordResetToken($token);

        if (!$this->_user) {
            throw new InvalidArgumentException('El enlace para restablecer la contraseña es inválido o ha expirado.');
        }

        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['password', 'confirm_password'], 'required', 'message' => 'Campo obligatorio.'],
            ['password', 'string', 'min' => 6, 'message' => 'La contraseña debe tener al menos 6 caracteres.'],
            ['confirm_password', 'compare', 'compareAttribute' => 'password', 'message' => 'Las contraseñas no coinciden.'],
        ];
    }

    /**
     * Restablece la contraseña.
     *
     * @return bool si la contraseña fue guardada correctamente
     */
    public function resetPassword()
    {
        $user = $this->_user;
        
        // Usamos los métodos que creamos anteriormente en tu modelo User
        $user->setPassword($this->password);
        $user->removePasswordResetToken();

        return $user->save(false);
    }
}