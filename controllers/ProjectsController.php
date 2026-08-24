<?php

namespace app\controllers;

use Yii;
use app\models\Projects;
use app\models\ProjectsSearch;
use app\models\Customers;
use app\models\WorkOrders;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;

class ProjectsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new ProjectsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;

        if (!$user->isAdmin) {
            $customerId = $user->getRealCustomerId();
            if ($model->customer_id != $customerId) {
                throw new NotFoundHttpException('El proyecto solicitado no existe o no tiene permisos para verlo.');
            }
        }

        $workOrdersQuery = WorkOrders::find()->where(['project_id' => $model->id])->orderBy(['created_at' => SORT_DESC]);
        $workOrdersCount = $workOrdersQuery->count();
        $workOrdersCostCop = (float)$workOrdersQuery->sum('total_cost');

        return $this->render('view', [
            'model' => $model,
            'workOrders' => $workOrdersQuery->all(),
            'workOrdersCount' => $workOrdersCount,
            'workOrdersCostCop' => $workOrdersCostCop,
        ]);
    }

    public function actionCreate($customer_id = null)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Solo los administradores pueden crear proyectos.');
            return $this->redirect(['index']);
        }

        $model = new Projects();
        $model->status = Projects::STATUS_ACTIVE;

        if ($customer_id) {
            $customer = Customers::findOne($customer_id);
            if ($customer) {
                $model->customer_id = $customer->id;
                $model->business_name = $customer->business_name;
                $model->document_number = $customer->document_number;
                $model->address = $customer->address;
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Proyecto/Empresa creado exitosamente.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Solo los administradores pueden modificar proyectos.');
            return $this->redirect(['index']);
        }

        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Proyecto/Empresa actualizado exitosamente.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Solo los administradores pueden eliminar proyectos.');
            return $this->redirect(['index']);
        }

        $model = $this->findModel($id);

        if ($model->is_default) {
            Yii::$app->session->setFlash('error', 'No se puede eliminar el proyecto predeterminado del cliente.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->getWorkOrders()->count() > 0) {
            Yii::$app->session->setFlash('error', 'No se puede eliminar un proyecto que tiene órdenes de trabajo asociadas.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Proyecto eliminado exitosamente.');

        return $this->redirect(['index']);
    }

    /**
     * Endpoint AJAX para obtener los proyectos de un cliente
     */
    public function actionListByCustomer($customer_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            $realCustId = $user->getRealCustomerId();
            if ($customer_id != $realCustId) {
                return ['success' => false, 'projects' => []];
            }
        }

        $projects = Projects::find()
            ->where(['customer_id' => $customer_id, 'status' => Projects::STATUS_ACTIVE])
            ->orderBy(['is_default' => SORT_DESC, 'name' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($projects as $proj) {
            $result[] = [
                'id' => $proj->id,
                'name' => $proj->getDisplayName(),
                'code' => $proj->code,
                'is_default' => (bool)$proj->is_default,
            ];
        }

        return ['success' => true, 'projects' => $result];
    }

    protected function findModel($id)
    {
        if (($model = Projects::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('El proyecto solicitado no existe.');
    }
}
