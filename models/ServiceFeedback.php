<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

class ServiceFeedback extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%service_feedback}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['rating_service', 'nps_score'], 'required', 'message' => 'Este campo es obligatorio.'],
            
            // Validar rangos numéricos
            ['rating_service', 'integer', 'min' => 1, 'max' => 5],
            ['nps_score', 'integer', 'min' => 0, 'max' => 10],
            ['effort_score', 'integer', 'min' => 1, 'max' => 5],
            
            [['is_resolved'], 'boolean'],
            [['comments'], 'string'],
            [['ticket_id', 'work_order_id'], 'string', 'max' => 50],
            ['client_email', 'email'],
            
            // Capturar IP automáticamente antes de validar si es necesario
            ['ip_address', 'default', 'value' => function() {
                return Yii::$app->request->userIP;
            }],
        ];
    }

    public function attributeLabels()
    {
        return [
            'rating_service' => '¿Cómo calificas nuestro servicio?',
            'nps_score' => '¿Qué tan probable es que nos recomiendes? (0-10)',
            'effort_score' => '¿Qué tan fácil fue gestionar tu solicitud?',
            'is_resolved' => '¿Tu solicitud fue resuelta?',
            'comments' => 'Comentarios o sugerencias',
            'client_email' => 'Tu correo (Opcional)',
            'work_order_id' => 'Orden de Trabajo',
        ];
    }

    /**
     * Relación directa con el ticket vía ticket_code.
     */
    public function getTicket()
    {
        return $this->hasOne(Tickets::class, ['ticket_code' => 'ticket_id']);
    }

    /**
     * Relación directa con la orden de trabajo.
     */
    public function getWorkOrder()
    {
        return $this->hasOne(WorkOrders::class, ['id' => 'work_order_id']);
    }

    /**
     * Relación directa con el cliente vía correo electrónico.
     */
    public function getCustomer()
    {
        return $this->hasOne(Customers::class, ['email' => 'client_email']);
    }

    /**
     * Resuelve el ticket asociado ya sea por ID numérico o ticket_code.
     */
    public function getResolvedTicket()
    {
        if (empty($this->ticket_id)) {
            return null;
        }
        if (is_numeric($this->ticket_id)) {
            return Tickets::findOne((int)$this->ticket_id);
        }
        return Tickets::findOne(['ticket_code' => $this->ticket_id]);
    }

    /**
     * Resuelve la orden de trabajo asociada por ID numérico o código.
     */
    public function getResolvedWorkOrder()
    {
        if (empty($this->work_order_id)) {
            return null;
        }
        if (is_numeric($this->work_order_id)) {
            return WorkOrders::findOne((int)$this->work_order_id);
        }
        return WorkOrders::findOne(['code' => $this->work_order_id]);
    }

    /**
     * Resuelve el cliente asociado usando el correo, el ticket o la orden de trabajo de la evaluación.
     */
    public function getResolvedCustomer()
    {
        if (!empty($this->client_email)) {
            $customer = Customers::findOne(['email' => $this->client_email]);
            if ($customer) {
                return $customer;
            }
        }
        $ticket = $this->getResolvedTicket();
        if ($ticket && $ticket->customer) {
            return $ticket->customer;
        }
        $workOrder = $this->getResolvedWorkOrder();
        if ($workOrder && $workOrder->customer) {
            return $workOrder->customer;
        }
        return null;
    }

    /**
     * Devuelve la categoría NPS del puntaje (Promotor, Pasivo, Detractor).
     */
    public function getNpsCategoryLabel()
    {
        if ($this->nps_score === null || $this->nps_score === '') {
            return 'No especificado';
        }
        if ($this->nps_score >= 9) {
            return 'Promotor';
        }
        if ($this->nps_score >= 7) {
            return 'Pasivo';
        }
        return 'Detractor';
    }

    /**
     * Devuelve el distintivo HTML de la categoría NPS.
     */
    public function getNpsCategoryBadge()
    {
        if ($this->nps_score === null || $this->nps_score === '') {
            return '<span class="badge badge-ghost badge-sm">N/A</span>';
        }
        if ($this->nps_score >= 9) {
            return '<span class="badge badge-success badge-sm text-white font-medium">Promotor (' . $this->nps_score . ')</span>';
        }
        if ($this->nps_score >= 7) {
            return '<span class="badge badge-warning badge-sm text-white font-medium">Pasivo (' . $this->nps_score . ')</span>';
        }
        return '<span class="badge badge-error badge-sm text-white font-medium">Detractor (' . $this->nps_score . ')</span>';
    }

    /**
     * Devuelve representación textual del puntaje CES.
     */
    public function getEffortScoreLabel()
    {
        $map = [
            1 => '1 - Muy difícil',
            2 => '2 - Difícil',
            3 => '3 - Normal',
            4 => '4 - Fácil',
            5 => '5 - Muy fácil',
        ];
        return $map[$this->effort_score] ?? ($this->effort_score ? $this->effort_score . ' / 5' : 'N/A');
    }

    /**
     * Devuelve estrellas en HTML para la calificación del servicio.
     */
    public function getRatingStarsHtml()
    {
        $rating = (int) $this->rating_service;
        $stars = str_repeat('⭐', max(0, min(5, $rating)));
        $empty = str_repeat('☆', max(0, 5 - min(5, $rating)));
        return '<span class="text-warning drop-shadow-sm" title="' . $rating . ' de 5 estrellas">' . $stars . '<span class="opacity-40">' . $empty . '</span></span>';
    }
}