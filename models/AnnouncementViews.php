<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "announcement_views".
 *
 * @property int $id
 * @property int $announcement_id
 * @property int $user_id
 * @property string|null $viewed_at
 */
class AnnouncementViews extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'announcement_views';
    }

    public function rules()
    {
        return [
            [['announcement_id', 'user_id'], 'required'],
            [['announcement_id', 'user_id'], 'integer'],
            [['viewed_at'], 'safe'],
            [['announcement_id', 'user_id'], 'unique', 'targetAttribute' => ['announcement_id', 'user_id']], // Evita duplicados
        ];
    }
    
    // Relación para saber quién vio (si tienes modelo User o Customers)
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}