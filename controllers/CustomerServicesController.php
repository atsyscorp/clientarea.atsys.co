<?php

namespace app\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use app\models\Orders;
use app\models\Products;
use app\models\Customers;
use app\models\OrderItems;
use app\models\CustomerServices;
use app\models\CustomerServicesSearch;

class CustomerServicesController extends \yii\web\Controller
{

    public function actionIndex()
    {
        $searchModel = new \app\models\CustomerServicesSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate($customer_id = null)
    {
        $model = new CustomerServices();

        if ($customer_id) {
            $model->customer_id = $customer_id;
        }

        $model->start_date = date('Y-m-d');
        $model->next_due_date = date('Y-m-d', strtotime('+1 year'));
        $model->status = 1;

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                if ($model->product->type == 'hosting') {
                            
                    // 1. Preparar datos para el aprovisionamiento
                    $product = $model->product_id; // Asegúrate de tener la relación en OrderItems
                    $customer = $customer_id;

                    // Generar usuario y contraseña seguros para el cliente
                    // Usuario: primeras 8 letras del dominio o nombre limpio
                    $panelUser = substr(preg_replace('/[^a-zA-Z0-9]/', '', explode('.', $model->domain)[0]), 0, 8) . rand(10,99);
                    $panelPass = Yii::$app->security->generateRandomString(12); // Clave fuerte
                    
                    // 2. Llamar al Helper de CyberPanel
                    $provisionResult = \app\components\CyberPanel::createAccount(
                        $model->product->server_id,      // ID del servidor (debe estar en products)
                        $model->domain,                  // Dominio comprado
                        $model->product->server_package, // Paquete (ej: Default)
                        $model->customer->email,         // Email dueño
                        $panelPass,                      // Password generado
                        $panelUser                       // Usuario generado
                    );

                    // 3. Si se creó bien en el servidor, guardamos el registro local
                    if ($provisionResult['success']) {
                        $serviceProvisions = CustomerServices::findOne($model->id);
                        $serviceProvisions->password_service = $panelPass;
                        $serviceProvisions->username_service = $panelUser;
                        $serviceProvisions->server_id = $serviceProvisions->product->server_id;
                        $serviceProvisions->save(false);
                        if(!(null !== $this->request->post('silent') && $this->request->post('silent') == '1')) $this->sendServiceActivationEmail($serviceProvisions);
                    } else {
                        // Si falló CyberPanel (ej: dominio ya existe), lo registramos pero marcamos error
                        Yii::error("Fallo aprovisionamiento CyberPanel: " . $provisionResult['message']);
                        Yii::$app->session->setFlash('warning', 'Pago recibido, pero hubo un retraso activando el hosting. Soporte lo revisará manualmente.');
                        // Aquí podrías crear un Ticket automático para ti mismo (Support Ticket)
                    }
                }

                if ($customer_id) {
                    Yii::$app->session->setFlash('success', 'Servicio asignado y notificación enviada.');

                    return $this->redirect(['customers/view', 'id' => $customer_id]);
                }
                return $this->redirect(['index']);
            }
        }

        $customers = \yii\helpers\ArrayHelper::map( Customers::find()->all(), 'id', 'name');
        $products = \yii\helpers\ArrayHelper::map(
            Products::find()->where(['status' => 1])->all(), 
            'id', 
            function($product) {
                return $product->name . ' (Renov: ' . Yii::$app->formatter->asCurrency($product->price_renewal) . ')';
            }
        );

        return $this->render('create', [
            'model' => $model,
            'customers' => $customers,
            'products' => $products,
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
                    } 
                    elseif ($model->status == 'active') {
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
            function($product) {
                return $product->name . ' (Renov: ' . Yii::$app->formatter->asCurrency($product->price_renewal) . ')';
            }
        );

        return $this->render('update', [
            'model' => $model,
            'customers' => $customers,
            'products' => $products,
        ]);
    }

    /**
     * Función privada para enviar el correo
     */
    protected function sendServiceActivationEmail($service)
    {
        $clientEmail = $service->customer->email ?? null; // Asumiendo que Customer tiene email
        if (!$clientEmail) return;

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
            $order->code = 'REN-' . date('Ymd') . '-' . rand(100,999);
            $order->customer_id = $service->customer_id;
            
            // Usamos el precio del producto base para la renovación
            // Asegúrate que en CustomerServices tengas la relación 'product' definida:
            // public function getProduct() { return $this->hasOne(Products::class, ['id' => 'product_id']); }
            $renewalPrice = $service->product->price; 

            $order->subtotal = $renewalPrice;
            $order->total = $renewalPrice;
            $order->status = 0; // Pendiente
            $order->created_at = date('Y-m-d H:i:s');
            
            if (!$order->save()) throw new \Exception('Error creando orden.');

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
            
            if (!$item->save()) throw new \Exception('Error creando ítem.');

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

            if (!$order->save()) throw new \Exception('Error iniciando la orden.');

            // B. Procesar cada servicio seleccionado
            foreach ($selection as $serviceId) {
                
                $service = \app\models\CustomerServices::findOne([
                    'id' => $serviceId, 
                    'customer_id' => $order->customer_id
                ]);

                if (!$service) continue;

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

                if (!$item->save()) throw new \Exception('Error al agregar ítem: ' . $service->domain);
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

        // 2. Obtener datos clave
        $domain = $model->domain;
        
        // TRUCO: Usamos el operador de fusión de null (??) correctamente.
        // Intenta usar el server_id del servicio; si es null, usa el del producto.
        $serverId = $model->server_id ?? $model->product->server_id;

        if (!$serverId) {
            Yii::$app->session->setFlash('error', 'Error crítico: No se encuentra el ID del servidor asociado.');
            return $this->redirect(Yii::$app->request->referrer);
        }

        // 3. Lógica de Toggle (API Primero, BD después)
        // Asumimos: 1 = Activo, 2 = Suspendido
        
        if ($model->status == 1) {
            // --- ACCIÓN: SUSPENDER ---
            $apiResult = \app\components\CyberPanel::suspendAccount($serverId, $domain);
            
            if ($apiResult) {
                $model->status = 2; // Cambiar a Suspendido
                $model->save(false); // Guardamos sin validar para agilidad
                Yii::$app->session->setFlash('warning', "La cuenta de hosting <b>{$domain}</b> ha sido SUSPENDIDA.");
            } else {
                Yii::$app->session->setFlash('error', "Falló la suspensión en el servidor. El estado local no se cambió.");
            }

        } else {
            // --- ACCIÓN: REACTIVAR ---
            $apiResult = \app\components\CyberPanel::unsuspendAccount($serverId, $domain);
            
            if ($apiResult) {
                $model->status = 1; // Cambiar a Activo
                $model->save(false);
                Yii::$app->session->setFlash('success', "La cuenta de hosting <b>{$domain}</b> ha sido REACTIVADA.");
            } else {
                Yii::$app->session->setFlash('error', "Falló la reactivación en el servidor. Revisa la conexión con CyberPanel.");
            }
        }

        // 4. Redirección segura
        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

}
