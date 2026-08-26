<?php

namespace app\controllers;

use yii\helpers\Html;

use Yii;
use app\models\Tickets;
use app\models\TicketsSearch;
use app\models\TicketReplies;
use app\models\TicketSpamBlacklist;
use app\models\User;
use app\models\Notifications;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\web\Response;

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
                            'actions' => ['index', 'view', 'create', 'reply', 'close', 'bulk', 'upload-image', 'badge-count', 'get-new-replies'],
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                        // REGLA 2: Solo el ADMIN puede ELIMINAR, fusionar, bloquear remitente y actualizar (editar)
                        [
                            'actions' => ['update', 'in-progress', 'delete', 'toggle-lock', 'merge', 'block-sender', 'unblock-sender'],
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
                        'toggle-lock' => ['POST'],
                        'merge' => ['POST'],
                        'block-sender' => ['POST'],
                        'unblock-sender' => ['POST'],
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
        $dataProvider = $searchModel->search($this->request->queryParams);

        // Si NO es administrador de ATSYS, aplicamos el filtro de privacidad
        if (!Yii::$app->user->identity->isAdmin) {

            $identity = Yii::$app->user->identity;

            // 1. Identificar al "Dueño de la Empresa"
            // Si el usuario tiene un parent_id (es delegado), usamos el ID de su jefe.
            // Si no tiene parent_id (es el titular), usamos su propio ID.
            $ownerId = (!empty($identity->parent_id)) ? $identity->parent_id : $identity->id;

            // 2. Buscar el registro de la Empresa (Customers) basado en el Titular
            $myCustomer = \app\models\Customers::findOne(['user_id' => $ownerId]);
            $realCustomerId = $myCustomer ? $myCustomer->id : -1;

            // 3. Filtrar el DataProvider
            // Así, tanto el titular como el delegado verán todos los tickets de la empresa
            $dataProvider->query->andWhere(['customer_id' => $realCustomerId]);
        }

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
        $isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;

        if (!$isAdmin) {
            $myCustomerId = Yii::$app->user->identity->realCustomerId;
            if (!$myCustomerId || $model->customer_id != $myCustomerId) {
                throw new \yii\web\ForbiddenHttpException('No tienes permiso para ver este ticket.');
            }
        }

        $newReply = new TicketReplies();

        return $this->render('view', [
            'model' => $model,
            'newReply' => $newReply,
            'replies' => $model->getTicketReplies()->orderBy('created_at ASC')->all(),
        ]);
    }

    /**
     * Devuelve las respuestas nuevas de un ticket por AJAX (Polling)
     */
    public function actionGetNewReplies($id, $lastReplyId)
    {
        $this->layout = false;
        
        $model = $this->findModel($id);
        
        // Verificación de permisos (igual que view)
        if (!Yii::$app->user->identity->isAdmin) {
            $myCustomerId = Yii::$app->user->identity->realCustomerId;
            if (!$myCustomerId || $model->customer_id != $myCustomerId) {
                throw new \yii\web\ForbiddenHttpException('No tienes permiso para ver este ticket.');
            }
        }
        
        $newReplies = $model->getTicketReplies()
            ->where(['>', 'id', $lastReplyId])
            ->orderBy('created_at ASC')
            ->all();
            
        $html = '';
        foreach ($newReplies as $reply) {
            $html .= $this->renderPartial('/tickets/_reply', [
                'reply' => $reply,
                'model' => $model,
            ]);
        }
        
        return $html;
    }

    protected function findModel($id)
    {
        // Identificar el id del ticket.
        if (is_numeric($id)) {
            if (($model = Tickets::findOne($id)) !== null) {
                return $model;
            }
        } else {
            // Si contiene TKT en $id
            if (($model = Tickets::findOne(['ticket_code' => $id])) !== null) {
                return $model;
            }
        }

        throw new NotFoundHttpException('El ticket seleccionado no existe.');
    }

    /**
     * Acción para guardar una respuesta nueva
     */
    public function actionReply($id)
    {
        $ticket = $this->findModel($id);
        $isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;

        if (!$isAdmin) {
            $myCustomerId = Yii::$app->user->identity->realCustomerId;
            if (!$myCustomerId || $ticket->customer_id != $myCustomerId) {
                throw new \yii\web\ForbiddenHttpException('No tienes permiso para responder a este ticket.');
            }

            // Si el usuario logueado o el remitente del ticket está en lista negra, no permitir responder
            $userEmail = Yii::$app->user->identity->email ?? '';
            $isBlocked = $ticket->isSenderBlacklisted() || (!empty($userEmail) && TicketSpamBlacklist::find()->where(['email' => strtolower(trim($userEmail))])->exists());
            if ($isBlocked) {
                Yii::$app->session->setFlash('error', 'Tu cuenta o dirección de correo electrónico se encuentra restringida para responder a tickets.');
                return $this->redirect(['view', 'id' => $id]);
            }
        }

        // Si el ticket está bloqueado para respuestas y no es admin, no permitir responder
        if ($ticket->isLocked() && !$isAdmin) {
            Yii::$app->session->setFlash('info', 'El ticket se encuentra cerrado.');
            return $this->redirect(['view', 'id' => $id]);
        }

        // Restringir a máximo 3 respuestas consecutivas del cliente si no está ni respondido ni en progreso
        if (!$ticket->canCustomerReply($isAdmin)) {
            Yii::$app->session->setFlash('warning', 'Has alcanzado el límite máximo de 3 respuestas consecutivas sin atención. Por favor espera a que nuestro equipo responda o ponga en proceso tu ticket.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $reply = new TicketReplies(); // Asegúrate de tener el use app\models\TicketReplies;

        if ($this->request->isPost) {
            // Cargamos los datos del formulario (mensaje, etc.)
            if ($reply->load($this->request->post())) {

                $reply->ticket_id = $ticket->id;

                // Determinamos quién responde
                $reply->sender_type = $isAdmin ? 'admin' : 'customer';
                $reply->user_id = Yii::$app->user->id;

                // Lógica de Archivos Adjuntos
                $reply->attachmentFile = UploadedFile::getInstance($reply, 'attachmentFile');
                if ($reply->attachmentFile) {
                    $folderPath = Yii::getAlias('@webroot/uploads/tickets/' . $ticket->id . '/');
                    if (!file_exists($folderPath)) {
                        mkdir($folderPath, 0777, true);
                    }
                    $fileName = time() . '_' . $reply->attachmentFile->baseName . '.' . $reply->attachmentFile->extension;
                    $filePath = $folderPath . $fileName;
                    if ($reply->attachmentFile->saveAs($filePath)) {
                        $reply->attachment = 'uploads/tickets/' . $ticket->id . '/' . $fileName;
                    }
                }

                // Guardamos la respuesta
                if ($reply->save()) {

                    // Actualizamos el ticket padre
                    // Si responde el cliente, el estado pasa a 'open' o 'customer_reply' para que lo veas
                    // Si responde el admin, pasa a 'answered'
                    $ticket->status = ($isAdmin) ? 'answered' : 'customer_reply';
                    $ticket->updated_at = date('Y-m-d H:i:s');
                    
                    // Extraer menciones del cuerpo de la respuesta, fusionarlas con la lista existente de CC del ticket
                    $emails = Tickets::extractEmailsFromMessage($reply->message);
                    if ($ticket->customer_id && !empty($emails)) {
                        $validEmails = Tickets::filterDelegatesByCustomer($emails, $ticket->customer_id);
                        $existingEmails = !empty($ticket->cc_emails) 
                            ? array_map('trim', explode(',', $ticket->cc_emails)) 
                            : [];
                        $mergedEmails = array_unique(array_merge($existingEmails, $validEmails));
                        $ticket->cc_emails = !empty($mergedEmails) ? implode(', ', $mergedEmails) : null;
                    }
                    
                    $ticket->save(false); // false para saltar validaciones estrictas del ticket si solo actualizamos fecha

                    // ========================================================
                    // 🔔 NUEVO: DISPARADOR DE NOTIFICACIONES PUSH A N8N
                    // Solo si responde el CLIENTE, avisamos a los ADMINS
                    // ========================================================
                    if (!$isAdmin) {
                        $this->triggerN8nNotification(
                            "💬 Respuesta a ticket " . $ticket->ticket_code,
                            "Mensaje: " . mb_substr(strip_tags($reply->message), 0, 50, 'UTF-8') . "...",
                            $ticket->id
                        );

                        // Notificación en plataforma para Admins
                        Notifications::notifyAdmins(
                            "💬 Respuesta en Ticket " . $ticket->ticket_code,
                            "El cliente ha respondido al ticket: " . mb_substr(strip_tags($reply->message), 0, 80, 'UTF-8') . "...",
                            "/tickets/view?id=" . $ticket->id,
                            Notifications::TYPE_INFO
                        );
                    } else {
                        // Notificación en plataforma para Cliente
                        Notifications::notifyCustomer(
                            $ticket->customer_id,
                            "💬 Nueva Respuesta en Ticket " . $ticket->ticket_code,
                            "Soporte ha respondido a tu ticket: " . mb_substr(strip_tags($reply->message), 0, 80, 'UTF-8') . "...",
                            "/tickets/view?id=" . $ticket->id,
                            Notifications::TYPE_SUCCESS
                        );
                    }
                    // ========================================================

                    // Lógica de Email (Tu código original)
                    $adminEmail = Yii::$app->params['adminEmail'] ?? 'gerencia@atsys.co';

                    try {
                        $department = $ticket->getDepartmentEmail();
                        $mailer = Yii::$app->mailer->compose('ticket_reply', ['reply' => $reply])
                            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                            ->setReplyTo(Yii::$app->params['departmentEmails'][$ticket->department] ?? (Yii::$app->params['senderEmail'] ?? 'soporte@atsys.co'))
                            ->setTo($isAdmin ? $ticket->email : $adminEmail)
                            ->setSubject("[#{$ticket->ticket_code}]: " . $ticket->subject);

                        // CC mentioned delegates
                        if (!empty($ticket->cc_emails)) {
                            $ccList = array_filter(array_map('trim', explode(',', $ticket->cc_emails)));
                            if (!empty($ccList)) {
                                $mailer->setCc($ccList);
                            }
                        }

                        if ($reply->attachment) {
                            $mailer->attach(Yii::getAlias('@webroot/') . $reply->attachment, [
                                'fileName' => basename(Yii::getAlias('@webroot/') . $reply->attachment),
                            ]);
                        }

                        $mailer->send();
                        Yii::$app->session->setFlash('success', $isAdmin ? 'Respuesta enviada.' : 'Respuesta agregada correctamente.');
                    } catch (\Throwable $e) {
                        Yii::$app->session->setFlash('warning', 'Respuesta guardada, pero falló el envío del correo: ' . $e->getMessage());
                    }

                } else {
                    // Manejo de errores de validación del modelo Reply
                    Yii::$app->session->setFlash('error', 'Error guardando respuesta: ' . implode(',', $reply->getFirstErrors()));
                }
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Obtiene los delegados de un cliente específico por AJAX
     */
    public function actionGetDelegates($customer_id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $customer = \app\models\Customers::findOne($customer_id);
        if (!$customer || !$customer->user_id) {
            return ['success' => true, 'delegates' => []];
        }
        
        $delegates = User::find()
            ->select(['id', 'contact_name', 'username', 'email'])
            ->where([
                'or',
                ['id' => $customer->user_id],
                ['parent_id' => $customer->user_id]
            ])
            ->andWhere(['status' => User::STATUS_ACTIVE])
            ->asArray()
            ->all();
            
        return ['success' => true, 'delegates' => $delegates];
    }

    /**
     * Envía una señal a N8N para procesar la notificación Push a los Administradores
     */
    protected function triggerN8nNotification($title, $body, $ticketId)
    {
        $tokens = \app\models\AdminTokens::find()->select('token')->column();

        if (empty($tokens)) {
            return; // No hay nadie a quien notificar
        }

        // 2. Preparar el payload, incluir imagen de ATSYS
        $payload = [
            'tokens' => $tokens, // Array de tokens
            'title' => $title,
            'body' => $body,
            'message' => $body,
            'link' => "https://clientarea.atsys.co/tickets/view?id=" . $ticketId,
            'image' => 'https://clientarea.atsys.co/images/atsys-clientarea-og.webp',
            'type' => 'ticket'
        ];

        // 3. Configurar la petición a N8N usando la URL dinámica del sistema
        $n8nUrl = Yii::$app->params['n8n_admin_push_url'] ?? 'https://n8n-new.atsys.co/webhook/send-admin-push';

        try {
            $ch = curl_init($n8nUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            // Silenciamos el error para no interrumpir el flujo del usuario
            Yii::error("Error enviando push a N8N: " . $e->getMessage(), 'n8n_push');
        }
    }

    /**
     * Crear Ticket (Automático: Asigna usuario y estado abierto)
     */
    public function actionCreate()
    {

        $user = Yii::$app->user->identity;
        $isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;

        // 1. Definimos el límite de tickets activos simultáneos
        $limiteTicketsActivos = isset(Yii::$app->params['ticket_max_pending']) ? (int)Yii::$app->params['ticket_max_pending'] : 4;

        $model = new Tickets(['scenario' => 'create']);

        // 2. Aplicamos la regla SOLO a los clientes (excluimos a los administradores de ATSYS)
        if (!$isAdmin) {

            $userEmail = $user ? $user->email : '';
            if (!empty($userEmail) && TicketSpamBlacklist::find()->where(['email' => strtolower(trim($userEmail))])->exists()) {
                Yii::$app->session->setFlash('error', 'Tu cuenta se encuentra bloqueada para la creación de nuevos tickets. Si consideras que se trata de un error, por favor contacta con soporte.');
                return $this->redirect(['index']);
            }

            $customer_id = Yii::$app->user->identity->getRealCustomerId();

            if (!$customer_id) {
                Yii::$app->session->setFlash('error', 'Tu usuario no tiene un perfil de cliente asociado.');
                return $this->redirect(['index']);
            }

            $model->customer_id = $customer_id;

            // Contamos los tickets que el cliente tiene en espera o en progreso
            $ticketsActivos = Tickets::find()
                ->where(['customer_id' => $customer_id])
                ->andWhere(['in', 'status', ['open', 'in_progress']])
                ->count();

            // 3. Si llega al límite, bloqueamos y redirigimos
            if ($ticketsActivos >= $limiteTicketsActivos) {
                Yii::$app->session->setFlash('warning', '<b>Límite de solicitudes activas:</b> Actualmente tienes ' . $ticketsActivos . ' tickets en proceso. Para abrir una nueva solicitud, por favor espera a que nuestro equipo resuelva las actuales o marca como resueltos los tickets que ya fueron solucionados.');
                return $this->redirect(['index']);
            }

        }

        $model->status = Tickets::STATUS_OPEN;
        $model->created_at = date('Y-m-d H:i:s');

        // Generar un código de ticket único (Ej: TKT-84920)
        $model->ticket_code = 'TKT-' . strtoupper(substr(uniqid(), -5));

        if ($this->request->isPost && $model->load($this->request->post())) {

            if (Yii::$app->user->identity->isAdmin) {
                $model->customer_id = $this->request->post('Tickets')['customer_id'];
                $customer = \app\models\Customers::findOne(['id' => $this->request->post('Tickets')['customer_id']]);
            }

            // 1. Capturamos el archivo desde el modelo Tickets
            $model->attachmentFile = \yii\web\UploadedFile::getInstance($model, 'attachmentFile');

            // INICIO TRANSACCIÓN
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // 1. Guardar el Ticket (Encabezado)
                $model->email = ($model->customer_id == '9999') ?
                    $this->request->post('Tickets')['email'] : (($isAdmin) ? $customer->email : Yii::$app->user->identity->email);
                if ($model->save()) {

                    // 2. Guardar el Mensaje Inicial en TicketReplies
                    $reply = new TicketReplies();
                    $reply->ticket_id = $model->id;
                    $reply->message = $model->message; // Tomado del campo virtual

                    // Definir quién escribe (la primera respuesta representa la solicitud del cliente)
                    $reply->sender_type = 'customer';
                    $reply->created_at = date('Y-m-d H:i:s');
                    
                    if ($isAdmin) {
                        $reply->user_id = ($customer && $customer->user_id) ? $customer->user_id : null;
                    } else {
                        $reply->user_id = Yii::$app->user->id;
                    }

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

                        $model->sendNewTicketNotifications($model->message, $user, $isAdmin);

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
                Yii::$app->session->setFlash('error', 'Ocurrió un error inesperado: ' . $e->getMessage() . " on line: " . $e->getLine());
            }
        }

        $delegates = [];
        if (!$isAdmin) {
            $customer_id = Yii::$app->user->identity->getRealCustomerId();
            if ($customer_id) {
                $customer = \app\models\Customers::findOne($customer_id);
                if ($customer && $customer->user_id) {
                    $delegates = User::find()
                        ->where([
                            'or',
                            ['id' => $customer->user_id],
                            ['parent_id' => $customer->user_id]
                        ])
                        ->andWhere(['status' => User::STATUS_ACTIVE])
                        ->all();
                }
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
            'delegates' => $delegates,
        ]);
    }

    /**
     * Función auxiliar para enviar las notificaciones
     */
    protected function sendNewTicketEmails($ticket, $messageContent, $user)
    {
        try {
            $adminEmail = Yii::$app->params['adminEmail'];

            $email = Yii::$app->mailer->compose(
                ['html' => 'newTicket-html'],
                ['ticket' => $ticket, 'message' => $messageContent]
            )
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setTo($ticket->email)
                ->setReplyTo(Yii::$app->params['departmentEmails'][$ticket->department] ?? (Yii::$app->params['senderEmail'] ?? 'soporte@atsys.co'))
                ->setSubject('[#' . $ticket->ticket_code . '] ' . $ticket->subject);

            // CC mentioned delegates
            if (!empty($ticket->cc_emails)) {
                $ccList = array_filter(array_map('trim', explode(',', $ticket->cc_emails)));
                if (!empty($ccList)) {
                    $email->setCc($ccList);
                }
            }

            $email->send();

            Yii::$app->mailer->compose(
                ['html' => 'adminNewTicket-html'],
                ['ticket' => $ticket, 'message' => $messageContent, 'user' => $user]
            )
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setTo($adminEmail)
                ->setSubject('Nuevo Ticket [' . $ticket->ticket_code . '] - ' . $ticket->subject)
                ->send();
        } catch (\Throwable $e) {
            Yii::error("Fallo al enviar correo de nuevo ticket #{$ticket->ticket_code}: " . $e->getMessage());
        }
    }

    /**
     * Cerrar Ticket (Cambia estado a Cerrado)
     */
    public function actionClose($id)
    {
        $model = $this->findModel($id);
        $isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;

        if (!$isAdmin) {
            $myCustomerId = Yii::$app->user->identity->realCustomerId;

            if (!$myCustomerId || $model->customer_id !== $myCustomerId) {
                throw new \yii\web\ForbiddenHttpException('No tienes permiso para gestionar este ticket.');
            }
        }

        $model->status = Tickets::STATUS_CLOSED;

        // Solo el administrador puede bloquear respuestas al cerrar
        if ($isAdmin && $model->hasAttribute('is_locked')) {
            $lock = Yii::$app->request->post('lock', Yii::$app->request->get('lock', 0));
            if ($lock) {
                $model->is_locked = 1;
            }
        }

        if ($model->save(false)) {
            // Enviar correo de cierre con enlace a la encuesta de satisfacción al cliente
            if (!empty($model->email)) {
                try {
                    Yii::$app->mailer->compose('ticket_closed-html', ['ticket' => $model])
                        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                        ->setReplyTo(Yii::$app->params['departmentEmails'][$model->department] ?? (Yii::$app->params['senderEmail'] ?? 'soporte@atsys.co'))
                        ->setTo($model->email)
                        ->setSubject('Ticket Cerrado [' . $model->ticket_code . '] - ¿Cómo fue tu experiencia?')
                        ->send();
                } catch (\Throwable $e) {
                    Yii::error("Fallo al enviar correo de cierre de ticket #{$model->ticket_code}: " . $e->getMessage());
                }
            }

            $msg = ($isAdmin && $model->isLocked())
                ? 'El ticket ha sido cerrado y las respuestas han sido bloqueadas.'
                : 'El ticket ha sido cerrado.';
            Yii::$app->session->setFlash('info', $msg);
        } else {
            Yii::$app->session->setFlash('error', 'No se pudo cerrar el ticket.');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    /**
     * Alterna el estado de bloqueo de respuestas (solo admin)
     */
    public function actionToggleLock($id)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException('No tienes permiso para realizar esta acción.');
        }

        $model = $this->findModel($id);

        if (!$model->hasAttribute('is_locked')) {
            Yii::$app->session->setFlash('error', 'La base de datos aún no contiene el campo para bloquear respuestas. Por favor ejecuta las migraciones.');
            return $this->redirect(Yii::$app->request->referrer ?: ['view', 'id' => $id]);
        }

        $model->is_locked = $model->isLocked() ? 0 : 1;

        if ($model->save(false)) {
            $msg = $model->isLocked()
                ? 'Se han bloqueado las respuestas para este ticket.'
                : 'Se han desbloqueado las respuestas para este ticket.';
            Yii::$app->session->setFlash('info', $msg);
        } else {
            Yii::$app->session->setFlash('error', 'No se pudo actualizar el estado de bloqueo.');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['view', 'id' => $id]);
    }

    /**
     * Cambia el estado del ticket a "En Progreso"
     */
    public function actionInProgress($id)
    {
        // Solo administradores o personal de soporte deberían poder hacer esto
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException('No tienes permiso para realizar esta acción.');
        }

        $model = $this->findModel($id);
        $model->status = 'in_progress';

        if ($model->save(false)) { // save(false) para saltar validaciones de otros campos si no son necesarias
            // Notificación en plataforma para el Cliente
            $list = $model::getDepartmentListShort();
            $deptName = $list[$model->department] ?? 'Soporte';

            Notifications::notifyCustomer(
                $model->customer_id,
                "⚙️ Ticket en Proceso: " . $model->ticket_code,
                "El departamento de {$deptName} ha marcado tu ticket en proceso: " . $model->subject,
                "/tickets/view?id=" . $model->id,
                Notifications::TYPE_INFO
            );

            Yii::$app->session->setFlash('success', 'El ticket ahora está marcado como En Progreso.');
        } else {
            Yii::$app->session->setFlash('error', 'Hubo un error al actualizar el estado.');
        }

        return $this->redirect(['view', 'id' => $model->id]);
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

    /**
     * Bloquea al remitente del ticket agregándolo a la lista negra de SPAM y opcionalmente
     * cierra el ticket, bloquea respuestas y desactiva su cuenta de usuario si está registrado.
     * Solo para administradores.
     * @param int|string $id ID o ticket_code del ticket
     * @return \yii\web\Response
     */
    public function actionBlockSender($id)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException('No tienes permiso para realizar esta acción.');
        }

        $ticket = $this->findModel($id);
        $email = strtolower(trim((string)$ticket->email));

        if (empty($email)) {
            Yii::$app->session->setFlash('error', 'El ticket no cuenta con un correo electrónico válido para bloquear.');
            return $this->redirect(Yii::$app->request->referrer ?: ['view', 'id' => $id]);
        }

        $reason = trim((string)$this->request->post('reason', ''));
        if (empty($reason)) {
            $reason = 'Bloqueado desde ticket #' . $ticket->ticket_code;
        }

        $lockTicket = (bool)$this->request->post('lock_ticket', 1);
        $deactivateUser = (bool)$this->request->post('deactivate_user', 0);

        // 1. Agregar a TicketSpamBlacklist
        $blacklist = TicketSpamBlacklist::findOne(['email' => $email]);
        if (!$blacklist) {
            $blacklist = new TicketSpamBlacklist();
            $blacklist->email = $email;
            $blacklist->reason = $reason;
            $blacklist->created_at = date('Y-m-d H:i:s');
            if (!$blacklist->save()) {
                Yii::$app->session->setFlash('error', 'No se pudo agregar el correo a la lista negra: ' . implode(', ', $blacklist->getFirstErrors()));
                return $this->redirect(Yii::$app->request->referrer ?: ['view', 'id' => $id]);
            }
        } else {
            // Actualizar motivo si cambió
            $blacklist->reason = $reason;
            $blacklist->save(false);
        }

        // 2. Opcional: Cerrar y bloquear ticket
        if ($lockTicket) {
            $ticket->status = Tickets::STATUS_CLOSED;
            if ($ticket->hasAttribute('is_locked')) {
                $ticket->is_locked = 1;
            }
            $ticket->save(false);
        }

        // 3. Opcional: Desactivar cuenta de usuario si existe y no es el admin actual
        if ($deactivateUser) {
            $user = User::findOne(['email' => $email]);
            if ($user && $user->id !== Yii::$app->user->id) {
                $user->status = User::STATUS_INACTIVE;
                $user->save(false);
            }
        }

        Yii::$app->session->setFlash('success', "El remitente (" . Html::encode($email) . ") ha sido bloqueado con éxito. No podrá crear nuevos tickets ni enviar respuestas.");
        return $this->redirect(Yii::$app->request->referrer ?: ['view', 'id' => $id]);
    }

    /**
     * Desbloquea al remitente del ticket eliminándolo de la lista negra de SPAM.
     * Solo para administradores.
     * @param int|string $id ID o ticket_code del ticket
     * @return \yii\web\Response
     */
    public function actionUnblockSender($id)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException('No tienes permiso para realizar esta acción.');
        }

        $ticket = $this->findModel($id);
        $email = strtolower(trim((string)$ticket->email));

        if (!empty($email)) {
            TicketSpamBlacklist::deleteAll(['email' => $email]);
            Yii::$app->session->setFlash('success', "El remitente (" . Html::encode($email) . ") ha sido desbloqueado de la lista negra.");
        } else {
            Yii::$app->session->setFlash('warning', 'No se encontró un correo electrónico asociado a este ticket.');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['view', 'id' => $id]);
    }

    public function actionBulk()
    {
        // 1. Forzamos respuesta JSON
        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($this->request->isPost) {
            $ids = $this->request->post('ids');
            $action = $this->request->post('action'); // 'close', 'delete' o 'merge'

            if (empty($ids)) {
                return ['success' => false, 'message' => 'No has seleccionado ningún ticket.'];
            }

            if (in_array($action, ['delete', 'merge']) && (!Yii::$app->user->identity || !Yii::$app->user->identity->isAdmin)) {
                return ['success' => false, 'message' => 'No tienes permisos para realizar esta acción.'];
            }

            $count = 0;

            if ($action === 'merge') {
                $targetId = $this->request->post('target_id');
                if (empty($targetId)) {
                    return ['success' => false, 'message' => 'Debes seleccionar el ticket destino para fusionar.'];
                }

                $targetTicket = Tickets::findOne($targetId);
                if (!$targetTicket) {
                    $targetTicket = Tickets::findOne(['ticket_code' => trim($targetId)]);
                }

                if (!$targetTicket) {
                    return ['success' => false, 'message' => 'El ticket destino no existe.'];
                }

                $adminUser = Yii::$app->user->identity;
                $errors = [];

                foreach ($ids as $id) {
                    if ($id == $targetTicket->id) {
                        continue;
                    }
                    $sourceModel = $this->findModel($id);
                    if ($sourceModel) {
                        try {
                            if ($sourceModel->mergeInto($targetTicket, $adminUser)) {
                                $count++;
                            }
                        } catch (\Exception $e) {
                            $errors[] = $e->getMessage();
                        }
                    }
                }

                $message = "Se fusionaron $count tickets dentro del ticket #" . $targetTicket->ticket_code . " correctamente.";
                Yii::$app->session->setFlash('success', $message);
                return [
                    'success' => true,
                    'message' => $message,
                    'count' => $count
                ];
            }

            foreach ($ids as $id) {
                $model = $this->findModel($id);

                if ($model) {
                    if ($action === 'close' && $model->status !== 'closed') {
                        $model->status = 'closed';
                        if ($model->save())
                            $count++;
                    } elseif ($action === 'delete') {
                        if ($model->delete())
                            $count++;
                    }
                }
            }

            $message = $action === 'delete'
                ? "Se eliminaron $count tickets correctamente."
                : "Se cerraron $count tickets correctamente.";

            // Guardamos el mensaje en sesión para que se vea al recargar
            Yii::$app->session->setFlash('success', $message);

            // 2. Retornamos JSON en lugar de redirect
            return [
                'success' => true,
                'message' => $message,
                'count' => $count
            ];
        }

        return ['success' => false, 'message' => 'Petición inválida.'];
    }

    /**
     * Fusionar uno o varios tickets dentro de un ticket destino (Solo Admin)
     */
    public function actionMerge()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$this->request->isPost) {
            return ['success' => false, 'message' => 'Método no permitido.'];
        }

        $targetId = $this->request->post('target_id');
        $sourceIds = $this->request->post('source_ids', []);

        if (empty($targetId) || empty($sourceIds)) {
            return ['success' => false, 'message' => 'Debes especificar el ticket destino y al menos un ticket origen.'];
        }

        if (!is_array($sourceIds)) {
            $sourceIds = [$sourceIds];
        }

        $targetTicket = Tickets::findOne($targetId);
        if (!$targetTicket) {
            $targetTicket = Tickets::findOne(['ticket_code' => trim($targetId)]);
        }

        if (!$targetTicket) {
            return ['success' => false, 'message' => 'No se encontró el ticket destino especificado.'];
        }

        $adminUser = Yii::$app->user->identity;
        $mergedCount = 0;
        $errors = [];

        foreach ($sourceIds as $srcId) {
            if ($srcId == $targetTicket->id) {
                continue;
            }

            $sourceTicket = Tickets::findOne($srcId);
            if (!$sourceTicket) {
                $sourceTicket = Tickets::findOne(['ticket_code' => trim($srcId)]);
            }

            if ($sourceTicket) {
                try {
                    if ($sourceTicket->mergeInto($targetTicket, $adminUser)) {
                        $mergedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error al fusionar #" . $sourceTicket->ticket_code . ": " . $e->getMessage();
                }
            }
        }

        if ($mergedCount > 0) {
            $msg = "Se fusionaron {$mergedCount} ticket(s) dentro del ticket #" . $targetTicket->ticket_code . " correctamente.";
            Yii::$app->session->setFlash('success', $msg);
            return [
                'success' => true,
                'message' => $msg,
                'target_id' => $targetTicket->id,
                'merged_count' => $mergedCount,
                'errors' => $errors,
            ];
        } else {
            $msg = !empty($errors) ? implode(' ', $errors) : 'No se pudo fusionar ningún ticket.';
            return ['success' => false, 'message' => $msg];
        }
    }

    public function actionUploadImage()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $file = \yii\web\UploadedFile::getInstanceByName('file');

        if (!$file) {
            return ['error' => 'No se recibió ningún archivo.'];
        }

        // Validación de tamaño (Ejemplo: 2MB)
        if ($file->size > 2 * 1024 * 1024) {
            return ['error' => 'La imagen es muy pesada (Máximo 2MB).'];
        }

        // Validación de tipo de archivo
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array(strtolower($file->extension), $allowedExtensions)) {
            return ['error' => 'Solo se permiten imágenes JPG, PNG o WebP.'];
        }

        $folder = 'uploads/tickets/content/';
        $path = Yii::getAlias('@webroot/') . $folder;

        if (!is_dir($path)) {
            \yii\helpers\FileHelper::createDirectory($path);
        }

        $fileName = uniqid('img_') . '.' . $file->extension;

        if ($file->saveAs($path . $fileName)) {
            return [
                'location' => 'https://clientarea.atsys.co/' . $folder . $fileName
            ];
        }

        return ['error' => 'Error interno al guardar el archivo.'];
    }

    public function actionBadgeCount()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $ticketBadgeCount = 0;
        if (!Yii::$app->user->isGuest) {
            if (Yii::$app->user->identity->isAdmin) {
                $ticketBadgeCount = (int) \app\models\Tickets::find()
                    ->where(['in', 'status', ['open', 'customer_reply']])
                    ->count();
            } else {
                $realCustomerId = Yii::$app->user->identity->getRealCustomerId() ?? -1;
                $ticketBadgeCount = (int) \app\models\Tickets::find()
                    ->where(['customer_id' => $realCustomerId])
                    ->andWhere(['status' => 'answered'])
                    ->count();
            }
        }

        return [
            'success' => true,
            'count' => $ticketBadgeCount,
        ];
    }

}
