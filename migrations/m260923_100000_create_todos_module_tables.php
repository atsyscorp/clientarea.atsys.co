<?php

use yii\db\Migration;

/**
 * Class m260923_100000_create_todos_module_tables
 * Creates tables for the admin To-Do list module:
 * - todos
 * - todo_time_logs
 * - todo_checklist_items
 * - todo_comments
 */
class m260923_100000_create_todos_module_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // Limpiar tablas si quedaron parcialmente creadas de un intento anterior
        $tablesToClean = [
            '{{%todo_comments}}',
            '{{%todo_checklist_items}}',
            '{{%todo_time_logs}}',
            '{{%todos}}',
        ];
        foreach ($tablesToClean as $tableName) {
            if ($this->db->getTableSchema($tableName, true) !== null) {
                $this->dropTable($tableName);
            }
        }

        // 1. Tabla principal: todos
        $this->createTable('{{%todos}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'priority' => $this->string(20)->notNull()->defaultValue('medium'), // low, medium, high, urgent
            'status' => $this->string(20)->notNull()->defaultValue('pending'),   // pending, in_progress, completed, cancelled
            'due_date' => $this->dateTime()->null(),
            'reminder_at' => $this->dateTime()->null(),
            'reminder_sent' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'customer_id' => $this->integer()->null(),
            'assigned_to' => $this->integer()->null(),
            'created_by' => $this->integer()->notNull(),
            'estimated_minutes' => $this->integer()->null()->defaultValue(0),
            'total_time_spent' => $this->integer()->notNull()->defaultValue(0), // Total en segundos
            'completed_at' => $this->dateTime()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-todos-status', '{{%todos}}', 'status');
        $this->createIndex('idx-todos-priority', '{{%todos}}', 'priority');
        $this->createIndex('idx-todos-due_date', '{{%todos}}', 'due_date');
        $this->createIndex('idx-todos-reminder', '{{%todos}}', ['reminder_at', 'reminder_sent']);
        $this->createIndex('idx-todos-customer_id', '{{%todos}}', 'customer_id');
        $this->createIndex('idx-todos-assigned_to', '{{%todos}}', 'assigned_to');
        $this->createIndex('idx-todos-created_by', '{{%todos}}', 'created_by');

        $this->addForeignKey(
            'fk-todos-customer_id',
            '{{%todos}}',
            'customer_id',
            '{{%customers}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // 2. Tabla para medición de tiempos: todo_time_logs
        $this->createTable('{{%todo_time_logs}}', [
            'id' => $this->primaryKey(),
            'todo_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'start_time' => $this->dateTime()->notNull(),
            'end_time' => $this->dateTime()->null(),
            'duration_seconds' => $this->integer()->notNull()->defaultValue(0),
            'description' => $this->text()->null(),
            'is_running' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-todo_time_logs-todo_id', '{{%todo_time_logs}}', 'todo_id');
        $this->createIndex('idx-todo_time_logs-user_id', '{{%todo_time_logs}}', 'user_id');
        $this->createIndex('idx-todo_time_logs-is_running', '{{%todo_time_logs}}', 'is_running');

        $this->addForeignKey(
            'fk-todo_time_logs-todo_id',
            '{{%todo_time_logs}}',
            'todo_id',
            '{{%todos}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // 3. Tabla para checklist / subtareas: todo_checklist_items
        $this->createTable('{{%todo_checklist_items}}', [
            'id' => $this->primaryKey(),
            'todo_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'is_completed' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-todo_checklist-todo_id', '{{%todo_checklist_items}}', 'todo_id');

        $this->addForeignKey(
            'fk-todo_checklist-todo_id',
            '{{%todo_checklist_items}}',
            'todo_id',
            '{{%todos}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // 4. Tabla para seguimiento / notas / comentarios: todo_comments
        $this->createTable('{{%todo_comments}}', [
            'id' => $this->primaryKey(),
            'todo_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'comment' => $this->text()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-todo_comments-todo_id', '{{%todo_comments}}', 'todo_id');
        $this->createIndex('idx-todo_comments-user_id', '{{%todo_comments}}', 'user_id');

        $this->addForeignKey(
            'fk-todo_comments-todo_id',
            '{{%todo_comments}}',
            'todo_id',
            '{{%todos}}',
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
        $tablesToDrop = [
            '{{%todo_comments}}',
            '{{%todo_checklist_items}}',
            '{{%todo_time_logs}}',
            '{{%todos}}',
        ];

        foreach ($tablesToDrop as $tableName) {
            if ($this->db->getTableSchema($tableName, true) !== null) {
                $this->dropTable($tableName);
            }
        }
    }
}
