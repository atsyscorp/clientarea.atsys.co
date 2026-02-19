<?php

namespace app\controllers;

use Yii;
use app\models\ServiceFeedback;
use yii\web\Controller;
use yii\web\Response;

class FeedbackController extends Controller
{
    /**
     * Muestra y procesa el formulario de satisfacción.
     * Puedes pasarle un ?ticket_id=XYZ123 en la URL para vincularlo.
     */
    public function actionRate($ticket_id = null)
    {
        $model = new ServiceFeedback();
        
        // Pre-cargar datos si vienen en la URL
        if ($ticket_id) {
            $model->ticket_id = $ticket_id;
        }

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                // Respuesta exitosa
                Yii::$app->session->setFlash('success', '¡Gracias por tu opinión! Nos ayuda a mejorar.');
                return $this->refresh(); // O redirigir a una página de "Thank You"
            }
        }

        return $this->render('rate', [
            'model' => $model,
        ]);
    }
    
    // Opcional: Para una API rápida si lo consumes desde un frontend desacoplado
    public function actionApiCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = new ServiceFeedback();
        
        if ($model->load(Yii::$app->request->post(), '') && $model->save()) {
            return ['status' => 'success', 'message' => 'Feedback recibido'];
        }
        
        return ['status' => 'error', 'errors' => $model->errors];
    }
}