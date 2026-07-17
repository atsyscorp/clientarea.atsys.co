<?php

use yii\db\Migration;

/**
 * Class m260716_161500_add_ticket_id_to_work_orders
 */
class m260716_161500_add_ticket_id_to_work_orders extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%work_orders}}', 'ticket_id', $this->integer()->null()->defaultValue(null));

        // Foreign key to tickets table
        $this->addForeignKey(
            'fk-work_orders-ticket_id',
            '{{%work_orders}}',
            'ticket_id',
            '{{%tickets}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-work_orders-ticket_id', '{{%work_orders}}');
        $this->dropColumn('{{%work_orders}}', 'ticket_id');
    }
}
