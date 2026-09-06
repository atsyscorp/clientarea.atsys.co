<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "announcement_comments".
 *
 * @property int $id
 * @property int $announcement_id
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $comment
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Announcements $announcement
 * @property AnnouncementComments $parent
 * @property AnnouncementComments[] $replies
 * @property User $user
 */
class AnnouncementComments extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'announcement_comments';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['announcement_id', 'user_id', 'comment'], 'required'],
            [['announcement_id', 'user_id', 'parent_id'], 'integer'],
            [['comment'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcements::class, 'targetAttribute' => ['announcement_id' => 'id']],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => AnnouncementComments::class, 'targetAttribute' => ['parent_id' => 'id']],
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
            'announcement_id' => 'Announcement ID',
            'user_id' => 'User ID',
            'parent_id' => 'Parent ID',
            'comment' => 'Comment',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Announcement]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAnnouncement()
    {
        return $this->hasOne(Announcements::class, ['id' => 'announcement_id']);
    }

    /**
     * Gets query for [[Parent]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(AnnouncementComments::class, ['id' => 'parent_id']);
    }

    /**
     * Gets query for [[Replies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getReplies()
    {
        return $this->hasMany(AnnouncementComments::class, ['parent_id' => 'id']);
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
}
