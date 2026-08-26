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

            [['merged_into_id'], 'integer'],
            [['merged_into_id'], 'exist', 'skipOnError' => true, 'targetClass' => self::class, 'targetAttribute' => ['merged_into_id' => 'id']],
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
            'merged_into_id' => 'Fusionado En',
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
     * Gets query for [[MergedIntoTicket]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMergedIntoTicket()
    {
        return $this->hasOne(self::class, ['id' => 'merged_into_id']);
    }

    /**
     * Gets query for [[MergedSourceTickets]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMergedSourceTickets()
    {
        return $this->hasMany(self::class, ['merged_into_id' => 'id']);
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
     * Comprueba si el remitente del ticket está en la lista negra de SPAM / bloqueado.
     * @return bool
     */
    public function isSenderBlacklisted()
    {
        if (empty($this->email)) {
            return false;
        }
        return TicketSpamBlacklist::find()
            ->where(['email' => strtolower(trim($this->email))])
            ->exists();
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

        if ($this->isSenderBlacklisted()) {
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

    /**
     * Mapa canónico de estado a color y etiqueta.
     *
     * Única fuente de verdad para pintar el estado de un ticket. Antes cada
     * vista definía el suyo y el mismo estado salía de distinto color según
     * la pantalla. Para renderizar, usar app\widgets\StatusBadge.
     *
     * @return array<string, array{class: string, label: string}>
     */
    public static function statusBadgeMap()
    {
        return [
            self::STATUS_OPEN           => ['class' => 'badge-error',   'label' => 'Abierto'],
            self::STATUS_ANSWERED       => ['class' => 'badge-success', 'label' => 'Respondido'],
            self::STATUS_CUSTOMER_REPLY => ['class' => 'badge-warning', 'label' => 'Cliente'],
            self::STATUS_IN_PROGRESS    => ['class' => 'badge-info',    'label' => 'En Proceso'],
            self::STATUS_CLOSED         => ['class' => 'badge-neutral', 'label' => 'Cerrado'],
        ];
    }

    /**
     * @return array{class: string, label: string}
     */
    public function getStatusBadge()
    {
        return static::statusBadgeMap()[$this->status]
            ?? ['class' => 'badge-ghost', 'label' => 'Desconocido'];
    }

    public function getStatusText() {
        return $this->getStatusBadge()['label'];
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

    /**
     * Fusiona este ticket (origen) dentro de un ticket destino (target).
     * @param Tickets $targetTicket
     * @param User $adminUser
     * @return bool
     * @throws \Exception
     */
    public function mergeInto(self $targetTicket, User $adminUser)
    {
        if ($this->id == $targetTicket->id) {
            throw new \InvalidArgumentException('Un ticket no puede fusionarse consigo mismo.');
        }

        if ($this->merged_into_id) {
            throw new \InvalidArgumentException("El ticket {$this->ticket_code} ya se encuentra fusionado.");
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $adminName = !empty($adminUser->contact_name) ? $adminUser->contact_name : $adminUser->username;

            // 1. Mover archivos adjuntos físicos si existen
            $sourceDir = Yii::getAlias('@webroot/uploads/tickets/') . $this->id;
            $targetDir = Yii::getAlias('@webroot/uploads/tickets/') . $targetTicket->id;

            if (is_dir($sourceDir)) {
                if (!is_dir($targetDir)) {
                    \yii\helpers\FileHelper::createDirectory($targetDir);
                }
                $files = scandir($sourceDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $srcFile = $sourceDir . '/' . $file;
                        $dstFile = $targetDir . '/' . $file;
                        if (is_file($srcFile)) {
                            if (file_exists($dstFile)) {
                                $dstFile = $targetDir . '/' . $this->id . '_' . $file;
                            }
                            rename($srcFile, $dstFile);
                        }
                    }
                }
                @rmdir($sourceDir);
            }

            // 2. Transferir respuestas (TicketReplies)
            TicketReplies::updateAll(
                ['ticket_id' => $targetTicket->id],
                ['ticket_id' => $this->id]
            );

            // 3. Reasignar órdenes de trabajo (WorkOrders) si existen
            WorkOrders::updateAll(
                ['ticket_id' => $targetTicket->id],
                ['ticket_id' => $this->id]
            );

            // 4. Consolidar cc_emails
            if (!empty($this->cc_emails)) {
                $existingCc = !empty($targetTicket->cc_emails)
                    ? array_map('trim', explode(',', $targetTicket->cc_emails))
                    : [];
                $sourceCc = array_map('trim', explode(',', $this->cc_emails));
                $mergedCc = array_unique(array_merge($existingCc, $sourceCc));
                $targetTicket->cc_emails = implode(', ', array_filter($mergedCc));
                $targetTicket->save(false);
            }

            // 5. Agregar mensaje de sistema en el ticket DESTINO
            $targetNotice = new TicketReplies();
            $targetNotice->ticket_id = $targetTicket->id;
            $targetNotice->user_id = $adminUser->id;
            $targetNotice->sender_type = TicketReplies::SENDER_TYPE_SYSTEM;
            $targetNotice->message = "📌 <strong>Ticket Fusionado:</strong> El ticket <strong>#" . \yii\helpers\Html::encode($this->ticket_code) . "</strong> (<em>" . \yii\helpers\Html::encode($this->subject) . "</em>) fue fusionado en este ticket por <strong>" . \yii\helpers\Html::encode($adminName) . "</strong>.";
            $targetNotice->created_at = date('Y-m-d H:i:s');
            $targetNotice->save(false);

            // 6. Agregar mensaje de sistema en el ticket ORIGEN
            $sourceNotice = new TicketReplies();
            $sourceNotice->ticket_id = $this->id;
            $sourceNotice->user_id = $adminUser->id;
            $sourceNotice->sender_type = TicketReplies::SENDER_TYPE_SYSTEM;
            $sourceNotice->message = "🔒 <strong>Ticket Fusionado:</strong> Este ticket fue fusionado en el ticket <strong>#" . \yii\helpers\Html::encode($targetTicket->ticket_code) . "</strong> (<em>" . \yii\helpers\Html::encode($targetTicket->subject) . "</em>) por <strong>" . \yii\helpers\Html::encode($adminName) . "</strong>.";
            $sourceNotice->created_at = date('Y-m-d H:i:s');
            $sourceNotice->save(false);

            // 7. Actualizar estado y referencia del ticket ORIGEN
            $this->merged_into_id = $targetTicket->id;
            $this->status = self::STATUS_CLOSED;
            $this->is_locked = 1;
            $this->updated_at = date('Y-m-d H:i:s');
            $this->save(false);

            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Envía todas las notificaciones requeridas al registrar un nuevo ticket (Email, Notificación In-App, y Push N8N).
     *
     * @param string $messageContent Contenido inicial del ticket.
     * @param User|Customers|null $actorObj Usuario o Cliente creador del ticket.
     * @param bool $isCreatedByAdmin Indica si el ticket fue creado por un administrador en la plataforma.
     */
    public function sendNewTicketNotifications($messageContent, $actorObj = null, $isCreatedByAdmin = false)
    {
        $customerName = 'Usuario Externo';
        if ($this->customer) {
            $customerName = $this->customer->business_name;
        } elseif ($actorObj && isset($actorObj->username)) {
            $customerName = $actorObj->username;
        }

        $adminEmail = Yii::$app->params['adminEmail'] ?? 'gerencia@atsys.co';
        $senderEmail = Yii::$app->params['senderEmail'] ?? 'noreply@atsys.co';

        // 1. CORREO ELECTRÓNICO AL CLIENTE (Confirmación)
        if (!empty($this->email)) {
            try {
                $email = Yii::$app->mailer->compose(
                    ['html' => 'newTicket-html'],
                    [
                        'ticket' => $this,
                        'message' => $messageContent,
                        'user' => $actorObj,
                        'customer' => $this->customer,
                        'customerName' => $customerName
                    ]
                )
                ->setFrom([$senderEmail => Yii::$app->name])
                ->setTo($this->email)
                ->setReplyTo(Yii::$app->params['departmentEmails'][$this->department] ?? ($senderEmail ?? 'soporte@atsys.co'))
                ->setSubject('[#' . $this->ticket_code . '] ' . $this->subject);

                if (!empty($this->cc_emails)) {
                    $ccList = array_filter(array_map('trim', explode(',', $this->cc_emails)));
                    if (!empty($ccList)) {
                        $email->setCc($ccList);
                    }
                }
                $email->send();
            } catch (\Throwable $e) {
                Yii::error("Error enviando email de confirmación al cliente (#{$this->ticket_code}): " . $e->getMessage(), 'ticket_notification');
            }
        }

        // 2. CORREO ELECTRÓNICO AL ADMIN (Aviso)
        try {
            Yii::$app->mailer->compose(
                ['html' => 'adminNewTicket-html'],
                [
                    'ticket' => $this,
                    'message' => $messageContent,
                    'user' => $actorObj,
                    'customer' => $this->customer
                ]
            )
            ->setFrom([$senderEmail => Yii::$app->name])
            ->setTo($adminEmail)
            ->setSubject('Nuevo Ticket [' . $this->ticket_code . '] - ' . $this->subject)
            ->send();
        } catch (\Throwable $e) {
            Yii::error("Error enviando email al admin (#{$this->ticket_code}): " . $e->getMessage(), 'ticket_notification');
        }

        // 3. NOTIFICACIONES IN-APP (TABLA NOTIFICATIONS DE LA BD)
        if (!$isCreatedByAdmin) {
            try {
                Notifications::notifyAdmins(
                    "🎫 Nuevo Ticket: " . $this->ticket_code,
                    "El cliente " . $customerName . " ha creado el ticket: " . $this->subject,
                    "/tickets/view?id=" . $this->id,
                    Notifications::TYPE_INFO
                );
            } catch (\Throwable $e) {
                Yii::error("Error registrando notificación in-app para admins (#{$this->ticket_code}): " . $e->getMessage(), 'ticket_notification');
            }
        } else {
            if ($this->customer_id) {
                try {
                    Notifications::notifyCustomer(
                        $this->customer_id,
                        "🎫 Nuevo Ticket Creado: " . $this->ticket_code,
                        "Se ha registrado una nueva solicitud a tu nombre: " . $this->subject,
                        "/tickets/view?id=" . $this->id,
                        Notifications::TYPE_INFO
                    );
                } catch (\Throwable $e) {
                    Yii::error("Error registrando notificación in-app para cliente (#{$this->ticket_code}): " . $e->getMessage(), 'ticket_notification');
                }
            }
        }

        // 4. NOTIFICACIÓN PUSH VIA WEBHOOK N8N
        if (!$isCreatedByAdmin) {
            $this->triggerN8nPush(
                "Nuevo ticket: " . $this->ticket_code . " enviado por: " . $customerName,
                "Mensaje: " . mb_substr(strip_tags($messageContent), 0, 50, 'UTF-8') . "..."
            );
        }
    }

    /**
     * Envía webhook PUSH a N8N para administradores.
     */
    public function triggerN8nPush($title, $body)
    {
        try {
            $tokens = AdminTokens::find()->select('token')->column();
            if (empty($tokens)) {
                return false;
            }

            $webhookUrl = Yii::$app->params['n8n_admin_push_url'] ?? 'https://n8n-new.atsys.co/webhook/send-admin-push';
            $payload = [
                'tokens' => $tokens,
                'title' => $title,
                'body' => $body,
                'message' => $body,
                'link' => "https://clientarea.atsys.co/tickets/view?id=" . $this->id,
                'image' => 'https://clientarea.atsys.co/images/atsys-clientarea-og.webp',
                'type' => 'ticket'
            ];

            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000);
            curl_exec($ch);
            curl_close($ch);
            return true;
        } catch (\Throwable $e) {
            Yii::error("Error enviando PUSH N8N ticket #{$this->ticket_code}: " . $e->getMessage(), 'n8n_push');
            return false;
        }
    }

}

