<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tickets".
 *
 * @property int $id
 * @property string|null $ticket_code
 * @property int|null $customer_id Null si es un prospecto que aun no es cliente
 * @property string $email Email del que reporta
 * @property string $subject
 * @property string|null $status
 * @property string|null $priority
 * @property string|null $source
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Customers $customer
 * @property TicketReplies[] $ticketReplies
 */
class Tickets extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const STATUS_OPEN = 'open';
    const STATUS_ANSWERED = 'answered';
    const STATUS_CUSTOMER_REPLY = 'customer_reply';
    const STATUS_CLOSED = 'closed';
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';
    const SOURCE_WEB = 'web';
    const SOURCE_EMAIL = 'email';
    const SOURCE_WHATSAPP = 'whatsapp';

    const DEPT_SUPPORT = 'support';
    const DEPT_COMMERCIAL = 'commercial';
    const DEPT_BILLING = 'billing';

    // Propiedad virtual para capturar el mensaje del formulario
    public $message;

    // Propiedad virtual para adjuntar archivo, funciona solo al crear el ticket
    public $attachmentFile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tickets';
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->customer_id == 9999) {
                $this->customer_id = null;
            }
            return true;
        }
        return false;
    }

    /**
     * Se ejecuta ANTES de borrar el ticket completo.
     */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Definir la ruta de la carpeta de ESTE ticket
        $dirPath = Yii::getAlias('@webroot/uploads/tickets/') . $this->id;

        // Usamos el Helper de Yii para borrar el directorio y todo su contenido (recursivo)
        if (is_dir($dirPath)) {
            \yii\helpers\FileHelper::removeDirectory($dirPath);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ticket_code'], 'string', 'max' => 50],
            [['customer_id'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 'open'],
            [['priority'], 'default', 'value' => 'medium'],
            [['source'], 'default', 'value' => 'web'],
            [['customer_id'], 'integer'],
            
            // ELIMINÉ 'subject' DE REQUIRED PORQUE EL EMAIL ES CONDICIONAL
            // Y SI ES UN CLIENTE REGISTRADO, EL EMAIL YA LO TIENES EN LA RELACIÓN.
            // SI PREFIERES QUE SIEMPRE ESCRIBAN ASUNTO:
            [['subject'], 'required'],
            
            [['status', 'priority', 'source'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['email', 'subject'], 'string', 'max' => 255],
            ['status', 'in', 'range' => array_keys(self::optsStatus())],
            ['priority', 'in', 'range' => array_keys(self::optsPriority())],
            ['source', 'in', 'range' => array_keys(self::optsSource())],

            [['message'], 'required', 'on' => 'create'], 
            [['message'], 'string'],

            [['customer_id'], 'exist', 
                'skipOnError' => true, 
                'targetClass' => Customers::class, 
                'targetAttribute' => ['customer_id' => 'id'],
                // ESTA ES LA CLAVE: No validar si es 9999
                'when' => function($model) {
                    return $model->customer_id != 9999;
                }
            ],

            // Regla para el email obligatorio solo si es 9999
            ['email', 'required', 'when' => function ($model) {
                return $model->customer_id == 9999;
            }, 'whenClient' => "function (attribute, value) {
                // IMPORTANTE: Asegúrate que el ID en tu vista sea 'select-customer' o el que uses
                return $('#select-customer').val() == '9999';
            }"],

            // Adjuntar archivo
            [['attachmentFile'], 'file', 
                'skipOnEmpty' => true, 
                'extensions' => 'png, jpg, jpeg, pdf, zip, rar', 
                'maxSize' => 1024 * 1024 * 10, // 10MB
                'checkExtensionByMimeType' => false,
            ],

            // Departamento
            [['department'], 'string'],
            [['department'], 'default', 'value' => self::DEPT_SUPPORT],
            [['department'], 'in', 'range' => [self::DEPT_SUPPORT, self::DEPT_COMMERCIAL, self::DEPT_BILLING]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'customer_id' => 'Cliente',
            'email' => 'Email',
            'subject' => 'Asunto',
            'status' => 'Estado',
            'priority' => 'Prioridad',
            'source' => 'Fuente',
            'department' => 'Departamento',
            'created_at' => 'Creado',
            'updated_at' => 'Últ. Actualización',
        ];
    }

    /**
     * Gets query for [[Customer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customers::class, ['id' => 'customer_id']);
    }

    /**
     * Gets query for [[TicketReplies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTicketReplies()
    {
        return $this->hasMany(TicketReplies::class, ['ticket_id' => 'id']);
    }


    /**
     * column status ENUM value labels
     * @return string[]
     */
    public static function optsStatus()
    {
        return [
            self::STATUS_OPEN => 'open',
            self::STATUS_ANSWERED => 'answered',
            self::STATUS_CUSTOMER_REPLY => 'customer_reply',
            self::STATUS_CLOSED => 'closed',
        ];
    }

    /**
     * column priority ENUM value labels
     * @return string[]
     */
    public static function optsPriority()
    {
        return [
            self::PRIORITY_LOW => 'low',
            self::PRIORITY_MEDIUM => 'medium',
            self::PRIORITY_HIGH => 'high',
            self::PRIORITY_CRITICAL => 'critical',
        ];
    }

    /**
     * column source ENUM value labels
     * @return string[]
     */
    public static function optsSource()
    {
        return [
            self::SOURCE_WEB => 'web',
            self::SOURCE_EMAIL => 'email',
            self::SOURCE_WHATSAPP => 'whatsapp',
        ];
    }

    /**
     * @return string
     */
    public function displayStatus()
    {
        return self::optsStatus()[$this->status];
    }

    /**
     * @return bool
     */
    public function isStatusOpen()
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function setStatusToOpen()
    {
        $this->status = self::STATUS_OPEN;
    }

    /**
     * @return bool
     */
    public function isStatusAnswered()
    {
        return $this->status === self::STATUS_ANSWERED;
    }

    public function setStatusToAnswered()
    {
        $this->status = self::STATUS_ANSWERED;
    }

    /**
     * @return bool
     */
    public function isStatusCustomerreply()
    {
        return $this->status === self::STATUS_CUSTOMER_REPLY;
    }

    public function setStatusToCustomerreply()
    {
        $this->status = self::STATUS_CUSTOMER_REPLY;
    }

    /**
     * @return bool
     */
    public function isStatusClosed()
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function setStatusToClosed()
    {
        $this->status = self::STATUS_CLOSED;
    }

    /**
     * @return string
     */
    public function displayPriority()
    {
        return self::optsPriority()[$this->priority];
    }

    /**
     * @return bool
     */
    public function isPriorityLow()
    {
        return $this->priority === self::PRIORITY_LOW;
    }

    public function setPriorityToLow()
    {
        $this->priority = self::PRIORITY_LOW;
    }

    /**
     * @return bool
     */
    public function isPriorityMedium()
    {
        return $this->priority === self::PRIORITY_MEDIUM;
    }

    public function setPriorityToMedium()
    {
        $this->priority = self::PRIORITY_MEDIUM;
    }

    /**
     * @return bool
     */
    public function isPriorityHigh()
    {
        return $this->priority === self::PRIORITY_HIGH;
    }

    public function setPriorityToHigh()
    {
        $this->priority = self::PRIORITY_HIGH;
    }

    /**
     * @return bool
     */
    public function isPriorityCritical()
    {
        return $this->priority === self::PRIORITY_CRITICAL;
    }

    public function setPriorityToCritical()
    {
        $this->priority = self::PRIORITY_CRITICAL;
    }

    /**
     * @return string
     */
    public function displaySource()
    {
        return self::optsSource()[$this->source];
    }

    /**
     * @return bool
     */
    public function isSourceWeb()
    {
        return $this->source === self::SOURCE_WEB;
    }

    public function setSourceToWeb()
    {
        $this->source = self::SOURCE_WEB;
    }

    /**
     * @return bool
     */
    public function isSourceEmail()
    {
        return $this->source === self::SOURCE_EMAIL;
    }

    public function setSourceToEmail()
    {
        $this->source = self::SOURCE_EMAIL;
    }

    /**
     * @return bool
     */
    public function isSourceWhatsapp()
    {
        return $this->source === self::SOURCE_WHATSAPP;
    }

    public function setSourceToWhatsapp()
    {
        $this->source = self::SOURCE_WHATSAPP;
    }

    public function getStatusText() {
        $statusLabels = [
            self::STATUS_OPEN => 'Abierto',
            self::STATUS_ANSWERED => 'Respondido',
            self::STATUS_CUSTOMER_REPLY => 'Respuesta del cliente',
            self::STATUS_CLOSED => 'Cerrado',
        ];
        return $statusLabels[$this->status] ?? 'Desconocido';
    }

    public static function getDepartmentList()
    {
        return [
            self::DEPT_SUPPORT => 'Soporte Técnico',
            self::DEPT_COMMERCIAL => 'Comercial / Ventas',
            self::DEPT_BILLING => 'Facturación y Pagos',
        ];
    }

    public static function getDepartmentListShort()
    {
        return [
            self::DEPT_SUPPORT => 'Soporte',
            self::DEPT_COMMERCIAL => 'Comercial',
            self::DEPT_BILLING => 'Facturación',
        ];
    }

    public function getDepartmentLabel()
    {
        $colors = [
            self::DEPT_SUPPORT => 'badge-info',
            self::DEPT_COMMERCIAL => 'badge-secondary',
            self::DEPT_BILLING => 'badge-accent',
        ];
        
        $list = self::getDepartmentList();
        $label = $list[$this->department] ?? $this->department;
        $color = $colors[$this->department] ?? 'badge-ghost';
        
        return "<span class='badge {$color} badge-outline gap-1'>{$label}</span>";
    }

    public function getDepartmentLabelShort()
    {
        $colors = [
            self::DEPT_SUPPORT => 'badge-info',
            self::DEPT_COMMERCIAL => 'badge-secondary',
            self::DEPT_BILLING => 'badge-accent',
        ];
        
        $list = self::getDepartmentListShort();
        $label = $list[$this->department] ?? $this->department;
        $color = $colors[$this->department] ?? 'badge-ghost';
        
        return "<span class='badge {$color} badge-outline gap-1'>{$label}</span>";
    }
    
    public function getDepartmentEmail() 
    {
        $departmentsAddresses = [
            self::DEPT_SUPPORT => ['soporte@atsys.co' => 'Soporte ATSYS'],
            self::DEPT_COMMERCIAL => ['hola@atsys.co' => 'Info ATSYS'],
            self::DEPT_BILLING => ['facturacion@atsys.co' => 'Facturación ATSYS']
        ];
        return $departmentsAddresses[$this->department] ?? $departmentsAddresses[self::DEPT_SUPPORT];


    }

}
