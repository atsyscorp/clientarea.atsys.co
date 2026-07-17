<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

class WorkOrderUpdates extends \yii\db\ActiveRecord
{
    /**
     * @var \yii\web\UploadedFile
     */
    public $attachmentFile;
    
    /**
     * @var \yii\web\UploadedFile
     */
    public $replyAttachmentFile;

    public static function tableName()
    {
        return 'work_order_updates';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
                'value' => date('Y-m-d H:i:s'), // Usamos PHP date para evitar el error de mPDF si quisieras imprimir esto
            ],
        ];
    }

    public function rules()
    {
        return [
            /*
            [['work_order_id', 'description'], 'required'],
            [['work_order_id', 'created_by', 'is_visible', 'notify_email', 'allow_reply', 'replied_by'], 'integer'],
            [['allow_reply'], 'default', 'value' => 0],
            [['description'], 'string'],
            */

            [['created_at', 'client_reply', 'replied_by', 'replied_at', 'attachment_url', 'reply_attachment_url'], 'default', 'value' => null],
            [['allow_reply'], 'default', 'value' => 0],
            [['work_order_id', 'description'], 'required'],
            [['work_order_id', 'created_by', 'is_visible', 'notify_email', 'allow_reply', 'replied_by'], 'integer'],
            [['description', 'client_reply', 'attachment_url', 'reply_attachment_url'], 'string'],
            [['created_at', 'replied_at'], 'safe'],
            [['work_order_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkOrders::class, 'targetAttribute' => ['work_order_id' => 'id']],
            [['attachmentFile', 'replyAttachmentFile'], 'file', 'skipOnEmpty' => true, 'maxSize' => 1024 * 1024 * 15],
        ];
    }

    public function getWorkOrder()
    {
        return $this->hasOne(WorkOrders::class, ['id' => 'work_order_id']);
    }
    
    // Relación con el usuario que escribió la nota (Admin)
    public function getAuthor()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }
}