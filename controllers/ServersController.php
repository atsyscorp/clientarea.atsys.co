<?php

namespace app\controllers;

use yii\helpers\Html;

use Yii;
use app\models\Servers;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;

class ServersController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    // Listado de servidores
    public function actionIndex()
    {
        $servers = new ActiveDataProvider([
            'query' => Servers::find(),
            'pagination' => [
                'pageSize' => 15,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ]
            ],
        ]);

        return $this->render('index', ['dataProvider' => $servers]);
    }

    // Agregar un nuevo servidor
    public function actionCreate()
    {
        $model = new Servers();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Servidor " . Html::encode($model->name) . " agregado.");
            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $model]);
    }

    // Editar servidor existente
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Configuración de servidor " . Html::encode($model->name) . " actualizada.");
            return $this->redirect(['index']);
        }

        return $this->render('update', ['model' => $model]);
    }

    // Eliminar servidor
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = Servers::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('El servidor no existe.');
    }
}