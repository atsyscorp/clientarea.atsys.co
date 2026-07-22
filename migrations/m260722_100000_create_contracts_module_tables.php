<?php

use yii\db\Migration;

/**
 * Class m260722_100000_create_contracts_module_tables
 */
class m260722_100000_create_contracts_module_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Crear tabla contracts
        $this->createTable('{{%contracts}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'code' => $this->string(50)->notNull(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'total_amount' => $this->decimal(12, 2)->defaultValue(0.00),
            'currency' => $this->string(3)->defaultValue('COP'),
            'start_date' => $this->date()->null(),
            'end_date' => $this->date()->null(),
            'status' => $this->tinyInteger(1)->defaultValue(1), // 0: Borrador, 1: Activo, 2: Suspendido, 3: Finalizado, 4: Cancelado
            'progress_percentage' => $this->decimal(5, 2)->defaultValue(0.00),
            'progress_mode' => $this->tinyInteger(1)->defaultValue(0), // 0: Automático, 1: Manual
            'contract_file' => $this->string(500)->null(),
            'created_at' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
        ]);

        $this->createIndex('idx-contracts-code', '{{%contracts}}', 'code', true);
        $this->createIndex('idx-contracts-customer_id', '{{%contracts}}', 'customer_id');

        $this->addForeignKey(
            'fk-contracts-customer_id',
            '{{%contracts}}',
            'customer_id',
            '{{%customers}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // 2. Agregar columnas a work_orders
        $this->addColumn('{{%work_orders}}', 'contract_id', $this->integer()->null()->defaultValue(null));
        $this->addColumn('{{%work_orders}}', 'progress_percentage', $this->decimal(5, 2)->defaultValue(0.00));

        $this->createIndex('idx-work_orders-contract_id', '{{%work_orders}}', 'contract_id');
        $this->addForeignKey(
            'fk-work_orders-contract_id',
            '{{%work_orders}}',
            'contract_id',
            '{{%contracts}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // 3. Crear tabla contract_tasks
        $this->createTable('{{%contract_tasks}}', [
            'id' => $this->primaryKey(),
            'contract_id' => $this->integer()->notNull(),
            'work_order_id' => $this->integer()->null()->defaultValue(null),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'weight_percentage' => $this->decimal(5, 2)->defaultValue(0.00),
            'progress_percentage' => $this->decimal(5, 2)->defaultValue(0.00),
            'status' => $this->tinyInteger(1)->defaultValue(0), // 0: Pendiente, 1: En Progreso, 2: Completada
            'due_date' => $this->date()->null(),
            'created_at' => $this->dateTime()->null(),
        ]);

        $this->createIndex('idx-contract_tasks-contract_id', '{{%contract_tasks}}', 'contract_id');
        $this->addForeignKey(
            'fk-contract_tasks-contract_id',
            '{{%contract_tasks}}',
            'contract_id',
            '{{%contracts}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-contract_tasks-work_order_id',
            '{{%contract_tasks}}',
            'work_order_id',
            '{{%work_orders}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // 4. Crear tabla contract_documents
        $this->createTable('{{%contract_documents}}', [
            'id' => $this->primaryKey(),
            'contract_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'file_url' => $this->string(500)->notNull(),
            'uploaded_at' => $this->dateTime()->null(),
        ]);

        $this->addForeignKey(
            'fk-contract_documents-contract_id',
            '{{%contract_documents}}',
            'contract_id',
            '{{%contracts}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%contract_documents}}');
        $this->dropTable('{{%contract_tasks}}');

        $this->dropForeignKey('fk-work_orders-contract_id', '{{%work_orders}}');
        $this->dropIndex('idx-work_orders-contract_id', '{{%work_orders}}');
        $this->dropColumn('{{%work_orders}}', 'progress_percentage');
        $this->dropColumn('{{%work_orders}}', 'contract_id');

        $this->dropTable('{{%contracts}}');
    }
}
