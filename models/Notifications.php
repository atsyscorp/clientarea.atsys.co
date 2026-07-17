<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "notifications".
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $body
 * @property string|null $link
 * @property string $type
 * @property int $is_read
 * @property string $created_at
 *
 * @property User $user
 */
class Notifications extends \yii\db\ActiveRecord
{
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_DANGER = 'danger';
    const TYPE_PROMO = 'promo';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notifications';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'title', 'body'], 'required'],
            [['user_id', 'is_read'], 'integer'],
            [['body'], 'string'],
            [['created_at', 'link'], 'safe'],
            [['title', 'link'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 50],
            [['type'], 'default', 'value' => self::TYPE_INFO],
            [['is_read'], 'default', 'value' => 0],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Usuario',
            'title' => 'Título',
            'body' => 'Mensaje',
            'link' => 'Enlace',
            'type' => 'Tipo',
            'is_read' => 'Leído',
            'created_at' => 'Fecha de Creación',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Helper to get options for type
     * @return array
     */
    public static function optsType()
    {
        return [
            self::TYPE_INFO => 'Información',
            self::TYPE_SUCCESS => 'Éxito',
            self::TYPE_WARNING => 'Advertencia',
            self::TYPE_DANGER => 'Peligro',
            self::TYPE_PROMO => 'Publicitario / Especial',
        ];
    }

    /**
     * Creates a single notification for a specific user.
     *
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param string|null $link
     * @param string $type
     * @return bool
     */
    public static function create($userId, $title, $body, $link = null, $type = self::TYPE_INFO)
    {
        $notification = new self();
        $notification->user_id = $userId;
        $notification->title = $title;
        $notification->body = $body;
        $notification->link = $link;
        $notification->type = $type;
        $notification->is_read = 0;
        $notification->created_at = date('Y-m-d H:i:s');
        return $notification->save();
    }

    /**
     * Sends a notification to all administrator users.
     *
     * @param string $title
     * @param string $body
     * @param string|null $link
     * @param string $type
     */
    public static function notifyAdmins($title, $body, $link = null, $type = self::TYPE_INFO)
    {
        $admins = User::find()->where(['role' => User::ROLE_ADMIN])->all();
        foreach ($admins as $admin) {
            self::create($admin->id, $title, $body, $link, $type);
        }
    }

    /**
     * Sends a notification to a customer's primary owner and all their delegates (subaccounts).
     *
     * @param int $customerId
     * @param string $title
     * @param string $body
     * @param string|null $link
     * @param string $type
     */
    public static function notifyCustomer($customerId, $title, $body, $link = null, $type = self::TYPE_INFO)
    {
        $customer = Customers::findOne($customerId);
        if (!$customer) {
            return;
        }

        // Notify the owner (if linked to a user account)
        if ($customer->user_id) {
            self::create($customer->user_id, $title, $body, $link, $type);
        }

        // Notify all delegates (subaccounts)
        foreach ($customer->delegates as $delegate) {
            self::create($delegate->id, $title, $body, $link, $type);
        }
    }

    /**
     * Sends a notification to all client users (excluding admins).
     *
     * @param string $title
     * @param string $body
     * @param string|null $link
     * @param string $type
     */
    public static function notifyAllClients($title, $body, $link = null, $type = self::TYPE_PROMO)
    {
        $clients = User::find()
            ->where(['in', 'role', [User::ROLE_CLIENT, User::ROLE_SUBACCOUNT]])
            ->all();

        foreach ($clients as $client) {
            self::create($client->id, $title, $body, $link, $type);
        }
    }
}
