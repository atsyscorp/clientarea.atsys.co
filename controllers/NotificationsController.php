<?php

namespace app\controllers;

use yii\helpers\Html;

use Yii;
use app\models\Notifications;
use app\models\Customers;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

/**
 * NotificationsController implements the actions for the Notifications model.
 */
class NotificationsController extends Controller
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
                        'actions' => ['index', 'read', 'mark-all-read', 'poll'],
                        'allow' => true,
                        'roles' => ['@'], // Only logged-in users
                    ],
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
                        }
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all notifications of the current logged-in user.
     * @return string
     */
    public function actionIndex($unread = null)
    {
        $userId = Yii::$app->user->id;
        $query = Notifications::find()->where(['user_id' => $userId]);

        if ($unread !== null) {
            $query->andWhere(['is_read' => 1 ? 0 : 1]); // if unread=1, filter is_read = 0
            if ($unread == 1) {
                $query->where(['user_id' => $userId, 'is_read' => 0]);
            }
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query->orderBy(['created_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 15,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'unreadOnly' => ($unread == 1),
        ]);
    }

    /**
     * Marks a notification as read and redirects to its link.
     * @param int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the notification cannot be found
     * @throws ForbiddenHttpException if the notification does not belong to the current user
     */
    public function actionRead($id)
    {
        $model = $this->findModel($id);

        if ($model->user_id !== Yii::$app->user->id) {
            throw new ForbiddenHttpException('No tienes permiso para ver esta notificación.');
        }

        $model->is_read = 1;
        $model->save(false);

        if (!empty($model->link)) {
            return $this->redirect($model->link);
        }

        return $this->redirect(['index']);
    }

    /**
     * Marks all notifications of the current user as read.
     * @return \yii\web\Response
     */
    public function actionMarkAllRead()
    {
        $userId = Yii::$app->user->id;
        Notifications::updateAll(['is_read' => 1], ['user_id' => $userId, 'is_read' => 0]);

        Yii::$app->session->setFlash('success', 'Todas las notificaciones han sido marcadas como leídas.');
        return $this->redirect(['index']);
    }

    /**
     * Creates a new notification (Special/Promotional) for all clients or a specific one.
     * Only accessible by Admin.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Notifications();
        $model->type = Notifications::TYPE_PROMO;

        if ($this->request->isPost) {
            $post = $this->request->post();
            $target = $post['target'] ?? 'all'; // 'all' or specific customer_id
            $title = $post['Notifications']['title'] ?? '';
            $body = $post['Notifications']['body'] ?? '';
            $link = $post['Notifications']['link'] ?? null;

            if (empty($title) || empty($body)) {
                Yii::$app->session->setFlash('error', 'El título y el mensaje son campos obligatorios.');
            } else {
                if ($target === 'all') {
                    Notifications::notifyAllClients($title, $body, $link, Notifications::TYPE_PROMO);
                    Yii::$app->session->setFlash('success', 'Notificación publicitaria enviada con éxito a todos los clientes.');
                    return $this->redirect(['index']);
                } else {
                    $customerId = (int) $target;
                    $customer = Customers::findOne($customerId);
                    if ($customer) {
                        Notifications::notifyCustomer($customerId, $title, $body, $link, Notifications::TYPE_PROMO);
                        Yii::$app->session->setFlash('success', "Notificación enviada con éxito al cliente: " . Html::encode($customer->business_name) . ".");
                        return $this->redirect(['index']);
                    } else {
                        Yii::$app->session->setFlash('error', 'El cliente seleccionado no existe.');
                    }
                }
            }
        }

        $customers = Customers::find()->where(['status' => Customers::STATUS_ACTIVE])->orderBy('business_name')->all();

        return $this->render('create', [
            'model' => $model,
            'customers' => $customers,
        ]);
    }

    /**
     * Devuelve el recuento y el HTML de las notificaciones recientes para AJAX polling.
     */
    public function actionPoll()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return [
                'unreadCount' => 0,
                'htmlMobile' => '',
                'htmlDesktop' => ''
            ];
        }

        $userId = Yii::$app->user->id;
        $unreadCount = Notifications::find()->where(['user_id' => $userId, 'is_read' => 0])->count();
        $recentNotifications = Notifications::find()->where(['user_id' => $userId])->orderBy(['created_at' => SORT_DESC])->limit(5)->all();

        return [
            'unreadCount' => (int) $unreadCount,
            'htmlMobile' => $this->renderPartial('_dropdown', ['recentNotifications' => $recentNotifications, 'isMobile' => true]),
            'htmlDesktop' => $this->renderPartial('_dropdown', ['recentNotifications' => $recentNotifications, 'isMobile' => false]),
        ];
    }

    /**
     * Finds the Notifications model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id
     * @return Notifications the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Notifications::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La notificación no existe.');
    }
}
