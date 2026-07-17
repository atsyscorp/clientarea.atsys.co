<?php

namespace app\models;

use Yii;

use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "customers".
 *
 * @property int $id
 * @property string|null $document_type
 * @property string $document_number NIT o Cedula
 * @property string $business_name Razón Social
 * @property string|null $trade_name Nombre comercial
 * @property string|null $contact_name
 * @property string|null $contact_position
 * @property string $email
 * @property string $primary_phone Móvil para WhatsApp
 * @property string|null $secondary_phone
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state_province
 * @property string|null $status
 * @property string|null $notes
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $user_id
 *
 * @property InventoryBatches[] $inventoryBatches
 */
class Customers extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const DOCUMENT_TYPE_NIT = 'NIT';
    const DOCUMENT_TYPE_CC = 'CC';
    const DOCUMENT_TYPE_RUT = 'RUT';
    const DOCUMENT_TYPE_PASSPORT = 'PASSPORT';
    const DOCUMENT_TYPE_OTHER = 'OTHER';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PROSPECT = 'prospect';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'customers';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'), // O simplemente no poner 'value' si usas int en DB
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['trade_name', 'contact_name', 'contact_position', 'secondary_phone', 'address', 'city', 'state_province', 'notes'], 'default', 'value' => null],
            [['document_type'], 'default', 'value' => 'NIT'],
            [['status'], 'default', 'value' => 'active'],
            [['document_type', 'status', 'notes'], 'string'],
            [['document_number', 'business_name', 'email', 'primary_phone'], 'required'],
            [['created_at', 'updated_at', 'user_id'], 'safe'],
            [['document_number', 'primary_phone', 'secondary_phone'], 'string', 'max' => 50],
            [['business_name', 'trade_name', 'contact_name', 'email', 'address'], 'string', 'max' => 255],
            [['contact_position', 'city', 'state_province'], 'string', 'max' => 100],
            ['document_type', 'in', 'range' => array_keys(self::optsDocumentType())],
            ['status', 'in', 'range' => array_keys(self::optsStatus())],
            [['document_number'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'document_type' => 'Tipo de Documento',
            'document_number' => 'Número de Documento',
            'business_name' => 'Razón Social',
            'trade_name' => 'Nombre Comercial',
            'contact_name' => 'Nombre del Contacto',
            'contact_position' => 'Cargo',
            'email' => 'Correo Electrónico',
            'primary_phone' => 'Teléfono Principal',
            'secondary_phone' => 'Teléfono Secundario',
            'address' => 'Dirección',
            'city' => 'Ciudad',
            'state_province' => 'Departamento / Estado',
            'status' => 'Estado',
            'notes' => 'Notas / Observaciones',
            'created_at' => 'Fecha de Creación',
            'updated_at' => 'Última Actualización',
        ];
    }

    /**
     * Gets query for [[InventoryBatches]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInventoryBatches()
    {
        return $this->hasMany(InventoryBatches::class, ['customer_id' => 'id']);
    }


    /**
     * column document_type ENUM value labels
     * @return string[]
     */
    public static function optsDocumentType()
    {
        return [
            self::DOCUMENT_TYPE_NIT => 'NIT',
            self::DOCUMENT_TYPE_CC => 'CC',
            self::DOCUMENT_TYPE_RUT => 'RUT',
            self::DOCUMENT_TYPE_PASSPORT => 'PASSPORT',
            self::DOCUMENT_TYPE_OTHER => 'OTHER',
        ];
    }

    /**
     * column status ENUM value labels
     * @return string[]
     */
    public static function optsStatus()
    {
        return [
            self::STATUS_ACTIVE => 'active',
            self::STATUS_INACTIVE => 'inactive',
            self::STATUS_PROSPECT => 'prospect',
        ];
    }

    /**
     * @return string
     */
    public function displayDocumentType()
    {
        return self::optsDocumentType()[$this->document_type];
    }

    /**
     * @return bool
     */
    public function isDocumentTypeNit()
    {
        return $this->document_type === self::DOCUMENT_TYPE_NIT;
    }

    public function setDocumentTypeToNit()
    {
        $this->document_type = self::DOCUMENT_TYPE_NIT;
    }

    /**
     * @return bool
     */
    public function isDocumentTypeCc()
    {
        return $this->document_type === self::DOCUMENT_TYPE_CC;
    }

    public function setDocumentTypeToCc()
    {
        $this->document_type = self::DOCUMENT_TYPE_CC;
    }

    /**
     * @return bool
     */
    public function isDocumentTypeRut()
    {
        return $this->document_type === self::DOCUMENT_TYPE_RUT;
    }

    public function setDocumentTypeToRut()
    {
        $this->document_type = self::DOCUMENT_TYPE_RUT;
    }

    /**
     * @return bool
     */
    public function isDocumentTypePassport()
    {
        return $this->document_type === self::DOCUMENT_TYPE_PASSPORT;
    }

    public function setDocumentTypeToPassport()
    {
        $this->document_type = self::DOCUMENT_TYPE_PASSPORT;
    }

    /**
     * @return bool
     */
    public function isDocumentTypeOther()
    {
        return $this->document_type === self::DOCUMENT_TYPE_OTHER;
    }

    public function setDocumentTypeToOther()
    {
        $this->document_type = self::DOCUMENT_TYPE_OTHER;
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
    public function isStatusActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function setStatusToActive()
    {
        $this->status = self::STATUS_ACTIVE;
    }

    /**
     * @return bool
     */
    public function isStatusInactive()
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function setStatusToInactive()
    {
        $this->status = self::STATUS_INACTIVE;
    }

    /**
     * @return bool
     */
    public function isStatusProspect()
    {
        return $this->status === self::STATUS_PROSPECT;
    }

    public function setStatusToProspect()
    {
        $this->status = self::STATUS_PROSPECT;
    }

    public function getServices()
    {
        return $this->hasMany(CustomerServices::class, ['customer_id' => 'id']);
    }

    /**
     * Gets query for [[Tickets]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTickets()
    {
        return $this->hasMany(Tickets::class, ['customer_id' => 'id']);
    }

    /**
     * Gets query for [[WorkOrders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkOrders()
    {
        return $this->hasMany(WorkOrders::class, ['customer_id' => 'id']);
    }

    /**
     * Relación con el usuario Titular (dueño).
     * @return \yii\db\ActiveQuery
     */
    public function getOwner()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Relación con los usuarios Delegados (subcuentas).
     * @return \yii\db\ActiveQuery
     */
    public function getDelegates()
    {
        return $this->hasMany(User::class, ['parent_id' => 'user_id']);
    }


    /**
     * Calcula estadísticas y datos para el gráfico de Gantt de los tickets de este cliente.
     * @return array
     */
    public function getTicketStats()
    {
        $tickets = $this->tickets;
        
        $total = count($tickets);
        $answered = 0;
        $pending = 0;
        
        $totalResponseTime = 0;
        $responseTimeCount = 0;
        
        $timelineTickets = [];
        
        // Ordenar por created_at de forma ascendente para la cronología
        usort($tickets, function($a, $b) {
            return strtotime($a->created_at) <=> strtotime($b->created_at);
        });
        
        // Conservar los últimos 15 tickets para el Gantt y evitar sobrecargar
        $recentTicketsForTimeline = array_slice($tickets, -15);
        
        $minTime = null;
        $maxTime = null;
        
        // Primer ciclo: conteos y tiempos de respuesta
        foreach ($tickets as $ticket) {
            if ($ticket->status === Tickets::STATUS_ANSWERED || $ticket->status === Tickets::STATUS_CLOSED) {
                $answered++;
                
                // Calcular tiempo de primera respuesta: creación de ticket hasta primera réplica de admin
                $firstAdminReply = TicketReplies::find()
                    ->where(['ticket_id' => $ticket->id, 'sender_type' => TicketReplies::SENDER_TYPE_ADMIN])
                    ->orderBy(['created_at' => SORT_ASC])
                    ->one();
                    
                if ($firstAdminReply) {
                    $created = strtotime($ticket->created_at);
                    $replied = strtotime($firstAdminReply->created_at);
                    $diff = $replied - $created;
                    if ($diff > 0) {
                        $totalResponseTime += $diff;
                        $responseTimeCount++;
                    }
                }
            } else {
                $pending++;
            }
        }
        
        // Determinar el rango de la ventana de tiempo del Gantt
        foreach ($recentTicketsForTimeline as $ticket) {
            $createdTs = strtotime($ticket->created_at);
            $lastActivityTs = strtotime($ticket->updated_at ?: $ticket->created_at);
            
            $endTs = in_array($ticket->status, [Tickets::STATUS_OPEN, Tickets::STATUS_CUSTOMER_REPLY, Tickets::STATUS_IN_PROGRESS]) 
                ? time() 
                : $lastActivityTs;
                
            if ($minTime === null || $createdTs < $minTime) {
                $minTime = $createdTs;
            }
            if ($maxTime === null || $endTs > $maxTime) {
                $maxTime = $endTs;
            }
        }
        
        // Formatear promedio de tiempo de respuesta
        $avgResponseText = 'N/A';
        if ($responseTimeCount > 0) {
            $avgSeconds = $totalResponseTime / $responseTimeCount;
            if ($avgSeconds < 3600) {
                $minutes = round($avgSeconds / 60);
                $avgResponseText = $minutes . ' m';
            } elseif ($avgSeconds < 86400) {
                $hours = floor($avgSeconds / 3600);
                $minutes = round(($avgSeconds % 3600) / 60);
                $avgResponseText = $hours . 'h ' . $minutes . 'm';
            } else {
                $days = floor($avgSeconds / 86400);
                $hours = round(($avgSeconds % 86400) / 3600);
                $avgResponseText = $days . 'd ' . $hours . 'h';
            }
        }
        
        // Segundo ciclo: estructurar datos de línea de tiempo con posiciones relativas (%)
        $totalSpan = ($maxTime !== null && $minTime !== null && $maxTime > $minTime) ? ($maxTime - $minTime) : 1;
        
        foreach ($recentTicketsForTimeline as $ticket) {
            $createdTs = strtotime($ticket->created_at);
            $lastActivityTs = strtotime($ticket->updated_at ?: $ticket->created_at);
            
            $endTs = in_array($ticket->status, [Tickets::STATUS_OPEN, Tickets::STATUS_CUSTOMER_REPLY, Tickets::STATUS_IN_PROGRESS]) 
                ? time() 
                : $lastActivityTs;
                
            $startPercent = (($createdTs - $minTime) / $totalSpan) * 100;
            $endPercent = (($endTs - $minTime) / $totalSpan) * 100;
            $widthPercent = max(3.0, $endPercent - $startPercent); // Garantizar mínimo 3%
            
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
        
        // Contar tickets por meses del año actual
        $currentYear = date('Y');
        $monthlyCounts = array_fill(1, 12, 0);
        foreach ($tickets as $ticket) {
            $ticketYear = date('Y', strtotime($ticket->created_at));
            if ($ticketYear === $currentYear) {
                $ticketMonth = (int)date('n', strtotime($ticket->created_at));
                $monthlyCounts[$ticketMonth]++;
            }
        }
        
        return [
            'total' => $total,
            'answered' => $answered,
            'pending' => $pending,
            'avg_response_time' => $avgResponseText,
            'timeline' => array_reverse($timelineTickets), // Mostrar los más recientes arriba
            'min_time' => $minTime,
            'max_time' => $maxTime,
            'monthly_counts' => array_values($monthlyCounts),
        ];
    }

}
