<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Orders;
use app\models\OrderItems;
use app\models\Products;
use app\models\OrdersSearch;
use app\models\CustomerServices;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class OrdersController extends Controller
{

    /**
     * IMPORTANTE: Desactiva CSRF para la confirmación de PayPal
     */
    public function beforeAction($action)
    {
        if ($action->id === 'paypal-confirm') {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    protected function findModel($id)
    {
        if (($model = Orders::findOne(['id' => $id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('La orden seleccionada no existe.');
    }

    public function actionIndex()
    {
        $searchModel = new OrdersSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        if (!Yii::$app->user->isGuest && !Yii::$app->user->identity->isAdmin) {
            $identity = Yii::$app->user->identity;
            $ownerId = (!empty($identity->parent_id)) ? $identity->parent_id : $identity->id;
            $myCustomer = \app\models\Customers::findOne(['user_id' => $ownerId]);
            $realCustomerId = $myCustomer ? $myCustomer->id : -1;

            $dataProvider->query->andWhere(['customer_id' => $realCustomerId]);
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

        public function actionCreate()
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException('No tienes permisos para realizar esta acción.');
        }

        $model = new Orders();
        $model->currency = 'COP'; // Default
        
        if ($this->request->isPost) {
            $post = $this->request->post();
            $itemsPost = $post['items'] ?? [];
            
            if (empty($itemsPost)) {
                Yii::$app->session->setFlash('error', 'Debes agregar al menos un ítem a la orden.');
                return $this->render('create', ['model' => $model]);
            }
            
            $model->code = 'ORD-' . date('Ymd') . '-' . rand(100,999);
            $model->customer_id = $post['Orders']['customer_id'] ?? null;
            $model->currency = $post['Orders']['currency'] ?? 'COP';
            
            $totalAmount = 0;
            foreach ($itemsPost as $itemData) {
                $totalAmount += floatval($itemData['amount']);
            }
            
            $model->subtotal = $totalAmount;
            $model->total = $totalAmount;
            $model->status = 0;
            $model->created_at = date('Y-m-d H:i:s');
            
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('Error al crear la orden: ' . current($model->getFirstErrors()));
                }
                
                $emailItemsHtml = "";
                
                foreach ($itemsPost as $itemData) {
                    $item = new OrderItems();
                    $item->order_id = $model->id;
                    $itemAmount = floatval($itemData['amount']);
                    
                    if ($itemData['type'] === 'product' && !empty($itemData['product_id'])) {
                        $product = \app\models\Products::findOne($itemData['product_id']);
                        $item->service_id = $product ? $product->id : 0;
                        $item->service_name = $product ? $product->name : 'Producto no encontrado';
                    } else {
                        $defaultProduct = \app\models\Products::find()->one();
                        $item->service_id = $defaultProduct ? $defaultProduct->id : 0;
                        $item->service_name = !empty($itemData['description']) ? $itemData['description'] : 'Concepto manual';
                    }
                    
                    $item->unit_price = $itemAmount;
                    $item->total = $itemAmount;
                    $item->action_type = OrderItems::ACTION_TYPE_PAYMENT;
                    
                    if (!$item->save(false)) {
                        throw new \Exception("Error guardando los ítems de la orden.");
                    }
                    
                    $emailItemsHtml .= "<li style='margin-bottom:5px;'><strong>{$item->service_name}</strong>: " . Yii::$app->formatter->asCurrency($itemAmount) . " {$model->currency}</li>";
                }
                
                // --- INICIO NOTIFICACIONES ---
                $customer = \app\models\Customers::findOne($model->customer_id);
                if ($customer && $customer->user_id) {
                    $order_total_formatted = Yii::$app->formatter->asCurrency($totalAmount) . ' ' . $model->currency;
                    $paymentLink = \yii\helpers\Url::to(['orders/view', 'id' => $model->id], true);
                    
                    // Email
                    try {
                        Yii::$app->mailer->compose(['html' => 'manual_payment_request-html'], [
                            'business_name' => $customer->business_name,
                            'itemsHtml' => $emailItemsHtml,
                            'order_total' => $order_total_formatted,
                            'paymentLink' => $paymentLink
                        ])
                        ->setFrom([Yii::$app->params['senderEmail'] ?? 'no-reply@atsys.co' => Yii::$app->params['senderName'] ?? 'ATSYS'])
                        ->setTo($customer->email)
                        ->setSubject("Nueva orden de pago generada: " . $model->code)
                        ->send();
                    } catch (\Throwable $e) {
                        Yii::error("Error enviando email de orden manual: " . $e->getMessage());
                    }
                    
                    // In-app Notification
                    try {
                        \app\models\Notifications::notifyCustomer(
                            $model->customer_id,
                            "💳 Nueva orden de pago",
                            "Se ha generado la orden {$model->code} por {$order_total_formatted}.",
                            "/orders/view?id=" . $model->id
                        );
                    } catch (\Throwable $e) {
                        Yii::error("Error creando notificación in-app: " . $e->getMessage());
                    }
                }
                // --- FIN NOTIFICACIONES ---
                
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Orden de pago generada correctamente y notificada al cliente.');
                return $this->redirect(['view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }
        
        return $this->render('create', [
            'model' => $model,
        ]);
    }
    public function actionView($id)
    {
        $model = $this->findModel($id);

        // --- NUEVO: DETECCIÓN DE FIN DE SEMANA ---
        // Configuramos la zona horaria para Colombia, para que no use la UTC del servidor
        $timezone = new \DateTimeZone('America/Bogota');
        $date = new \DateTime('now', $timezone);
        // 'N' devuelve 1 (Lunes) a 7 (Domingo)
        $dayOfWeek = (int) $date->format('N');

        // Consideramos "Fin de semana" desde el Viernes (5) hasta el Domingo (7)
        $isWeekend = ($dayOfWeek >= 5 && $dayOfWeek <= 7);
        // -----------------------------------------

        // Calcular valores dinámicos para COP, USD y EUR
        $exchangeRateUsd = ($model->currency === 'USD') ? ($model->exchange_rate ?: \app\helpers\CurrencyHelper::getTrm('USD')) : \app\helpers\CurrencyHelper::getTrm('USD');
        $totalUsd = ($model->currency === 'USD') ? ($model->total_usd ?: $model->total) : round($model->total / $exchangeRateUsd, 2);

        $exchangeRateEur = ($model->currency === 'EUR') ? ($model->exchange_rate ?: \app\helpers\CurrencyHelper::getTrm('EUR')) : \app\helpers\CurrencyHelper::getTrm('EUR');
        $totalEur = ($model->currency === 'EUR') ? ($model->total_usd ?: $model->total) : round($model->total / $exchangeRateEur, 2);

        // Pasarela Wompi (COP)
        $wompiPublicKey = Yii::$app->params['wmpi_pubKey'];
        $wompiIntegritySecret = Yii::$app->params['wmpi_integrity'];
        $amountInCents = round($model->total * 100);
        $reference = $model->code;
        $cadenaConcatenada = $reference . $amountInCents . 'COP' . $wompiIntegritySecret;
        $integritySignature = hash('sha256', $cadenaConcatenada);

        $wompiParams = [
            'publicKey' => $wompiPublicKey,
            'currency' => 'COP',
            'amountInCents' => $amountInCents,
            'reference' => $reference,
            'signature' => $integritySignature,
            'redirectUrl' => \yii\helpers\Url::to(['orders/transaction-result', 'id' => $model->id], true),
        ];

        // Pasarela PayPal (USD)
        $paypalUsdParams = [
            'clientId' => Yii::$app->params['paypalClientId'],
            'currency' => 'USD',
            'amount' => $totalUsd,
            'exchangeRate' => $exchangeRateUsd,
        ];

        // Pasarela PayPal (EUR)
        $paypalEurParams = [
            'clientId' => Yii::$app->params['paypalClientId'],
            'currency' => 'EUR',
            'amount' => $totalEur,
            'exchangeRate' => $exchangeRateEur,
        ];

        $viewParams = [
            'model' => $model,
            'gateway' => $model->currency,
            'isWeekend' => $isWeekend,
            'wompi' => $wompiParams,
            'paypalUsd' => $paypalUsdParams,
            'paypalEur' => $paypalEurParams,
            'totalUsd' => $totalUsd,
            'totalEur' => $totalEur,
            'exchangeRateUsd' => $exchangeRateUsd,
            'exchangeRateEur' => $exchangeRateEur,
        ];

        return $this->render('view', $viewParams);
    }

    public function actionTransactionResult($id)
    {
        if (!$id)
            return $this->redirect(['index']);

        $url = "https://production.wompi.co/v1/transactions/" . $id;

        try {
            $response = file_get_contents($url);
            $json = json_decode($response, true);
            if (!isset($json['data']))
                throw new \Exception("Respuesta inválida de Wompi");

            $data = $json['data'];
            $order = Orders::findOne(['code' => $data['reference']]);

            if (!$order)
                throw new NotFoundHttpException("La orden asociada no existe.");

            if ($data['status'] == 'APPROVED') {
                self::processSuccessfulPayment($order, $id, $data['payment_method_type']);
            }

            return $this->render('transaction-result', ['order' => $order, 'wompiData' => $data]);
        } catch (\Exception $e) {
            return $this->redirect(['index']);
        }
    }

    public function actionPaypalConfirm()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        if ($request->isPost) {
            $data = json_decode($request->getRawBody(), true);
            $orderId = $data['order_id'] ?? null;
            $paypalTransactionId = $data['transaction_id'] ?? null;
            $status = $data['status'] ?? null;
            $paidCurrency = $data['currency'] ?? 'USD';

            if ($orderId && $status === 'COMPLETED') {
                $order = Orders::findOne($orderId);
                if ($order) {
                    // Si la orden original es COP, pero se pagó en USD/EUR, actualizamos la moneda y TRM en la orden
                    if ($order->currency === 'COP' && in_array($paidCurrency, ['USD', 'EUR'])) {
                        $order->currency = $paidCurrency;
                        $exchangeRate = \app\helpers\CurrencyHelper::getTrm($paidCurrency);
                        $order->exchange_rate = $exchangeRate;
                        $order->total_usd = round($order->total / $exchangeRate, 2);

                        // Actualizamos también cada ítem de la orden
                        foreach ($order->orderItems as $item) {
                            $item->total_usd = round($item->total / $exchangeRate, 2);
                            $item->unit_price_usd = round($item->unit_price / $exchangeRate, 2);
                            $item->save(false);
                        }
                    }
                    self::processSuccessfulPayment($order, $paypalTransactionId, 'PAYPAL');
                    return ['success' => true];
                }
            }
        }
        return ['success' => false, 'message' => 'Error en validación'];
    }

    /**
     * Acción para que el administrador cambie manualmente el estado del pedido
     */
    public function actionChangeStatus($id)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new ForbiddenHttpException('No tienes permiso para realizar esta acción.');
        }

        $order = $this->findModel($id);
        $request = Yii::$app->request;

        $newStatus = (int) $request->post('status', $request->get('status', $order->status));
        $paymentMethod = $request->post('payment_method', $request->get('payment_method', $order->payment_method));
        $transactionRef = $request->post('transaction_ref', $request->get('transaction_ref', $order->transaction_ref));
        $executeProvisioning = (bool) $request->post('execute_provisioning', $request->get('execute_provisioning', 0));

        if ($newStatus === 1 && $executeProvisioning && $order->status != 1) {
            self::processSuccessfulPayment($order, $transactionRef ?: 'MANUAL_ADMIN', $paymentMethod ?: 'Manual (Administrador)');
            Yii::$app->session->setFlash('success', "La orden #{$order->code} se marcó como PAGADA y se procesó el aprovisionamiento de servicios correctamente.");
        } else {
            $order->status = $newStatus;
            if (!empty($paymentMethod)) {
                $order->payment_method = $paymentMethod;
            }
            if (!empty($transactionRef)) {
                $order->transaction_ref = $transactionRef;
            }
            $order->save(false);
            Yii::$app->session->setFlash('success', "Estado de la orden #{$order->code} actualizado correctamente.");
        }

        return $this->redirect(['view', 'id' => $order->id]);
    }

    /**
     * NÚCLEO CENTRALIZADO DE APROVISIONAMIENTO
     */
    public static function processSuccessfulPayment($order, $transactionRef, $paymentMethod)
    {
        if ($order->status == 0) {
            $order->status = 1;
            $order->payment_method = $paymentMethod;
            $order->transaction_ref = $transactionRef;
            $order->save(false);

            foreach ($order->orderItems as $item) {

                // --- LÓGICA DE RENOVACIÓN Y RESTAURACIÓN ---
                if ($item->action_type == 'renew' || $item->action_type == 'penalty') {
                    // Importante: Buscar por product_id ($item->service_id) para no confundir hosting y dominio
                    $service = CustomerServices::find()->where([
                        'customer_id' => $order->customer_id, 
                        'domain' => $item->domain_name,
                        'product_id' => $item->service_id
                    ])->one();
                    
                    if ($service) {
                        $cycle = $service->product->billing_cycle ?? 'yearly';
                        
                        // Si estaba vencido por mucho tiempo, podríamos querer reiniciar la fecha desde hoy,
                        // pero mantener +ciclo desde la fecha de vencimiento es lo estándar para renovaciones atrasadas.
                        $service->next_due_date = date('Y-m-d', strtotime(($cycle == 'monthly' ? '+1 month' : '+1 year'), strtotime($service->next_due_date)));
                        $service->status = 1;

                        if ($service->product && $service->product->type == 'hosting' && !empty($service->server_id)) {
                            // Detectamos el servidor asociado al servicio para desuspender en el panel correcto
                            $serverRen = $service->server;
                            if ($serverRen) {
                                if ($serverRen->type == 'virtualmin') {
                                    // Cambia esto por tu método real en el componente de Virtualmin
                                    Yii::$app->virtualmin->unsuspendAccount($serverRen->username, $serverRen->auth_token, $serverRen->hostname, $service->domain);
                                } elseif ($serverRen->type == 'cyberpanel') {
                                    \app\components\CyberPanel::unsuspendAccount($serverRen->id, $service->domain);
                                }
                            }
                            self::sendUnsuspensionEmail($service);
                        }
                        $service->save(false);
                    }
                }

                // --- LÓGICA DE CREACIÓN DE HOSTING ---
                if ($item->action_type == 'hosting_setup') {

                    $product = $item->product; // Accedemos al producto a través del ítem
                    $domain = $item->domain_name;
                    $customer = $order->customer;
                    $panelUser = substr(preg_replace('/[^a-zA-Z0-9]/', '', explode('.', $domain)[0]), 0, 8) . rand(10, 99);
                    $panelPass = Yii::$app->security->generateRandomString(12);

                    $server = null;
                    $provisionResult = ['success' => false, 'message' => 'No se ejecutó el aprovisionamiento.'];

                    // 1. Determinar qué servidor usar (Fijo vs Balanceo)
                    if (!empty($product->server_id)) {
                        $server = $product->server;
                    } else {
                        $server = \app\models\Servers::find()->where(['status' => 1])->one();
                    }

                    // 2. Ejecutar Aprovisionamiento si hay servidor
                    if (!$server) {
                        Yii::error("No hay servidores activos para aprovisionar el dominio {$domain} en la orden {$order->code}.");
                        continue;
                    } else {
                        // Enrutamiento de Panel
                        if ($server->type == 'virtualmin') {
                            $provisionResult = Yii::$app->virtualmin->createAccountDinamic(
                                $server->username,
                                $server->auth_token,
                                $server->hostname,
                                $domain,
                                $panelPass,
                                $panelUser,
                                $product->server_package // Viene de $product, no de $service
                            );
                        } elseif ($server->type == 'cyberpanel') {
                            $provisionResult = \app\components\CyberPanel::createAccount(
                                $server->id,
                                $domain,
                                $product->server_package,
                                $customer->email,
                                $panelPass,
                                $panelUser
                            );
                        }
                    }

                    // 3. Registrar el servicio en la base de datos si fue exitoso (o como pendiente si falló)
                    $newService = new CustomerServices();
                    $newService->customer_id = $customer->id;
                    $newService->product_id = $product->id;
                    $newService->domain = $domain;
                    // OJO AQUÍ: Guardamos el ID del servidor que REALMENTE se usó (ideal para el balanceo)
                    $newService->server_id = $server->id;
                    $newService->username_service = $panelUser;
                    $newService->password_service = $panelPass;
                    $newService->created_at = date('Y-m-d');
                    $newService->next_due_date = date('Y-m-d', strtotime(($product->billing_cycle == 'monthly' ? '+1 month' : '+1 year')));
                    
                    if ($provisionResult['success']) {
                        $newService->status = 1;
                    } else {
                        $newService->status = 0; // Pendiente / Error de aprovisionamiento
                        Yii::error("Fallo aprovisionamiento en servidor {$server->hostname} para {$domain}: " . json_encode($provisionResult));
                    }

                    if (!$newService->save()) {
                        Yii::error("Fallo al guardar servicio de hosting en BD local: " . json_encode($newService->getErrors()));
                    }
                }

                // --- LÓGICA DE REGISTRO/TRANSFERENCIA DE DOMINIO ---
                if ($item->action_type == 'register' || $item->action_type == 'transfer') {
                    $domain = $item->domain_name;
                    $customer = $order->customer;
                    $product = $item->product;

                    $newService = new CustomerServices();
                    $newService->customer_id = $customer->id;
                    $newService->product_id = $product ? $product->id : $item->service_id;
                    $newService->domain = $domain;
                    $newService->created_at = date('Y-m-d');
                    // Los dominios por defecto son anuales
                    $newService->next_due_date = date('Y-m-d', strtotime('+1 year'));
                    $newService->status = 1; // 1 = Activo (o pendiente de registro manual por admin)

                    if (!$newService->save()) {
                        Yii::error("Fallo al guardar dominio en BD local: " . json_encode($newService->getErrors()));
                    }
                }
            }

            // ENVIAR CONFIRMACIÓN AL CLIENTE
            self::sendPaymentConfirmationEmail($order);

            // NOTIFICAR AL ADMIN SI REQUIERE FACTURA
            if ($order->require_invoice) {
                self::sendAdminInvoiceRequiredEmail($order);
            }
        }
    }

    public static function sendAdminInvoiceRequiredEmail($order)
    {
        try {
            $customer = $order->customer;
            $itemsHtml = "";
            foreach ($order->orderItems as $item) {
                $itemsHtml .= "<li>{$item->service_name} - " . Yii::$app->formatter->asCurrency($item->total) . " {$order->currency}</li>";
            }

            $body = "
                <h2>Requerimiento de Factura Electrónica</h2>
                <p>El cliente ha solicitado factura electrónica para la orden <strong>{$order->code}</strong> que acaba de ser pagada.</p>
                <h3>Detalles del Cliente</h3>
                <ul>
                    <li><strong>Nombre/Razón Social:</strong> {$customer->business_name}</li>
                    <li><strong>Documento/NIT:</strong> {$customer->document_number}</li>
                    <li><strong>Email:</strong> {$customer->email}</li>
                    <li><strong>Teléfono:</strong> {$customer->primary_phone}</li>
                    <li><strong>Dirección:</strong> {$customer->address}, {$customer->city}</li>
                </ul>
                <h3>Detalles del Pedido</h3>
                <ul>
                    $itemsHtml
                </ul>
                <p><strong>Total:</strong> " . Yii::$app->formatter->asCurrency($order->total) . " {$order->currency}</p>
                <p>Por favor generar la factura y enviarla al cliente.</p>
            ";

            Yii::$app->mailer->compose()
                ->setFrom([Yii::$app->params['senderEmail'] ?? 'no-reply@atsys.co' => Yii::$app->params['senderName'] ?? 'ATSYS'])
                ->setTo(Yii::$app->params['adminEmail'] ?? 'hola@atsys.co')
                ->setSubject("Factura Electrónica Requerida - Orden {$order->code}")
                ->setHtmlBody($body)
                ->send();
        } catch (\Throwable $e) {
            Yii::error("Error notificando requerimiento de factura al admin: " . $e->getMessage());
        }
    }

    public static function sendUnsuspensionEmail($service)
    {
        try {
            $customer = $service->customer;
            Yii::$app->mailer->compose(['html' => 'unsuspended_account-html'], [
                'business_name' => $customer->business_name,
                'domain' => $service->domain,
            ])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                ->setTo($customer->email)
                ->setSubject("✅ Servicio reactivado: {$service->domain}")
                ->send();
        } catch (\Throwable $e) {
            Yii::error("Error reactivación email: " . $e->getMessage());
        }
    }

    public static function sendPaymentConfirmationEmail($order)
    {
        try {
            $customer = $order->customer;
            $itemsHtml = "";
            foreach ($order->orderItems as $item) {
                // Adaptamos el valor a mostrar según la moneda para el correo
                $isForeign = in_array($order->currency, ['USD', 'EUR']);
                $val = ($isForeign && $order->exchange_rate !== NULL) ? ($item->total_usd ?? $item->total) : $item->total;
                $itemsHtml .= "<tr><td style='padding:8px; border-bottom:1px solid #eee;'>{$item->service_name}</td><td style='padding:8px; border-bottom:1px solid #eee; text-align:right;'>" . Yii::$app->formatter->asCurrency($val) . " {$order->currency}</td></tr>";
            }

            $isForeign = in_array($order->currency, ['USD', 'EUR']);
            Yii::$app->mailer->compose(['html' => 'payment_confirmation-html'], [
                'business_name' => $customer->business_name,
                'order_code' => $order->code,
                'payment_date' => date('d/m/Y H:i'),
                'payment_method' => $order->payment_method,
                'itemsHtml' => $itemsHtml,
                'total' => Yii::$app->formatter->asCurrency(($isForeign ? $order->total_usd : $order->total)) . " {$order->currency}"
            ])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setReplyTo(Yii::$app->params['departmentEmails']['billing'] ?? 'facturacion@atsys.co')
                ->setTo($customer->email)
                ->setSubject("✅ Pago Recibido - Orden {$order->code}")
                ->send();
        } catch (\Throwable $e) {
            Yii::error("Error recibo pago email: " . $e->getMessage());
        }
    }

    public function actionDelete($id)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            throw new ForbiddenHttpException('No tienes permisos para realizar esta acción.');
        }

        $model = $this->findModel($id);
        if ($model->delete()) {
            Yii::$app->session->setFlash('success', 'Orden eliminada correctamente.');
        } else {
            Yii::$app->session->setFlash('error', 'No se pudo eliminar la orden.');
        }

        return $this->redirect(['index']);
    }

    public function actionBulk()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            return ['success' => false, 'message' => 'No tienes permisos para realizar esta acción.'];
        }

        if ($this->request->isPost) {
            $ids = $this->request->post('ids');
            $action = $this->request->post('action');

            if (empty($ids) || !is_array($ids)) {
                return ['success' => false, 'message' => 'No has seleccionado ninguna orden.'];
            }

            $count = 0;

            if ($action === 'delete') {
                foreach ($ids as $id) {
                    $model = Orders::findOne($id);
                    if ($model) {
                        if ($model->delete()) {
                            $count++;
                        }
                    }
                }

                $message = "Se eliminaron $count órdenes correctamente.";
                Yii::$app->session->setFlash('success', $message);

                return [
                    'success' => true,
                    'message' => $message,
                    'count' => $count
                ];
            }

            return ['success' => false, 'message' => 'Acción no válida.'];
        }

        return ['success' => false, 'message' => 'Petición inválida.'];
    }

    public function actionReceipt($id)
    {
        $order = $this->findModel($id);
        
        if ($order->status != 1) {
            throw new ForbiddenHttpException('Solo se pueden generar comprobantes de órdenes pagadas.');
        }

        // Permisos
        if (Yii::$app->user->isGuest) {
            throw new ForbiddenHttpException('Acceso denegado.');
        }
        if (!Yii::$app->user->identity->isAdmin) {
            $identity = Yii::$app->user->identity;
            $ownerId = (!empty($identity->parent_id)) ? $identity->parent_id : $identity->id;
            $myCustomer = \app\models\Customers::findOne(['user_id' => $ownerId]);
            if (!$myCustomer || $order->customer_id != $myCustomer->id) {
                throw new ForbiddenHttpException('No tienes permiso para ver este comprobante.');
            }
        }

        $content = $this->renderPartial('_receipt_pdf', ['model' => $order]);

        $pdf = new \kartik\mpdf\Pdf([
            'mode' => \kartik\mpdf\Pdf::MODE_UTF8,
            'format' => \kartik\mpdf\Pdf::FORMAT_A4,
            'orientation' => \kartik\mpdf\Pdf::ORIENT_PORTRAIT,
            'destination' => \kartik\mpdf\Pdf::DEST_BROWSER,
            'content' => $content,
            'cssInline' => '
                body { font-family: "Helvetica", sans-serif; color: #333; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .text-left { text-align: left; }
                .font-bold { font-weight: bold; }
                .text-xl { font-size: 24px; }
                .text-lg { font-size: 18px; }
                .text-sm { font-size: 12px; }
                .text-xs { font-size: 10px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 10px; font-size: 14px; }
                th { background-color: #f8f9fa; }
                .header-container { width: 100%; margin-bottom: 30px; }
                .logo-cell { width: 50%; }
                .info-cell { width: 50%; text-align: right; }
                .info-cell h1 { margin: 0; color: #2c3e50; font-size: 24px; }
                .alert-box { border: 1px solid #ddd; background-color: #f9f9f9; padding: 15px; margin-top: 30px; border-radius: 5px; font-size: 12px; text-align: center; }
            ',
            'options' => ['title' => 'Comprobante de Venta - ' . $order->code],
            'methods' => [
                'SetHeader' => ['Comprobante de Venta - ATSYS'],
                'SetFooter' => ['{PAGENO}'],
            ]
        ]);

        return $pdf->render();
    }

}