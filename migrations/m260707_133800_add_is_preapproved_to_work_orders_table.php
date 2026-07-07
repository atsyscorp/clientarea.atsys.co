<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%work_orders}}`.
 */
class m260707_133800_add_is_preapproved_to_work_orders_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%work_orders}}', 'is_preapproved', $this->boolean()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%work_orders}}', 'is_preapproved');
    }
}
