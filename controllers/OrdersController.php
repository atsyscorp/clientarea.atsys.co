<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Orders;
use app\models\CustomerServices;
use yii\web\NotFoundHttpException;

class OrdersController extends Controller {

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

        // --- LÓGICA WOMPI ---
        // 1. Datos básicos
        $wompiPublicKey = Yii::$app->params['wmpi_pubKey']; // TU LLAVE PÚBLICA DE PRUEBA
        $wompiIntegritySecret = Yii::$app->params['wmpi_integrity']; // TU SECRETO DE INTEGRIDAD
        
        // 2. Monto en centavos (Wompi lo exige así: 10000 pesos = 1000000 centavos)
        $amountInCents = $model->total * 100;
        
        // 3. Referencia única (Usamos tu código de orden)
        $reference = $model->code; 
        $currency = 'COP';

        // 4. Generar la firma de integridad (Concatenación específica)
        // OJO: El orden es vital: Referencia + Monto + Moneda + Secreto
        $cadenaConcatenada = $reference . $amountInCents . $currency . $wompiIntegritySecret;
        $integritySignature = hash('sha256', $cadenaConcatenada);

        return $this->render('view', [
            'model' => $model,
            'wompi' => [
                'publicKey' => $wompiPublicKey,
                'currency' => $currency,
                'amountInCents' => $amountInCents,
                'reference' => $reference,
                'signature' => $integritySignature,
                // Redirección al terminar el pago (vuelve a tu web)
                'redirectUrl' => \yii\helpers\Url::to(['orders/transaction-result', 'id' => $model->id], true), 
            ]
        ]);
    }

    /**
     * Retorno de Wompi después del pago.
     * Recibe el parametro ?id=TRANSACTION_ID
     */
    public function actionTransactionResult($id)
    {
        // 1. Validar que llegue el ID
        if (!$id) {
            return $this->redirect(['index']);
        }

        // 2. Consultar a Wompi el estado REAL de la transacción
        // OJO: Si pasas a producción, cambia esta URL a https://production.wompi.co/v1/transactions/
        $url = "https://sandbox.wompi.co/v1/transactions/" . $id;

        try {
            // Hacemos la petición GET a Wompi
            $response = file_get_contents($url);
            $json = json_decode($response, true);
            
            if (!isset($json['data'])) {
                throw new \Exception("Respuesta inválida de Wompi");
            }

            $data = $json['data'];
            
            // 3. Buscar la orden en NUESTRA base de datos usando la referencia
            // Wompi devuelve la referencia que enviamos (ej: ORD-2026...)
            $orderCode = $data['reference'];
            $order = \app\models\Orders::findOne(['code' => $orderCode]);

            if (!$order) {
                throw new \yii\web\NotFoundHttpException("La orden asociada no existe.");
            }

            // 4. Actualizar el estado según lo que diga Wompi
            // Estados posibles: APPROVED, DECLINED, VOIDED, ERROR
            if ($data['status'] == 'APPROVED') {
                
                // Solo actualizamos si no estaba ya pagada (para evitar duplicados)
                if ($order->status == 0) { // 0 = Pendiente
                    $order->status = 1; // 1 = Pagado / Procesando
                    $order->payment_method = $data['payment_method_type']; // Ej: CARD, NEQUI
                    $order->transaction_ref = $id; // Guardamos el ID de Wompi
                    $order->save(false);

                    foreach ($order->orderItems as $item) {
                        
                        // CASO A: ES UNA RENOVACIÓN
                        if ($item->action_type == 'renew') {
                            // Buscamos el servicio del cliente por dominio y cliente
                            // (Asumiendo que 'domain' es único para ese cliente)
                            $service = CustomerServices::find()
                                ->where(['customer_id' => $order->customer_id])
                                ->andWhere(['domain' => $item->domain_name]) 
                                ->one();

                            if ($service) {
                                // Determinamos cuánto tiempo sumar según el producto
                                // Si el producto es mensual, sumamos 1 mes, si es anual, 1 año.
                                // Si no tienes billing_cycle a mano, asume '+1 year' por defecto.
                                $cycle = $service->product->billing_cycle ?? 'yearly'; 
                                $tiempoASumar = ($cycle == 'monthly') ? '+1 month' : '+1 year';

                                // Calculamos nueva fecha desde la fecha actual de vencimiento
                                // Si ya estaba vencido, ¿sumamos desde hoy o desde cuando venció?
                                // Lo justo usualmente es desde la fecha de vencimiento anterior.
                                $nuevaFecha = date('Y-m-d', strtotime($tiempoASumar, strtotime($service->next_due_date)));
                                
                                $service->next_due_date = $nuevaFecha;
                                $service->status = 1;

                                // Si es hosting, debe desuspender el servicio
                                if($service->product->type == 'hosting') {
                                    if($service->server_id !== NULL) {
                                        $unsuspendResult = \app\components\CyberPanel::unsuspendAccount($service->server_id, $service->domain);
                                        $this->sendUnsuspensionEmail($service);
                                    }
                                }

                                if(!$service->save(false)) {
                                    Yii::$app->session->setFlash('error', 'No se pudo renovar el producto ' . $service->product->name.': ' . json_encode($service->getErrors()));
                                }
                            }
                        }

                        // CASO B: ES COMPRA NUEVA (Hosting Setup)
                        if ($item->action_type == 'hosting_setup') {
                            
                            // 1. Preparar datos para el aprovisionamiento
                            $product = $item->product; // Asegúrate de tener la relación en OrderItems
                            $customer = $order->customer;
                            
                            // Generar usuario y contraseña seguros para el cliente
                            // Usuario: primeras 8 letras del dominio o nombre limpio
                            $panelUser = substr(preg_replace('/[^a-zA-Z0-9]/', '', explode('.', $item->domain_name)[0]), 0, 8) . rand(10,99);
                            $panelPass = Yii::$app->security->generateRandomString(12); // Clave fuerte
                            
                            // 2. Llamar al Helper de CyberPanel
                            $provisionResult = \app\components\CyberPanel::createAccount(
                                $product->server_id,      // ID del servidor (debe estar en products)
                                $item->domain_name,       // Dominio comprado
                                $product->server_package, // Paquete (ej: Default)
                                $customer->email,         // Email dueño
                                $panelPass,               // Password generado
                                $panelUser                // Usuario generado
                            );

                            // 3. Si se creó bien en el servidor, guardamos el registro local
                            if ($provisionResult['success']) {
                                
                                $newService = new \app\models\CustomerServices();
                                $newService->customer_id = $customer->id;
                                $newService->product_id = $product->id;
                                $newService->domain = $item->domain_name;
                                $newService->server_id= $product->server_id;
                                
                                // Guardamos credenciales para que el cliente las vea
                                $newService->server_id = $product->server_id;
                                $newService->username_service = $panelUser;
                                $newService->password_service = $panelPass; // Considera encriptar esto si prefieres
                                
                                // Fechas
                                $newService->created_at = date('Y-m-d');
                                // Calculamos vencimiento (1 año o 1 mes)
                                $cycle = $product->billing_cycle ?? 'yearly';
                                $newService->next_due_date = date('Y-m-d', strtotime(($cycle == 'monthly' ? '+1 month' : '+1 year')));
                                $newService->status = 1;

                                if ($newService->save()) {
                                    Yii::info("Servicio aprovisionado: {$item->domain_name}");
                                } else {
                                    Yii::error("Error guardando servicio local: " . json_encode($newService->errors));
                                }

                            } else {
                                // Si falló CyberPanel (ej: dominio ya existe), lo registramos pero marcamos error
                                Yii::error("Fallo aprovisionamiento CyberPanel: " . $provisionResult['message']);
                                Yii::$app->session->setFlash('warning', 'Pago recibido, pero hubo un retraso activando el hosting. Soporte lo revisará manualmente.');
                                // Aquí podrías crear un Ticket automático para ti mismo (Support Ticket)
                            }
                        }

                        // CASO C: Registro de dominio
                        if($item->action_type == 'register') {
                            $cycle = $item->product->billing_cycle ?? 'yearly';
                            $tiempoASumar = ($cycle == 'monthly') ? '+1 month' : '+1 year';

                            $service = new CustomerServices();
                            $service->customer_id = $order->customer_id;
                            $service->product_id = $product->id;
                            $service->domain = $item->domain_name;
                            $service->start_date = date('Y-m-d');
                            $service->next_due_date = date('Y-m-d', strtotime('+1 year'));
                            $service->status = 1;
                            $service->created_at = date('Y-m-d');
                            $service->save(false);
                        }

                    }

                    // Enviar confirmación de pago al usuario.
                    $this->sendPaymentConfirmationEmail($order);
                    Yii::$app->session->setFlash('success', '¡Pago recibido correctamente!');
                }
            
            } elseif ($data['status'] == 'DECLINED' || $data['status'] == 'ERROR') {
                // Si falló, podemos dejarla en pendiente o cancelarla, 
                // pero NO la marcamos como pagada.
                Yii::$app->session->setFlash('error', 'El pago fue rechazado por el banco.');
            }

            // 5. Renderizar la vista de resultado
            return $this->render('transaction-result', [
                'order' => $order,
                'wompiData' => $data
            ]);

        } catch (\Exception $e) {
            Yii::error("Error consultando Wompi: " . $e->getMessage());
            Yii::$app->session->setFlash('error', 'No pudimos verificar el estado del pago automáticamente. ' . json_encode($e->getMessage()));
            return $this->redirect(['index']);
        }
    }

    private function sendUnsuspensionEmail($service)
    {
        try {
            $customer = $service->customer;
            $subject = "✅ Servicio reactivado: {$service->domain}";

            Yii::$app->mailer->compose(['html' => 'unsuspended_account-html'],[
                'business_name' => $customer->business_name,
                'domain' => $service->domain,
            ])
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($customer->email)
            ->setSubject($subject)
            ->setBcc(['soporteatsys@gmail.com','hola@atsys.co'])
            ->send();
                
        } catch (\Exception $e) {
            echo "Error enviando email: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Envía el recibo oficial de pago al cliente
     */
    private function sendPaymentConfirmationEmail($order)
    {
        try {
            $customer = $order->customer;
            $subject = "✅ Pago Recibido - Orden #{$order->code}";

            $itemsHtml = "";
            foreach ($order->orderItems as $item) {
                $itemsHtml .= "<tr>
                    <td style='padding: 8px; border-bottom: 1px solid #eee;'>{$item->service_name}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: right;'>" . Yii::$app->formatter->asCurrency($item->total) . "</td>
                </tr>";
            }

            $total = Yii::$app->formatter->asCurrency($order->total);

            Yii::$app->mailer->compose(['html' => 'payment_confirmation-html'],[
                'business_name' => $customer->business_name,
                'order_code' => $order->code,
                'payment_date' => date('d/m/Y H:i'),
                'payment_method' => $order->payment_method,
                'itemsHtml' => $itemsHtml,
                'total' => $total
            ])
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($customer->email)
            ->setSubject($subject)
            ->send();
        } catch (\Exception $e) {
            Yii::error("Error enviando recibo pago: " . $e->getMessage());
        }
    }
}