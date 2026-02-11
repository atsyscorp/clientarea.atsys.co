<?php

namespace app\controllers;

use Yii;
use app\models\Tickets;
use app\models\TicketsSearch;
use app\models\TicketReplies;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class TicketsController extends \yii\web\Controller
{

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        // REGLA 1: Usuarios autenticados pueden ver, crear y cerrar SUS tickets
                        [
                            'actions' => ['index', 'view', 'create', 'reply', 'close', 'bulk'],
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                        // REGLA 2: Solo el ADMIN puede ELIMINAR y actualizar (editar)
                        [
                            'actions' => ['update', 'delete'],
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
                        'close' => ['POST'], // Cerrar también debería ser POST por seguridad
                    ],
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new TicketsSearch();
        $queryParams = $this->request->queryParams;

        $dataProvider = $searchModel->search($queryParams);

        if (!Yii::$app->user->identity->isAdmin) {
            $myCustomer = \app\models\Customers::findOne(['user_id' => Yii::$app->user->id]);
            $realCustomerId = $myCustomer ? $myCustomer->id : -1;
            $dataProvider->query->andWhere(['customer_id' => $realCustomerId]);
        }
        // ---------------------------

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Muestra el ticket y sus respuestas
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $newReply = new TicketReplies();

        return $this->render('view', [
            'model' => $model,
            'newReply' => $newReply,
            'replies' => $model->getTicketReplies()->orderBy('created_at ASC')->all(),
        ]);
    }

    protected function findModel($id) {
        if (($model = Tickets::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('El ticket seleccionado no existe.');
    }

    /**
     * Acción para guardar una respuesta nueva
     */
    public function actionReply($id)
    {
        $ticket = $this->findModel($id);
        $reply = new TicketReplies();

        if ($this->request->isPost) {
            if ($reply->load($this->request->post())) {
                $reply->ticket_id = $ticket->id;
                $reply->sender_type = (Yii::$app->user->identity->isAdmin) ? 'admin' : 'customer'; // Porque esto se hace desde el panel
                $reply->user_id = Yii::$app->user->identity->id; // Descomenta si tienes login de usuarios configurado
                $reply->attachmentFile = UploadedFile::getInstance($reply, 'attachmentFile');

                if ($reply->attachmentFile) {
                    $folderPath = Yii::getAlias('@webroot/uploads/tickets/' . $ticket->id . '/');
                    
                    // 2. Crear carpeta si no existe
                    if (!file_exists($folderPath)) {
                        mkdir($folderPath, 0777, true);
                    }

                    $fileName = time() . '_' . $reply->attachmentFile->baseName . '.' . $reply->attachmentFile->extension;
                    $filePath = $folderPath . $fileName;

                    if ($reply->attachmentFile->saveAs($filePath)) {
                        $reply->attachment = 'uploads/tickets/' . $ticket->id . '/' . $fileName;
                    }
                }

                if ($reply->save()) {
                    // Actualizamos el estado del ticket
                    $ticket->status = 'answered';
                    $ticket->updated_at = date('Y-m-d H:i:s');
                    $ticket->save();
                    
                    $adminEmail = Yii::$app->params['adminEmail'] ?? 'gerencia@atsys.co';

                    try {
                        $mailer = Yii::$app->mailer->compose('ticket_reply', [
                            'reply' => $reply
                        ])
                        ->setFrom(['soporte@atsys.co' => 'Soporte ATSYS'])
                        ->setTo((Yii::$app->user->identity->isAdmin) ? $ticket->email : $adminEmail)
                        ->setSubject("[#{$ticket->ticket_code}]: " . $ticket->subject)
                        ->setBcc($adminEmail);

                        if($reply->attachment) {
                            $mailer->attach(Yii::getAlias('@webroot/') . $reply->attachment, [
                                'fileName' => basename(Yii::getAlias('@webroot/') . $reply->attachment),
                            ]);
                        }

                        $mailer->send();

                        Yii::$app->session->setFlash('success', (Yii::$app->user->identity->isAdmin) ? 'Respuesta enviada y notificada al cliente.' : 'Respuesta agregada');
                    } catch (\Exception $e) {
                        // Si falla el correo, guardamos la respuesta pero avisamos del error
                        Yii::$app->session->setFlash('warning', 'Respuesta guardada, pero falló el envío del correo: ' . $e->getMessage());
                    }
                } else {
                    echo implode(',', $reply->getSummaryError(true)); die;
                }
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Crear Ticket (Automático: Asigna usuario y estado abierto)
     */
    public function actionCreate()
    {
        $model = new Tickets(['scenario' => 'create']);
        
        $user = Yii::$app->user->identity;

        if(Yii::$app->user->identity->isAdmin) {
            $model->customer_id = Yii::$app->user->identity->customer;
        }

        if (!Yii::$app->user->identity->isAdmin) {
            $customer = \app\models\Customers::findOne(['user_id' => Yii::$app->user->id]);
            
            if ($customer) {
                $model->customer_id = $customer->id;
            } else {
                Yii::$app->session->setFlash('error', 'Tu usuario no tiene un perfil de cliente asociado.');
                return $this->redirect(['index']);
            }
        }

        $model->status = Tickets::STATUS_OPEN;
        $model->created_at = date('Y-m-d H:i:s');
        
        // Generar un código de ticket único (Ej: TKT-84920)
        $model->ticket_code = 'TKT-' . strtoupper(substr(uniqid(), -5)); 

        if ($this->request->isPost && $model->load($this->request->post())) {

            // 1. Capturamos el archivo desde el modelo Tickets
            $model->attachmentFile = \yii\web\UploadedFile::getInstance($model, 'attachmentFile');
            
            // INICIO TRANSACCIÓN
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // 1. Guardar el Ticket (Encabezado)
                $model->email = ($model->customer_id == '9999') ? $model->email : Yii::$app->user->identity->email;
                if ($model->save()) {
                    
                    // 2. Guardar el Mensaje Inicial en TicketReplies
                    $reply = new TicketReplies();
                    $reply->ticket_id = $model->id;
                    $reply->message = $model->message; // Tomado del campo virtual
                    
                    // Definir quién escribe (Cliente)
                    // Ajusta 'customer' o 'user' según lo que uses en tu base de datos para sender_type
                    $reply->sender_type = 'customer'; 
                    $reply->created_at = date('Y-m-d H:i:s');

                    if ($model->attachmentFile) {
                        $uploadPath = Yii::getAlias('@webroot/uploads/tickets/' . $model->id . '/');
                        if (!file_exists($uploadPath)) {
                            mkdir($uploadPath, 0777, true);
                        }

                        $fileName = time() . '_' . $model->attachmentFile->baseName . '.' . $model->attachmentFile->extension;
                        
                        if ($model->attachmentFile->saveAs($uploadPath . $fileName)) {
                            $reply->attachment = 'uploads/tickets/' . $model->id . '/' . $fileName;
                        }
                    }
                    
                    if ($reply->save()) {
                        // Si ambos se guardan, confirmamos cambios
                        $transaction->commit();

                        $this->sendNewTicketEmails($model, $model->message, $user);

                        Yii::$app->session->setFlash('success', '¡Ticket creado exitosamente! Te hemos enviado un correo de confirmación.');
                        return $this->redirect(['view', 'id' => $model->id]);
                    } else {
                        // Si falla el guardado del mensaje, lanzamos excepción
                        $transaction->rollBack();
                        throw new \Exception('No se pudo guardar el mensaje del ticket. ' . json_encode($reply->getErrors()));
                    }
                } else {
                    // Si falla el guardado del ticket, lanzamos excepción
                    throw new \Exception('No se pudo guardar el ticket. ' . json_encode($model->getErrors()));
                }
                
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
            }
        }

        $customers = [];
        if (Yii::$app->user->identity->isAdmin) {
            $customers = \yii\helpers\ArrayHelper::map(
                \app\models\Customers::find()->orderBy('business_name')->all(), 
                'id', 
                'business_name'
            );
            $customers[9999] = '★ Cliente No Registrado';
        }

        return $this->render('create', [
            'model' => $model,
            'customers' => $customers,
        ]);
    }

    /**
     * Función auxiliar para enviar las notificaciones
     */
    protected function sendNewTicketEmails($ticket, $messageContent, $user)
    {
        $adminEmail = Yii::$app->params['adminEmail'] ?? 'hola@atsys.co';

        Yii::$app->mailer->compose(
            ['html' => 'newTicket-html'],
            ['ticket' => $ticket, 'message' => $messageContent]
        )
        ->setFrom([Yii::$app->params['senderEmail'] => 'Soporte ATSYS'])
        ->setTo($ticket->email) // O $user->email
        ->setBcc($adminEmail)
        ->setSubject('[#'.$ticket->ticket_code.'] '. $ticket->subject)
        ->send();

        Yii::$app->mailer->compose(
            ['html' => 'adminNewTicket-html'],
            ['ticket' => $ticket, 'message' => $messageContent, 'user' => $user]
        )
        ->setFrom([Yii::$app->params['senderEmail'] => 'ATSYS Client Area'])
        ->setSubject('Nuevo Ticket [' . $ticket->ticket_code . '] - ' . $ticket->subject)
        ->send();
    }

    /**
     * Cerrar Ticket (Cambia estado a Cerrado)
     */
    public function actionClose($id)
    {
        $model = $this->findModel($id);

        if (!Yii::$app->user->identity->isAdmin) {
            $myCustomer = \app\models\Customers::findOne(['user_id' => Yii::$app->user->id]);
            if (!$myCustomer || $model->customer_id !== $myCustomer->id) {
                throw new \yii\web\ForbiddenHttpException('No tienes permiso para gestionar este ticket.');
            }
        }

        $model->status = Tickets::STATUS_CLOSED;
        
        if ($model->save()) {
            Yii::$app->session->setFlash('info', 'El ticket ha sido cerrado.');
        } else {
            Yii::$app->session->setFlash('error', 'No se pudo cerrar el ticket.');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    /**
     * Eliminar Ticket (Solo Admin, protegido por behaviors)
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        Yii::$app->session->setFlash('success', 'Ticket eliminado correctamente.');
        return $this->redirect(['index']);
    }

    public function actionBulk()
    {
        // Solo permitimos peticiones POST por seguridad
        if ($this->request->isPost) {
            $ids = $this->request->post('ids'); // Array de IDs seleccionados
            $action = $this->request->post('action'); // 'close' o 'delete'

            if (empty($ids)) {
                Yii::$app->session->setFlash('warning', 'No has seleccionado ningún ticket.');
                return $this->redirect(['index']);
            }

            $count = 0;
            
            foreach ($ids as $id) {
                $model = $this->findModel($id); // Asegúrate de tener findModel disponible o usa Tickets::findOne($id)
                
                if ($model) {
                    if ($action === 'close' && $model->status !== 'closed') {
                        $model->status = 'closed';
                        if ($model->save()) $count++;
                    } 
                    elseif ($action === 'delete') {
                        if ($model->delete()) $count++;
                    }
                }
            }

            $message = $action === 'delete' 
                ? "Se eliminaron $count tickets correctamente." 
                : "Se cerraron $count tickets correctamente.";

            Yii::$app->session->setFlash('success', $message);
        }

        return $this->redirect(['index']);
    }

}
