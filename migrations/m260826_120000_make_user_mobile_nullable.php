<?php

use yii\db\Migration;

/**
 * Class m260826_120000_make_user_mobile_nullable
 *
 * El registro ya no pide número de celular, así que el INSERT de una cuenta
 * nueva deja `mobile` sin valor. Si la columna es NOT NULL y MySQL corre en
 * modo estricto, ese INSERT falla. Esta migración solo relaja la restricción;
 * conserva el tipo de dato original y el teléfono sigue capturándose desde el
 * perfil del usuario.
 */
class m260826_120000_make_user_mobile_nullable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $column = $this->getMobileColumn();

        if ($column === null) {
            echo "    > la tabla user no tiene columna 'mobile', nada que hacer\n";
            return;
        }

        if ($column->allowNull) {
            echo "    > user.mobile ya acepta NULL, nada que hacer\n";
            return;
        }

        // Se usa dbType para no alterar longitud ni tipo original de la columna.
        $table = $this->db->schema->getRawTableName('{{%user}}');
        $this->execute("ALTER TABLE `{$table}` MODIFY `mobile` {$column->dbType} NULL DEFAULT NULL");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // No se revierte: volver a NOT NULL rompería las cuentas creadas sin
        // teléfono desde que se quitó el campo del registro.
        echo "m260826_120000_make_user_mobile_nullable no se revierte.\n";
        return false;
    }

    /**
     * @return \yii\db\ColumnSchema|null
     */
    private function getMobileColumn()
    {
        $tableSchema = $this->db->getTableSchema('{{%user}}', true);

        return $tableSchema ? $tableSchema->getColumn('mobile') : null;
    }
}
