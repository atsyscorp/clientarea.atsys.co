<?php

use yii\db\Migration;

/**
 * Handles adding youtube_url to announcements.
 */
class m260901_105600_add_youtube_url_and_comments_to_announcements extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%announcements}}', 'youtube_url', $this->string(255)->null()->after('content'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%announcements}}', 'youtube_url');
    }
}
