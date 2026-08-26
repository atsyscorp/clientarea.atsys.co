<?php

use yii\db\Migration;

/**
 * Class m260730_200000_add_work_order_id_to_service_feedback
 */
class m260730_200000_add_work_order_id_to_service_feedback extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%service_feedback}}', 'work_order_id', $this->string(50)->null()->defaultValue(null));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%service_feedback}}', 'work_order_id');
    }
}
