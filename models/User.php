<?php

namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\Security;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements \yii\web\IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;

    // Constantes de Roles
    const ROLE_CLIENT = 10;
    const ROLE_ADMIN = 20;

    public static function tableName() {
        return '{{%user}}';
    }

    public function behaviors() {
        return [
            TimestampBehavior::className(),
        ];
    }

    public static function findIdentity($id) {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findIdentityByAccessToken($token, $type = null) {
        return static::findOne(['auth_key' => $token, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findByUsername($username) {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findByEmail($username) {
        return static::findOne(['email' => $username]);
    }

    public static function findByPasswordResetToken($token) {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    public static function isPasswordResetTokenValid($token) {
        if (empty($token)) { return false; }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Genera el hash de la contraseña a partir del password en texto plano.
     * @param string $password
     */
    public function setPassword($password)
    {
        // IMPORTANTE: Asegúrate de que en tu Base de Datos la columna se llame 'password_hash'
        // Si se llama solo 'password', cambia la línea a: $this->password = ...
        $this->password_hash = \Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Genera la clave de autenticación "remember me" (necesario para el registro).
     */
    public function generateAuthKey()
    {
        $this->auth_key = \Yii::$app->security->generateRandomString();
    }

    // Método para generar el token de verificación
    public function generateEmailVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    // Método para buscar por token de verificación
    public static function findByVerificationToken($token) {
        return static::findOne([
            'verification_token' => $token,
            'status' => self::STATUS_INACTIVE
        ]);
    }

    /**
     * Elimina el token de verificación de correo.
     */
    public function removeEmailVerificationToken()
    {
        $this->verification_token = null;
    }

    // Relación: Un Usuario TIENE UN perfil de Cliente
    public function getCustomer()
    {
        return $this->hasOne(\app\models\Customers::class, ['user_id' => 'id']);
    }

    // Helper para saber si es admin (útil para las vistas)
    public function getIsAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Genera un nuevo token de reset de password
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Elimina el token de reset de password
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

}
