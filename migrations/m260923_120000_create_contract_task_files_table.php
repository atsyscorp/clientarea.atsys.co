<?php

use yii\db\Migration;

/**
 * Class m260923_120000_create_contract_task_files_table
 */
class m260923_120000_create_contract_task_files_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%contract_task_files}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull(),
            'contract_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'file_url' => $this->string(500)->notNull(),
            'file_size' => $this->integer()->null()->defaultValue(null),
            'created_at' => $this->dateTime()->null(),
        ]);

        $this->createIndex('idx-contract_task_files-task_id', '{{%contract_task_files}}', 'task_id');
        $this->createIndex('idx-contract_task_files-contract_id', '{{%contract_task_files}}', 'contract_id');

        $this->addForeignKey(
            'fk-contract_task_files-task_id',
            '{{%contract_task_files}}',
            'task_id',
            '{{%contract_tasks}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-contract_task_files-contract_id',
            '{{%contract_task_files}}',
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
        $this->dropForeignKey('fk-contract_task_files-contract_id', '{{%contract_task_files}}');
        $this->dropForeignKey('fk-contract_task_files-task_id', '{{%contract_task_files}}');
        $this->dropIndex('idx-contract_task_files-contract_id', '{{%contract_task_files}}');
        $this->dropIndex('idx-contract_task_files-task_id', '{{%contract_task_files}}');
        $this->dropTable('{{%contract_task_files}}');
    }
}
