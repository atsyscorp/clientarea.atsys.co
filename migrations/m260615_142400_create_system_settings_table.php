<?php

use yii\db\Migration;

/**
 * Class m260615_142400_create_system_settings_table
 */
class m260615_142400_create_system_settings_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%system_settings}}', [
            'id' => $this->primaryKey(),
            'category' => $this->string(50)->notNull(),
            'key' => $this->string(100)->notNull()->unique(),
            'value' => $this->text()->null(),
            'label' => $this->string(255)->notNull(),
            'description' => $this->string(255)->null(),
            'type' => $this->string(30)->notNull()->defaultValue('text'),
            'updated_at' => $this->dateTime()->null(),
        ]);

        // Intentar leer params.php para sembrar la tabla con los valores actuales del usuario
        $paramsFile = Yii::getAlias('@app/config/params.php');
        $params = [];
        if (file_exists($paramsFile)) {
            try {
                $params = require $paramsFile;
            } catch (\Exception $e) {
                // Silenciar errores de lectura
            }
        }

        $wompiPubKey = $params['wmpi_pubKey'] ?? 'pub_prod_UbGVrJOt3EZ6xBKQaPy8lah9pFQchr0T';
        $wompiIntegrity = $params['wmpi_integrity'] ?? 'prod_integrity_qGF2hvg6bUCrUAY2qEK7yefE5soM5JZ0';
        $paypalClientId = $params['paypalClientId'] ?? '';
        $paypalSecret = $params['paypalSecret'] ?? '';
        $paypalMode = $params['paypalMode'] ?? 'live';
        $whoisKey = $params['whois']['key'] ?? '7f041f7fd72736886ea4bfffa0e8dcec9e32fde4069065bde0b18622310bf0be';

        // Sembrar valores por defecto
        $this->batchInsert('{{%system_settings}}', 
            ['category', 'key', 'value', 'label', 'description', 'type'], 
            [
                [
                    'tickets', 
                    'ticket_hours_to_close', 
                    '48', 
                    'Límite de Horas para Cerrar Ticket', 
                    'Tiempo límite de inactividad del cliente antes de cerrar automáticamente un ticket (en horas).', 
                    'number'
                ],
                [
                    'tickets', 
                    'ticket_max_pending', 
                    '4', 
                    'Límite de Tickets Pendientes', 
                    'Número máximo de tickets en proceso (abiertos o en progreso) que un cliente puede tener antes de restringir la creación de nuevos tickets.', 
                    'number'
                ],
                [
                    'paypal', 
                    'paypalClientId', 
                    $paypalClientId, 
                    'PayPal Client ID', 
                    'Identificador del cliente para la pasarela de PayPal.', 
                    'text'
                ],
                [
                    'paypal', 
                    'paypalSecret', 
                    $paypalSecret, 
                    'PayPal Secret Key', 
                    'Clave secreta para la pasarela de PayPal.', 
                    'password'
                ],
                [
                    'paypal', 
                    'paypalMode', 
                    $paypalMode, 
                    'PayPal Mode', 
                    'Modo de ejecución para PayPal: sandbox (pruebas) o live (producción).', 
                    'text'
                ],
                [
                    'wompi', 
                    'wmpi_pubKey', 
                    $wompiPubKey, 
                    'Wompi Public Key', 
                    'Clave pública de conexión a la pasarela Wompi.', 
                    'text'
                ],
                [
                    'wompi', 
                    'wmpi_integrity', 
                    $wompiIntegrity, 
                    'Wompi Integrity Secret', 
                    'Clave de integridad (secreto) para la firma de Wompi.', 
                    'password'
                ],
                [
                    'whois',
                    'whois_key',
                    $whoisKey,
                    'Whois JSON API Key',
                    'Clave de API de WhoisJSON para la consulta de disponibilidad de dominios.',
                    'password'
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%system_settings}}');
    }
}
