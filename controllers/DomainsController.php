<?php

namespace app\controllers;

use Yii;
use app\models\CustomerServices;
use app\models\DomainDnsRecords;
use app\services\NamecheapService;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use Exception;

class DomainsController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'dns-save' => ['POST'],
                    'dns-sync' => ['POST'],
                    'update-ns' => ['POST'],
                    'api-action' => ['POST'],
                    'get-pricing' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * List user domains
     */
    public function actionIndex()
    {
        $query = CustomerServices::find()
            ->joinWith('product p')
            ->where(['p.type' => 'domain']);

        if (!Yii::$app->user->identity->isAdmin) {
            $query->andWhere(['customer_services.customer_id' => Yii::$app->user->identity->realCustomerId]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Manage a specific domain
     */
    public function actionManage($id)
    {
        $model = $this->findModel($id);

        return $this->render('manage', [
            'model' => $model,
        ]);
    }

    /**
     * DNS Management Interface
     */
    public function actionDns($id)
    {
        $model = $this->findModel($id);
        
        $records = DomainDnsRecords::find()
            ->where(['customer_service_id' => $model->id])
            ->all();

        return $this->render('dns', [
            'model' => $model,
            'records' => $records,
        ]);
    }

    /**
     * Sync DNS records from Namecheap to local DB
     */
    public function actionDnsSync($id)
    {
        $model = $this->findModel($id);
        
        if (empty($model->domain)) {
            Yii::$app->session->setFlash('error', 'El servicio no tiene un nombre de dominio asignado.');
            return $this->redirect(['dns', 'id' => $id]);
        }

        try {
            $ncService = new NamecheapService();
            $hosts = $ncService->getHosts($model->domain);
            
            // Begin transaction
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Clear local records
                DomainDnsRecords::deleteAll(['customer_service_id' => $model->id]);
                
                // Insert new ones
                foreach ($hosts as $host) {
                    $record = new DomainDnsRecords();
                    $record->customer_service_id = $model->id;
                    $record->host = $host['Name'];
                    $record->record_type = $host['Type'];
                    $record->address = $host['Address'];
                    $record->mx_pref = !empty($host['MXPref']) ? (int)$host['MXPref'] : 10;
                    $record->ttl = !empty($host['TTL']) ? (int)$host['TTL'] : 1800;
                    $record->save(false);
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Registros DNS sincronizados exitosamente.');
            } catch (Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            Yii::$app->session->setFlash('error', 'Error sincronizando registros: ' . $e->getMessage());
        }

        return $this->redirect(['dns', 'id' => $id]);
    }

    /**
     * Save DNS records from form to local DB and Namecheap
     */
    public function actionDnsSave($id)
    {
        $model = $this->findModel($id);
        $postData = Yii::$app->request->post('DnsRecords', []);
        
        $recordsForNc = [];
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            DomainDnsRecords::deleteAll(['customer_service_id' => $model->id]);
            
            foreach ($postData as $i => $data) {
                if (empty($data['host']) || empty($data['address']) || empty($data['record_type'])) {
                    continue; // Skip incomplete records
                }
                
                $record = new DomainDnsRecords();
                $record->customer_service_id = $model->id;
                $record->host = $data['host'];
                $record->record_type = $data['record_type'];
                $record->address = $data['address'];
                $record->mx_pref = !empty($data['mx_pref']) ? (int)$data['mx_pref'] : 10;
                $record->ttl = !empty($data['ttl']) ? (int)$data['ttl'] : 1800;
                
                if (!$record->save()) {
                    throw new Exception("Error validando el registro: " . json_encode($record->getErrors()));
                }

                $recordsForNc[] = [
                    'HostName' => $record->host,
                    'RecordType' => $record->record_type,
                    'Address' => $record->address,
                    'MXPref' => $record->mx_pref,
                    'TTL' => $record->ttl,
                ];
            }
            
            // Push to DNS provider
            $ncService = new NamecheapService();
            if ($ncService->setHosts($model->domain, $recordsForNc)) {
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Registros DNS guardados exitosamente.');
            } else {
                throw new Exception("Error al procesar los registros con el proveedor.");
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Error al guardar registros: ' . $e->getMessage());
        }
        
        return $this->redirect(['dns', 'id' => $id]);
    }

    /**
     * Update NameServers
     */
    public function actionUpdateNs($id)
    {
        $model = $this->findModel($id);
        
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post('CustomerServices', []);
            $model->ns1 = $post['ns1'] ?? null;
            $model->ns2 = $post['ns2'] ?? null;
            $model->ns3 = $post['ns3'] ?? null;
            $model->ns4 = $post['ns4'] ?? null;
            
            if ($model->save(true, ['ns1', 'ns2', 'ns3', 'ns4'])) {
                try {
                    $ncService = new NamecheapService();
                    $nameservers = [$model->ns1, $model->ns2, $model->ns3, $model->ns4];
                    if ($ncService->setCustomNameservers($model->domain, $nameservers)) {
                        Yii::$app->session->setFlash('success', 'NameServers actualizados correctamente.');
                    } else {
                        Yii::$app->session->setFlash('warning', 'Guardado localmente, pero el proveedor reportó un error al actualizar los NameServers.');
                    }
                } catch (Exception $e) {
                    Yii::$app->session->setFlash('error', 'Guardado localmente, pero error de proveedor: ' . $e->getMessage());
                }
            } else {
                Yii::$app->session->setFlash('error', 'Error al guardar NameServers localmente.');
            }
        }
        
        return $this->redirect(['manage', 'id' => $id]);
    }

    /**
     * Admin action to register/renew directly via API
     */
    public function actionApiAction($id)
    {
        if (!Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException('No tienes permisos para realizar esta acción.');
        }

        $model = $this->findModel($id);
        
        if (Yii::$app->request->isPost) {
            $action = Yii::$app->request->post('api_action');
            $years = (int)Yii::$app->request->post('years', 1);
            $coupon = Yii::$app->request->post('coupon');
            
            try {
                $ncService = new NamecheapService();
                
                if ($action === 'renew') {
                    if ($ncService->renewDomain($model->domain, $years, $coupon)) {
                        Yii::$app->session->setFlash('success', "Dominio renovado exitosamente por $years año(s) en el proveedor.");
                    } else {
                        Yii::$app->session->setFlash('error', 'El proveedor reportó un error al intentar renovar el dominio.');
                    }
                } elseif ($action === 'register') {
                    if ($ncService->registerDomain($model->domain, $years, $model->customer, $coupon)) {
                        Yii::$app->session->setFlash('success', "Dominio registrado exitosamente por $years año(s) en el proveedor.");
                    } else {
                        Yii::$app->session->setFlash('error', 'El proveedor reportó un error al intentar registrar el dominio.');
                    }
                }
            } catch (Exception $e) {
                Yii::$app->session->setFlash('error', 'Error devuelto por la API: ' . $e->getMessage());
            }
        }
        
        return $this->redirect(['manage', 'id' => $id]);
    }

    /**
     * Get pricing via AJAX
     */
    public function actionGetPricing()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!Yii::$app->user->identity->isAdmin) {
            return ['success' => false, 'message' => 'Acceso denegado.'];
        }

        $domain = Yii::$app->request->post('domain');
        $action = Yii::$app->request->post('action', 'REGISTER');
        $years = (int)Yii::$app->request->post('years', 1);
        $coupon = Yii::$app->request->post('coupon');

        if (empty($domain)) {
            return ['success' => false, 'message' => 'El dominio es requerido.'];
        }

        try {
            $ncService = new NamecheapService();
            $prices = $ncService->getPricing($domain, $action, $coupon);
            
            if (isset($prices[$years])) {
                return [
                    'success' => true, 
                    'data' => $prices[$years]
                ];
            } else {
                return ['success' => false, 'message' => "No se encontró tarifa para $years año(s)."];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Finds the CustomerServices model based on its primary key value.
     */
    protected function findModel($id)
    {
        $query = CustomerServices::find()
            ->joinWith('product p')
            ->where(['customer_services.id' => $id, 'p.type' => 'domain']);
            
        if (!Yii::$app->user->identity->isAdmin) {
            $query->andWhere(['customer_services.customer_id' => Yii::$app->user->identity->realCustomerId]);
        }

        if (($model = $query->one()) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('El dominio no existe o no tienes permiso para verlo.');
    }
}
