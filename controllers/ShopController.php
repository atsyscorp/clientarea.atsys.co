<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Orders;
use app\models\Products;
use app\models\Customers;
use app\models\OrderItems;
use yii\filters\AccessControl;

class ShopController extends Controller
{
    /**
     * Permitimos acceso público a la tienda
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index', 'configure'], // Acciones protegidas o públicas
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'configure'], 
                        'roles' => ['?', '@'], // ? = Invitado, @ = Logueado (Todos)
                    ],
                ],
            ],
        ];
    }

    /**
     * Catálogo de Productos (Vitrina)
     */
    public function actionIndex()
    {
        // Traemos solo productos activos y ordenados por precio
        $products = Products::find()
            ->where(['status' => 1])
            ->orderBy(['price' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'products' => $products,
        ]);
    }

    /**
     * Paso 1: Configuración del Dominio
     * Aquí el cliente elige el dominio para el hosting que seleccionó.
     */
    public function actionConfigure($id)
    {
        // 1. Buscamos el producto principal (Hosting)
        $product = Products::findOne($id);
        
        if (!$product || !$product->status) {
            throw new \yii\web\NotFoundHttpException("El producto seleccionado no está disponible.");
        }

        // 2. Modelo dinámico para validar el formulario sin crear una tabla nueva
        $model = new \yii\base\DynamicModel(['domain', 'extension', 'action']);
        $model->addRule(['domain', 'action'], 'required')
              ->addRule('domain', 'string', ['min' => 3])
              ->addRule('action', 'in', ['range' => ['register', 'transfer', 'own']])
              ->addRule('extension', 'string'); // Ej: .com, .co

        // 3. Procesar formulario cuando el usuario da clic en "Continuar"
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            
            $domainPrice = 0;
            $tldId = null;
            $domainActionLabel = 'Propio (DNS)';

            // A. Lógica de Precio del Dominio
            // Solo cobramos si es registro o transferencia
            if ($model->action == 'register' || $model->action == 'transfer') {
                
                // Buscamos el producto TLD en la base de datos (Ej: busca ".com")
                // Usamos 'like' para asegurar que encuentre ".com" aunque se llame "Dominio .com"
                $tldProduct = Products::find()
                    ->where(['like', 'name', $model->extension]) 
                    ->andWhere(['type' => 'domain']) // Asegúrate de tener este campo o quita esta línea si no lo usas
                    ->one();

                if ($tldProduct) {
                    $domainPrice = $tldProduct->price;
                    $tldId = $tldProduct->id;
                    $domainActionLabel = ($model->action == 'register') ? 'Registro' : 'Transferencia';
                }
            }

            // B. Calcular el Total
            $totalPrice = $product->price + $domainPrice;

            // C. Armar el nombre completo del dominio
            // Si elige "propio", tomamos el input. Si es registro, unimos nombre + extensión.
            $fullDomainName = strtolower($model->domain . $model->extension);

            // D. GUARDAR EN SESIÓN (ESTANDARIZADO)
            // Aquí es donde arreglamos el error de la pantalla en blanco
            Yii::$app->session->set('cart_order', [
                
                // DATOS DEL HOSTING (Producto Principal)
                'product_id' => $product->id,
                'product_name' => $product->name,
                'hosting_price' => $product->price, // Variable clave para el checkout
                
                // DATOS DEL DOMINIO (Complemento)
                'domain' => $fullDomainName,
                'domain_action' => $model->action, // register, transfer, own
                'domain_price' => $domainPrice,    // Variable clave para el checkout
                'domain_label' => $domainActionLabel,
                'tld_id' => $tldId,
                
                // TOTALES
                'total' => $totalPrice
            ]);

            // Redirigimos al Checkout para pagar
            return $this->redirect(['checkout']);
        }

        // Renderizar la vista si no se ha enviado el formulario
        return $this->render('configure', [
            'product' => $product,
            'model' => $model,
        ]);
    }

    /**
     * Paso 2: Checkout (Login o Confirmación)
     */
    public function actionCheckout()
    {
        // 1. Validar carrito
        $cart = Yii::$app->session->get('cart_order');
        if (!$cart) return $this->redirect(['index']);

        // 2. Validar Login (Igual que antes...)
        if (Yii::$app->user->isGuest) {
            return $this->render('checkout', [
                'cart' => $cart, 
                'isGuest' => true, 
                'modelLogin' => new \app\models\LoginForm()
            ]);
        }
        
        $customer = \app\models\Customers::findOne(['user_id' => Yii::$app->user->id]);
        if (!$customer) return $this->redirect(['customers/create']);

        // 3. PROCESAR ORDEN (POST)
        if (Yii::$app->request->isPost) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // A. CREAR ORDEN (Usamos 'total', NO 'price')
                $order = new Orders();
                $order->code = 'ORD-' . date('Ymd') . '-' . rand(100,999);
                $order->customer_id = $customer->id;
                $order->subtotal = $cart['total']; // Usamos el TOTAL calculado
                $order->total = $cart['total'];
                $order->status = 0; // Pendiente
                $order->created_at = date('Y-m-d H:i:s');
                
                if (!$order->save()) throw new \Exception('Error al crear orden.');

                // B. ÍTEM 1: EL HOSTING (Usamos 'hosting_price')
                if($cart['hosting_price'] > 0) {
                    $itemHosting = new OrderItems();
                    $itemHosting->order_id = $order->id;
                    $itemHosting->service_id = $cart['product_id'];
                    $itemHosting->service_name = $cart['product_name'];
                    $itemHosting->unit_price = $cart['hosting_price'];
                    $itemHosting->total = $cart['hosting_price'];
                    $itemHosting->action_type = 'hosting_setup';
                    // Guardamos el dominio como referencia técnica, aunque no se cobre aquí
                    $itemHosting->domain_name = $cart['domain']; 
                    if (!$itemHosting->save()) throw new \Exception('Error al agregar hosting.' . json_encode($itemHosting->getErrors()));
                }

                // C. ÍTEM 2: EL DOMINIO (Solo si tiene costo > 0)
                if ($cart['domain_price'] > 0) {
                    $itemDomain = new OrderItems();
                    $itemDomain->order_id = $order->id;
                    // Si tienes ID de producto TLD úsalo, si no, usa el del hosting como fallback
                    $itemDomain->service_id = $cart['tld_id'] ?? $cart['product_id']; 
                    $itemDomain->service_name = 'Dominio: ' . $cart['domain'];
                    $itemDomain->domain_name = $cart['domain'];
                    $itemDomain->unit_price = $cart['domain_price']; // PRECIO ESPECÍFICO
                    $itemDomain->total = $cart['domain_price'];
                    $itemDomain->action_type = $cart['domain_action']; // register / transfer
                    
                    if (!$itemDomain->save()) throw new \Exception('Error al agregar dominio.');
                }

                $transaction->commit();
                Yii::$app->session->remove('cart_order');
                
                // Redirigir a la vista de la orden (donde pagará)
                return $this->redirect(['orders/view', 'id' => $order->id]);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        // Renderizar vista
        return $this->render('checkout', [
            'cart' => $cart,
            'isGuest' => false,
            'customer' => $customer,
        ]);
    }

    /**
     * Elimina SOLO el dominio del carrito
     */
    public function actionRemoveDomain()
    {
        $cart = Yii::$app->session->get('cart_order');
        if ($cart) {
            // Reseteamos valores del dominio
            $cart['domain'] = 'No seleccionado';
            $cart['domain_price'] = 0;
            $cart['domain_action'] = 'none';
            $cart['tld_id'] = null;
            
            // Recalculamos el total (Solo queda el hosting)
            $cart['total'] = $cart['hosting_price'];
            
            // Guardamos cambios
            Yii::$app->session->set('cart_order', $cart);
        }
        return $this->redirect(['checkout']);
    }

    /**
     * Cancela toda la orden (Vaciar Carrito)
     */
    public function actionClearCart()
    {
        Yii::$app->session->remove('cart_order');
        Yii::$app->session->setFlash('info', 'Has vaciado tu carrito de compras.');
        return $this->redirect(['index']); // Vuelve a la vitrina
    }

}