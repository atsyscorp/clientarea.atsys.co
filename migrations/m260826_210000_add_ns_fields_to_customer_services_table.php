<?php

use yii\db\Migration;

/**
 * Class m260826_210000_add_ns_fields_to_customer_services_table
 */
class m260826_210000_add_ns_fields_to_customer_services_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%customer_services}}', 'ns1', $this->string(100)->defaultValue('ns1.atsys.co'));
        $this->addColumn('{{%customer_services}}', 'ns2', $this->string(100)->defaultValue('ns2.atsys.co'));
        $this->addColumn('{{%customer_services}}', 'ns3', $this->string(100)->defaultValue('ns3.atsys.co'));
        $this->addColumn('{{%customer_services}}', 'ns4', $this->string(100)->defaultValue('ns4.atsys.co'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%customer_services}}', 'ns1');
        $this->dropColumn('{{%customer_services}}', 'ns2');
        $this->dropColumn('{{%customer_services}}', 'ns3');
        $this->dropColumn('{{%customer_services}}', 'ns4');
    }
}
