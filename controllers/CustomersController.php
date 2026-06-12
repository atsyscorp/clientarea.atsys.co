<?php

namespace app\controllers;

use Yii;
use app\models\Customers;
use app\models\CustomersSearch;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * CustomersController implements the CRUD actions for Customers model.
 */
class CustomersController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'only' => ['index', 'view', 'create', 'update', 'delete', 'impersonate', 'stop-impersonating'],
                    'rules' => [
                        [
                            'actions' => ['create', 'view', 'update'], 
                            'allow' => true,
                            'roles' => ['@'],
                            'matchCallback' => function ($rule, $action) {
                                if (Yii::$app->user->isGuest) {
                                    return false;
                                }

                                if (Yii::$app->user->identity->isAdmin) {
                                    return true;
                                }

                                if ($action->id === 'create') {
                                    return Yii::$app->user->identity->customer === null;
                                }

                                if (in_array($action->id, ['view', 'update'])) {
                                    $id = Yii::$app->request->get('id');
                                    return Yii::$app->user->identity->customer && Yii::$app->user->identity->customer->id == $id;
                                }
                                
                                return false;
                            }
                        ],
                        [
                            'actions' => ['index', 'delete', 'impersonate'],
                            'allow' => true,
                            'matchCallback' => function ($rule, $action) {
                                if (Yii::$app->user->isGuest) {
                                    return false;
                                }
                                return Yii::$app->user->identity->isAdmin;
                            }
                        ],
                        [
                            'actions' => ['stop-impersonating'],
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Customers models.
     *
     * @return string
     */
    public function actionIndex()
    {
        // 2. Instanciamos el modelo de búsqueda
        $searchModel = new CustomersSearch();
        
        // 3. Obtenemos los datos filtrados según lo que llegue en la URL (queryParams)
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel, // <--- 4. Pasamos el searchModel a la vista
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Customers model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Customers model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Customers();

        // ASIGNACIÓN AUTOMÁTICA DEL ID DE USUARIO
        if (!Yii::$app->user->isGuest) {
            $model->user_id = Yii::$app->user->id;
        }

        if ($this->request->isPost) {
            if(!Yii::$app->user->isGuest && !Yii::$app->user->identity->isAdmin) {
                $model->status = 'active';
            }
            if ($model->load($this->request->post()) && $model->save()) {
                Yii::$app->session->setFlash('success', '¡Perfil completado! Bienvenido a ATSYS.');
                return $this->redirect(['/site/index']); // Ahora sí lo dejamos ir al Dashboard
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Customers model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Customers model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Impersonate a customer.
     * @param int $id ID of the customer to impersonate
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionImpersonate($id)
    {
        $customer = $this->findModel($id);
        
        if (empty($customer->user_id)) {
            Yii::$app->session->setFlash('error', 'Este cliente no tiene un usuario de acceso configurado.');
            return $this->redirect(['index']);
        }
        
        $user = \app\models\User::findOne($customer->user_id);
        if (!$user) {
            Yii::$app->session->setFlash('error', 'No se encontró el usuario asociado a este cliente.');
            return $this->redirect(['index']);
        }

        // Guardamos el ID del administrador actual en la sesión antes de loguearnos como cliente
        $adminId = Yii::$app->user->id;
        
        // Iniciamos sesión como el cliente
        if (Yii::$app->user->login($user, 3600 * 24)) {
            Yii::$app->session->set('original_admin_id', $adminId);
            Yii::$app->session->setFlash('success', 'Has iniciado sesión como ' . ($customer->business_name ?: $customer->contact_name));
            return $this->redirect(['/site/index']);
        }
        
        Yii::$app->session->setFlash('error', 'No se pudo iniciar sesión como el cliente.');
        return $this->redirect(['index']);
    }

    /**
     * Stop impersonating and return to the original admin user.
     * @return \yii\web\Response
     */
    public function actionStopImpersonating()
    {
        $session = Yii::$app->session;
        if ($session->has('original_admin_id')) {
            $adminId = $session->get('original_admin_id');
            $adminUser = \app\models\User::findOne($adminId);
            
            if ($adminUser && $adminUser->isAdmin) {
                // Removemos el ID del admin de la sesión antes de loguearnos
                $session->remove('original_admin_id');
                
                if (Yii::$app->user->login($adminUser, 3600 * 24)) {
                    Yii::$app->session->setFlash('success', 'Has vuelto a tu cuenta de Administrador.');
                    return $this->redirect(['/customers/index']);
                }
            }
        }
        
        // Si no hay admin original o falla, cerramos sesión
        Yii::$app->user->logout();
        return $this->redirect(['/site/login']);
    }

    /**
     * Finds the Customers model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Customers the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Customers::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
