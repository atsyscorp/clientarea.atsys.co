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
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
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
                    ->setFrom([Yii::$app->params['senderEmail'] => 'Sistema ATSYS'])
                    ->setTo('hola@atsys.co')
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
                    ->setFrom([Yii::$app->params['senderEmail'] => 'Sistema ATSYS'])
                    ->setTo('hola@atsys.co')
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
                ->setFrom([Yii::$app->params['senderEmail'] => 'Proyectos ATSYS'])
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
                
                // --- INICIO: Lógica de Envío Automático ---
                try {
                    // 1. Generar PDF en memoria (String)
                    $pdf = $this->createPdfObject($model, Pdf::DEST_STRING);
                    $pdfContent = $pdf->render();

                    // 2. Enviar Correo
                    Yii::$app->mailer->compose(['html' => 'work_order_notification-html'], ['model' => $model])
                        ->setFrom([Yii::$app->params['senderEmail'] => 'Proyectos ATSYS'])
                        ->setTo($model->customer->email)
                        ->setSubject("Nueva Orden de Trabajo: " . $model->title)
                        ->attachContent($pdfContent, [
                            'fileName' => $model->code . '.pdf', 
                            'contentType' => 'application/pdf'
                        ])
                        ->send();

                    Yii::$app->session->setFlash('success', 'Orden creada y enviada al cliente exitosamente.');

                } catch (\Exception $e) {
                    // Si falla el correo, no detenemos el proceso, solo avisamos
                    Yii::error($e->getMessage());
                    Yii::$app->session->setFlash('warning', 'La orden se guardó, pero hubo un error enviando el email: ' . $e->getMessage());
                }
                // --- FIN: Lógica de Envío ---

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
                if ($update->notify_email && $update->is_visible) {
                    try {
                        Yii::$app->mailer->compose(['html' => 'admin-notification'], [
                            'title' => '🚀 Nuevo Avance en tu Proyecto',
                            'content' => "<p>Se ha registrado un nuevo avance en la orden <strong>{$workOrder->code}</strong>:</p>
                                          <blockquote style='background:#f9f9f9; padding:10px; border-left:3px solid #134C42;'>
                                            " . nl2br($update->description) . "
                                          </blockquote>
                                          <p><a href='https://clientarea.atsys.co/work-orders/view?id={$workOrder->id}'>Ver en el portal</a></p>",
                            'color' => '#134C42'
                        ])
                        ->setFrom([Yii::$app->params['senderEmail'] => 'Proyectos ATSYS'])
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

        // Si ya tiene fecha registrada, detenemos el proceso.
        if ($workOrder->down_payment_sent_at !== null) {
            Yii::$app->session->setFlash('warning', 'El anticipo para esta orden ya fue generado y enviado el ' . Yii::$app->formatter->asDatetime($workOrder->down_payment_sent_at));
            return $this->redirect(['view', 'id' => $id]);
        }
        
        // Validamos que tenga cliente
        if (!$workOrder->customer_id) {
            Yii::$app->session->setFlash('error', 'Esta orden de trabajo no tiene un cliente asociado.');
            return $this->redirect(['view', 'id' => $id]);
        }

        // 1. Definir el Monto a Cobrar
        // Aquí puedes decidir si cobras el 100% o el 50%
        // Por defecto, pongamos el 50% como anticipo (como mencionaste antes)
        $amountToPay = $workOrder->total_cost * 0.50; 
        $concept = "Anticipo 50% - OT #" . $workOrder->id;

        // Si prefieres cobrar el total, descomenta esto:
        // $amountToPay = $workOrder->total;
        // $concept = "Pago Total - OT #" . $workOrder->id;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // A. Crear la Cabecera de la Orden (Factura)
            $order = new \app\models\Orders();
            $order->code = 'OT-' . $workOrder->id . '-' . date('His'); // Código único
            $order->customer_id = $workOrder->customer_id;
            $order->subtotal = $amountToPay;
            $order->total = $amountToPay;
            $order->status = 0; // Pendiente
            $order->created_at = date('Y-m-d H:i:s');
            
            if (!$order->save()) throw new \Exception('Error al crear la orden de pago.');

            // B. Crear el Ítem del Detalle
            $item = new \app\models\OrderItems();
            $item->order_id = $order->id;
            $item->service_id = 9999;
            $item->service_name = $concept;
            $item->unit_price = $amountToPay;
            $item->total = $amountToPay;
            $item->action_type = 'payment'; // Solo cobro, no activa hosting
            
            if (!$item->save()) throw new \Exception('Error al crear el detalle del ítem.' . json_encode($item->getErrors()));

            // C. ACTUALIZAR LA ORDEN DE TRABAJO (MARCAR COMO ENVIADO)
            $workOrder->down_payment_sent_at = date('Y-m-d H:i:s');
            if (!$workOrder->save(false)) throw new \Exception('Error actualizando estado de OT.');

            $transaction->commit();

            // C. ENVIAR EL EMAIL DE COBRO (Aquí está la magia)
            $this->sendPaymentRequestEmail($order, $workOrder);

            Yii::$app->session->setFlash('success', 'Orden de pago generada y correo enviado al cliente.');
            
            // Redirigir a la vista de la orden de trabajo o a la orden de pago
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
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($customer->email)
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

}