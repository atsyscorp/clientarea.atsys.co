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

    public $password;

    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;

    // Constantes de Roles
    const ROLE_CLIENT = 10;
    const ROLE_SUBACCOUNT = 11;
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
        // 1. Intentar buscar por auth_key del usuario directamente
        $user = static::findOne(['auth_key' => $token, 'status' => self::STATUS_ACTIVE]);
        if ($user) {
            return $user;
        }

        // 2. Si no se encuentra, buscar por api_token de la tabla customers
        $customer = \app\models\Customers::findOne([
            'api_token' => $token,
            'status' => \app\models\Customers::STATUS_ACTIVE
        ]);
        if ($customer && $customer->user_id) {
            return static::findOne(['id' => $customer->user_id, 'status' => self::STATUS_ACTIVE]);
        }

        return null;
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

    // Cuentas desglosadas.
    // 1. Relación para saber quién es el jefe
    public function getParentAccount()
    {
        return $this->hasOne(User::class, ['id' => 'parent_id']);
    }

    // 2. Saber si es sub-cuenta
    public function getIsSubAccount()
    {
        return $this->parent_id !== null && $this->role === self::ROLE_SUBACCOUNT;
    }

    // 3. Obtener el ID de "la empresa" (Si es jefe, su propio ID. Si es empleado, el ID del jefe)
    public function getCompanyOwnerId()
    {
        // 1. Identificamos el ID del usuario dueño (Titular)
        $ownerId = $this->parent_id ? $this->parent_id : $this->id;

        // 2. Buscamos el registro en la tabla Customers
        $customer = \app\models\Customers::findOne(['user_id' => $ownerId]);

        // 3. LA CORRECCIÓN: Validamos si el cliente existe antes de pedir el ->id
        // Devolvemos -1 si no existe para que el GridView simplemente salga vacío y no se rompa
        return $customer ? $customer->id : -1;
    }

    // --- HELPERS DE ROLES Y PERMISOS ---

    /**
     * ¿Es el dueño absoluto de la cuenta? (No tiene jefe)
     */
    public function getIsCustomerOwner()
    {
        return $this->parent_id === null && !$this->isAdmin;
    }

    /**
     * ¿Es un delegado con rol Administrativo (Backup)?
     */
    public function getIsCustomerAdmin()
    {
        return $this->parent_id !== null && $this->role == 12;
    }

    /**
     * ¿Es un delegado estándar? (Solo soporte)
     */
    public function getIsCustomerStandard()
    {
        return $this->parent_id !== null && $this->role == 11;
    }

    /**
     * ¿Puede ver y gestionar la Facturación / Pagos / Servicios?
     * (Solo el dueño y el delegado administrativo)
     */
    public function getCanManageBilling()
    {
        return $this->isCustomerOwner || $this->isCustomerAdmin;
    }

    /**
     * ¿Puede gestionar el equipo (crear otras sub-cuentas)?
     * (Usualmente solo el dueño, pero podrías agregar al Admin si quieres)
     */
    public function getCanManageTeam()
    {
        return $this->isCustomerOwner; // O cambiar a: return $this->isCustomerOwner || $this->isCustomerAdmin;
    }

    /**
     * Obtiene el ID real de la empresa (customer_id) sin importar si es el Titular o un Delegado.
     * @return int|null
     */
    public function getRealCustomerId()
    {
        // 1. Determinamos quién es el dueño de la empresa
        $ownerId = $this->parent_id ? $this->parent_id : $this->id;

        // 2. Buscamos la empresa vinculada a ese dueño
        $customer = \app\models\Customers::findOne(['user_id' => $ownerId]);

        // 3. Devolvemos el ID de la tabla customers
        return $customer ? $customer->id : null;
    }

    /**
     * Comprueba si el usuario tiene su correo registrado en la lista negra de tickets.
     * @return bool
     */
    public function getIsTicketBlocked()
    {
        if (empty($this->email)) {
            return false;
        }
        return \app\models\TicketSpamBlacklist::find()
            ->where(['email' => strtolower(trim($this->email))])
            ->exists();
    }

}
