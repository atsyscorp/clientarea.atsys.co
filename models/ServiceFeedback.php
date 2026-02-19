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
            [['ticket_id'], 'string', 'max' => 50],
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
        ];
    }
}