<?php

use yii\db\Migration;

/**
 * Handles altering column `attachment` in table `{{%ticket_replies}}` to TEXT.
 */
class m260916_150000_alter_ticket_replies_attachment_to_text extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%ticket_replies}}', 'attachment', $this->text()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%ticket_replies}}', 'attachment', $this->string(255)->null());
    }
}
