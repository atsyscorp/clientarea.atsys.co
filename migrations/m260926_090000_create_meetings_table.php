<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%meetings}}`.
 */
class m260926_090000_create_meetings_table extends Migration
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

        $this->createTable('{{%meetings}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->null(),
            'client_name' => $this->string(255)->notNull(),
            'client_email' => $this->string(255)->notNull(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'start_time' => $this->dateTime()->notNull(),
            'end_time' => $this->dateTime()->notNull(),
            'google_event_id' => $this->string(255)->null(),
            'meet_url' => $this->string(500)->null(),
            'calendar_html_link' => $this->string(500)->null(),
            'notes' => 'MEDIUMTEXT NULL', // Permite almacenar transcripciones completas y resúmenes de Gemini
            'status' => $this->string(30)->notNull()->defaultValue('scheduled'),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
        ], $tableOptions);

        $this->createIndex('idx-meetings-customer_id', '{{%meetings}}', 'customer_id');
        $this->createIndex('idx-meetings-created_by', '{{%meetings}}', 'created_by');
        $this->createIndex('idx-meetings-start_time', '{{%meetings}}', 'start_time');
        $this->createIndex('idx-meetings-status', '{{%meetings}}', 'status');

        $this->addForeignKey(
            'fk-meetings-customer_id',
            '{{%meetings}}',
            'customer_id',
            '{{%customers}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-meetings-created_by',
            '{{%meetings}}',
            'created_by',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-meetings-created_by', '{{%meetings}}');
        $this->dropForeignKey('fk-meetings-customer_id', '{{%meetings}}');
        $this->dropIndex('idx-meetings-status', '{{%meetings}}');
        $this->dropIndex('idx-meetings-start_time', '{{%meetings}}');
        $this->dropIndex('idx-meetings-created_by', '{{%meetings}}');
        $this->dropIndex('idx-meetings-customer_id', '{{%meetings}}');
        $this->dropTable('{{%meetings}}');
    }
}
