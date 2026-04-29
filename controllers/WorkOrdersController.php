<?php

namespace app\controllers;

use Yii;
use app\models\WorkOrders;
use app\models\WorkOrdersSearch; // Crea este search model igual que hiciste con customers
use app\models\WorkOrderUpdates;
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
        if(!$isAdmin) {
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
            $myCustomer = \app\models\Customers::findOne(['user_id' => $user->id]);
            if (!$myCustomer || $model->customer_id != $myCustomer->id) {
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
            $myCustomer = \app\models\Customers::findOne(['user_id' => $user->id]);
            if (!$myCustomer || $model->customer_id != $myCustomer->id) {
                 throw new \yii\web\ForbiddenHttpException();
            }
        }

        if ($model->status == WorkOrders::STATUS_PENDING) {
            $model->status = WorkOrders::STATUS_APPROVED;
            if ($model->save(false)) {
                
                // 3. NOTIFICACIÓN AL ADMIN (hola@atsys.co)
                try {

                    // Si existe el campo de una solicitud, debe eliminarse
                    if(Yii::$app->request->post('previousOrder')) {
                        WorkOrders::deleteAll([
                            'id' => Yii::$app->request->post('previousOrder')
                        ]);
                    }

                    $htmlContent = "
                        <p>El cliente <strong>{$model->customer->business_name}</strong> ha aprobado la siguiente orden:</p>
                        <ul>
                            <li><strong>Código:</strong> {$model->code}</li>
                            <li><strong>Proyecto:</strong> {$model->title}</li>
                            <li><strong>Monto:</strong> " . Yii::$app->formatter->asCurrency($model->total_cost) . "</li>
                        </ul>
                        <p><strong>Acción sugerida:</strong> Verificar pago o iniciar desarrollo.</p>
                    ";

                    Yii::$app->mailer->compose(['html' => 'admin-notification'], [
                        'title' => '✅ Orden Aprobada',
                        'content' => $htmlContent,
                        'color' => '#10b981' // Verde Éxito
                    ])
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                    ->setTo(Yii::$app->params['adminEmail'])
                    ->setSubject("✅ APROBADA: Orden " . $model->code . " - " . $model->customer->business_name)
                    ->send();
                } catch (\Exception $e) {
                    // Si falla el correo, solo lo registramos en logs para no asustar al cliente
                    Yii::error("Error enviando notificación de aprobación: " . $e->getMessage());
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
            $myCustomer = \app\models\Customers::findOne(['user_id' => $user->id]);
            if (!$myCustomer || $model->customer_id != $myCustomer->id) {
                 throw new \yii\web\ForbiddenHttpException();
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

    public function actionRejectRequest($id) {
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
                'SetHeader'=>['','ATSYS | Orden de Trabajo',''], 
                'SetFooter'=>['{PAGENO}'],
            ]
        ]);
    }

    public function actionPdf($id)
    {
        $model = $this->findModel($id);
        
        // Validación de seguridad...
        if (!Yii::$app->user->identity->isAdmin && $model->customer_id != Yii::$app->user->id) {
             throw new \yii\web\ForbiddenHttpException();
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
                ->setTo($clientEmail)
                ->setBcc(Yii::$app->params['adminEmail'])
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
                ->setTo($model->customer->email)
                ->setBcc(Yii::$app->params['adminEmail'])
                ->setSubject("Nueva Orden de Trabajo: " . $model->title)
                ->attachContent($pdfContent, [
                    'fileName' => $model->code . '.pdf', 
                    'contentType' => 'application/pdf'
                ])
                ->send();

            return true;
        } catch (\Exception $e) {
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

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {

                $send = $this->pdfAndEmailOrder($model);
                if ($send === true) {
                    Yii::$app->session->setFlash('success', 'Orden creada y enviada al cliente exitosamente.');
                } else {
                    Yii::$app->session->setFlash('warning', 'La orden se guardó, pero hubo un error enviando el email: ' . $send);
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
            if ($model->load($this->request->post()) && $model->save()) {
                Yii::$app->session->setFlash('success', 'Orden actualizada exitosamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionRequest() {

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
            if ($model->load($this->request->post()) && $model->request()) {
                Yii::$app->session->setFlash('success', 'Orden solicitada exitosamente, pronto recibirás un correo con el detalle propuesto para que lo revises.');
                return $this->redirect(['index']);
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
            
            if ($update->save()) {
                
                // Lógica opcional de notificación
                if ($update->notify_email && $update->allow_reply == 1) {
                    try {
                        Yii::$app->mailer->compose(['html' => 'admin-notification'], [
                            'title' => '🚀 Nuevo Avance en tu Proyecto',
                            'content' => "<p>Se ha registrado un nuevo avance en la orden <strong>{$workOrder->code}</strong>:</p>
                                          <blockquote style='background:#f9f9f9; padding:10px; border-left:3px solid #134C42;'>
                                            " . nl2br($update->description) . "
                                          </blockquote>
                                          ".(($update->allow_reply == 1) ? '<p>Este avance incluye una solicitud de respuesta de tu parte. Por favor, ingresa a la orden de trabajo para ver los detalles y responder.</p><br><br>' : '')."
                                          <p><a href='https://clientarea.atsys.co/work-orders/view?id={$workOrder->id}' style='background-color: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Ver en el área de clientes</a></p>",
                            'color' => '#134C42'
                        ])
                        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                        ->setTo($workOrder->customer->email)
                        ->setBcc(Yii::$app->params['adminEmail'])
                        ->setSubject("Avance: " . $workOrder->title)
                        ->send();
                    } catch (\Exception $e) {} // Silencioso
                }

                Yii::$app->session->setFlash('success', 'Avance registrado.');
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Genera una Orden de Pago (Orders) basada en la Orden de Trabajo.
     * Puede ser por el total o un porcentaje (ej: 50%).
     */
    public function actionGeneratePayment($id)
    {
        $workOrder = $this->findModel($id);

        if ($workOrder->down_payment_sent_at !== null) {
            Yii::$app->session->setFlash('warning', 'El anticipo para esta orden ya fue generado y enviado el ' . Yii::$app->formatter->asDatetime($workOrder->down_payment_sent_at));
            return $this->redirect(['view', 'id' => $id]);
        }
        
        if (!$workOrder->customer_id) {
            Yii::$app->session->setFlash('error', 'Esta orden de trabajo no tiene un cliente asociado.');
            return $this->redirect(['view', 'id' => $id]);
        }

        // 1. Definir los Montos a Cobrar (50% de anticipo)
        $amountToPayCop = $workOrder->total_cost * 0.50; 
        
        // Si la orden está en USD, calculamos también la mitad del valor en dólares
        $amountToPayUsd = ($workOrder->currency === 'USD') ? ($workOrder->total_cost_usd * 0.50) : null;
        
        $concept = "Anticipo 50% - OT #" . $workOrder->id;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // A. Crear la Cabecera de la Orden (Factura)
            $order = new \app\models\Orders();
            $order->code = 'OT-' . $workOrder->id . '-' . date('His');
            $order->customer_id = $workOrder->customer_id;
            
            // Mantenemos la contabilidad base en pesos
            $order->subtotal = $amountToPayCop; 
            $order->total = $amountToPayCop;
            
            // --- INTEGRACIÓN PAYPAL: HEREDAR MONEDA ---
            // IMPORTANTE: Asegúrate de que la tabla `orders` también tenga 
            // las columnas `currency`, `exchange_rate` y `total_usd` (igual que work_orders)
            $order->currency = $workOrder->currency ?? 'COP';
            if ($order->currency === 'USD') {
                $order->exchange_rate = $workOrder->exchange_rate;
                $order->total_usd = $amountToPayUsd;
                // Si tienes un subtotal_usd en la tabla orders, agrégalo aquí:
                // $order->subtotal_usd = $amountToPayUsd; 
            }
            // ------------------------------------------

            $order->status = 0; 
            $order->created_at = date('Y-m-d H:i:s');
            
            if (!$order->save()) throw new \Exception('Error al crear la orden de pago: ' . json_encode($order->getErrors()));

            // B. Crear el Ítem del Detalle
            $item = new \app\models\OrderItems();
            $item->order_id = $order->id;
            $item->service_id = 9999;
            $item->service_name = $concept;
            $item->unit_price = $amountToPayCop;
            $item->total = $amountToPayCop;
            
            // Si tu tabla order_items tiene soporte para USD, lo llenamos:
            if ($order->currency === 'USD' && $item->hasAttribute('total_usd')) {
                $item->unit_price_usd = $amountToPayUsd;
                $item->total_usd = $amountToPayUsd;
            }

            $item->action_type = 'payment'; 
            
            if (!$item->save()) throw new \Exception('Error al crear el detalle del ítem: ' . json_encode($item->getErrors()));

            // C. ACTUALIZAR LA ORDEN DE TRABAJO
            $workOrder->down_payment_sent_at = date('Y-m-d H:i:s');
            if (!$workOrder->save(false)) throw new \Exception('Error actualizando estado de OT.');

            $transaction->commit();

            // D. ENVIAR EL EMAIL DE COBRO
            $this->sendPaymentRequestEmail($order, $workOrder);

            Yii::$app->session->setFlash('success', 'Orden de pago generada y correo enviado al cliente.');
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
            
            $subject = "Pago Requerido - Orden de Trabajo #{$workOrder->id}";
            Yii::$app->mailer->compose([
                'html' => 'payment_request-html'
            ],[
                'business_name' => $customer->business_name,
                'work_order_id' => $workOrder->id,
                'order_total' => Yii::$app->formatter->asCurrency($order->total),
                'paymentLink' => $paymentLink
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
        $model = $this->findModel($id);
        
        // Verificamos que sea POST y que la orden esté en estado correcto
        if ($this->request->isPost && $model->status === WorkOrders::STATUS_APPROVED) {

            $model->status = WorkOrders::STATUS_COMPLETED;
            $model->completed_at = date('Y-m-d H:i:s');

            if ($model->save()) {
                
                // Lógica de Notificación
                if ($this->request->post('notify_client')) {
                    Yii::$app->mailer->compose(['html' => 'workOrderClosed-html'], ['model' => $model])
                        ->setFrom([Yii::$app->params['adminEmail'] => Yii::$app->name])
                        ->setTo($model->customer->email)
                        ->setBcc(Yii::$app->params['adminEmail'])
                        ->setSubject('¡Trabajo Finalizado! Orden #' . $model->code)
                        ->send();
                        
                    Yii::$app->session->setFlash('success', 'Orden cerrada y notificación enviada.');
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
                try {
                    Yii::$app->mailer->compose([
                        'html' => 'workOrderReply-html'
                    ],
                    [
                        'model' => $workOrder,
                        'update' => $update,
                    ])
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

}