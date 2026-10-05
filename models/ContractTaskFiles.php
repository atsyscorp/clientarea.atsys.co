<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "contract_task_files".
 *
 * @property int $id
 * @property int $task_id
 * @property int $contract_id
 * @property string $title
 * @property string $file_url
 * @property int|null $file_size
 * @property string|null $created_at
 *
 * @property ContractTasks $task
 * @property Contracts $contract
 */
class ContractTaskFiles extends ActiveRecord
{
    public static function tableName()
    {
        return 'contract_task_files';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['task_id', 'contract_id', 'title', 'file_url'], 'required'],
            [['task_id', 'contract_id', 'file_size'], 'integer'],
            [['created_at'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['file_url'], 'string', 'max' => 500],
            [['task_id'], 'exist', 'skipOnError' => true, 'targetClass' => ContractTasks::class, 'targetAttribute' => ['task_id' => 'id']],
            [['contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => Contracts::class, 'targetAttribute' => ['contract_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Hito / Tarea',
            'contract_id' => 'Contrato',
            'title' => 'Nombre del Archivo / Evidencia',
            'file_url' => 'Enlace del Archivo',
            'file_size' => 'Tamaño',
            'created_at' => 'Fecha de Carga',
        ];
    }

    public function getTask()
    {
        return $this->hasOne(ContractTasks::class, ['id' => 'task_id']);
    }

    public function getContract()
    {
        return $this->hasOne(Contracts::class, ['id' => 'contract_id']);
    }

    /**
     * Determina si el archivo está alojado en Google Drive.
     * @return bool
     */
    public function isDriveFile()
    {
        return strpos($this->file_url, 'drive.google.com') !== false || strpos($this->file_url, 'google.com') !== false;
    }

    /**
     * Obtiene la extensión del archivo en minúsculas.
     * @return string
     */
    public function getFileExtension()
    {
        $path = parse_url($this->file_url, PHP_URL_PATH);
        $ext = pathinfo($path ?: $this->title, PATHINFO_EXTENSION);
        if (!$ext && strpos($this->title, '.') !== false) {
            $ext = pathinfo($this->title, PATHINFO_EXTENSION);
        }
        return strtolower($ext);
    }

    /**
     * Formatea el tamaño del archivo de bytes a KB o MB legibles.
     * @return string
     */
    public function getFormattedSize()
    {
        if (empty($this->file_size)) {
            return '';
        }
        $bytes = (int)$this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Retorna un ícono SVG representativo según la extensión del archivo.
     * @return string HTML SVG
     */
    public function getFileIconSvg()
    {
        $ext = $this->getFileExtension();

        if ($ext === 'pdf') {
            return '<svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" /></svg>';
        } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'])) {
            return '<svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>';
        } elseif (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) {
            return '<svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>';
        } elseif (in_array($ext, ['doc', 'docx', 'odt', 'rtf', 'txt'])) {
            return '<svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>';
        } elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) {
            return '<svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>';
        }

        return '<svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>';
    }
}
