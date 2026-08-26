<?php

namespace app\controllers;

use yii\helpers\Html;

use Yii;
use app\models\User;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;

class UsersController extends Controller
{
    /**
     * @inheritDoc
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
                            return Yii::$app->user->identity->isAdmin;
                        }
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     */
    public function actionIndex()
    {
        $query = User::find();

        // Búsqueda simple
        $q = Yii::$app->request->get('q');
        if (!empty($q)) {
            $query->andWhere(['or',
                ['like', 'username', $q],
                ['like', 'email', $q]
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ]
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'q' => $q,
        ]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost) {
            $post = $this->request->post('User');
            
            if ($post) {
                $model->username = $post['username'] ?? $model->username;
                $model->email = $post['email'] ?? $model->email;
                $model->role = isset($post['role']) ? (int)$post['role'] : $model->role;
                $model->status = isset($post['status']) ? (int)$post['status'] : $model->status;
                $model->mobile = $post['mobile'] ?? $model->mobile;

                // Cambio de contraseña opcional
                $newPassword = $this->request->post('new_password');
                if (!empty(trim($newPassword))) {
                    $model->setPassword($newPassword);
                }

                if ($model->save(false)) {
                    Yii::$app->session->setFlash('success', 'Usuario actualizado correctamente.');
                    return $this->redirect(['index']);
                } else {
                    Yii::$app->session->setFlash('error', 'No se pudieron guardar los cambios.');
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Impersonate a user directly by their user ID.
     * @param int $id ID of the user to impersonate
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the user cannot be found
     */
    public function actionImpersonate($id)
    {
        $user = $this->findModel($id);
        
        if ($user->id == Yii::$app->user->id) {
            Yii::$app->session->setFlash('error', 'No puedes impersonarte a ti mismo.');
            return $this->redirect(['index']);
        }

        $adminId = Yii::$app->user->id;

        if (Yii::$app->user->login($user, 3600 * 24)) {
            Yii::$app->session->set('original_admin_id', $adminId);
            Yii::$app->session->setFlash('success', 'Has iniciado sesión como ' . Html::encode($user->username));
            return $this->redirect(['/site/index']);
        }

        Yii::$app->session->setFlash('error', 'No se pudo iniciar sesión como este usuario.');
        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('El usuario solicitado no existe.');
    }
}
