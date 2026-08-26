<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%announcements}}`.
 */
class m260805_120000_add_is_pinned_to_announcements_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%announcements}}', 'is_pinned', $this->boolean()->defaultValue(0)->after('is_active'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%announcements}}', 'is_pinned');
    }
}
