<?php

use yii\db\Migration;

/**
 * Class m260918_090000_insert_work_order_expiration_setting
 */
class m260918_090000_insert_work_order_expiration_setting extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $exists = (new \yii\db\Query())
            ->from('{{%system_settings}}')
            ->where(['key' => 'work_order_expiration_days'])
            ->exists();

        if (!$exists) {
            $this->insert('{{%system_settings}}', [
                'category' => 'work_orders',
                'key' => 'work_order_expiration_days',
                'value' => '5',
                'label' => 'Días de Vigencia de Órdenes de Trabajo',
                'description' => 'Número de días calendario de vigencia para una orden de trabajo pendiente antes de expirar por inactividad o falta de aprobación.',
                'type' => 'number',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%system_settings}}', ['key' => 'work_order_expiration_days']);
    }
}
