<?php

use yii\db\Migration;

/**
 * Class m260806_150000_fix_work_order_updates_charset_utf8mb4
 *
 * Convierte las tablas y columnas de texto a utf8mb4 / utf8mb4_unicode_ci
 * para permitir la inserción de caracteres Unicode especiales (como '→', emojis, guiones largos, etc.)
 */
class m260806_150000_fix_work_order_updates_charset_utf8mb4 extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        if ($this->db->driverName === 'mysql') {
            // Convertir la tabla work_order_updates y sus columnas a utf8mb4
            $this->execute("ALTER TABLE {{%work_order_updates}} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $this->execute("ALTER TABLE {{%work_order_updates}} MODIFY `description` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $this->execute("ALTER TABLE {{%work_order_updates}} MODIFY `client_reply` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            // Convertir otras tablas principales que almacenan contenido ingresado por usuarios
            $tablesToConvert = [
                'work_orders',
                'tickets',
                'ticket_replies',
                'notifications',
                'projects',
                'contracts',
                'announcements',
                'service_feedback',
            ];

            foreach ($tablesToConvert as $table) {
                try {
                    $this->execute("ALTER TABLE {{%{$table}}} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                } catch (\Exception $e) {
                    // Si la tabla no existe en la BD actual, ignorar suavemente
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Operación no destructiva al revertir
    }
}
