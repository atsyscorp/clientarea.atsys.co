<?php

namespace app\controllers;

use Yii;
use app\models\TicketSpamBlacklist;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * TicketSpamBlacklistController implements the CRUD actions for TicketSpamBlacklist model.
 */
class TicketSpamBlacklistController extends Controller
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
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'],
                            'matchCallback' => function ($rule, $action) {
                                return !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
                            }
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all TicketSpamBlacklist models and handles fast creation.
     * @return string|\yii\web\Response
     */
    public function actionIndex()
    {
        // Auto-crear la tabla si no existe
        try {
            Yii::$app->db->createCommand("
                CREATE TABLE IF NOT EXISTS `ticket_spam_blacklist` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `email` VARCHAR(255) NOT NULL UNIQUE,
                  `reason` VARCHAR(255) NULL,
                  `created_at` DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ")->execute();
        } catch (\Exception $e) {
            Yii::error("Error creando tabla ticket_spam_blacklist: " . $e->getMessage());
        }

        $model = new TicketSpamBlacklist();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                Yii::$app->session->setFlash('success', 'Correo electrónico agregado a la lista negra con éxito.');
                return $this->redirect(['index']);
            }
        }

        $dataProvider = new ActiveDataProvider([
            'query' => TicketSpamBlacklist::find()->orderBy(['created_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing TicketSpamBlacklist model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'El correo electrónico ha sido eliminado de la lista negra de SPAM.');
        return $this->redirect(['index']);
    }

    /**
     * Finds the TicketSpamBlacklist model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return TicketSpamBlacklist the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = TicketSpamBlacklist::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La dirección de correo solicitada no existe.');
    }
}
