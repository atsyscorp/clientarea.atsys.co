<?php

use yii\db\Migration;

/**
 * Class m260723_170000_add_is_locked_to_tickets_table
 */
class m260723_170000_add_is_locked_to_tickets_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%tickets}}', 'is_locked', $this->boolean()->defaultValue(false)->after('status'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%tickets}}', 'is_locked');
    }
}
