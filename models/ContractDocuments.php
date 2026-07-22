<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "contract_documents".
 *
 * @property int $id
 * @property int $contract_id
 * @property string $title
 * @property string $file_url
 * @property string|null $uploaded_at
 *
 * @property Contracts $contract
 */
class ContractDocuments extends ActiveRecord
{
    public static function tableName()
    {
        return 'contract_documents';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'uploaded_at',
                'updatedAtAttribute' => false,
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['contract_id', 'title', 'file_url'], 'required'],
            [['contract_id'], 'integer'],
            [['uploaded_at'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['file_url'], 'string', 'max' => 500],
            [['contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => Contracts::class, 'targetAttribute' => ['contract_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'contract_id' => 'Contrato',
            'title' => 'Título del Documento / Anexo',
            'file_url' => 'URL del Archivo',
            'uploaded_at' => 'Fecha de Carga',
        ];
    }

    public function getContract()
    {
        return $this->hasOne(Contracts::class, ['id' => 'contract_id']);
    }
}
