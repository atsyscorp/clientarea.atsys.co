<?php

use yii\db\Migration;

/**
 * Class m261007_170500_insert_meetings_schedule_setting
 * Inserta la configuración del horario de atención comercial (estilo WhatsApp)
 * para el agendamiento de reuniones y citas virtuales.
 */
class m261007_170500_insert_meetings_schedule_setting extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $exists = (new \yii\db\Query())
            ->from('{{%system_settings}}')
            ->where(['key' => 'meetings_schedule'])
            ->exists();

        if (!$exists) {
            $defaultSchedule = [
                '1' => ['enabled' => true,  'start' => '08:00', 'end' => '17:00', 'name' => 'Lunes',     'short' => 'Lun'],
                '2' => ['enabled' => true,  'start' => '08:00', 'end' => '17:00', 'name' => 'Martes',    'short' => 'Mar'],
                '3' => ['enabled' => true,  'start' => '08:00', 'end' => '17:00', 'name' => 'Miércoles', 'short' => 'Mié'],
                '4' => ['enabled' => true,  'start' => '08:00', 'end' => '17:00', 'name' => 'Jueves',    'short' => 'Jue'],
                '5' => ['enabled' => true,  'start' => '08:00', 'end' => '17:00', 'name' => 'Viernes',   'short' => 'Vie'],
                '6' => ['enabled' => false, 'start' => '08:00', 'end' => '12:00', 'name' => 'Sábado',    'short' => 'Sáb'],
                '0' => ['enabled' => false, 'start' => '08:00', 'end' => '12:00', 'name' => 'Domingo',   'short' => 'Dom'],
            ];

            $this->insert('{{%system_settings}}', [
                'category' => 'meetings',
                'key' => 'meetings_schedule',
                'value' => json_encode($defaultSchedule, JSON_UNESCAPED_UNICODE),
                'label' => 'Horario de Atención para Citas',
                'description' => 'Configuración de días hábiles y horarios de atención al cliente para solicitudes de reuniones virtuales (estilo WhatsApp Business).',
                'type' => 'schedule',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%system_settings}}', ['key' => 'meetings_schedule']);
    }
}
