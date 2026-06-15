<?php

use yii\db\Migration;

/**
 * Class m260615_145800_insert_cron_push_settings
 */
class m260615_145800_insert_cron_push_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->batchInsert('{{%system_settings}}',
            ['category', 'key', 'value', 'label', 'description', 'type'],
            [
                [
                    'tickets',
                    'ticket_hours_sla',
                    '24',
                    'Horas Límite de SLA',
                    'Tiempo límite de respuesta para ATSYS antes de disparar una alerta de riesgo SLA (en horas).',
                    'number'
                ],
                [
                    'tickets',
                    'n8n_admin_push_url',
                    'https://n8n-new.atsys.co/webhook/send-admin-push',
                    'Webhook N8N Admin Push',
                    'URL del Webhook de N8N utilizado para enviar notificaciones Push a los dispositivos de los administradores.',
                    'text'
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%system_settings}}', ['key' => [
            'ticket_hours_sla',
            'n8n_admin_push_url'
        ]]);
    }
}
