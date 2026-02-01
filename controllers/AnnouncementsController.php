<?php

namespace app\controllers;

use Yii;
use app\models\Announcements;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * AnnouncementsController implementa el CRUD para el modelo Announcements.
 */
class AnnouncementsController extends Controller
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
                        'actions' => ['index', 'view', 'react'],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create', 'update', 'delete'], 
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
                    'react' => ['POST'], // Agregamos react como POST para seguridad
                ],
            ],
        ];
    }

    /**
     * Lista todos los comunicados.
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Announcements::find(),
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC, // Lo más nuevo primero
                ]
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Muestra el detalle y REGISTRA LA VISTA AUTOMÁTICAMENTE
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $userId = Yii::$app->user->id;

        // LÓGICA DE "VISTO": Si no lo ha visto, lo registramos
        $hasViewed = (new \yii\db\Query())
            ->from('announcement_views')
            ->where(['announcement_id' => $id, 'user_id' => $userId])
            ->exists();

        if (!$hasViewed) {
            Yii::$app->db->createCommand()->insert('announcement_views', [
                'announcement_id' => $id,
                'user_id' => $userId,
                'viewed_at' => date('Y-m-d H:i:s'),
            ])->execute();
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Acción AJAX para alternar reacciones (Like/Unlike o Cambiar reacción)
     */
    public function actionReact()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $announcementId = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type'); // 'like', 'love', etc.
        $userId = Yii::$app->user->id;

        // Verificar si ya reaccionó
        $existing = (new \yii\db\Query())
            ->from('announcement_reactions')
            ->where(['announcement_id' => $announcementId, 'user_id' => $userId])
            ->one();

        if ($existing) {
            if ($existing['reaction_type'] == $type) {
                // Si da clic a lo mismo, QUITAMOS la reacción (Toggle off)
                Yii::$app->db->createCommand()->delete('announcement_reactions', ['id' => $existing['id']])->execute();
                return ['status' => 'removed'];
            } else {
                // Si es diferente, ACTUALIZAMOS (de Like a Love, por ejemplo)
                Yii::$app->db->createCommand()->update('announcement_reactions', 
                    ['reaction_type' => $type], 
                    ['id' => $existing['id']]
                )->execute();
                return ['status' => 'updated'];
            }
        } else {
            // Si no existe, CREAMOS
            Yii::$app->db->createCommand()->insert('announcement_reactions', [
                'announcement_id' => $announcementId,
                'user_id' => $userId,
                'reaction_type' => $type
            ])->execute();
            return ['status' => 'created'];
        }
    }

    /**
     * Crea un nuevo comunicado.
     */
    public function actionCreate()
    {
        $model = new Announcements();
        
        // Valores por defecto
        $model->is_active = 1;
        $model->type = 'info'; 

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                
                // Asignar creador automáticamente
                $model->created_by = Yii::$app->user->id;
                
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'Comunicado publicado correctamente.');
                    return $this->redirect(['index']);
                }
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Actualiza un comunicado existente.
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post())) {
            // Opcional: Actualizar updated_at si no lo hace la BD automáticamente
            // $model->updated_at = date('Y-m-d H:i:s');
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Comunicado actualizado.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Elimina un comunicado.
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Comunicado eliminado.');

        return $this->redirect(['index']);
    }

    /**
     * Busca el modelo basado en su ID.
     * Si no lo encuentra, lanza error 404.
     */
    protected function findModel($id)
    {
        if (($model = Announcements::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La página solicitada no existe.');
    }
}