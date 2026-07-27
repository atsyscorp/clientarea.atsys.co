<?php
namespace app\controllers;

use Yii;
use app\models\User;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;

class SubaccountsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Solo usuarios logueados
                        'matchCallback' => function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            
                            // 1. Bloqueamos a los administradores generales de ATSYS (tu equipo de soporte)
                            if ($user->isAdmin) {
                                return false;
                            }

                            // 2. PERMITIR ACCESO SI:
                            // - Es el Titular de la cuenta (parent_id es null)
                            // - O es una Sub-cuenta con Rol Administrativo (role == 12)
                            return $user->parent_id === null || $user->role == 12;
                        }
                    ],
                ],
            ],
        ];
    }

    // Acción para crear la subcuenta
    public function actionCreate()
    {
        $model = new User(); // O un modelo de formulario específico (ej: SignupForm)
        //$model->scenario = 'create_subaccount'; // Opcional, para reglas de validación específicas

        if ($this->request->isPost) {

            if ($model->load($this->request->post())) {

                $userData = $this->request->post('User');

                // Comprobar si el email ya existe
                $existingUser = User::findOne(['email' => $userData['email']]);
                if ($existingUser) {
                    Yii::$app->session->setFlash('error', 'El correo electrónico ya existe.');
                    return $this->redirect(['create']);
                }

                // Establecer valores (no lo hace al usar $model->load)
                $model->email = $userData['email'];
                $model->contact_name = $userData['contact_name'];
                $model->password = $userData['password'];

                // 1. Vinculamos la subcuenta al Titular logueado
                $model->parent_id = Yii::$app->user->identity->parent_id ? Yii::$app->user->identity->parent_id : Yii::$app->user->id;
                
                // 2. Encriptamos la contraseña que escribió el Titular
                $plainPassword = $model->password;

                $emailPrefix = explode('@', $model->email)[0];
                $cleanPrefix = preg_replace('/[^a-zA-Z0-9]/', '', $emailPrefix);

                $model->username = $cleanPrefix . '_' . rand(1000, 9999);
                $model->password_hash = Yii::$app->security->generatePasswordHash($model->password);
                $model->auth_key = Yii::$app->security->generateRandomString();

                $model->setPassword($model->password);
                
                // 3. Asignar rol de subcuenta
                $model->role = $userData['role']; // Subcuenta

                if ($model->save()) {

                    // --- ENVIAR CORREO A LA SUBCUENTA ---
                    try {
                        Yii::$app->mailer->compose('new_subaccount', [
                            'model' => $model,
                            'plainPassword' => $plainPassword,
                            'titular' => Yii::$app->user->identity
                        ])
                        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                        ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                        ->setTo($model->email)
                        ->setSubject('Tus credenciales de acceso')
                        ->send();
                    } catch (\Throwable $e) {
                        Yii::error("Fallo al enviar correo de bienvenida a delegado {$model->email}: " . $e->getMessage());
                        Yii::$app->session->setFlash('warning', 'Delegado creado, pero falló el envío del correo de bienvenida.');
                    }

                    Yii::$app->session->setFlash('success', 'Delegado creado con éxito. Ya puede iniciar sesión.');
                    return $this->redirect(['index']);
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Lista todos los delegados (sub-cuentas) que pertenecen al titular actual.
     */
    public function actionIndex()
    {
        $identity = Yii::$app->user->identity;

        // 1. Identificamos el ID del Titular Absoluto (Owner)
        $ownerId = $identity->parent_id ? $identity->parent_id : $identity->id;

        // 2. Buscamos a todos los usuarios cuyo parent_id sea el del Titular
        $queryUsers = User::find()->where([
            'parent_id' => $ownerId
        ]);

        if ($identity->role == 12) {
            $queryUsers->andWhere(['role' => 11]);
        }

        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $queryUsers,
            'pagination' => [
                'pageSize' => 15, 
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC, 
                ]
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Edita un delegado existente.
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost) {
            $postData = $this->request->post('User');
            
            $model->contact_name = $postData['contact_name'];
            $model->email = $postData['email'];
            $model->role = $postData['role']; // Capturamos si es Administrativa o Estándar

            // Solo cambiamos la contraseña si el titular escribió algo en el campo
            if (!empty($postData['password'])) {
                $model->password = $postData['password'];
                $model->password_hash = Yii::$app->security->generatePasswordHash($model->password);
            }

            if ($model->save(false)) { // save(false) para ignorar validaciones de rules faltantes
                Yii::$app->session->setFlash('success', 'Delegado actualizado correctamente.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Elimina (revoca el acceso) a un delegado.
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Guardamos los datos necesarios antes de eliminar el registro
        $userEmail = $model->email;
        $userName = $model->contact_name ?? $model->email;
        $titularName = Yii::$app->user->identity->contact_name ?? Yii::$app->user->identity->email;

        if ($model->delete()) {
            // --- ENVIAR CORREO DE REVOCACIÓN ---
            try {
                Yii::$app->mailer->compose('revoked_access', [
                    'userName' => $userName,
                    'titularName' => $titularName
                ])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                ->setTo($userEmail)
                ->setSubject('Notificación: Tu acceso a ATSYS ha sido revocado')
                ->send();
            } catch (\Throwable $e) {
                // Registramos el error de envío pero permitimos que el flujo continúe
                Yii::error("Fallo al enviar correo de revocación a $userEmail: " . $e->getMessage());
            }

            Yii::$app->session->setFlash('success', 'Acceso revocado correctamente y usuario notificado.');
        } else {
            Yii::$app->session->setFlash('error', 'No se pudo eliminar el acceso.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Función auxiliar para encontrar el modelo y asegurar que pertenece al Titular logueado
     */
    protected function findModel($id)
    {
        // IMPORTANTE: Buscamos por ID pero también validamos que el parent_id sea el del usuario actual
        if (($model = User::findOne(['id' => $id, 'parent_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }

        throw new \yii\web\NotFoundHttpException('El delegado solicitado no existe o no tienes permiso para gestionarlo.');
    }
    
}