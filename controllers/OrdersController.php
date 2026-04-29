<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Orders;
use app\models\CustomerServices;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class OrdersController extends Controller {

    // (Opcional) Desactivar la validación CSRF solo para el endpoint de Wompi si este hace un POST directo
    // public $enableCsrfValidation = false; 

    protected function findModel($id)
    {
        if (($model = Orders::findOne(['id' => $id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('La orden seleccionada no existe.');
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        // Preparamos el array base para la vista
        $viewParams = [
            'model' => $model,
            'gateway' => $model->currency // 'COP' o 'USD'
        ];

        if ($model->currency === 'COP') {
            // --- LÓGICA WOMPI ---
            $wompiPublicKey = Yii::$app->params['wmpi_pubKey']; 
            $wompiIntegritySecret = Yii::$app->params['wmpi_integrity']; 
            
            $amountInCents = $model->total * 100;
            $reference = $model->code; 
            
            $cadenaConcatenada = $reference . $amountInCents . 'COP' . $wompiIntegritySecret;
            $integritySignature = hash('sha256', $cadenaConcatenada);

            $viewParams['wompi'] = [
                'publicKey' => $wompiPublicKey,
                'currency' => 'COP',
                'amountInCents' => $amountInCents,
                'reference' => $reference,
                'signature' => $integritySignature,
                'redirectUrl' => \yii\helpers\Url::to(['orders/transaction-result', 'id' => $model->id], true), 
            ];
        } elseif ($model->currency === 'USD') {
            // --- LÓGICA PAYPAL ---
            $viewParams['paypal'] = [
                'clientId' => Yii::$app->params['paypalClientId'],
                'currency' => 'USD',
                // Asegúrate de usar el campo que guarda el total en dólares que configuramos antes
                'amount' => $model->total_usd ?? $model->total, 
            ];
        }

        return $this->render('view', $viewParams);
    }

    /**
     * Retorno de Wompi después del pago (Vía Redirección GET).
     */
    public function actionTransactionResult($id)
    {
        if (!$id) {
            return $this->redirect(['index']);
        }

        $url = "https://production.wompi.co/v1/transactions/" . $id;

        try {
            $response = file_get_contents($url);
            $json = json_decode($response, true);
            
            if (!isset($json['data'])) {
                throw new \Exception("Respuesta inválida de Wompi");
            }

            $data = $json['data'];
            $orderCode = $data['reference'];
            $order = Orders::findOne(['code' => $orderCode]);

            if (!$order) {
                throw new NotFoundHttpException("La orden asociada no existe.");
            }

            if ($data['status'] == 'APPROVED') {
                // LLAMAMOS AL NÚCLEO CENTRALIZADO
                $this->processSuccessfulPayment($order, $id, $data['payment_method_type']);
            } elseif ($data['status'] == 'DECLINED' || $data['status'] == 'ERROR') {
                Yii::$app->session->setFlash('error', 'El pago fue rechazado por el banco.');
            }

            return $this->render('transaction-result', [
                'order' => $order,
                'wompiData' => $data
            ]);

        } catch (\Exception $e) {
            Yii::error("Error consultando Wompi: " . $e->getMessage());
            Yii::$app->session->setFlash('error', 'No pudimos verificar el estado del pago automáticamente.');
            return $this->redirect(['index']);
        }
    }

    /**
     * Endpoint asíncrono para PayPal (Llamado vía Fetch/AJAX desde la vista)
     */
    public function actionPaypalConfirm()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        if ($request->isPost) {
            $data = json_decode($request->getRawBody(), true);
            
            $orderId = $data['order_id'] ?? null;
            $paypalTransactionId = $data['transaction_id'] ?? null;
            $status = $data['status'] ?? null;

            if ($orderId && $status === 'COMPLETED') {
                $order = Orders::findOne($orderId);
                
                if ($order) {
                    // LLAMAMOS AL NÚCLEO CENTRALIZADO
                    $this->processSuccessfulPayment($order, $paypalTransactionId, 'PAYPAL');
                    return ['success' => true, 'message' => 'Pago procesado correctamente'];
                }
            }
        }
        return ['success' => false, 'message' => 'Datos inválidos o pago no completado'];
    }

    /**
     * NÚCLEO CENTRALIZADO DE APROVISIONAMIENTO
     * Extraído para ser reutilizado por Wompi, PayPal y futuros métodos de pago.
     */
    private function processSuccessfulPayment($order, $transactionRef, $paymentMethod)
    {
        // Solo actualizamos si no estaba ya pagada
        if ($order->status == 0) { 
            $order->status = 1; 
            $order->payment_method = $paymentMethod; 
            $order->transaction_ref = $transactionRef; 
            $order->save(false);

            foreach ($order->orderItems as $item) {
                // CASO A: ES UNA RENOVACIÓN
                if ($item->action_type == 'renew') {
                    $service = CustomerServices::find()
                        ->where(['customer_id' => $order->customer_id])
                        ->andWhere(['domain' => $item->domain_name]) 
                        ->one();

                    if ($service) {
                        $cycle = $service->product->billing_cycle ?? 'yearly'; 
                        $tiempoASumar = ($cycle == 'monthly') ? '+1 month' : '+1 year';
                        $nuevaFecha = date('Y-m-d', strtotime($tiempoASumar, strtotime($service->next_due_date)));
                        
                        $service->next_due_date = $nuevaFecha;
                        $service->status = 1;

                        if($service->product->type == 'hosting') {
                            if($service->server_id !== NULL) {
                                \app\components\CyberPanel::unsuspendAccount($service->server_id, $service->domain);
                                $this->sendUnsuspensionEmail($service);
                            }
                        }

                        if(!$service->save(false)) {
                            Yii::$app->session->setFlash('error', 'No se pudo renovar el producto ' . $service->product->name);
                        }
                    }
                }

                // CASO B: ES COMPRA NUEVA (Hosting Setup)
                if ($item->action_type == 'hosting_setup') {
                    $product = $item->product; 
                    $customer = $order->customer;
                    
                    $panelUser = substr(preg_replace('/[^a-zA-Z0-9]/', '', explode('.', $item->domain_name)[0]), 0, 8) . rand(10,99);
                    $panelPass = Yii::$app->security->generateRandomString(12); 
                    
                    $provisionResult = \app\components\CyberPanel::createAccount(
                        $product->server_id, $item->domain_name, $product->server_package, 
                        $customer->email, $panelPass, $panelUser
                    );

                    if ($provisionResult['success']) {
                        $newService = new \app\models\CustomerServices();
                        $newService->customer_id = $customer->id;
                        $newService->product_id = $product->id;
                        $newService->domain = $item->domain_name;
                        $newService->server_id = $product->server_id;
                        $newService->username_service = $panelUser;
                        $newService->password_service = $panelPass; 
                        $newService->created_at = date('Y-m-d');
                        
                        $cycle = $product->billing_cycle ?? 'yearly';
                        $newService->next_due_date = date('Y-m-d', strtotime(($cycle == 'monthly' ? '+1 month' : '+1 year')));
                        $newService->status = 1;

                        if ($newService->save()) {
                            Yii::info("Servicio aprovisionado: {$item->domain_name}");
                        } else {
                            Yii::error("Error guardando servicio local: " . json_encode($newService->errors));
                        }
                    } else {
                        Yii::error("Fallo aprovisionamiento CyberPanel: " . $provisionResult['message']);
                        Yii::$app->session->setFlash('warning', 'Pago recibido, retraso activando el hosting.');
                    }
                }

                // CASO C: Registro de dominio
                if($item->action_type == 'register') {
                    $cycle = $item->product->billing_cycle ?? 'yearly';
                    $service = new CustomerServices();
                    $service->customer_id = $order->customer_id;
                    $service->product_id = $item->product->id;
                    $service->domain = $item->domain_name;
                    $service->start_date = date('Y-m-d');
                    $service->next_due_date = date('Y-m-d', strtotime(($cycle == 'monthly') ? '+1 month' : '+1 year'));
                    $service->status = 1;
                    $service->created_at = date('Y-m-d');
                    $service->save(false);
                }
            }

            // Enviar confirmación de pago al usuario.
            $this->sendPaymentConfirmationEmail($order);
            Yii::$app->session->setFlash('success', '¡Pago recibido correctamente!');
        }
    }

    // ... tus funciones sendUnsuspensionEmail y sendPaymentConfirmationEmail se mantienen exactamente iguales abajo ...
}