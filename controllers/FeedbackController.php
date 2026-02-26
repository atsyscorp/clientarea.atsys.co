<?php

namespace app\controllers;

use Yii;
use app\models\ServiceFeedback;
use app\models\ServiceFeedbackSearch;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;

class FeedbackController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index'], // 'rate' queda público para los clientes
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'matchCallback' => function ($rule, $action) {
                            // Validamos que esté logueado y sea admin
                            return !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
                        }
                    ],
                ],
            ],
        ];
    }

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

    public function actionIndex()
    {
        $searchModel = new ServiceFeedbackSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // --- CÁLCULOS PARA LAS GRÁFICAS ---
        $totalReviews = ServiceFeedback::find()->count();
        
        // Promedio general usando tu columna real
        $averageRating = ServiceFeedback::find()->average('rating_service');

        // Agrupar calificaciones para la gráfica de pastel
        $ratingCounts = (new \yii\db\Query())
            ->select(['rating_service', 'COUNT(*) as count'])
            ->from(ServiceFeedback::tableName())
            ->groupBy('rating_service')
            ->orderBy(['rating_service' => SORT_DESC])
            ->all();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'totalReviews' => $totalReviews,
            'averageRating' => $averageRating ? round($averageRating, 1) : 0, // Previene error si no hay datos
            'ratingCounts' => $ratingCounts,
        ]);
    }
}