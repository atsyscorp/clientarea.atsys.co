<?php

use yii\db\Migration;

/**
 * Class m260805_170000_create_projects_module_tables
 */
class m260805_170000_create_projects_module_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $now = date('Y-m-d H:i:s');

        // 1. Crear tabla projects
        $this->createTable('{{%projects}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'code' => $this->string(50)->notNull(),
            'name' => $this->string(255)->notNull(),
            'business_name' => $this->string(255)->null(),
            'document_number' => $this->string(50)->null(),
            'address' => $this->text()->null(),
            'is_default' => $this->tinyInteger(1)->defaultValue(0),
            'status' => $this->tinyInteger(1)->defaultValue(1), // 1: Activo, 0: Inactivo
            'notes' => $this->text()->null(),
            'created_at' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
        ]);

        $this->createIndex('idx-projects-customer_id', '{{%projects}}', 'customer_id');
        $this->addForeignKey(
            'fk-projects-customer_id',
            '{{%projects}}',
            'customer_id',
            '{{%customers}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // 2. Agregar columna project_id temporalmente nulable a work_orders
        $this->addColumn('{{%work_orders}}', 'project_id', $this->integer()->null()->defaultValue(null));

        // 3. Crear proyecto por defecto para cada cliente existente
        $customers = (new \yii\db\Query())
            ->select(['id', 'business_name', 'trade_name', 'document_number', 'address'])
            ->from('{{%customers}}')
            ->all();

        foreach ($customers as $customer) {
            $name = !empty($customer['trade_name']) ? $customer['trade_name'] : $customer['business_name'];
            $code = 'PRJ-' . str_pad($customer['id'], 4, '0', STR_PAD_LEFT) . '-DEF';
            
            $this->insert('{{%projects}}', [
                'customer_id' => $customer['id'],
                'code' => $code,
                'name' => 'Proyecto Principal - ' . $name,
                'business_name' => $customer['business_name'],
                'document_number' => $customer['document_number'],
                'address' => $customer['address'],
                'is_default' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $projectId = $this->db->getLastInsertID();

            // Asignar las órdenes de trabajo existentes de este cliente a su proyecto por defecto
            $this->update('{{%work_orders}}', ['project_id' => $projectId], ['customer_id' => $customer['id']]);
        }

        // 4. Ajustar la columna project_id a NOT NULL en work_orders
        $this->alterColumn('{{%work_orders}}', 'project_id', $this->integer()->notNull());

        // 5. Crear índice y llave foránea en work_orders
        $this->createIndex('idx-work_orders-project_id', '{{%work_orders}}', 'project_id');
        $this->addForeignKey(
            'fk-work_orders-project_id',
            '{{%work_orders}}',
            'project_id',
            '{{%projects}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-work_orders-project_id', '{{%work_orders}}');
        $this->dropIndex('idx-work_orders-project_id', '{{%work_orders}}');
        $this->dropColumn('{{%work_orders}}', 'project_id');

        $this->dropForeignKey('fk-projects-customer_id', '{{%projects}}');
        $this->dropIndex('idx-projects-customer_id', '{{%projects}}');
        $this->dropTable('{{%projects}}');
    }
}
