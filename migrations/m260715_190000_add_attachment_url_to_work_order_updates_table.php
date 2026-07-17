<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%work_order_updates}}`.
 */
class m260715_190000_add_attachment_url_to_work_order_updates_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%work_order_updates}}', 'attachment_url', $this->string(500)->null());
        $this->addColumn('{{%work_order_updates}}', 'reply_attachment_url', $this->string(500)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%work_order_updates}}', 'attachment_url');
        $this->dropColumn('{{%work_order_updates}}', 'reply_attachment_url');
    }
}
