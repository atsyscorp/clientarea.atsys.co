<?php

use yii\db\Migration;

class m260827_083500_add_require_invoice_to_orders extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%orders}}', 'require_invoice', $this->boolean()->defaultValue(false));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%orders}}', 'require_invoice');
    }
}
