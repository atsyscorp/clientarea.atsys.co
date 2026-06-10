<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%ticket_spam_blacklist}}`.
 */
class m260605_193902_create_ticket_spam_blacklist_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%ticket_spam_blacklist}}', [
            'id' => $this->primaryKey(),
            'email' => $this->string(255)->notNull()->unique(),
            'reason' => $this->string(255)->null(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%ticket_spam_blacklist}}');
    }
}
