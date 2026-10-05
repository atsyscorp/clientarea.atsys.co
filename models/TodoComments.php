<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model class for table "{{%todo_comments}}".
 *
 * @property int $id
 * @property int $todo_id
 * @property int $user_id
 * @property string $comment
 * @property string $created_at
 *
 * @property Todos $todo
 * @property User $user
 */
class TodoComments extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%todo_comments}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['todo_id', 'user_id', 'comment'], 'required'],
            [['todo_id', 'user_id'], 'integer'],
            [['comment'], 'string'],
            [['todo_id'], 'exist', 'skipOnError' => true, 'targetClass' => Todos::class, 'targetAttribute' => ['todo_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && empty($this->created_at)) {
            $this->created_at = date('Y-m-d H:i:s');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'todo_id' => 'Tarea',
            'user_id' => 'Usuario',
            'comment' => 'Comentario / Nota de Seguimiento',
            'created_at' => 'Fecha',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTodo()
    {
        return $this->hasOne(Todos::class, ['id' => 'todo_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
