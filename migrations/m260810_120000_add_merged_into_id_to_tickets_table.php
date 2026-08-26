<?php

use yii\db\Migration;

/**
 * Class m260810_120000_add_merged_into_id_to_tickets_table
 */
class m260810_120000_add_merged_into_id_to_tickets_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%tickets}}', 'merged_into_id', $this->integer()->null()->defaultValue(null)->after('is_locked'));

        $this->createIndex(
            'idx-tickets-merged_into_id',
            '{{%tickets}}',
            'merged_into_id'
        );

        $this->addForeignKey(
            'fk-tickets-merged_into_id',
            '{{%tickets}}',
            'merged_into_id',
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
        $this->dropForeignKey('fk-tickets-merged_into_id', '{{%tickets}}');
        $this->dropIndex('idx-tickets-merged_into_id', '{{%tickets}}');
        $this->dropColumn('{{%tickets}}', 'merged_into_id');
    }
}
