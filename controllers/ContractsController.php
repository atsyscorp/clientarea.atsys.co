<?php

namespace app\controllers;

use Yii;
use app\models\Contracts;
use app\models\ContractsSearch;
use app\models\ContractTasks;
use app\models\ContractDocuments;
use app\models\WorkOrders;
use app\models\Customers;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use app\models\Notifications;

class ContractsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Todos los usuarios autenticados (Clientes y Admins)
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new ContractsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;

        // Si no es admin, validar propiedad del contrato y que no sea borrador
        if (!$user->isAdmin) {
            $customerId = $user->getRealCustomerId();
            if ($model->customer_id != $customerId || $model->status == Contracts::STATUS_DRAFT) {
                throw new NotFoundHttpException('El contrato solicitado no existe o no tiene permisos para verlo.');
            }
        }

        $newTask = new ContractTasks();
        $newTask->contract_id = $model->id;

        $newDoc = new ContractDocuments();
        $newDoc->contract_id = $model->id;

        return $this->render('view', [
            'model' => $model,
            'newTask' => $newTask,
            'newDoc' => $newDoc,
        ]);
    }

    public function actionCreate()
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Solo los administradores pueden crear contratos.');
            return $this->redirect(['index']);
        }

        $model = new Contracts();
        if (empty($model->code)) {
            $model->code = Contracts::generateNextCode();
        }

        // Preselección de cliente si viene query param customer_id
        $customerId = Yii::$app->request->get('customer_id');
        if ($customerId) {
            $model->customer_id = $customerId;
        }

        if ($model->load(Yii::$app->request->post())) {
            $file = UploadedFile::getInstance($model, 'attachmentFile');
            if ($file) {
                $uploadDir = Yii::getAlias('@webroot/uploads/contracts/');
                if (!is_dir($uploadDir)) {
                    FileHelper::createDirectory($uploadDir);
                }
                $fileName = 'contract_' . time() . '_' . rand(1000, 9999) . '.' . $file->extension;
                $filePath = $uploadDir . $fileName;
                if ($file->saveAs($filePath)) {
                    $model->contract_file = '/uploads/contracts/' . $fileName;
                }
            }

            if ($model->save()) {
                if ($model->status != Contracts::STATUS_DRAFT) {
                    Notifications::notifyCustomer(
                        $model->customer_id,
                        "📜 Nuevo Contrato: " . $model->code,
                        "Se ha registrado un nuevo contrato para tu empresa: " . $model->title,
                        "/contracts/view?id=" . $model->id,
                        Notifications::TYPE_SUCCESS
                    );
                    $this->sendContractEmail($model);
                }
                Yii::$app->session->setFlash('success', 'Contrato registrado con éxito. Código: ' . $model->code);
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $customersList = Customers::find()->select(['business_name', 'id'])->indexBy('id')->column();

        return $this->render('create', [
            'model' => $model,
            'customersList' => $customersList,
        ]);
    }

    public function actionUpdate($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Solo los administradores pueden modificar contratos.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $file = UploadedFile::getInstance($model, 'attachmentFile');
            if ($file) {
                $uploadDir = Yii::getAlias('@webroot/uploads/contracts/');
                if (!is_dir($uploadDir)) {
                    FileHelper::createDirectory($uploadDir);
                }
                $fileName = 'contract_' . time() . '_' . rand(1000, 9999) . '.' . $file->extension;
                $filePath = $uploadDir . $fileName;
                if ($file->saveAs($filePath)) {
                    $model->contract_file = '/uploads/contracts/' . $fileName;
                }
            }

            if ($model->save()) {
                $model->recalculateProgress();

                // Notificación en plataforma para el Cliente
                if ($model->status != Contracts::STATUS_DRAFT) {
                    Notifications::notifyCustomer(
                        $model->customer_id,
                        "📜 Contrato Actualizado: " . $model->code,
                        "Tu contrato '" . $model->title . "' ha sido actualizado. Avance global: " . number_format($model->progress_percentage, 1) . "%.",
                        "/contracts/view?id=" . $model->id,
                        Notifications::TYPE_INFO
                    );
                    $this->sendContractEmail($model);
                }

                Yii::$app->session->setFlash('success', 'Contrato actualizado correctamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $customersList = Customers::find()->select(['business_name', 'id'])->indexBy('id')->column();

        return $this->render('update', [
            'model' => $model,
            'customersList' => $customersList,
        ]);
    }

    public function actionDelete($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Acción no permitida.');
            return $this->redirect(['index']);
        }

        $model = $this->findModel($id);
        $model->delete();

        Yii::$app->session->setFlash('success', 'El contrato fue eliminado exitosamente.');
        return $this->redirect(['index']);
    }

    public function actionAddTask($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Solo los administradores pueden agregar tareas/hitos al contrato.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $contract = $this->findModel($id);
        $task = new ContractTasks();
        $task->contract_id = $contract->id;

        if ($task->load(Yii::$app->request->post()) && $task->save()) {
            $contract->recalculateProgress();
            $contract->refresh();

            if ($contract->status != Contracts::STATUS_DRAFT) {
                Notifications::notifyCustomer(
                    $contract->customer_id,
                    "🚀 Nuevo Hito en Contrato: " . $contract->code,
                    "Se registró el hito '" . $task->title . "'. Avance global del contrato: " . number_format($contract->progress_percentage, 1) . "%.",
                    "/contracts/view?id=" . $contract->id,
                    Notifications::TYPE_INFO
                );
            }

            Yii::$app->session->setFlash('success', 'Tarea / Hito agregado exitosamente al contrato.');
        } else {
            Yii::$app->session->setFlash('error', 'Error al guardar la tarea. Revisa los datos.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionUpdateTask($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Solo los administradores pueden modificar tareas.');
            return $this->redirect(['index']);
        }

        $task = ContractTasks::findOne($id);
        if (!$task) {
            throw new NotFoundHttpException('La tarea especificada no existe.');
        }

        if ($task->load(Yii::$app->request->post()) && $task->save()) {
            if ($task->contract) {
                $task->contract->recalculateProgress();
                $task->contract->refresh();

                if ($task->contract->status != Contracts::STATUS_DRAFT) {
                    Notifications::notifyCustomer(
                        $task->contract->customer_id,
                        "🚀 Avance de Hito en Contrato: " . $task->contract->code,
                        "Se actualizó el hito '" . $task->title . "' (" . number_format($task->progress_percentage, 1) . "%). Avance global: " . number_format($task->contract->progress_percentage, 1) . "%.",
                        "/contracts/view?id=" . $task->contract->id,
                        Notifications::TYPE_INFO
                    );
                }
            }
            Yii::$app->session->setFlash('success', 'Tarea actualizada correctamente.');
        } else {
            Yii::$app->session->setFlash('error', 'Error al actualizar la tarea.');
        }

        return $this->redirect(['view', 'id' => $task->contract_id]);
    }

    public function actionDeleteTask($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Acción no permitida.');
            return $this->redirect(['index']);
        }

        $task = ContractTasks::findOne($id);
        if ($task) {
            $contractId = $task->contract_id;
            $task->delete();
            $contract = Contracts::findOne($contractId);
            if ($contract) {
                $contract->recalculateProgress();
            }
            Yii::$app->session->setFlash('success', 'Tarea eliminada del contrato.');
            return $this->redirect(['view', 'id' => $contractId]);
        }

        return $this->redirect(['index']);
    }

    public function actionUploadDocument($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Solo administradores pueden adjuntar documentos.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $contract = $this->findModel($id);
        $files = UploadedFile::getInstancesByName('docFiles');
        // Fallback si enviaron solo uno con docFile
        if (empty($files)) {
            $singleFile = UploadedFile::getInstanceByName('docFile');
            if ($singleFile) {
                $files = [$singleFile];
            }
        }

        $titles = Yii::$app->request->post('doc_titles', []);
        $singleTitle = Yii::$app->request->post('doc_title');
        if (empty($titles) && !empty($singleTitle)) {
            $titles = [$singleTitle];
        }

        $uploadedCount = 0;

        if (!empty($files)) {
            $uploadDir = Yii::getAlias('@webroot/uploads/contracts/docs/');
            if (!is_dir($uploadDir)) {
                FileHelper::createDirectory($uploadDir);
            }

            foreach ($files as $i => $file) {
                $fileName = 'doc_' . time() . '_' . $i . '_' . rand(1000, 9999) . '.' . $file->extension;
                $filePath = $uploadDir . $fileName;

                if ($file->saveAs($filePath)) {
                    $doc = new ContractDocuments();
                    $doc->contract_id = $contract->id;
                    $docTitle = isset($titles[$i]) && !empty(trim($titles[$i])) ? trim($titles[$i]) : $file->name;
                    $doc->title = $docTitle;
                    $doc->file_url = '/uploads/contracts/docs/' . $fileName;
                    if ($doc->save()) {
                        $uploadedCount++;
                    }
                }
            }

            if ($uploadedCount > 0) {
                if ($contract->status != Contracts::STATUS_DRAFT) {
                    Notifications::notifyCustomer(
                        $contract->customer_id,
                        "📄 Nuevo(s) Documento(s) en Contrato: " . $contract->code,
                        "Se han adjuntado " . $uploadedCount . " documento(s) anexo(s) en tu contrato " . $contract->code . ".",
                        "/contracts/view?id=" . $contract->id,
                        Notifications::TYPE_INFO
                    );
                }
                Yii::$app->session->setFlash('success', "Se cargaron $uploadedCount documento(s) anexo(s) correctamente.");
            } else {
                Yii::$app->session->setFlash('error', 'No se pudieron guardar los archivos subidos.');
            }
        } else {
            Yii::$app->session->setFlash('error', 'No se seleccionó ningún archivo.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionDeleteDocument($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Acción no permitida.');
            return $this->redirect(['index']);
        }

        $doc = ContractDocuments::findOne($id);
        if ($doc) {
            $contractId = $doc->contract_id;
            $filePath = Yii::getAlias('@webroot' . $doc->file_url);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $doc->delete();
            Yii::$app->session->setFlash('success', 'Documento anexo eliminado.');
            return $this->redirect(['view', 'id' => $contractId]);
        }

        return $this->redirect(['index']);
    }

    public function actionRecalculateProgress($id)
    {
        $contract = $this->findModel($id);
        $contract->recalculateProgress();
        Yii::$app->session->setFlash('info', 'Porcentaje de avance recalculado.');
        return $this->redirect(['view', 'id' => $id]);
    }

    protected function sendContractEmail($model)
    {
        if (!$model->customer || empty($model->customer->email)) {
            return false;
        }

        try {
            $mailer = Yii::$app->mailer->compose(['html' => 'contract_notification-html'], ['model' => $model])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setTo($model->customer->email)
                ->setBcc(Yii::$app->params['adminEmail'])
                ->setSubject("📜 Nuevo Contrato Activo: " . $model->code . " - " . $model->title);

            if ($model->contract_file) {
                $filePath = Yii::getAlias('@webroot' . $model->contract_file);
                if (file_exists($filePath)) {
                    $mailer->attach($filePath);
                }
            }

            return $mailer->send();
        } catch (\Exception $e) {
            Yii::error("Error enviando email de contrato " . $model->code . ": " . $e->getMessage());
            return false;
        }
    }

    protected function findModel($id)
    {
        if (($model = Contracts::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('El contrato solicitado no existe.');
    }
}
