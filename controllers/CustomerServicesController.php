<?php

namespace app\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;

use app\models\Orders;
use app\models\Servers;
use app\models\Products;
use app\models\Customers;
use app\models\OrderItems;
use app\models\CustomerServices;
use app\models\CustomerServicesSearch;

class CustomerServicesController extends \yii\web\Controller
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
                            return !Yii::$app->user->isGuest && (
                                Yii::$app->user->identity->isAdmin ||
                                Yii::$app->user->identity->role == 10 ||
                                Yii::$app->user->identity->role == 12);
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

        $searchModel = new \app\models\CustomerServicesSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate($customer_id = null)
    {
        $isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
        if (!$isAdmin) {
            $user = Yii::$app->user->identity;
            if ($user->role === \app\models\User::ROLE_CLIENT && !$user->customer) {
                return $this->redirect('/customers/create');
            }
        }

        $model = new CustomerServices();

        if ($customer_id) {
            $model->customer_id = $customer_id;
        }

        $model->start_date = date('Y-m-d');
        $model->next_due_date = date('Y-m-d', strtotime('+1 year'));
        $model->status = 1;

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {

                // 1. Lógica de Asignación de Servidor y Credenciales (SOLO PARA HOSTING)
                if ($model->product->type == 'hosting') {

                    // A. ¿Eligió servidor manualmente o es automático?
                    if (empty($model->server_id)) {
                        // Balanceo Automático: Buscar servidor activo con más espacio
                        $server = \app\models\Servers::find()
                            ->where(['is_active' => 1])
                            ->andWhere('current_accounts < max_accounts')
                            ->orderBy(['current_accounts' => SORT_ASC])
                            ->one();

                        if (!$server) {
                            Yii::$app->session->setFlash('error', 'Error: No hay servidores activos con capacidad disponible para aprovisionar.');
                            return $this->redirect($this->request->referrer); // Detener y volver al formulario
                        }
                        $model->server_id = $server->id;
                    } else {
                        // Asignación Manual seleccionada en el form
                        $server = \app\models\Servers::findOne($model->server_id);
                    }

                    // B. Generar credenciales si vienen vacías desde el formulario
                    if (empty($model->username_service)) {
                        $model->username_service = substr(preg_replace('/[^a-zA-Z0-9]/', '', explode('.', $model->domain)[0]), 0, 8) . rand(10, 99);
                    }
                    if (empty($model->password_service)) {
                        $model->password_service = Yii::$app->security->generateRandomString(12);
                    }
                }

                // 2. Guardar el registro local con todos los datos completos
                if ($model->save()) {

                    // 3. Ejecutar Aprovisionamiento Remoto
                    if ($model->product->type == 'hosting' && isset($server)) {

                        $provisionResult = ['success' => false, 'message' => 'Método no implementado'];

                        try {
                            // C. Enrutamiento Dinámico de API según el panel del Servidor
                            if ($server->type == 'virtualmin') {
                                // Conexión a Virtualmin usando el componente nuevo
                                $provisionResult = Yii::$app->virtualmin->createAccountDinamic(
                                    $server->username,               // api_atsys
                                    $server->auth_token,             // Contraseña de API
                                    $server->hostname,               // nexus01.atsys.co
                                    $model->domain,                  // Dominio
                                    $model->password_service,        // Clave del cliente
                                    $model->username_service,        // Usuario del cliente
                                    $model->product->server_package  // Plan/Template
                                );
                            } elseif ($server->type == 'cyberpanel') {
                                // Conexión a CyberPanel (Tu código existente)
                                $provisionResult = \app\components\CyberPanel::createAccount(
                                    $server->id,
                                    $model->domain,
                                    $model->product->server_package,
                                    $model->customer->email,
                                    $model->password_service,
                                    $model->username_service
                                );
                            }
                        } catch (\Exception $e) {
                            $provisionResult['message'] = $e->getMessage();
                        }

                        // 4. Validar respuesta de la API
                        if (isset($provisionResult['success']) && $provisionResult['success'] === true) {
                            // Restar capacidad al servidor físico
                            $server->current_accounts += 1;
                            $server->save(false);

                            if (!(null !== $this->request->post('silent') && $this->request->post('silent') == '1')) {
                                $this->sendServiceActivationEmail($model);
                            }
                        } else {
                            // Fallo en la API remota
                            Yii::error("Fallo aprovisionamiento en [{$server->type}]: " . ($provisionResult['message'] ?? 'Error desconocido'));
                            Yii::$app->session->setFlash('warning', 'El servicio fue registrado localmente, pero hubo un error creando la cuenta en ' . ucfirst($server->type) . '. Soporte deberá activarlo manualmente.');

                            // Opcionalmente podrías cambiar el estado a 2 (Suspendido/Pendiente) para que destaque
                            // $model->status = 2; 
                            // $model->save(false);
                        }
                    }

                    // 5. Redirecciones Finales
                    if ($model->customer_id) {
                        Yii::$app->session->setFlash('success', 'Servicio procesado correctamente.');
                        return $this->redirect(['customers/view', 'id' => $model->customer_id]);
                    }
                    return $this->redirect(['index']);
                }
            }
        }

        $customers = \yii\helpers\ArrayHelper::map(Customers::find()->all(), 'id', 'name');
        $products = \yii\helpers\ArrayHelper::map(
            Products::find()->where(['status' => 1])->all(),
            'id',
            function ($product) {
                return $product->name . ' (Renov: ' . Yii::$app->formatter->asCurrency($product->price_renewal) . ')';
            }
        );
        $servers = \yii\helpers\ArrayHelper::map(Servers::find()->all(), 'id', 'name');

        return $this->render('create', [
            'model' => $model,
            'customers' => $customers,
            'products' => $products,
            'servers' => $servers,
            'lockedCustomer' => $customer_id,
        ]);
    }

    protected function findModel($id)
    {
        if (($model = CustomerServices::findOne(['id' => $id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('El servicio asociado no existe.');
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $oldStatus = $model->status;

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {

            if ($model->product->type == 'hosting' && $model->status != $oldStatus) {

                // Si el server_id no está en el servicio, usamos el del producto como fallback
                $serverId = $model->server_id ?? $model->product->server_id;

                if ($serverId) {
                    if ($model->status == 2) {
                        // Cambió a SUSPENDIDO -> Apagar en servidor
                        \app\components\CyberPanel::suspendAccount($serverId, $model->domain);
                        Yii::$app->session->setFlash('warning', 'Servicio actualizado y suspendido.');
                    } elseif ($model->status == 'active') {
                        // Cambió a ACTIVO -> Encender en servidor
                        \app\components\CyberPanel::unsuspendAccount($serverId, $model->domain);
                        Yii::$app->session->setFlash('success', 'Servicio actualizado y reactivado.');
                    }
                }
            }

            // Redirigir de vuelta a la ficha del cliente (UX mejorada)
            return $this->redirect(['customers/view', 'id' => $model->customer_id]);
        }

        // Preparamos las listas para los Dropdowns (Igual que en create)
        $customers = \yii\helpers\ArrayHelper::map(Customers::find()->all(), 'id', 'name');

        // Productos con el precio visual en el nombre
        $products = \yii\helpers\ArrayHelper::map(
            Products::find()->all(),
            'id',
            function ($product) {
                return $product->name . ' (Renov: ' . Yii::$app->formatter->asCurrency($product->price_renewal) . ')';
            }
        );

        $servers = \yii\helpers\ArrayHelper::map(
            Servers::find()
                ->where(['is_active' => 1])
                ->select(['name', 'id'])
                ->indexBy('id')
                ->column(),
            'id',
            'name'
        );

        return $this->render('update', [
            'model' => $model,
            'customers' => $customers,
            'products' => $products,
            'servers' => $servers,
        ]);
    }

    /**
     * Función privada para enviar el correo
     */
    protected function sendServiceActivationEmail($service)
    {
        $clientEmail = $service->customer->email ?? null; // Asumiendo que Customer tiene email
        if (!$clientEmail)
            return;

        Yii::$app->mailer->compose(['html' => 'new_service-html'], ['service' => $service])
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($clientEmail)
            ->setBcc(Yii::$app->params['adminEmail'])
            ->setSubject('¡Nuevo Servicio Activado! - ' . $service->product->name)
            ->send();
    }

    /**
     * Genera una orden de renovación y redirige al pago
     */
    public function actionRenew($id)
    {
        // 1. Buscamos el servicio usando el modelo correcto
        $service = CustomerServices::findOne($id);

        if (!$service) {
            throw new \yii\web\NotFoundHttpException("Servicio no encontrado.");
        }

        // 2. Evitar duplicados: Verificamos si ya hay una orden pendiente
        $existingOrder = Orders::find()
            ->joinWith('orderItems')
            ->where(['customer_id' => $service->customer_id, 'status' => 0]) // 0 = Pendiente
            // Asumimos que CustomerServices tiene 'product_id'
            ->andWhere(['order_items.service_id' => $service->product_id])
            ->andWhere(['order_items.action_type' => 'renew'])
            ->one();

        if ($existingOrder) {
            Yii::$app->session->setFlash('info', 'Ya tienes una orden de renovación pendiente para este servicio.');
            return $this->redirect(['orders/view', 'id' => $existingOrder->id]);
        }

        // 3. Crear la Orden (Transacción)
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // A. Cabecera
            $order = new Orders();
            $order->code = 'REN-' . date('Ymd') . '-' . rand(100, 999);
            $order->customer_id = $service->customer_id;

            // Usamos el precio del producto base para la renovación
            // Asegúrate que en CustomerServices tengas la relación 'product' definida:
            // public function getProduct() { return $this->hasOne(Products::class, ['id' => 'product_id']); }
            $renewalPrice = $service->product->price;

            $order->subtotal = $renewalPrice;
            $order->total = $renewalPrice;
            $order->status = 0; // Pendiente
            $order->created_at = date('Y-m-d H:i:s');

            if (!$order->save())
                throw new \Exception('Error creando orden.');

            // B. Ítem de Renovación
            $item = new OrderItems();
            $item->order_id = $order->id;
            $item->service_id = $service->product_id;
            $item->service_name = $service->product->name . ' (Renovación)';

            // Asumiendo que en CustomerServices guardas el dominio en un campo 'domain'
            $item->domain_name = $service->domain;

            $item->unit_price = $renewalPrice;
            $item->total = $renewalPrice;
            $item->action_type = 'renew'; // Clave para tu lógica futura

            if (!$item->save())
                throw new \Exception('Error creando ítem.');

            $transaction->commit();

            // Redirigir al pago
            return $this->redirect(['orders/view', 'id' => $order->id]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Error: ' . $e->getMessage());
            // Redirige a donde tengas el listado de mis servicios
            return $this->redirect(['index']);
        }
    }

    /**
     * Genera una orden única para múltiples servicios seleccionados via Checkbox
     * También calcula precios dinámicos (Renovación vs Restauración)
     */
    public function actionBatchRenew()
    {
        $selection = Yii::$app->request->post('selection');

        if (empty($selection)) {
            Yii::$app->session->setFlash('warning', 'Selecciona al menos un servicio.');
            return $this->redirect(['index']);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {

            // A. Cabecera de Orden
            $order = new \app\models\Orders();
            $order->code = 'REN-MLT-' . date('Ymd-His');
            $order->customer_id = Yii::$app->user->identity->customer->id;
            $order->status = 0;
            $order->subtotal = 0;
            $order->total = 0;
            $order->created_at = date('Y-m-d H:i:s');

            $grandTotal = 0;

            if (!$order->save())
                throw new \Exception('Error iniciando la orden.');

            // B. Procesar cada servicio seleccionado
            foreach ($selection as $serviceId) {

                $service = \app\models\CustomerServices::findOne([
                    'id' => $serviceId,
                    'customer_id' => $order->customer_id
                ]);

                if (!$service)
                    continue;

                $product = $service->product;

                // --- LÓGICA DE PRECIOS DINÁMICA ---

                // 1. Precio Base: Renovación (o Normal si no hay precio de renovación definido)
                $finalPrice = $product->price_renewal > 0 ? $product->price_renewal : $product->price;
                $concept = $product->name . ' (Renovación) - ' . $service->domain;
                $actionType = 'renew';

                // 2. Lógica Especial para Dominios (Restauración)
                // Asumimos que el tipo de producto es 'domain' o verificamos si tiene extensión
                if ($product->type === 'domain') {

                    // Calculamos fechas
                    $dueDate = strtotime($service->next_due_date);
                    $today = time();

                    // Fecha límite para renovación normal: Vencimiento + 7 días de gracia
                    $restorationThreshold = strtotime('+7 days', $dueDate);

                    // Si HOY es mayor que la fecha límite (Vencimiento + 7 días)
                    if ($today > $restorationThreshold) {
                        if ($product->price_restoration > 0) {
                            $finalPrice = $product->price_restoration;
                            $concept = "RESTAURACIÓN DOMINIO (Vencido +7 días) - " . $service->domain;
                            $actionType = 'penalty';
                        }
                    }
                }

                // ------------------------------------

                $grandTotal += $finalPrice;

                // C. Crear Ítem
                $item = new \app\models\OrderItems();
                $item->order_id = $order->id;
                $item->service_id = $product->id;
                $item->service_name = $concept;
                $item->domain_name = $service->domain;
                $item->unit_price = $finalPrice;
                $item->total = $finalPrice;
                $item->action_type = $actionType; // 'renew' o 'penalty'

                if (!$item->save())
                    throw new \Exception('Error al agregar ítem: ' . $service->domain);
            }

            // D. Actualizar total
            $order->subtotal = $grandTotal;
            $order->total = $grandTotal;
            $order->save(false);

            $transaction->commit();
            return $this->redirect(['orders/view', 'id' => $order->id]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Error: ' . $e->getMessage());
            return $this->redirect(['index']);
        }
    }

    /**
     * Suspende/Desuspende servicio de hosting
     */
    public function actionToggle($id)
    {
        $model = $this->findModel($id);

        // 1. Validar tipo de producto
        if ($model->product->type !== 'hosting') {
            Yii::$app->session->setFlash('error', 'Error: El producto seleccionado no es un servicio de hosting.');
            return $this->redirect(Yii::$app->request->referrer);
        }

        // 2. Identificar el servidor y su tipo
        // Priorizamos el server_id del servicio (instancia física real)
        $server = $model->server;

        if (!$server) {
            $serverId = $model->server_id ?? $model->product->server_id;
            $server = \app\models\Servers::findOne($serverId);
        }

        if (!$server) {
            Yii::$app->session->setFlash('error', 'Error crítico: No se encuentra la configuración del servidor asociado.');
            return $this->redirect(Yii::$app->request->referrer);
        }

        // 3. Determinar acción y programa según el estado actual
        $isSuspending = ($model->status == 1);
        $provisionResult = ['success' => false, 'message' => ''];

        try {
            if ($server->type == 'virtualmin') {
                // Virtualmin usa programas distintos para cada acción
                $program = $isSuspending ? 'disable-domain' : 'enable-domain';

                $provisionResult = Yii::$app->virtualmin->sendCommandDynamic(
                    $server->username,
                    $server->auth_token,
                    $server->hostname,
                    $program,
                    ['domain' => $model->domain]
                );
            } elseif ($server->type == 'cyberpanel') {
                // Lógica para CyberPanel
                if ($isSuspending) {
                    $provisionResult['success'] = \app\components\CyberPanel::suspendAccount($server->id, $model->domain);
                } else {
                    $provisionResult['success'] = \app\components\CyberPanel::unsuspendAccount($server->id, $model->domain);
                }
            }
            // Aquí podrías añadir case 'cpanel' o 'plesk' fácilmente
        } catch (\Exception $e) {
            $provisionResult['message'] = $e->getMessage();
        }

        // 4. Actualizar Base de Datos si la API respondió con éxito
        if ($provisionResult['success']) {
            $model->status = $isSuspending ? 2 : 1;
            $model->save(false);

            $msg = $isSuspending ? "SUSPENDIDA" : "REACTIVADA";
            $type = $isSuspending ? 'warning' : 'success';

            Yii::$app->session->setFlash($type, "La cuenta <b>{$model->domain}</b> ha sido {$msg} en el servidor <b>{$server->name}</b> ({$server->type}).");
        } else {
            $errorMsg = $provisionResult['message'] ?: "Error en la comunicación con el API de {$server->type}.";
            Yii::$app->session->setFlash('error', "Falló la operación en el servidor físico. Detalle: {$errorMsg}");
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

}
