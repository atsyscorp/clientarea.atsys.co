<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model class for table "{{%todo_checklist_items}}".
 *
 * @property int $id
 * @property int $todo_id
 * @property string $title
 * @property int $is_completed
 * @property int $sort_order
 * @property string $created_at
 *
 * @property Todos $todo
 */
class TodoChecklistItems extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%todo_checklist_items}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['todo_id', 'title'], 'required'],
            [['todo_id', 'is_completed', 'sort_order'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['is_completed'], 'default', 'value' => 0],
            [['sort_order'], 'default', 'value' => 0],
            [['todo_id'], 'exist', 'skipOnError' => true, 'targetClass' => Todos::class, 'targetAttribute' => ['todo_id' => 'id']],
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
            'title' => 'Subtarea',
            'is_completed' => 'Completada',
            'sort_order' => 'Orden',
            'created_at' => 'Creada el',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTodo()
    {
        return $this->hasOne(Todos::class, ['id' => 'todo_id']);
    }
}
