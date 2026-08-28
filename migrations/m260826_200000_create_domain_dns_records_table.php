<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%domain_dns_records}}`.
 */
class m260826_200000_create_domain_dns_records_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%domain_dns_records}}', [
            'id' => $this->primaryKey(),
            'customer_service_id' => $this->integer()->notNull(),
            'host' => $this->string()->notNull(),
            'record_type' => $this->string(10)->notNull(),
            'address' => $this->string()->notNull(),
            'mx_pref' => $this->integer()->defaultValue(10),
            'ttl' => $this->integer()->defaultValue(1800),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex(
            '{{%idx-domain_dns_records-customer_service_id}}',
            '{{%domain_dns_records}}',
            'customer_service_id'
        );

        $this->addForeignKey(
            '{{%fk-domain_dns_records-customer_service_id}}',
            '{{%domain_dns_records}}',
            'customer_service_id',
            '{{%customer_services}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey(
            '{{%fk-domain_dns_records-customer_service_id}}',
            '{{%domain_dns_records}}'
        );

        $this->dropIndex(
            '{{%idx-domain_dns_records-customer_service_id}}',
            '{{%domain_dns_records}}'
        );

        $this->dropTable('{{%domain_dns_records}}');
    }
}
