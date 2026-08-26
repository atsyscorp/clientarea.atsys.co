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
                'only' => ['index', 'export'],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'export'],
                        'matchCallback' => function ($rule, $action) {
                            return !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
                        }
                    ],
                ],
            ],
        ];
    }

    /**
     * Muestra y procesa el formulario de satisfacción.
     * Puedes pasarle un ?ticket_id=XYZ123 o ?work_order_id=456 en la URL para vincularlo.
     */
    public function actionRate($ticket_id = null, $work_order_id = null)
    {
        $model = new ServiceFeedback();
        
        // Pre-cargar datos si vienen en la URL
        if ($ticket_id) {
            $model->ticket_id = (string)$ticket_id;
            $ticket = $model->getResolvedTicket();
            if ($ticket && $ticket->customer && !empty($ticket->customer->email)) {
                $model->client_email = $ticket->customer->email;
            }
        }

        if ($work_order_id) {
            $model->work_order_id = (string)$work_order_id;
            $workOrder = $model->getResolvedWorkOrder();
            if ($workOrder && $workOrder->customer && !empty($workOrder->customer->email)) {
                $model->client_email = $workOrder->customer->email;
            }
        }

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                Yii::$app->session->setFlash('success', '¡Gracias por tu opinión! Nos ayuda a mejorar.');
                return $this->refresh();
            }
        }

        return $this->render('rate', [
            'model' => $model,
        ]);
    }
    
    // API rápida si lo consumes desde un frontend desacoplado
    public function actionApiCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = new ServiceFeedback();
        
        if ($model->load(Yii::$app->request->post(), '') && $model->save()) {
            return ['status' => 'success', 'message' => 'Feedback recibido'];
        }
        
        return ['status' => 'error', 'errors' => $model->errors];
    }

    /**
     * Módulo consolidado de encuestas, métricas y gráficas.
     */
    public function actionIndex()
    {
        $searchModel = new ServiceFeedbackSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // --- CÁLCULOS Y MÉTRICAS CONSOLIDADAS ---
        $totalReviews = (int) ServiceFeedback::find()->count();
        
        // Promedios CSAT, NPS y CES
        $avgCsat = ServiceFeedback::find()->average('rating_service');
        $avgNps = ServiceFeedback::find()->average('nps_score');
        $avgCes = ServiceFeedback::find()->average('effort_score');

        // Clasificación NPS (Promotores 9-10, Pasivos 7-8, Detractores 0-6)
        $promotersCount = (int) ServiceFeedback::find()->where(['>=', 'nps_score', 9])->count();
        $passivesCount  = (int) ServiceFeedback::find()->where(['between', 'nps_score', 7, 8])->count();
        $detractorsCount = (int) ServiceFeedback::find()->where(['<=', 'nps_score', 6])->andWhere(['is not', 'nps_score', null])->count();
        
        $npsScore = 0;
        if ($totalReviews > 0) {
            $npsScore = round((($promotersCount - $detractorsCount) / $totalReviews) * 100, 1);
        }

        // Tasa de resolución
        $resolvedCount = (int) ServiceFeedback::find()->where(['is_resolved' => 1])->count();
        $unresolvedCount = (int) ServiceFeedback::find()->where(['is_resolved' => 0])->count();
        $resolutionRate = $totalReviews > 0 ? round(($resolvedCount / $totalReviews) * 100, 1) : 0;

        // Distribución por estrellas (1 a 5)
        $starsRaw = (new \yii\db\Query())
            ->select(['rating_service', 'COUNT(*) as count'])
            ->from(ServiceFeedback::tableName())
            ->groupBy('rating_service')
            ->indexBy('rating_service')
            ->all();

        $ratingCountsMap = [
            5 => isset($starsRaw[5]) ? (int)$starsRaw[5]['count'] : 0,
            4 => isset($starsRaw[4]) ? (int)$starsRaw[4]['count'] : 0,
            3 => isset($starsRaw[3]) ? (int)$starsRaw[3]['count'] : 0,
            2 => isset($starsRaw[2]) ? (int)$starsRaw[2]['count'] : 0,
            1 => isset($starsRaw[1]) ? (int)$starsRaw[1]['count'] : 0,
        ];

        // Tendencia temporal (últimas evaluaciones ordenadas cronológicamente)
        $recentFeedbacks = ServiceFeedback::find()
            ->orderBy(['created_at' => SORT_ASC])
            ->limit(30)
            ->all();

        $trendLabels = [];
        $trendData = [];
        foreach ($recentFeedbacks as $fb) {
            $dateStr = Yii::$app->formatter->asDate($fb->created_at, 'php:d M');
            $trendLabels[] = $dateStr;
            $trendData[] = (float) $fb->rating_service;
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'totalReviews' => $totalReviews,
            'averageRating' => $avgCsat ? round($avgCsat, 1) : 0,
            'averageNps' => $avgNps ? round($avgNps, 1) : 0,
            'npsScore' => $npsScore,
            'averageCes' => $avgCes ? round($avgCes, 1) : 0,
            'resolutionRate' => $resolutionRate,
            'resolvedCount' => $resolvedCount,
            'unresolvedCount' => $unresolvedCount,
            'promotersCount' => $promotersCount,
            'passivesCount' => $passivesCount,
            'detractorsCount' => $detractorsCount,
            'ratingCountsMap' => $ratingCountsMap,
            'trendLabels' => $trendLabels,
            'trendData' => $trendData,
        ]);
    }

    /**
     * Exporta las encuestas contestadas a CSV para análisis detallado.
     */
    public function actionExport()
    {
        $feedbacks = ServiceFeedback::find()->orderBy(['created_at' => SORT_DESC])->all();

        $filename = "reporte_satisfaccion_" . date('Y-m-d_H-i') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // BOM UTF-8 para compatibilidad con Excel
        fputs($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            'ID',
            'Fecha',
            'Referencia (Ticket / Orden)',
            'Cliente / Empresa',
            'Correo',
            'IP',
            'Calificación (1-5)',
            'NPS (0-10)',
            'Categoría NPS',
            'CES (Esfuerzo 1-5)',
            '¿Resuelto?',
            'Comentarios'
        ]);

        foreach ($feedbacks as $fb) {
            $customer = $fb->getResolvedCustomer();
            $customerName = $customer ? ($customer->business_name ?: $customer->contact_name) : 'Anónimo / Desconocido';
            $ticket = $fb->getResolvedTicket();
            $workOrder = $fb->getResolvedWorkOrder();

            $ref = 'N/A';
            if ($workOrder) {
                $ref = "Orden #{$workOrder->code} - {$workOrder->title}";
            } elseif ($ticket) {
                $ref = "Ticket #{$ticket->ticket_code} - {$ticket->subject}";
            } elseif ($fb->ticket_id) {
                $ref = "Ticket {$fb->ticket_id}";
            } elseif ($fb->work_order_id) {
                $ref = "Orden {$fb->work_order_id}";
            }

            fputcsv($output, [
                $fb->id,
                Yii::$app->formatter->asDatetime($fb->created_at, 'php:Y-m-d H:i:s'),
                $ref,
                $customerName,
                $fb->client_email ?: 'N/A',
                $fb->ip_address ?: 'N/A',
                $fb->rating_service,
                $fb->nps_score !== null ? $fb->nps_score : 'N/A',
                $fb->getNpsCategoryLabel(),
                $fb->effort_score !== null ? $fb->effort_score : 'N/A',
                $fb->is_resolved ? 'Sí' : 'No',
                $fb->comments
            ]);
        }

        fclose($output);
        exit;
    }
}