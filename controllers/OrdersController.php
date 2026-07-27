<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Orders;
use app\models\OrdersSearch;
use app\models\CustomerServices;
use yii\web\NotFoundHttpException;
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
                $this->processSuccessfulPayment($order, $id, $data['payment_method_type']);
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
                    $this->processSuccessfulPayment($order, $paypalTransactionId, 'PAYPAL');
                    return ['success' => true];
                }
            }
        }
        return ['success' => false, 'message' => 'Error en validación'];
    }

    /**
     * NÚCLEO CENTRALIZADO DE APROVISIONAMIENTO
     */
    private function processSuccessfulPayment($order, $transactionRef, $paymentMethod)
    {
        if ($order->status == 0) {
            $order->status = 1;
            $order->payment_method = $paymentMethod;
            $order->transaction_ref = $transactionRef;
            $order->save(false);

            foreach ($order->orderItems as $item) {

                // --- LÓGICA DE RENOVACIÓN ---
                if ($item->action_type == 'renew') {
                    $service = CustomerServices::find()->where(['customer_id' => $order->customer_id, 'domain' => $item->domain_name])->one();
                    if ($service) {
                        $cycle = $service->product->billing_cycle ?? 'yearly';
                        $service->next_due_date = date('Y-m-d', strtotime(($cycle == 'monthly' ? '+1 month' : '+1 year'), strtotime($service->next_due_date)));
                        $service->status = 1;

                        if ($service->product->type == 'hosting' && !empty($service->server_id)) {
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
                            $this->sendUnsuspensionEmail($service);
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

                    // 3. Registrar el servicio en la base de datos si fue exitoso
                    if ($provisionResult['success']) {
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
                        $newService->status = 1;

                        if (!$newService->save()) {
                            Yii::error("Se creó en el servidor pero falló al guardar en BD local: " . json_encode($newService->getErrors()));
                        }
                    } else {
                        Yii::error("Fallo aprovisionamiento en servidor {$server->hostname} para {$domain}: " . json_encode($provisionResult));
                    }
                }
            }

            // ENVIAR CONFIRMACIÓN
            $this->sendPaymentConfirmationEmail($order);
        }
    }

    private function sendUnsuspensionEmail($service)
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

    private function sendPaymentConfirmationEmail($order)
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

}