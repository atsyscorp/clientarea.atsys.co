<?php

use yii\db\Migration;

/**
 * Class m260617_152200_add_cc_emails_to_tickets_table
 */
class m260617_152200_add_cc_emails_to_tickets_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%tickets}}', 'cc_emails', $this->text()->null()->after('email'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%tickets}}', 'cc_emails');
    }
}
