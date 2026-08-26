<?php

use yii\db\Migration;

/**
 * Class m260808_100000_add_email_sent_to_notifications_table
 */
class m260808_100000_add_email_sent_to_notifications_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%notifications}}', 'email_sent', $this->tinyInteger(1)->notNull()->defaultValue(0)->after('is_read'));

        $this->createIndex(
            'idx-notifications-unread-email',
            '{{%notifications}}',
            ['is_read', 'email_sent']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-notifications-unread-email', '{{%notifications}}');
        $this->dropColumn('{{%notifications}}', 'email_sent');
    }
}
