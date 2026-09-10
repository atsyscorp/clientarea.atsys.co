<?php

use yii\db\Migration;

/**
 * Class m260909_140000_add_upgrade_to_order_items
 */
class m260909_140000_add_upgrade_to_order_items extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Ampliar action_type a VARCHAR(50) para mayor flexibilidad sin restricciones de enum
        $this->alterColumn('{{%order_items}}', 'action_type', $this->string(50)->notNull()->defaultValue('register'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%order_items}}', 'action_type', $this->string(50)->defaultValue('register'));
    }
}
