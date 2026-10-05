<?php

namespace app\controllers;

use Yii;
use app\models\Todos;
use app\models\TodosSearch;
use app\models\TodoTimeLogs;
use app\models\TodoChecklistItems;
use app\models\TodoComments;
use app\models\Customers;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\helpers\ArrayHelper;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * TodosController implements management of admin tasks, time tracking, follow-ups and reminders.
 */
class TodosController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (Yii::$app->user->isGuest) {
                                return false;
                            }
                            return (bool) Yii::$app->user->identity->isAdmin;
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'toggle-status' => ['POST'],
                    'start-timer' => ['POST'],
                    'stop-timer' => ['POST'],
                    'log-time' => ['POST'],
                    'delete-time-log' => ['POST'],
                    'add-checklist' => ['POST'],
                    'toggle-checklist' => ['POST'],
                    'delete-checklist' => ['POST'],
                    'add-comment' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Todos with Kanban / List switch and KPI summary metrics.
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new TodosSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);
        $viewMode = Yii::$app->request->get('view', 'board'); // 'board' or 'list'

        // Metrics for dashboard header
        $totalCount = (int) Todos::find()->count();
        $pendingCount = (int) Todos::find()->where(['status' => Todos::STATUS_PENDING])->count();
        $inProgressCount = (int) Todos::find()->where(['status' => Todos::STATUS_IN_PROGRESS])->count();
        $completedCount = (int) Todos::find()->where(['status' => Todos::STATUS_COMPLETED])->count();
        
        $overdueCount = (int) Todos::find()
            ->where(['not in', 'status', [Todos::STATUS_COMPLETED, Todos::STATUS_CANCELLED]])
            ->andWhere(['not', ['due_date' => null]])
            ->andWhere(['<', 'due_date', date('Y-m-d H:i:s')])
            ->count();

        // Total seconds tracked today
        $todaySeconds = (int) TodoTimeLogs::find()
            ->where(['>=', 'created_at', date('Y-m-d 00:00:00')])
            ->sum('duration_seconds');

        // Check if current user has an active running timer
        $runningTimer = TodoTimeLogs::find()
            ->where(['user_id' => Yii::$app->user->id, 'is_running' => 1])
            ->with(['todo'])
            ->one();

        // If board mode, get tasks grouped by status
        $boardTasks = [];
        if ($viewMode === 'board') {
            $statuses = [Todos::STATUS_PENDING, Todos::STATUS_IN_PROGRESS, Todos::STATUS_COMPLETED];
            foreach ($statuses as $st) {
                $q = clone $dataProvider->query;
                $boardTasks[$st] = $q->andWhere(['todos.status' => $st])->limit(50)->all();
            }
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'viewMode' => $viewMode,
            'totalCount' => $totalCount,
            'pendingCount' => $pendingCount,
            'inProgressCount' => $inProgressCount,
            'completedCount' => $completedCount,
            'overdueCount' => $overdueCount,
            'todayTrackedTime' => TodoTimeLogs::formatSeconds($todaySeconds),
            'runningTimer' => $runningTimer,
            'boardTasks' => $boardTasks,
            'customersList' => $this->getCustomersList(false),
        ]);
    }

    /**
     * Displays a single Todos model with timer, checklist, logs and follow-up notes.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $activeTimer = $model->getActiveTimer(Yii::$app->user->id);

        return $this->render('view', [
            'model' => $model,
            'activeTimer' => $activeTimer,
        ]);
    }

    /**
     * Creates a new Todos model.
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new Todos();
        $model->created_by = Yii::$app->user->id;
        $model->assigned_to = Yii::$app->user->id; // Default assigned to creator
        $model->priority = Todos::PRIORITY_MEDIUM;
        $model->status = Todos::STATUS_PENDING;

        if ($this->request->isPost && $model->load($this->request->post())) {
            $model->created_by = Yii::$app->user->id;
            if (empty($model->assigned_to)) {
                $model->assigned_to = Yii::$app->user->id;
            }
            if ($model->save()) {
                // Check if initial checklist items were provided
                $initialChecklist = Yii::$app->request->post('initial_checklist');
                if (!empty($initialChecklist)) {
                    $lines = explode("\n", str_replace("\r", "", $initialChecklist));
                    $order = 0;
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            $item = new TodoChecklistItems();
                            $item->todo_id = $model->id;
                            $item->title = $line;
                            $item->sort_order = $order++;
                            $item->created_at = date('Y-m-d H:i:s');
                            $item->save(false);
                        }
                    }
                }

                Yii::$app->session->setFlash('success', 'Tarea creada exitosamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'customersList' => $this->getCustomersList(true),
            'adminsList' => $this->getAdminsList(),
        ]);
    }

    /**
     * Updates an existing Todos model.
     * @param int $id ID
     * @return string|Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post())) {
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Tarea actualizada exitosamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'customersList' => $this->getCustomersList(true),
            'adminsList' => $this->getAdminsList(),
        ]);
    }

    /**
     * Deletes an existing Todos model.
     * @param int $id ID
     * @return Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();

        Yii::$app->session->setFlash('success', 'Tarea eliminada exitosamente.');
        return $this->redirect(['index']);
    }

    /**
     * Quick status toggle (AJAX or POST).
     * @param int $id
     * @return array|Response
     */
    public function actionToggleStatus($id)
    {
        $model = $this->findModel($id);
        $newStatus = Yii::$app->request->post('status');

        if (in_array($newStatus, array_keys(Todos::optsStatus()))) {
            $model->status = $newStatus;
            if ($newStatus === Todos::STATUS_COMPLETED) {
                $model->completed_at = date('Y-m-d H:i:s');
                // Also stop any running timer for this task
                $running = $model->getActiveTimer(Yii::$app->user->id);
                if ($running) {
                    $dur = max(1, time() - strtotime($running->start_time));
                    $running->end_time = date('Y-m-d H:i:s');
                    $running->duration_seconds = $dur;
                    $running->is_running = 0;
                    $running->save(false);
                    $model->recalculateTotalTime();
                }
            } else {
                $model->completed_at = null;
            }
            $model->save(false, ['status', 'completed_at', 'updated_at']);
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => true,
                'status' => $model->status,
                'statusLabel' => Todos::optsStatus()[$model->status] ?? $model->status,
                'statusBadge' => $model->getStatusBadgeClass(),
                'completionPercentage' => $model->getCompletionPercentage(),
            ];
        }

        Yii::$app->session->setFlash('success', 'Estado actualizado a: ' . (Todos::optsStatus()[$model->status] ?? $model->status));
        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Starts the live timer for this task.
     * Auto-stops any previous running timer for this user across any task.
     * @param int $id
     * @return array
     */
    public function actionStartTimer($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findModel($id);
        $userId = Yii::$app->user->id;

        // 1. Auto-stop any existing timer for this user
        $runningTimers = TodoTimeLogs::find()
            ->where(['user_id' => $userId, 'is_running' => 1])
            ->all();

        foreach ($runningTimers as $rTimer) {
            $dur = max(1, time() - strtotime($rTimer->start_time));
            $rTimer->end_time = date('Y-m-d H:i:s');
            $rTimer->duration_seconds = $dur;
            $rTimer->is_running = 0;
            $rTimer->save(false);
            if ($rTimer->todo) {
                $rTimer->todo->recalculateTotalTime();
            }
        }

        // 2. Start new timer
        $log = new TodoTimeLogs();
        $log->todo_id = $model->id;
        $log->user_id = $userId;
        $log->start_time = date('Y-m-d H:i:s');
        $log->is_running = 1;
        $log->duration_seconds = 0;
        $log->created_at = date('Y-m-d H:i:s');
        $log->save(false);

        // If todo was pending, transition to in_progress automatically
        if ($model->status === Todos::STATUS_PENDING) {
            $model->status = Todos::STATUS_IN_PROGRESS;
            $model->save(false, ['status', 'updated_at']);
        }

        return [
            'success' => true,
            'timerId' => $log->id,
            'startTime' => $log->start_time,
            'startTimestamp' => time(),
            'message' => 'Cronómetro iniciado.',
        ];
    }

    /**
     * Stops the running live timer.
     * @param int $id
     * @return array
     */
    public function actionStopTimer($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findModel($id);
        $userId = Yii::$app->user->id;

        $log = TodoTimeLogs::find()
            ->where(['todo_id' => $model->id, 'user_id' => $userId, 'is_running' => 1])
            ->one();

        if (!$log) {
            return ['success' => false, 'message' => 'No hay cronómetro activo para esta tarea.'];
        }

        $notes = trim(Yii::$app->request->post('description', ''));
        $duration = max(1, time() - strtotime($log->start_time));

        $log->end_time = date('Y-m-d H:i:s');
        $log->duration_seconds = $duration;
        $log->is_running = 0;
        if (!empty($notes)) {
            $log->description = $notes;
        }
        $log->save(false);

        $model->recalculateTotalTime();

        return [
            'success' => true,
            'duration' => $log->getFormattedDuration(),
            'totalTime' => $model->getFormattedTotalTime(),
            'totalSeconds' => $model->total_time_spent,
            'message' => 'Tiempo guardado exitosamente (' . $log->getFormattedDuration() . ').',
        ];
    }

    /**
     * Manually logs time for a task.
     * @param int $id
     * @return Response|array
     */
    public function actionLogTime($id)
    {
        $model = $this->findModel($id);
        $hours = (int) Yii::$app->request->post('hours', 0);
        $minutes = (int) Yii::$app->request->post('minutes', 0);
        $notes = trim(Yii::$app->request->post('description', ''));

        $totalSeconds = ($hours * 3600) + ($minutes * 60);

        if ($totalSeconds <= 0) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Por favor ingresa una cantidad de tiempo válida mayor a 0 minutos.'];
            }
            Yii::$app->session->setFlash('error', 'Ingresa una cantidad de tiempo válida.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $log = new TodoTimeLogs();
        $log->todo_id = $model->id;
        $log->user_id = Yii::$app->user->id;
        $log->start_time = date('Y-m-d H:i:s', time() - $totalSeconds);
        $log->end_time = date('Y-m-d H:i:s');
        $log->duration_seconds = $totalSeconds;
        $log->description = $notes;
        $log->is_running = 0;
        $log->created_at = date('Y-m-d H:i:s');
        $log->save(false);

        $model->recalculateTotalTime();

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => true,
                'totalTime' => $model->getFormattedTotalTime(),
                'message' => 'Tiempo registrado exitosamente (' . TodoTimeLogs::formatSeconds($totalSeconds) . ').',
            ];
        }

        Yii::$app->session->setFlash('success', 'Tiempo registrado exitosamente (' . TodoTimeLogs::formatSeconds($totalSeconds) . ').');
        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Deletes a specific time log.
     * @param int $id TimeLog ID
     * @return Response
     */
    public function actionDeleteTimeLog($id)
    {
        $log = TodoTimeLogs::findOne($id);
        if ($log) {
            $todo = $log->todo;
            $log->delete();
            if ($todo) {
                $todo->recalculateTotalTime();
            }
            Yii::$app->session->setFlash('success', 'Registro de tiempo eliminado.');
            return $this->redirect(['view', 'id' => $todo->id]);
        }

        return $this->redirect(['index']);
    }

    /**
     * Adds a checklist subtask item via AJAX.
     * @param int $id Todo ID
     * @return array
     */
    public function actionAddChecklist($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findModel($id);
        $title = trim(Yii::$app->request->post('title', ''));

        if (empty($title)) {
            return ['success' => false, 'message' => 'El título de la subtarea no puede estar vacío.'];
        }

        $item = new TodoChecklistItems();
        $item->todo_id = $model->id;
        $item->title = $title;
        $item->is_completed = 0;
        $item->sort_order = (int) TodoChecklistItems::find()->where(['todo_id' => $model->id])->count();
        $item->created_at = date('Y-m-d H:i:s');
        $item->save(false);

        return [
            'success' => true,
            'id' => $item->id,
            'title' => $item->title,
            'percentage' => $model->getCompletionPercentage(),
        ];
    }

    /**
     * Toggles checklist item completed status via AJAX.
     * @param int $id Checklist item ID
     * @return array
     */
    public function actionToggleChecklist($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $item = TodoChecklistItems::findOne($id);

        if (!$item) {
            return ['success' => false, 'message' => 'Subtarea no encontrada.'];
        }

        $item->is_completed = $item->is_completed ? 0 : 1;
        $item->save(false);

        $todo = $item->todo;

        return [
            'success' => true,
            'is_completed' => $item->is_completed,
            'percentage' => $todo ? $todo->getCompletionPercentage() : 0,
        ];
    }

    /**
     * Deletes a checklist item via AJAX.
     * @param int $id Checklist item ID
     * @return array
     */
    public function actionDeleteChecklist($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $item = TodoChecklistItems::findOne($id);

        if (!$item) {
            return ['success' => false, 'message' => 'Subtarea no encontrada.'];
        }

        $todo = $item->todo;
        $item->delete();

        return [
            'success' => true,
            'percentage' => $todo ? $todo->getCompletionPercentage() : 0,
        ];
    }

    /**
     * Adds a follow-up comment / progress note.
     * @param int $id Todo ID
     * @return Response
     */
    public function actionAddComment($id)
    {
        $model = $this->findModel($id);
        $commentText = trim(Yii::$app->request->post('comment', ''));

        if (!empty($commentText)) {
            $comment = new TodoComments();
            $comment->todo_id = $model->id;
            $comment->user_id = Yii::$app->user->id;
            $comment->comment = $commentText;
            $comment->created_at = date('Y-m-d H:i:s');
            $comment->save(false);

            Yii::$app->session->setFlash('success', 'Nota de seguimiento agregada.');
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Returns mapped customer list for dropdowns and filters.
     * @param bool $withDocument
     * @return array
     */
    protected function getCustomersList($withDocument = false)
    {
        $customers = Customers::find()
            ->select(['id', 'business_name', 'trade_name', 'document_number'])
            ->orderBy(['business_name' => SORT_ASC])
            ->all();

        return ArrayHelper::map($customers, 'id', function ($customer) use ($withDocument) {
            $name = $customer->trade_name ?: $customer->business_name;
            return $withDocument ? ($name . ' (' . $customer->document_number . ')') : $name;
        });
    }

    /**
     * Returns mapped assignable admin users list.
     * @return array
     */
    protected function getAdminsList()
    {
        $admins = User::find()
            ->select(['id', 'username', 'email'])
            ->where(['role' => User::ROLE_ADMIN, 'status' => User::STATUS_ACTIVE])
            ->orderBy(['username' => SORT_ASC])
            ->all();

        return ArrayHelper::map($admins, 'id', function ($user) {
            return $user->username . ' (' . $user->email . ')';
        });
    }

    /**
     * Finds the Todos model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id
     * @return Todos the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Todos::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La tarea solicitada no existe.');
    }
}
