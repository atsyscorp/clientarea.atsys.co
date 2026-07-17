<?php

use yii\db\Migration;

/**
 * Class m260717_032500_create_notifications_table
 */
class m260717_032500_create_notifications_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%notifications}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'body' => $this->text()->notNull(),
            'link' => $this->string(255)->null()->defaultValue(null),
            'type' => $this->string(50)->notNull()->defaultValue('info'), // info, success, warning, danger, promo
            'is_read' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $this->dateTime()->notNull(),
        ]);

        // Foreign key to user table
        $this->addForeignKey(
            'fk-notifications-user_id',
            '{{%notifications}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Index for performance querying unread notifications of a user
        $this->createIndex(
            'idx-notifications-user_id-is_read',
            '{{%notifications}}',
            ['user_id', 'is_read']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-notifications-user_id', '{{%notifications}}');
        $this->dropIndex('idx-notifications-user_id-is_read', '{{%notifications}}');
        $this->dropTable('{{%notifications}}');
    }
}
