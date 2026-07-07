<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "work_orders".
 *
 * @property int $id
 * @property int $customer_id
 * @property string $code
 * @property string $title
 * @property string $requirements
 * @property string|null $original_request
 * @property string|null $notes
 * @property float|null $total_cost
 * @property string $currency
 * @property float|null $exchange_rate
 * @property float|null $total_cost_usd
 * @property int|null $status 0: Borrador, 1: Enviada/Pendiente, 2: Aprobada, 3: Rechazada, 4: Finalizada
 * @property int $is_request
 * @property int|null $is_preapproved
 * @property string|null $down_payment_sent_at
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $completed_at
 *
 * @property Customers $customer
 * @property WorkOrderUpdates[] $workOrderUpdates
 */

class WorkOrders extends \yii\db\ActiveRecord
{
    public $attachmentFile;

    // Constantes de Estado...
    const STATUS_DRAFT = 0;
    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECTED = 3;
    const STATUS_COMPLETED = 4;
    const STATUS_NOT_COMPLETED = 5;  // Cerrada por inactividad/falta de respuesta del cliente
    const STATUS_PARTIAL = 6;        // Avance parcial entregado, retomable

    public static function tableName()
    {
        return 'work_orders';
    }

    public function rules()
    {
        return [
            // Valores por defecto
            [['original_request', 'notes', 'exchange_rate', 'total_cost_usd', 'down_payment_sent_at', 'created_at', 'updated_at', 'completed_at', 'attachment_url', 'pause_reason'], 'default', 'value' => null],
            [['total_cost', 'total_cost_usd'], 'default', 'value' => 0.00],
            [['status', 'is_request', 'has_service_contract', 'is_preapproved'], 'default', 'value' => 0],
            [['currency'], 'default', 'value' => 'COP'],
            
            [['customer_id', 'title', 'requirements'], 'required'],
            
            // Tipos de datos
            [['customer_id', 'status', 'is_request', 'has_service_contract', 'is_preapproved'], 'integer'],
            [['requirements', 'notes', 'original_request', 'attachment_url', 'pause_reason'], 'string'],
            [['total_cost', 'total_cost_usd', 'exchange_rate'], 'number'],
            [['down_payment_sent_at', 'created_at', 'updated_at', 'completed_at'], 'safe'],
            
            // Validaciones de longitud y formato
            [['code'], 'string', 'max' => 50],
            [['title'], 'string', 'max' => 255],
            [['currency'], 'string', 'max' => 3],
            [['currency'], 'in', 'range' => ['COP', 'USD', 'EUR']],

            // Adjuntar archivo
            [['attachmentFile'], 'file',
                'skipOnEmpty' => true,
                'extensions' => 'png, jpg, jpeg, pdf, zip, rar, doc, docx, xls, xlsx',
                'maxSize' => 1024 * 1024 * 15, // 15MB
                'checkExtensionByMimeType' => false,
            ],
            
            // Integridad referencial
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customers::class, 'targetAttribute' => ['customer_id' => 'id']],

            // VALIDACIÓN CONDICIONAL: TRM obligatoria si es USD o EUR
            [['exchange_rate'], 'required', 'when' => function ($model) {
                return in_array($model->currency, ['USD', 'EUR']);
            }, 'whenClient' => "function (attribute, value) {
                var val = $('#workorders-currency').val();
                return val === 'USD' || val === 'EUR'; // Asegúrate que el ID del input coincida en tu vista
            }", 'message' => 'La TRM es obligatoria para órdenes en USD o EUR.'],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Generador de Código Automático (Intacto)
            if ($this->isNewRecord && empty($this->code)) {
                $lastOrder = self::find()->orderBy(['id' => SORT_DESC])->one();
                $nextId = $lastOrder ? ($lastOrder->id + 1) : 1;
                $this->code = 'OT'.(($this->is_request == 1) ? 'R' : '').'-' . date('Y') . '-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }

            // --- PROTECCIÓN CONTRA RECÁLCULOS INFINITOS ---
            // Revisamos si el usuario realmente modificó el costo, la moneda o la TRM en este guardado.
            // Al aprobar o rechazar una orden (donde solo cambia el status), esto estará vacío.
            $atributosModificados = $this->getDirtyAttributes(['total_cost', 'currency', 'exchange_rate']);

            if (!empty($atributosModificados) || $this->isNewRecord) {
                
                if (in_array($this->currency, ['USD', 'EUR']) && !empty($this->exchange_rate) && !empty($this->total_cost)) {
                    // Como en el formulario digitas el valor en USD o EUR:
                    // 1. Guardamos ese valor digitado intacto en la columna USD (que almacena moneda extranjera)
                    $this->total_cost_usd = $this->total_cost; 
                    
                    // 2. Calculamos el equivalente en COP multiplicando por la TRM para la columna base
                    $this->total_cost = round($this->total_cost_usd * $this->exchange_rate, 2); 
                } else {
                    // Si vuelve a ser COP, limpiamos la basura
                    if ($this->currency === 'COP') {
                        $this->exchange_rate = null;
                        $this->total_cost_usd = null;
                    }
                }
            }

            return true;
        }
        return false;
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => date('Y-m-d H:i:s'), 
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'                  => 'ID',
            'customer_id'         => 'Cliente',
            'code'                => 'Código (OT)',
            'title'               => 'Título del Proyecto',
            'requirements'        => 'Detalle de Requerimientos',
            'notes'               => 'Notas Adicionales',
            'total_cost'          => 'Inversión Total (COP)',
            'currency'            => 'Moneda',
            'exchange_rate'       => 'TRM',
            'total_cost_usd'      => 'Inversión Total (USD)',
            'status'              => 'Estado',
            'pause_reason'        => 'Motivo de Pausa',
            'down_payment_sent_at'=> 'Anticipo enviado el',
            'created_at'          => 'Fecha Creación',
            'attachmentFile'      => 'Archivo Adjunto (Opcional)',
            'has_service_contract'=> 'Contrato de Servicios (Evita vencimiento)',
            'is_preapproved'      => 'Pre-Aprobar Orden de Trabajo',
        ];
    }

    public function getCustomer()
    {
        return $this->hasOne(Customers::class, ['id' => 'customer_id']);
    }

    public function getStatusHtml()
    {
        $statusMap = [
            self::STATUS_DRAFT         => ['label' => 'Borrador',                'class' => 'badge-ghost'],
            self::STATUS_PENDING       => ['label' => 'Pendiente',               'class' => 'badge-warning font-bold'],
            self::STATUS_APPROVED      => ['label' => 'Aprobada',                'class' => 'badge-success text-white'],
            self::STATUS_REJECTED      => ['label' => 'Rechazada',               'class' => 'badge-error text-white'],
            self::STATUS_COMPLETED     => ['label' => 'Finalizada',              'class' => 'badge-info text-white'],
            self::STATUS_NOT_COMPLETED => ['label' => 'No Finalizada',           'class' => 'badge-error text-white'],
            self::STATUS_PARTIAL       => ['label' => 'Parcialmente Finalizada', 'class' => 'badge-warning text-white font-bold'],
        ];

        $s = $statusMap[$this->status] ?? ['label' => 'Desconocido', 'class' => 'badge-ghost'];
        return "<span class='badge {$s['class']} font-bold'>{$s['label']}</span>";
    }

    public function request() {

        $this->is_request = 1;
        $this->customer_id = Yii::$app->user->identity->getRealCustomerId();
        $this->save(false);

        // Enviar notificacion a admin
        Yii::$app->mailer->compose([
            'html' => 'work_order_request-html',
        ],[
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'requirements' => $this->requirements,
            'customer' => $this->customer,
            'attachment_url' => $this->attachment_url,
        ])
        ->setFrom([
            Yii::$app->params['senderEmail'] => Yii::$app->name
        ])
        ->setTo(Yii::$app->params['adminEmail'])
        ->setSubject("Nueva solicitud de orden de trabajo: " . $this->code)
        ->send();

        return true;
    }
}