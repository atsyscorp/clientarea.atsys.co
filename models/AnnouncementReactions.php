<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "announcement_reactions".
 *
 * @property int $id
 * @property int $announcement_id
 * @property int $user_id
 * @property string $reaction_type
 * @property string|null $created_at
 */
class AnnouncementReactions extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'announcement_reactions';
    }

    public function rules()
    {
        return [
            [['announcement_id', 'user_id', 'reaction_type'], 'required'],
            [['announcement_id', 'user_id'], 'integer'],
            [['reaction_type'], 'string', 'max' => 20],
            [['created_at'], 'safe'],
            [['announcement_id', 'user_id'], 'unique', 'targetAttribute' => ['announcement_id', 'user_id']],
        ];
    }
}