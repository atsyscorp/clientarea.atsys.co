<?php

namespace app\controllers;

use Yii;
use app\models\WorkOrders;
use app\models\WorkOrdersSearch; // Crea este search model igual que hiciste con customers
use app\models\WorkOrderUpdates;
use app\models\Notifications;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use kartik\mpdf\Pdf;

class WorkOrdersController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Solo logueados
                        'matchCallback' => function ($rule, $action) {
                            return !Yii::$app->user->isGuest && (Yii::$app->user->identity->isAdmin || Yii::$app->user->identity->role == 12 || Yii::$app->user->identity->role == 10);
                        }
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
        if (!$isAdmin) {
            $user = Yii::$app->user->identity;
            if ($user->role === \app\models\User::ROLE_CLIENT && !$user->customer) {
                return $this->redirect('/customers/create');
            }
        }

        $searchModel = new \app\models\WorkOrdersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    // Vista detallada (El "Documento")
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;

        if (!$user->isAdmin) {
            $realCustomerId = $user->getRealCustomerId();
            if (!$realCustomerId || $model->customer_id != $realCustomerId) {
                throw new \yii\web\ForbiddenHttpException('No tienes permiso para ver este documento.');
            }
        }

        return $this->render('view', ['model' => $model]);
    }

    // Acciones del Cliente: Aprobar
    public function actionApprove($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;

        if (!$user->isAdmin) {
            $realCustomerId = $user->getRealCustomerId();
            if (!$realCustomerId || $model->customer_id != $realCustomerId) {
                throw new \yii\web\ForbiddenHttpException('No tienes permiso para realizar esta acción.');
            }
        }

        if ($model->status == WorkOrders::STATUS_PENDING) {
            $model->status = WorkOrders::STATUS_APPROVED;
            if ($model->save(false)) {

                // 1. Notificación interna en la plataforma al cliente
                Notifications::notifyCustomer(
                    $model->customer_id,
                    "✅ Orden Aprobada: " . $model->code,
                    "Has aprobado la orden de trabajo: " . $model->title . ". El equipo de ATSYS dará inicio al desarrollo.",
                    "/work-orders/view?id=" . $model->id,
                    Notifications::TYPE_SUCCESS
                );

                // 2. Si existe el campo de una solicitud previa, se elimina
                if (Yii::$app->request->post('previousOrder')) {
                    WorkOrders::deleteAll([
                        'id' => Yii::$app->request->post('previousOrder')
                    ]);
                }

                $customerName = !empty($model->customer->business_name)
                    ? $model->customer->business_name
                    : ($model->customer->name ?? 'Cliente');

                // 3. Notificación por email al Admin
                try {
                    $adminHtmlContent = "
                        <p>El cliente <strong>{$customerName}</strong> ha aprobado la siguiente orden de trabajo:</p>
                        <ul>
                            <li><strong>Código:</strong> {$model->code}</li>
                            <li><strong>Proyecto:</strong> {$model->title}</li>
                            <li><strong>Monto:</strong> " . Yii::$app->formatter->asCurrency($model->total_cost) . "</li>
                        </ul>
                        <p><strong>Acción sugerida:</strong> Verificar pago o iniciar desarrollo.</p>
                    ";

                    Yii::$app->mailer->compose(['html' => 'admin-notification'], [
                        'title' => '✅ Orden Aprobada',
                        'content' => $adminHtmlContent,
                        'color' => '#10b981' // Verde Éxito
                    ])
                        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                        ->setTo(Yii::$app->params['adminEmail'])
                        ->setSubject("✅ APROBADA: Orden " . $model->code . " - " . $customerName)
                        ->send();
                } catch (\Throwable $e) {
                    Yii::error("Error enviando correo de aprobación al Admin: " . $e->getMessage());
                }

                // 4. Notificación por email de confirmación al Cliente (con PDF)
                if ($model->customer && !empty($model->customer->email)) {
                    try {
                        $pdfContent = null;
                        try {
                            $pdf = $this->createPdfObject($model, Pdf::DEST_STRING);
                            $pdfContent = $pdf->render();
                        } catch (\Throwable $pe) {
                            Yii::error("Error generando PDF para confirmación al cliente: " . $pe->getMessage());
                        }

                        $clientHtmlContent = "
                            <p>Hola <strong>{$customerName}</strong>,</p>
                            <p>Gracias por aprobar la orden de trabajo <strong>{$model->code}</strong> - <strong>{$model->title}</strong>.</p>
                            <p>Hemos registrado tu aprobación exitosamente. El equipo de ATSYS dará inicio a las actividades programadas.</p>
                            <p>Adjunto encontrarás el comprobante en PDF con el detalle de la orden aprobada.</p>
                        ";

                        $mail = Yii::$app->mailer->compose(['html' => 'admin-notification'], [
                            'title' => '✅ Confirmación de Aprobación - Orden ' . $model->code,
                            'content' => $clientHtmlContent,
                            'color' => '#10b981'
                        ])
                            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                            ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                            ->setTo($model->customer->email)
                            ->setSubject("✅ Confirmación de Aprobación: Orden " . $model->code . " - " . $model->title);

                        if ($pdfContent) {
                            $mail->attachContent($pdfContent, [
                                'fileName' => $model->code . '-aprobada.pdf',
                                'contentType' => 'application/pdf'
                            ]);
                        }

                        $mail->send();
                    } catch (\Throwable $e) {
                        Yii::error("Error enviando correo de aprobación al Cliente: " . $e->getMessage());
                    }
                }

                Yii::$app->session->setFlash('success', 'Has aprobado la orden de trabajo. ¡Comenzaremos pronto!');
            }
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    // Acciones del Cliente: Rechazar
    public function actionReject($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;

        // SEGURIDAD CORREGIDA
        if (!$user->isAdmin) {
            $realCustomerId = $user->getRealCustomerId();
            if (!$realCustomerId || $model->customer_id != $realCustomerId) {
                throw new \yii\web\ForbiddenHttpException('No tienes permiso para realizar esta acción.');
            }
        }

        if ($model->status == WorkOrders::STATUS_PENDING) {
            $model->status = WorkOrders::STATUS_REJECTED;
            if ($model->save(false)) {

                // 3. NOTIFICACIÓN AL ADMIN (hola@atsys.co)
                try {
                    $htmlContent = "
                        <p>El cliente <strong>{$model->customer->business_name}</strong> ha decidido <strong>NO aprobar</strong> la orden:</p>
                        <ul>
                            <li><strong>Código:</strong> {$model->code}</li>
                            <li><strong>Proyecto:</strong> {$model->title}</li>
                        </ul>
                        <p>Se recomienda contactar al cliente para revisar objeciones.</p>
                    ";

                    Yii::$app->mailer->compose(['html' => 'admin-notification'], [
                        'title' => '❌ Orden Rechazada',
                        'content' => $htmlContent,
                        'color' => '#ef4444' // Rojo Error
                    ])
                        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                        ->setTo(Yii::$app->params['adminEmail'])
                        ->setSubject("❌ RECHAZADA: Orden " . $model->code . " - " . $model->customer->business_name)
                        ->send();
                } catch (\Exception $e) {
                    Yii::error("Error enviando notificación de rechazo: " . $e->getMessage());
                }

                Yii::$app->session->setFlash('error', 'Has rechazado la orden. Nos pondremos en contacto para revisar los detalles.');
            }
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    public function actionRejectRequest($id)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException();
        }

        $model = $this->findModel($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Solicitud eliminada con éxito.');
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = WorkOrders::findOne(['id' => $id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('La página solicitada no existe.');
    }

    private function getPdfStyles()
    {
        // Define aquí tus colores
        $color_primary = '#134C42';
        $color_text = '#333333';
        $color_gray = '#666666';

        return "
            body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: {$color_text}; line-height: 1.4; }
            .header-table { width: 100%; border-bottom: 2px solid {$color_primary}; padding-bottom: 15px; margin-bottom: 20px; }
            .company-name { font-size: 24px; font-weight: bold; color: {$color_primary}; text-transform: uppercase; margin: 0; }
            .company-slogan { font-size: 10px; color: {$color_gray}; letter-spacing: 3px; text-transform: uppercase; }
            .doc-title { font-size: 18px; font-weight: bold; text-align: right; text-transform: uppercase; }
            .doc-meta { text-align: right; font-size: 11px; color: {$color_gray}; }
            .info-table { width: 100%; margin-bottom: 30px; border-collapse: collapse; }
            .info-cell { width: 48%; vertical-align: top; }
            .spacer-cell { width: 4%; }
            .box { background-color: #f8f9fa; border: 1px solid #e9ecef; padding: 12px; border-radius: 4px; }
            .box-title { font-size: 10px; font-weight: bold; color: {$color_primary}; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 8px; }
            .box-content { font-size: 11px; }
            .box-row { margin-bottom: 4px; }
            .box-label { font-weight: bold; color: {$color_gray}; font-size: 9px; }
            .section-header { font-size: 14px; font-weight: bold; background-color: {$color_primary}; color: #fff; padding: 6px 10px; margin-top: 20px; margin-bottom: 15px; border-radius: 3px; }
            .project-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
            .requirements-text { text-align: justify; white-space: pre-wrap; font-size: 12px; }
            .notes-box { margin-top: 20px; border-left: 3px solid #ffc107; padding: 10px; background-color: #fffbf0; font-size: 11px; font-style: italic; color: #555; }
            .total-table { width: 100%; margin-top: 40px; border-top: 1px solid #ddd; padding-top: 10px; }
            .total-label { text-align: right; font-size: 14px; font-weight: bold; color: {$color_gray}; }
            .total-amount { text-align: right; font-size: 22px; font-weight: bold; color: {$color_primary}; }
            .footer { position: fixed; bottom: 0px; left: 0px; right: 0px; height: 30px; border-top: 1px solid #eee; text-align: center; font-size: 9px; color: #aaa; padding-top: 10px; }
            .stamp-approved { display: inline-block; border: 3px solid #10b981; color: #10b981; padding: 10px 20px; font-weight: bold; font-size: 16px; border-radius: 8px; transform: rotate(-3deg); opacity: 0.8; }
            .kv-heading-1{font-size:18px}
            
            table { width: 100% !important; max-width: 100%; border:0 none; border-collapse: collapse; page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            td, th { border: 0 none; padding: 5px; word-wrap: break-word; }
            p { margin-top: 0; margin-bottom: 10px; }
        ";
    }

    /**
     * Centraliza la creación del objeto PDF para no repetir código
     */
    private function createPdfObject($model, $destination = Pdf::DEST_BROWSER)
    {
        return new Pdf([
            'mode' => Pdf::MODE_UTF8,
            'format' => Pdf::FORMAT_A4,
            'destination' => $destination,
            'content' => $this->renderPartial('_pdf', ['model' => $model]),
            'cssInline' => $this->getPdfStyles(), // Usamos el CSS centralizado
            'options' => ['title' => 'Orden ' . $model->code],
            'methods' => [
                'SetHeader' => ['', 'ATSYS | Orden de Trabajo', ''],
                'SetFooter' => ['{PAGENO}'],
            ]
        ]);
    }

    public function actionPdf($id)
    {
        $model = $this->findModel($id);

        // Validación de seguridad...
        if (!Yii::$app->user->identity->isAdmin) {
            $realCustomerId = Yii::$app->user->identity->getRealCustomerId();
            if (!$realCustomerId || $model->customer_id != $realCustomerId) {
                throw new \yii\web\ForbiddenHttpException('No tienes permiso para ver este documento.');
            }
        }

        // Una sola línea para configurar todo
        $pdf = $this->createPdfObject($model, Pdf::DEST_BROWSER);

        return $pdf->render();
    }

    /**
     * Enviar PDF por Email al Cliente
     */
    public function actionSend($id)
    {
        if (!Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException();
        }

        $model = $this->findModel($id);
        $clientEmail = $model->customer->email;

        // 1. Generar el PDF en memoria (String)
        $pdf = $this->createPdfObject($model, Pdf::DEST_STRING);
        $pdfContent = $pdf->render();

        // 2. Enviar el correo con adjunto
        try {
            Yii::$app->mailer->compose(['html' => 'work_order_notification-html'], ['model' => $model])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                ->setTo($clientEmail)
                ->setSubject("Nueva Orden de Trabajo: " . $model->title)
                ->attachContent($pdfContent, [
                    'fileName' => $model->code . '.pdf',
                    'contentType' => 'application/pdf'
                ])
                ->send();

            // Cambiar estado a pendiente si estaba en borrador
            if ($model->status == \app\models\WorkOrders::STATUS_DRAFT) {
                $model->status = \app\models\WorkOrders::STATUS_PENDING;
                $model->save(false);
            }

            Yii::$app->session->setFlash('success', 'La orden ha sido enviada por correo correctamente.');

        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Error al enviar: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Método privado para reutilizar la lógica pesada de PDF y Correo
     * en cualquier parte del controlador sin duplicar código.
     */
    private function pdfAndEmailOrder($model)
    {
        try {
            // 1. Generar PDF en memoria (String)
            $pdf = $this->createPdfObject($model, Pdf::DEST_STRING);
            $pdfContent = $pdf->render();

            // 2. Enviar Correo
            Yii::$app->mailer->compose(['html' => 'work_order_notification-html'], ['model' => $model])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                ->setTo($model->customer->email)
                ->setSubject("Nueva Orden de Trabajo: " . $model->title)
                ->attachContent($pdfContent, [
                    'fileName' => $model->code . '.pdf',
                    'contentType' => 'application/pdf'
                ])
                ->send();

            return true;
        } catch (\Throwable $e) {
            Yii::error('Error enviando correo de orden: ' . $e->getMessage());
            return $e->getMessage(); // Retornamos el error para mostrarlo en el flash
        }
    }

    public function actionCreate()
    {
        // Solo Admin puede crear
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException();
        }

        $model = new WorkOrders();
        // Por defecto, si se envía email, nace como PENDIENTE (1)
        $model->status = WorkOrders::STATUS_PENDING;

        if ($contractId = Yii::$app->request->get('contract_id')) {
            $model->contract_id = $contractId;
            $contract = \app\models\Contracts::findOne($contractId);
            if ($contract) {
                $model->customer_id = $contract->customer_id;
                $model->has_service_contract = 1;
            }
        }
        if ($customerId = Yii::$app->request->get('customer_id')) {
            $model->customer_id = $customerId;
        }
        if ($projectId = Yii::$app->request->get('project_id')) {
            $model->project_id = $projectId;
            $proj = \app\models\Projects::findOne($projectId);
            if ($proj) {
                $model->customer_id = $proj->customer_id;
            }
        }
        if ($model->customer_id && !$model->project_id) {
            $defProj = \app\models\Projects::findOne(['customer_id' => $model->customer_id, 'is_default' => 1])
                    ?: \app\models\Projects::findOne(['customer_id' => $model->customer_id]);
            if ($defProj) {
                $model->project_id = $defProj->id;
            }
        }


        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {

                // Notificación en plataforma
                Notifications::notifyCustomer(
                    $model->customer_id,
                    "🛠️ Nueva Orden de Trabajo: " . $model->code,
                    "Se ha generado una nueva orden de trabajo para tu proyecto: " . $model->title,
                    "/work-orders/view?id=" . $model->id,
                    Notifications::TYPE_INFO
                );

                if ($model->is_preapproved == 1) {
                    $model->status = WorkOrders::STATUS_APPROVED;
                    $model->save(false);
                    Yii::$app->session->setFlash('success', 'Orden creada y pre-aprobada exitosamente (sin envío de email de confirmación).');
                } else {
                    $send = $this->pdfAndEmailOrder($model);
                    if ($send === true) {
                        Yii::$app->session->setFlash('success', 'Orden creada y enviada al cliente exitosamente.');
                    } else {
                        Yii::$app->session->setFlash('warning', 'La orden se guardó, pero hubo un error enviando el email: ' . $send);
                    }
                }

                return $this->redirect(['view', 'id' => $model->id]);

            } else {
                Yii::$app->session->setFlash('error', 'Error: ' . json_encode($model->getErrors()));
            }
        }

        return $this->render('create', [
            'model' => $model,
            // Enviamos la lista de clientes para el dropdown
            'customers' => \app\models\Customers::find()->orderBy('business_name')->all(),
        ]);
    }

    public function actionCreateFromTicket($ticket_id)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException('No tienes permiso para realizar esta acción.');
        }

        $ticket = \app\models\Tickets::findOne($ticket_id);
        if (!$ticket) {
            throw new \yii\web\NotFoundHttpException('El ticket seleccionado no existe.');
        }

        if (!$ticket->customer_id) {
            Yii::$app->session->setFlash('error', 'El ticket debe tener un cliente asignado para poder generar una orden de trabajo.');
            return $this->redirect(['tickets/view', 'id' => $ticket->id]);
        }

        // Build requirements from ticket messages
        $requirements = "<h3>Requerimiento original (Ticket #{$ticket->ticket_code})</h3>";
        $requirements .= "<div><strong>Asunto:</strong> " . \yii\helpers\Html::encode($ticket->subject) . "</div>";
        $requirements .= "<div><strong>Creado:</strong> " . Yii::$app->formatter->asDatetime($ticket->created_at, 'medium') . "</div>";
        $requirements .= "<hr style='border: 0; border-top: 1px solid #ddd; margin: 15px 0;' />";

        $replies = $ticket->getTicketReplies()->orderBy('created_at ASC')->all();
        if (empty($replies)) {
            $requirements .= "<p>No hay mensajes registrados en el ticket.</p>";
        } else {
            foreach ($replies as $reply) {
                $senderName = $reply->isSenderTypeAdmin() 
                    ? 'Soporte ATSYS' 
                    : ($reply->user ? \yii\helpers\Html::encode($reply->user->contact_name) : 'Cliente');
                
                $date = Yii::$app->formatter->asDatetime($reply->created_at, 'medium');
                
                $requirements .= "<div style='margin-bottom: 20px; padding: 12px; background-color: #fcfcfc; border: 1px solid #e3e3e3; border-left: 4px solid #134C42; border-radius: 4px;'>";
                $requirements .= "  <div style='font-size: 0.9em; margin-bottom: 8px;'>";
                $requirements .= "    <strong>{$senderName}</strong> <span style='color: #666; margin-left: 10px;'>{$date}</span>";
                $requirements .= "  </div>";
                $requirements .= "  <div class='message-content'>" . $reply->message . "</div>";
                $requirements .= "</div>";
            }
        }

        $model = new WorkOrders();
        $model->ticket_id = $ticket->id;
        $model->customer_id = $ticket->customer_id;
        $model->title = 'Ticket #' . $ticket->ticket_code . ': ' . $ticket->subject;
        $model->requirements = $requirements;
        $model->status = WorkOrders::STATUS_DRAFT; // Nace como borrador
        $model->currency = 'COP'; // COP por defecto
        
        // Deshabilitar temporalmente la validación del total_cost, TRM, etc. al crear
        if ($model->save(false)) {
            Yii::$app->session->setFlash('success', 'Orden de trabajo borrador generada exitosamente. Por favor, completa la información comercial.');
            return $this->redirect(['update', 'id' => $model->id]);
        } else {
            Yii::$app->session->setFlash('error', 'Error al guardar la orden de trabajo: ' . json_encode($model->getErrors()));
            return $this->redirect(['tickets/view', 'id' => $ticket->id]);
        }
    }

    public function actionApproveRequest($id)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException();
        }

        $model = $this->findModel($id);

        if ($model->is_request != 1) {
            Yii::$app->session->setFlash('error', 'Esta orden no es una solicitud.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($this->request->isPost) {
            $model->original_request = $model->getOldAttribute('requirements');
            if ($model->load($this->request->post())) {
                $model->is_request = 0;
                $model->status = WorkOrders::STATUS_PENDING;

                $model->code = preg_replace('/^OTR-/', 'OT-', $model->code);

                if ($model->save()) {
                    // Notificación en plataforma
                    Notifications::notifyCustomer(
                        $model->customer_id,
                        "🛠️ Orden de Trabajo Aprobada: " . $model->code,
                        "Tu solicitud ha sido aprobada y se ha convertido en la orden: " . $model->title,
                        "/work-orders/view?id=" . $model->id,
                        Notifications::TYPE_SUCCESS
                    );

                    $send = $this->pdfAndEmailOrder($model);

                    if ($send === true) {
                        Yii::$app->session->setFlash('success', 'Solicitud aprobada. El código cambió a ' . $model->code . ' y fue enviada al cliente.');
                    } else {
                        Yii::$app->session->setFlash('warning', 'Solicitud aprobada, pero falló el envío del email: ' . $send);
                    }

                    return $this->redirect(['view', 'id' => $model->id]);
                } else {
                    Yii::$app->session->setFlash('error', 'Error validando los requerimientos: ' . json_encode($model->getErrors()));
                }
            }
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    public function actionUpdate($id)
    {
        // Solo Admin puede actualizar
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException();
        }

        $model = $this->findModel($id);

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                $ticketAction = Yii::$app->request->post('WorkOrders')['ticket_action'] ?? null;
                
                // Si la orden viene de un ticket y está en Borrador, permitimos cambiar el estado
                if ($model->status == WorkOrders::STATUS_DRAFT && $model->ticket_id && $ticketAction) {
                    if ($ticketAction === 'preapprove') {
                        $model->status = WorkOrders::STATUS_APPROVED;
                    } elseif ($ticketAction === 'send') {
                        $model->status = WorkOrders::STATUS_PENDING;
                    }
                }

                if ($model->save()) {
                    if ($ticketAction === 'send') {
                        $send = $this->pdfAndEmailOrder($model);
                        if ($send === true) {
                            Yii::$app->session->setFlash('success', 'Orden actualizada y enviada al cliente exitosamente.');
                        } else {
                            Yii::$app->session->setFlash('warning', 'La orden se actualizó, pero hubo un error enviando el email: ' . $send);
                        }
                    } else {
                        Yii::$app->session->setFlash('success', 'Orden actualizada exitosamente.');
                    }
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
            'customers' => \app\models\Customers::find()->orderBy('business_name')->all(),
        ]);
    }

    public function actionRequest()
    {

        // Solo para clientes
        if (Yii::$app->user->isGuest || Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException();
        }

        $model = new WorkOrders();

        if (!Yii::$app->user->identity->isAdmin) {
            $customer_id = Yii::$app->user->identity->getRealCustomerId();

            if ($customer_id) {
                $model->customer_id = $customer_id;
            } else {
                Yii::$app->session->setFlash('error', 'Tu usuario no tiene un perfil de cliente asociado.');
                return $this->redirect(['index']);
            }
        }

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                $file = \yii\web\UploadedFile::getInstance($model, 'attachmentFile');
                if ($file) {
                    $uploadUrl = Yii::$app->googleDrive->upload($file);
                    if ($uploadUrl) {
                        $model->attachment_url = $uploadUrl;
                    }
                }
                if ($model->request()) {
                    // Notificación en plataforma para Admins
                    Notifications::notifyAdmins(
                        "🛠️ Nueva Solicitud de Orden: " . $model->code,
                        "El cliente " . $model->customer->business_name . " ha solicitado una nueva orden de trabajo: " . $model->title,
                        "/work-orders/view?id=" . $model->id,
                        Notifications::TYPE_INFO
                    );

                    Yii::$app->session->setFlash('success', 'Orden solicitada exitosamente, pronto recibirás un correo con el detalle propuesto para que lo revises.');
                    return $this->redirect(['index']);
                }
            }
        }

        return $this->render('request', [
            'model' => $model,
        ]);
    }

    /**
     * Eliminar Orden de Trabajo (Solo Admin, protegido por behaviors)
     */
    public function actionDelete($id)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException();
        }

        $this->findModel($id)->delete();

        Yii::$app->session->setFlash('success', 'Orden de trabajo eliminada correctamente.');
        return $this->redirect(['index']);
    }

    // Acción para agregar avance
    public function actionAddUpdate($id)
    {
        if (!Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException();
        }

        $workOrder = $this->findModel($id);
        $update = new WorkOrderUpdates();

        if ($this->request->isPost) {
            $update->load($this->request->post());
            $update->work_order_id = $workOrder->id;
            $update->created_by = Yii::$app->user->id;

            // Capturar y subir archivo del admin a Google Drive
            $file = \yii\web\UploadedFile::getInstance($update, 'attachmentFile');
            if ($file) {
                $uploadUrl = Yii::$app->googleDrive->upload($file, $workOrder->code);
                if ($uploadUrl) {
                    $update->attachment_url = $uploadUrl;
                }
            }

            if ($update->save()) {

                // Notificación en plataforma para el Cliente
                Notifications::notifyCustomer(
                    $workOrder->customer_id,
                    "🚀 Nuevo avance en Orden: " . $workOrder->code,
                    "Se ha registrado un nuevo avance en tu orden de trabajo: " . mb_substr(strip_tags($update->description), 0, 80, 'UTF-8') . "...",
                    "/work-orders/view?id=" . $workOrder->id,
                    Notifications::TYPE_INFO
                );

                // Lógica opcional de notificación
                if ($update->notify_email) {
                    try {
                        $adminEmail = Yii::$app->params['adminEmail'] ?? 'gerencia@atsys.co';
                        $adminEmails = !empty($adminEmail)
                            ? array_map('trim', explode(',', $adminEmail))
                            : ['gerencia@atsys.co'];

                        $mail = Yii::$app->mailer->compose(['html' => 'admin-notification'], [
                            'title' => '🚀 Nuevo Avance en tu Proyecto',
                            'content' => "<p>Se ha registrado un nuevo avance en la orden <strong>{$workOrder->code}</strong>:</p>
                                          <blockquote style='background:#f9f9f9; padding:10px; border-left:3px solid #134C42;'>
                                            " . nl2br($update->description) . "
                                          </blockquote>
                                          " . (($update->allow_reply == 1) ? '<p>Este avance incluye una solicitud de respuesta de tu parte. Por favor, ingresa a la orden de trabajo para ver los detalles y responder.</p><br><br>' : '') . "
                                          <p><a href='https://clientarea.atsys.co/work-orders/view?id={$workOrder->id}' style='background-color: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Ver en el área de clientes</a></p>",
                            'color' => '#134C42'
                        ])
                            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                            ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                            ->setTo($workOrder->customer->email)
                            ->setBcc($adminEmails)
                            ->setSubject("Avance: " . $workOrder->title);

                        $mail->send();
                    } catch (\Throwable $e) {
                        Yii::error("Error enviando notificación de avance OT: " . $e->getMessage());
                    } // Silencioso
                }

                Yii::$app->session->setFlash('success', 'Avance registrado.');
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Genera una Orden de Pago (Orders) basada en la Orden de Trabajo.
     * Puede ser por el total o un porcentaje (ej: 50% o 100%).
     */
    public function actionGeneratePayment($id, $percentage = null)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException();
        }

        $percentage = Yii::$app->request->post('percentage', Yii::$app->request->get('percentage', $percentage ?? 50));
        $percentage = (int) $percentage;

        $workOrder = $this->findModel($id);

        if ($workOrder->down_payment_sent_at !== null) {
            Yii::$app->session->setFlash('warning', 'El cobro para esta orden ya fue generado y enviado el ' . Yii::$app->formatter->asDatetime($workOrder->down_payment_sent_at));
            return $this->redirect(['view', 'id' => $id]);
        }

        if (!$workOrder->customer_id) {
            Yii::$app->session->setFlash('error', 'Esta orden de trabajo no tiene un cliente asociado.');
            return $this->redirect(['view', 'id' => $id]);
        }

        // 1. Definir los Montos a Cobrar
        $fraction = $percentage / 100;
        $amountToPayCop = $workOrder->total_cost * $fraction;

        // Si la orden está en USD o EUR, calculamos también el valor fraccionado en moneda extranjera
        $isForeign = in_array($workOrder->currency, ['USD', 'EUR']);
        $amountToPayForeign = $isForeign ? ($workOrder->total_cost_usd * $fraction) : null;

        $concept = $percentage == 100 ? "Pago Total - " . $workOrder->code : "Anticipo {$percentage}% - " . $workOrder->code;

        // Variables para la comisión (Inicializadas en 0)
        $feeForeign = 0;
        $feeCop = 0;

        if ($isForeign) {
            $paypalPercentage = 0.054; // 5.4%
            $paypalFixed = 0.30;       // $0.30 (USD/EUR)

            // Aplicamos la fórmula matemática para obtener el Bruto real a cobrar
            $grossForeign = ($amountToPayForeign + $paypalFixed) / (1 - $paypalPercentage);

            // La diferencia es la comisión exacta que le sumaremos como ítem
            $feeForeign = round($grossForeign - $amountToPayForeign, 2);

            // Calculamos su equivalente en COP usando la TRM pactada
            $feeCop = round($feeForeign * $workOrder->exchange_rate, 2);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // A. Crear la Cabecera de la Orden (Factura)
            $order = new \app\models\Orders();
            $order->code = 'OT-' . $workOrder->id . '-' . date('His');
            $order->customer_id = $workOrder->customer_id;

            // Sumamos el servicio + la comisión al total de la factura
            $order->subtotal = $amountToPayCop + $feeCop;
            $order->total = $amountToPayCop + $feeCop;

            $order->currency = $workOrder->currency ?? 'COP';
            if ($isForeign) {
                $order->exchange_rate = $workOrder->exchange_rate;
                $order->total_usd = $amountToPayForeign + $feeForeign;
            }

            $order->status = 0;
            $order->created_at = date('Y-m-d H:i:s');

            if (!$order->save())
                throw new \Exception('Error al crear la orden de pago: ' . json_encode($order->getErrors()));

            // B. Crear el Ítem 1: El Anticipo del Proyecto
            $item1 = new \app\models\OrderItems();
            $item1->order_id = $order->id;
            $item1->service_id = 9999;
            $item1->service_name = $concept;
            $item1->unit_price = $amountToPayCop;
            $item1->total = $amountToPayCop;

            // CORRECCIÓN 1: Usamos subtotal_usd según tu BD
            if ($isForeign) {
                $item1->unit_price_usd = $amountToPayForeign;
                $item1->total_usd = $amountToPayForeign;
            }
            $item1->action_type = 'payment';

            if (!$item1->save())
                throw new \Exception('Error al crear ítem de anticipo: ' . json_encode($item1->getErrors()));

            // C. Crear el Ítem 2: Recargo de PayPal (SOLO SI ES USD o EUR)
            if ($isForeign && $feeForeign > 0) {
                $item2 = new \app\models\OrderItems();
                $item2->order_id = $order->id;
                $item2->service_id = 9998; // ID reservado para comisiones
                $item2->service_name = "Recargo Procesamiento Internacional (PayPal)";
                $item2->unit_price = $feeCop;
                $item2->total = $feeCop;

                // CORRECCIÓN 2: Ajustamos a la BD
                $item2->unit_price_usd = $feeForeign;
                $item2->total_usd = $feeForeign;
                $item2->action_type = 'payment'; // Usamos 'payment' porque sí está en tu ENUM

                if (!$item2->save())
                    throw new \Exception('Error al crear ítem de comisión: ' . json_encode($item2->getErrors()));
            }

            // D. ACTUALIZAR LA ORDEN DE TRABAJO
            $workOrder->down_payment_sent_at = date('Y-m-d H:i:s');
            if (!$workOrder->save(false))
                throw new \Exception('Error actualizando estado de OT.');

            $transaction->commit();

            // Notificación en plataforma para el Cliente
            Notifications::notifyCustomer(
                $workOrder->customer_id,
                "💳 Pago Requerido: " . $workOrder->code,
                "Se ha generado una solicitud de cobro anticipado para la orden " . $workOrder->code . ". Total a pagar: " . Yii::$app->formatter->asCurrency($order->total) . " " . $order->currency,
                "/orders/view?id=" . $order->id,
                Notifications::TYPE_WARNING
            );

            // E. ENVIAR EL EMAIL DE COBRO
            $this->sendPaymentRequestEmail($order, $workOrder);

            Yii::$app->session->setFlash('success', 'Orden de pago generada con recargo internacional y enviada al cliente.');
            return $this->redirect(['view', 'id' => $workOrder->id]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Error: ' . $e->getMessage());
            return $this->redirect(['view', 'id' => $id]);
        }
    }

    /**
     * Envía el correo con el botón de pago
     */
    private function sendPaymentRequestEmail($order, $workOrder)
    {
        try {
            $customer = $order->customer;
            $paymentLink = \yii\helpers\Url::to(['orders/view', 'id' => $order->id], true); // Link absoluto

            $isForeign = in_array($workOrder->currency, ['USD', 'EUR']);
            $currencySuffix = ' ' . $workOrder->currency;
            $displayTotal = $isForeign ? ($order->total_usd ?? $order->total) : $order->total;

            $subject = "Pago Requerido - Orden de Trabajo {$workOrder->code}";
            Yii::$app->mailer->compose([
                'html' => 'payment_request-html'
            ], [
                'business_name' => $customer->business_name,
                'work_order_id' => $workOrder->code,
                'order_total' => Yii::$app->formatter->asCurrency($displayTotal) . $currencySuffix,
                'paymentLink' => $paymentLink,
                'payment_method' => ($isForeign) ? 'PayPal' : 'Wompi (Nequi, Tarjetas, PSE)'
            ])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setTo($customer->email)
                ->setBcc(Yii::$app->params['adminEmail'])
                ->setSubject($subject)
                ->send();
        } catch (\Exception $e) {
            Yii::error("Error enviando email de cobro OT: " . $e->getMessage());
        }
    }

    public function actionClose($id)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException();
        }

        $model = $this->findModel($id);

        // Verificamos que sea POST y que la orden esté en estado correcto
        if ($this->request->isPost && $model->status === WorkOrders::STATUS_APPROVED) {

            $model->status = WorkOrders::STATUS_COMPLETED;
            $model->completed_at = date('Y-m-d H:i:s');

            if ($model->save()) {

                // Lógica de Notificación
                if ($this->request->post('notify_client')) {
                    try {
                        $mailer = Yii::$app->mailer->compose(['html' => 'workOrderClosed-html'], ['model' => $model])
                            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                            ->setReplyTo(Yii::$app->params['adminEmail'] ?? 'gerencia@atsys.co')
                            ->setTo($model->customer->email);

                        if (!empty(Yii::$app->params['adminEmail'])) {
                            $mailer->setBcc(Yii::$app->params['adminEmail']);
                        }

                        $mailer->setSubject('¡Trabajo Finalizado! Orden #' . $model->code)
                            ->send();

                        Yii::$app->session->setFlash('success', 'Orden cerrada y notificación enviada.');
                    } catch (\Throwable $e) {
                        Yii::error('Error enviando notificación de cierre OT: ' . $e->getMessage());
                        Yii::$app->session->setFlash('warning', 'Orden cerrada, pero falló el envío de la notificación por correo.');
                    }
                } else {
                    Yii::$app->session->setFlash('success', 'Orden cerrada correctamente (sin notificación).');
                }
            } else {
                Yii::$app->session->setFlash('error', 'No se pudo cerrar la orden.');
            }
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    public function actionAddReply($id)
    {
        $request = Yii::$app->request;

        // 1. Seguridad básica: Solo aceptar peticiones POST
        if (!$request->isPost) {
            throw new \yii\web\BadRequestHttpException('Petición no válida. Solo se aceptan envíos por formulario.');
        }

        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            $realCustomerId = $user->getRealCustomerId();
            $workOrder = $this->findModel($id);
            if (!$realCustomerId || $workOrder->customer_id != $realCustomerId) {
                throw new \yii\web\ForbiddenHttpException('No tienes permiso para realizar esta acción.');
            }
        }

        $updateId = $request->post('update_id');
        $replyText = $request->post('reply');

        if (empty($updateId) || empty(trim($replyText))) {
            Yii::$app->session->setFlash('error', 'La respuesta no puede estar vacía.');
            return $this->redirect(['view', 'id' => $id]);
        }

        // 2. Buscar el avance asegurando que pertenezca a la Orden de Trabajo actual
        $update = \app\models\WorkOrderUpdates::findOne([
            'id' => $updateId,
            'work_order_id' => $id,
            'allow_reply' => 1 // Doble validación: asegurar que este avance permite respuesta
        ]);

        if (!$update) {
            throw new \yii\web\NotFoundHttpException('El registro de avance no existe o no admite respuestas.');
        }

        // 3. LA REGLA DE ORO (Backend): Bloquear si ya existe una respuesta
        if (!empty($update->client_reply)) {
            Yii::$app->session->setFlash('warning', 'Ya has enviado una respuesta para este avance. No se permiten modificaciones adicionales.');
            return $this->redirect(['view', 'id' => $id]);
        }

        // 3.5. Capturar y subir archivo a Google Drive (segmentado por código de OT)
        $file = \yii\web\UploadedFile::getInstanceByName('attachmentFile');
        if ($file) {
            $workOrder = $this->findModel($id);
            $uploadUrl = Yii::$app->googleDrive->upload($file, $workOrder->code);
            if ($uploadUrl) {
                $update->reply_attachment_url = $uploadUrl;
            }
        }

        // 4. Sanitizar y guardar los datos
        // Usamos HtmlPurifier por seguridad, para evitar inyección de scripts si el cliente copia/pega algo extraño
        $update->client_reply = \yii\helpers\HtmlPurifier::process(trim($replyText));
        $update->replied_at = date('Y-m-d H:i:s');
        $update->replied_by = Yii::$app->user->id; // Aquí queda el rastro exacto de quién respondió (Ideal para las subcuentas)

        if ($update->save(false)) { // false evita validaciones extrañas del modelo si no tienes rules strictas configuradas para estos campos

            Yii::$app->session->setFlash('success', 'Tu respuesta ha sido registrada correctamente en la bitácora.');

            // 5. Notificación al equipo de ATSYS
            $workOrder = \app\models\WorkOrders::findOne($id);

            if ($workOrder) {
                // Notificación en plataforma para Admins
                Notifications::notifyAdmins(
                    "💬 Respuesta en Avance de Orden: " . $workOrder->code,
                    "El cliente ha respondido al avance en la orden: " . mb_substr(strip_tags($update->client_reply), 0, 80, 'UTF-8') . "...",
                    "/work-orders/view?id=" . $workOrder->id,
                    Notifications::TYPE_INFO
                );

                try {
                    Yii::$app->mailer->compose(
                        [
                            'html' => 'workOrderReply-html'
                        ],
                        [
                            'model' => $workOrder,
                            'update' => $update,
                        ]
                    )
                        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                        ->setTo(Yii::$app->params['adminEmail'])
                        ->setSubject('Nueva respuesta del cliente - ' . $workOrder->code)
                        ->send();
                } catch (\Exception $e) {
                    // Si falla el correo/webhook, no bloqueamos al cliente, solo registramos el error en los logs
                    Yii::error('Error enviando notificación de respuesta OT: ' . $e->getMessage());
                }
            }

        } else {
            Yii::$app->session->setFlash('error', 'Ocurrió un error interno al guardar la respuesta. Por favor, contacta a soporte.');
        }

        // 6. Redirigir de vuelta a la vista de la orden
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Pausa una orden aprobada por falta de respuesta o interés del cliente.
     * Acepta POST con pause_type (5 o 6), pause_reason y notify_client opcionales.
     */
    public function actionPause($id)
    {
        $model = $this->findModel($id);

        if (!Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException('Solo el administrador puede pausar órdenes.');
        }

        if (Yii::$app->request->isPost && $model->status === WorkOrders::STATUS_APPROVED) {

            $pauseType   = (int) Yii::$app->request->post('pause_type');
            $pauseReason = trim(Yii::$app->request->post('pause_reason', ''));
            $notify      = Yii::$app->request->post('notify_client');

            $allowedTypes = [WorkOrders::STATUS_NOT_COMPLETED, WorkOrders::STATUS_PARTIAL];
            if (!in_array($pauseType, $allowedTypes)) {
                Yii::$app->session->setFlash('error', 'Tipo de pausa inválido.');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $model->status       = $pauseType;
            $model->pause_reason = $pauseReason ?: null;
            $model->completed_at = date('Y-m-d H:i:s');

            if ($model->save(false)) {
                $label = $pauseType === WorkOrders::STATUS_NOT_COMPLETED ? 'No Finalizada' : 'Parcialmente Finalizada';

                // Notificación opcional al cliente
                if ($notify) {
                    try {
                        $customer = $model->customer;
                        Yii::$app->mailer->compose(['html' => 'workOrderPaused-html'], [
                            'model'  => $model,
                            'label'  => $label,
                        ])
                        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                        ->setReplyTo(Yii::$app->params['adminEmail'] ?? 'gerencia@atsys.co')
                        ->setTo($customer->email)
                        ->setSubject("Actualización sobre tu Orden #{$model->code}")
                        ->send();
                    } catch (\Throwable $e) {
                        Yii::error('Error enviando email de pausa OT: ' . $e->getMessage());
                    }
                }

                Yii::$app->session->setFlash('success', "Orden marcada como \"{$label}\" correctamente.");
            } else {
                Yii::$app->session->setFlash('error', 'No se pudo actualizar el estado de la orden.');
            }
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Reactiva una orden pausada (estado 5 o 6) de vuelta a Aprobada.
     */
    public function actionResume($id)
    {
        $model = $this->findModel($id);

        if (!Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException('Solo el administrador puede reactivar órdenes.');
        }

        $pausedStatuses = [WorkOrders::STATUS_NOT_COMPLETED, WorkOrders::STATUS_PARTIAL];

        if (Yii::$app->request->isPost && in_array($model->status, $pausedStatuses)) {
            $model->status       = WorkOrders::STATUS_APPROVED;
            $model->pause_reason = null;
            $model->completed_at = null;

            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Orden reactivada correctamente. Puedes continuar trabajando en ella.');
            } else {
                Yii::$app->session->setFlash('error', 'No se pudo reactivar la orden.');
            }
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

}