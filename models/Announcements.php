<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "announcements".
 *
 * @property int $id
 * @property string|null $type
 * @property string $title
 * @property string $content
 * @property int|null $is_active
 * @property string|null $created_at
 * @property string|null $expires_at
 * @property int|null $created_by
 */
class Announcements extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_DANGER = 'danger';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'announcements';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['expires_at', 'created_by'], 'default', 'value' => null],
            [['type'], 'default', 'value' => 'info'],
            [['is_active'], 'default', 'value' => 1],
            [['type', 'content'], 'string'],
            [['title', 'content'], 'required'],
            [['is_active', 'created_by'], 'integer'],
            [['created_at', 'expires_at'], 'safe'],
            [['title'], 'string', 'max' => 255],
            ['type', 'in', 'range' => array_keys(self::optsType())],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'title' => 'Title',
            'content' => 'Content',
            'is_active' => 'Is Active',
            'created_at' => 'Created At',
            'expires_at' => 'Expires At',
            'created_by' => 'Created By',
        ];
    }


    /**
     * column type ENUM value labels
     * @return string[]
     */
    public static function optsType()
    {
        return [
            self::TYPE_INFO => 'info',
            self::TYPE_SUCCESS => 'success',
            self::TYPE_WARNING => 'warning',
            self::TYPE_DANGER => 'danger',
        ];
    }

    /**
     * @return string
     */
    public function displayType()
    {
        return self::optsType()[$this->type];
    }

    /**
     * @return bool
     */
    public function isTypeInfo()
    {
        return $this->type === self::TYPE_INFO;
    }

    public function setTypeToInfo()
    {
        $this->type = self::TYPE_INFO;
    }

    /**
     * @return bool
     */
    public function isTypeSuccess()
    {
        return $this->type === self::TYPE_SUCCESS;
    }

    public function setTypeToSuccess()
    {
        $this->type = self::TYPE_SUCCESS;
    }

    /**
     * @return bool
     */
    public function isTypeWarning()
    {
        return $this->type === self::TYPE_WARNING;
    }

    public function setTypeToWarning()
    {
        $this->type = self::TYPE_WARNING;
    }

    /**
     * @return bool
     */
    public function isTypeDanger()
    {
        return $this->type === self::TYPE_DANGER;
    }

    public function setTypeToDanger()
    {
        $this->type = self::TYPE_DANGER;
    }

    // Helper para verificar si está vigente (No ha expirado)
    public static function findActive()
    {
        return static::find()
            ->where(['is_active' => 1])
            ->andWhere(['OR', ['expires_at' => null], ['>=', 'expires_at', date('Y-m-d H:i:s')]]);
    }

    /**
     * Relación con las Vistas
     */
    public function getViews()
    {
        return $this->hasMany(AnnouncementViews::class, ['announcement_id' => 'id']);
    }

    /**
     * Relación con las Reacciones
     */
    public function getReactions()
    {
        return $this->hasMany(AnnouncementReactions::class, ['announcement_id' => 'id']);
    }

    /**
     * Helper: Cuenta total de vistas (para no traer todos los registros si solo quieres el número)
     */
    public function getViewsCount()
    {
        return $this->getViews()->count();
    }
    
    /**
     * Helper: Obtener usuarios que dieron Like/Love/etc
     */
    public function getReactors()
    {
        // Esto te devolvería los IDs de usuarios que reaccionaron
        return $this->getReactions()->select('user_id')->column();
    }
}
