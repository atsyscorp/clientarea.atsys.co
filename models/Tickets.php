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
 * @property int|null $is_locked
 * @property string|null $priority
 * @property string|null $source
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Customers $customer
 * @property TicketReplies[] $ticketReplies
 * @property WorkOrders[] $workOrders
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
    const STATUS_IN_PROGRESS = 'in_progress';

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

    // Propiedad virtual para capturar los delegados mencionados
    public $mentioned_delegates = [];

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

            // Extracción al crear el ticket
            if (!empty($this->message) && $this->customer_id) {
                $emails = self::extractEmailsFromMessage($this->message);
                $validEmails = self::filterDelegatesByCustomer($emails, $this->customer_id);
                $this->cc_emails = !empty($validEmails) ? implode(', ', $validEmails) : null;
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
            [['is_locked'], 'default', 'value' => 0],
            [['is_locked'], 'boolean'],
            [['priority'], 'default', 'value' => 'medium'],
            [['source'], 'default', 'value' => 'web'],
            [['customer_id'], 'integer'],
            [['mentioned_delegates'], 'safe'],
            
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
            
            // Validación de Lista Negra de SPAM
            [['email'], 'validateSpamBlacklist'],
        ];
    }

    /**
     * Valida que el remitente no esté registrado en la lista negra de SPAM.
     */
    public function validateSpamBlacklist($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            $isBlacklisted = TicketSpamBlacklist::find()
                ->where(['email' => strtolower(trim($this->$attribute))])
                ->exists();
            if ($isBlacklisted) {
                $this->addError($attribute, 'Este correo electrónico está en la lista negra de SPAM y no puede crear tickets.');
            }
        }
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
            'is_locked' => 'Respuestas Bloqueadas',
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
     * Gets query for [[WorkOrders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkOrders()
    {
        return $this->hasMany(WorkOrders::class, ['ticket_id' => 'id']);
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
            self::STATUS_IN_PROGRESS => 'in_progress',
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
     * Retorna verdadero si el ticket tiene bloqueadas las respuestas del cliente.
     * Incluye verificación de seguridad por si la migración aún no se ha ejecutado en BD.
     * @return bool
     */
    public function isLocked()
    {
        return $this->hasAttribute('is_locked') ? (bool)$this->is_locked : false;
    }

    /**
     * Cuenta el número de respuestas consecutivas enviadas por el cliente
     * desde la última respuesta de un administrador.
     * @return int
     */
    public function getConsecutiveCustomerRepliesCount()
    {
        $replies = $this->getTicketReplies()
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->all();

        $count = 0;
        foreach ($replies as $reply) {
            if ($reply->sender_type === TicketReplies::SENDER_TYPE_ADMIN) {
                break;
            }
            $count++;
        }
        return $count;
    }

    /**
     * Revisa si el cliente puede responder al ticket.
     * Restringe a máximo 3 respuestas consecutivas del cliente solo cuando el ticket
     * NO está respondido (answered) ni en progreso (in_progress).
     * Admins siempre pueden responder.
     *
     * @param bool $isAdmin
     * @return bool
     */
    public function canCustomerReply($isAdmin = false)
    {
        if ($isAdmin) {
            return true;
        }

        if ($this->isLocked()) {
            return false;
        }

        if ($this->status === self::STATUS_ANSWERED || $this->status === self::STATUS_IN_PROGRESS) {
            return true;
        }

        return $this->getConsecutiveCustomerRepliesCount() < 3;
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
            self::STATUS_CUSTOMER_REPLY => 'Cliente',
            self::STATUS_CLOSED => 'Cerrado',
            self::STATUS_IN_PROGRESS => 'En Proceso',
        ];
        return $statusLabels[$this->status] ?? 'Desconocido';
    }

    public function getLastResponderName()
    {
        $lastReply = $this->getTicketReplies()->orderBy(['created_at' => SORT_DESC])->one();

        if (!$lastReply) {
            return 'Desconocido';
        }

        if ($lastReply->sender_type === 'admin') {
            return ($this->department === 'support') ? 'Soporte' : (($this->department === 'commercial') ? 'Comercial' : 'ATSYS');
        } else {
            $senderUser = $lastReply->user;
            if ($senderUser) {
                return $senderUser->contact_name ?? $senderUser->email;
            } else {
                return $this->customer ? (
                    $this->customer->contact_name == $this->customer->business_name ?
                    $this->customer->contact_name :
                    $this->customer->contact_name . ' (' . $this->customer->business_name . ')'
                ) : $this->email;
            }
        }
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

    /**
     * Calcula los datos del gráfico de Gantt para un conjunto de tickets (ActiveQuery).
     * @param \yii\db\ActiveQuery $query
     * @param int $limit
     * @return array
     */
    public static function getGanttData($query, $limit = 20)
    {
        $ganttQuery = clone $query;
        $tickets = $ganttQuery->orderBy(['created_at' => SORT_DESC])->limit($limit)->all();
        
        if (empty($tickets)) {
            return [];
        }
        
        // Ordenar cronológicamente (ASC) para dibujar el Gantt de forma natural
        usort($tickets, function($a, $b) {
            return strtotime($a->created_at) <=> strtotime($b->created_at);
        });
        
        $minTime = null;
        $maxTime = null;
        
        // Primer ciclo: obtener límites de tiempo (minTime, maxTime)
        foreach ($tickets as $ticket) {
            $createdTs = strtotime($ticket->created_at);
            $lastActivityTs = strtotime($ticket->updated_at ?: $ticket->created_at);
            
            $endTs = in_array($ticket->status, [self::STATUS_OPEN, self::STATUS_CUSTOMER_REPLY, self::STATUS_IN_PROGRESS]) 
                ? time() 
                : $lastActivityTs;
                
            if ($minTime === null || $createdTs < $minTime) {
                $minTime = $createdTs;
            }
            if ($maxTime === null || $endTs > $maxTime) {
                $maxTime = $endTs;
            }
        }
        
        $totalSpan = ($maxTime !== null && $minTime !== null && $maxTime > $minTime) ? ($maxTime - $minTime) : 1;
        $timelineTickets = [];
        
        // Segundo ciclo: estructurar datos de línea de tiempo con porcentajes
        foreach ($tickets as $ticket) {
            $createdTs = strtotime($ticket->created_at);
            $lastActivityTs = strtotime($ticket->updated_at ?: $ticket->created_at);
            
            $endTs = in_array($ticket->status, [self::STATUS_OPEN, self::STATUS_CUSTOMER_REPLY, self::STATUS_IN_PROGRESS]) 
                ? time() 
                : $lastActivityTs;
                
            $startPercent = (($createdTs - $minTime) / $totalSpan) * 100;
            $endPercent = (($endTs - $minTime) / $totalSpan) * 100;
            $widthPercent = max(3.0, $endPercent - $startPercent); // Ancho mínimo de 3%
            
            // Texto de duración de actividad
            $durationSec = $endTs - $createdTs;
            if ($durationSec < 3600) {
                $durText = round($durationSec / 60) . ' minutos';
            } elseif ($durationSec < 86400) {
                $durText = round($durationSec / 3600, 1) . ' horas';
            } else {
                $durText = round($durationSec / 86400, 1) . ' días';
            }
            
            $timelineTickets[] = [
                'id' => $ticket->id,
                'ticket_code' => $ticket->ticket_code,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'status_text' => $ticket->getStatusText(),
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,
                'duration_text' => $durText,
                'left_percent' => round($startPercent, 2),
                'width_percent' => round($widthPercent, 2),
            ];
        }
        
        return [
            'timeline' => array_reverse($timelineTickets), // Mostrar los más recientes arriba
            'min_time' => $minTime,
            'max_time' => $maxTime,
        ];
    }

    /**
     * Extrae correos electrónicos de los atributos data-email en etiquetas span de menciones.
     * @param string $message
     * @return array
     */
    public static function extractEmailsFromMessage($message)
    {
        if (empty($message)) {
            return [];
        }
        $emails = [];
        if (preg_match_all('/data-email=["\']([^"\']+)["\']/i', $message, $matches)) {
            $emails = array_unique($matches[1]);
        }
        return $emails;
    }

    /**
     * Filtra los correos para asegurarse de que pertenezcan a delegados activos del cliente.
     * @param array $emails
     * @param int $customerId
     * @return array
     */
    public static function filterDelegatesByCustomer($emails, $customerId)
    {
        $customer = Customers::findOne($customerId);
        if (!$customer || !$customer->user_id) {
            return [];
        }
        return User::find()
            ->select('email')
            ->where(['email' => $emails])
            ->andWhere([
                'or',
                ['id' => $customer->user_id],
                ['parent_id' => $customer->user_id]
            ])
            ->andWhere(['status' => User::STATUS_ACTIVE])
            ->column();
    }

    /**
     * Obtiene los IDs de los usuarios correspondientes a los emails en cc_emails
     * @return array
     */
    public function getCcUserIds()
    {
        if (empty($this->cc_emails)) {
            return [];
        }
        $emails = array_map('trim', explode(',', $this->cc_emails));
        return User::find()
            ->select('id')
            ->where(['email' => $emails])
            ->column();
    }

}
