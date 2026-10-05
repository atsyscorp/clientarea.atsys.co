<?php

namespace app\controllers;

use yii\helpers\Html;

use Yii;
use app\models\Contracts;
use app\models\ContractsSearch;
use app\models\ContractTasks;
use app\models\ContractDocuments;
use app\models\ContractTaskFiles;
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

        // Si el cálculo es automático, sincronizar avance para asegurar coherencia y corregir desvíos previos
        if ($model->progress_mode == Contracts::PROGRESS_MODE_AUTO) {
            $prevProgress = $model->progress_percentage;
            $model->recalculateProgress();
            if ($model->progress_percentage != $prevProgress) {
                $model->refresh();
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
                if ($file->hasError) {
                    $desc = ($file->error === UPLOAD_ERR_INI_SIZE)
                        ? 'El archivo del contrato excede el tamaño máximo permitido por PHP (' . ini_get('upload_max_filesize') . ').'
                        : 'Error al subir el archivo del contrato (código ' . $file->error . ').';
                    Yii::$app->session->setFlash('error', $desc);
                } else {
                    $uploadUrl = null;
                    if (Yii::$app->has('googleDrive')) {
                        try {
                            $uploadUrl = Yii::$app->googleDrive->upload($file, $model->code, 'contracts');
                        } catch (\Exception $e) {
                            Yii::error("Google Drive contract upload error: " . $e->getMessage(), __METHOD__);
                        }
                    }
                    if (!$uploadUrl) {
                        $uploadDir = Yii::getAlias('@webroot/uploads/contracts/');
                        if (!is_dir($uploadDir)) {
                            FileHelper::createDirectory($uploadDir, 0777);
                        }
                        $fileName = 'contract_' . time() . '_' . rand(1000, 9999) . '.' . ($file->extension ?: 'pdf');
                        $filePath = $uploadDir . $fileName;
                        if ($file->saveAs($filePath)) {
                            $uploadUrl = '/uploads/contracts/' . $fileName;
                        } else {
                            Yii::$app->session->setFlash('error', 'No se pudo guardar el archivo del contrato en el servidor. Verifique los permisos de escritura.');
                        }
                    }
                    if ($uploadUrl) {
                        $model->contract_file = $uploadUrl;
                    }
                }
            }

            if ($model->save()) {
                if ($model->status != Contracts::STATUS_DRAFT) {
                    Notifications::notifyCustomer(
                        $model->customer_id,
                        "Nuevo Contrato: " . $model->code,
                        "Se ha registrado un nuevo contrato para tu empresa: " . $model->title,
                        "/contracts/view?id=" . $model->id,
                        Notifications::TYPE_SUCCESS
                    );
                    $this->sendContractEmail($model);
                }
                Yii::$app->session->setFlash('success', 'Contrato registrado con éxito. Código: ' . Html::encode($model->code));
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
                if ($file->hasError) {
                    $desc = ($file->error === UPLOAD_ERR_INI_SIZE)
                        ? 'El archivo del contrato excede el tamaño máximo permitido por PHP (' . ini_get('upload_max_filesize') . ').'
                        : 'Error al subir el archivo del contrato (código ' . $file->error . ').';
                    Yii::$app->session->setFlash('error', $desc);
                } else {
                    $uploadUrl = null;
                    if (Yii::$app->has('googleDrive')) {
                        try {
                            $uploadUrl = Yii::$app->googleDrive->upload($file, $model->code, 'contracts');
                        } catch (\Exception $e) {
                            Yii::error("Google Drive contract upload error: " . $e->getMessage(), __METHOD__);
                        }
                    }
                    if (!$uploadUrl) {
                        $uploadDir = Yii::getAlias('@webroot/uploads/contracts/');
                        if (!is_dir($uploadDir)) {
                            FileHelper::createDirectory($uploadDir, 0777);
                        }
                        $fileName = 'contract_' . time() . '_' . rand(1000, 9999) . '.' . ($file->extension ?: 'pdf');
                        $filePath = $uploadDir . $fileName;
                        if ($file->saveAs($filePath)) {
                            $uploadUrl = '/uploads/contracts/' . $fileName;
                        } else {
                            Yii::$app->session->setFlash('error', 'No se pudo guardar el archivo del contrato en el servidor. Verifique los permisos de escritura.');
                        }
                    }
                    if ($uploadUrl) {
                        $model->contract_file = $uploadUrl;
                    }
                }
            }

            if ($model->save()) {
                $model->recalculateProgress();

                // Notificación en plataforma para el Cliente
                if ($model->status != Contracts::STATUS_DRAFT) {
                    Notifications::notifyCustomer(
                        $model->customer_id,
                        "Contrato Actualizado: " . $model->code,
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
            // Procesar evidencias adjuntas si las hay
            $evidenceFiles = UploadedFile::getInstancesByName('taskEvidenceFiles');
            if (empty($evidenceFiles)) {
                $evidenceFiles = UploadedFile::getInstances($task, 'evidenceFiles');
            }
            if (!empty($evidenceFiles)) {
                $uploadResult = $this->uploadTaskFiles($task, $evidenceFiles, $contract);
                if (!empty($uploadResult['errors'])) {
                    Yii::$app->session->setFlash('warning', 'Hito registrado, pero hubo problemas con algunos archivos de evidencia: <br>' . implode('<br>', $uploadResult['errors']));
                }
            }

            $contract->recalculateProgress();
            $contract->refresh();

            if ($contract->status != Contracts::STATUS_DRAFT) {
                Notifications::notifyCustomer(
                    $contract->customer_id,
                    "Nuevo Hito en Contrato: " . $contract->code,
                    "Se registró el hito '" . $task->title . "'. Avance global del contrato: " . number_format($contract->progress_percentage, 1) . "%.",
                    "/contracts/view?id=" . $contract->id,
                    Notifications::TYPE_INFO
                );

                if (!empty($task->notify_email)) {
                    $this->sendTaskEmail($task, 'created');
                }
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
                // Procesar nuevas evidencias adjuntas si las hay
                $evidenceFiles = UploadedFile::getInstancesByName('taskEvidenceFiles');
                if (empty($evidenceFiles)) {
                    $evidenceFiles = UploadedFile::getInstances($task, 'evidenceFiles');
                }
                if (!empty($evidenceFiles)) {
                    $uploadResult = $this->uploadTaskFiles($task, $evidenceFiles, $task->contract);
                    if (!empty($uploadResult['errors'])) {
                        Yii::$app->session->setFlash('warning', 'Hito actualizado, pero hubo problemas con algunos archivos de evidencia: <br>' . implode('<br>', $uploadResult['errors']));
                    }
                }

                $task->contract->recalculateProgress();
                $task->contract->refresh();

                if ($task->contract->status != Contracts::STATUS_DRAFT) {
                    Notifications::notifyCustomer(
                        $task->contract->customer_id,
                        "Avance de Hito en Contrato: " . $task->contract->code,
                        "Se actualizó el hito '" . $task->title . "' (" . number_format($task->progress_percentage, 1) . "%). Avance global: " . number_format($task->contract->progress_percentage, 1) . "%.",
                        "/contracts/view?id=" . $task->contract->id,
                        Notifications::TYPE_INFO
                    );

                    if (!empty($task->notify_email)) {
                        $actionType = ($task->status == ContractTasks::STATUS_COMPLETED || floatval($task->progress_percentage) >= 100) ? 'completed' : 'updated';
                        $this->sendTaskEmail($task, $actionType);
                    }
                }
            }
            Yii::$app->session->setFlash('success', 'Tarea actualizada correctamente.');
        } else {
            Yii::$app->session->setFlash('error', 'Error al actualizar la tarea.');
        }

        return $this->redirect(['view', 'id' => $task->contract_id]);
    }

    public function actionSendTaskEmail($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Solo los administradores pueden enviar notificaciones por correo.');
            return $this->redirect(['index']);
        }

        $task = ContractTasks::findOne($id);
        if (!$task) {
            throw new NotFoundHttpException('El hito solicitado no existe.');
        }

        $targetEmail = Yii::$app->request->post('target_email');
        if (!empty($targetEmail) && strcasecmp(trim($targetEmail), 'hola@atsys.co') === 0) {
            Yii::$app->session->setFlash('error', 'No se permite enviar notificaciones a hola@atsys.co para evitar la creación automática de tickets.');
            return $this->redirect(['view', 'id' => $task->contract_id]);
        }
        $customMessage = Yii::$app->request->post('custom_message');

        $actionType = ($task->status == ContractTasks::STATUS_COMPLETED || floatval($task->progress_percentage) >= 100) ? 'completed' : 'manual';
        if ($this->sendTaskEmail($task, $actionType, $targetEmail, $customMessage)) {
            $destLabel = !empty($targetEmail) ? " a " . Html::encode($targetEmail) : " al cliente";
            Yii::$app->session->setFlash('success', "Notificación del hito '" . Html::encode($task->title) . "' enviada exitosamente{$destLabel} (y copia enviada a administración).");
        } else {
            Yii::$app->session->setFlash('error', "No se pudo enviar el correo del hito. Verifica que la dirección de correo sea válida.");
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

    public function actionUploadTaskFile($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Solo los administradores pueden adjuntar evidencias a los hitos.');
            return $this->redirect(['index']);
        }

        $task = ContractTasks::findOne($id);
        if (!$task) {
            throw new NotFoundHttpException('El hito solicitado no existe.');
        }

        $contract = $task->contract;
        if (!$contract) {
            throw new NotFoundHttpException('El contrato asociado no existe.');
        }

        $files = UploadedFile::getInstancesByName('taskEvidenceFiles');
        if (empty($files)) {
            $single = UploadedFile::getInstanceByName('taskEvidenceFile');
            if ($single) {
                $files = [$single];
            }
        }

        if (!empty($files)) {
            $result = $this->uploadTaskFiles($task, $files, $contract);
            if ($result['uploaded'] > 0) {
                Yii::$app->session->setFlash('success', "Se adjuntaron {$result['uploaded']} archivo(s) de evidencia al hito '" . Html::encode($task->title) . "'.");
            }
            if (!empty($result['errors'])) {
                Yii::$app->session->setFlash('error', implode('<br>', $result['errors']));
            }
        } else {
            Yii::$app->session->setFlash('error', 'No se seleccionó ningún archivo o superó el límite permitido por el servidor.');
        }

        return $this->redirect(['view', 'id' => $contract->id]);
    }

    public function actionDeleteTaskFile($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin) {
            Yii::$app->session->setFlash('error', 'Acción no permitida.');
            return $this->redirect(['index']);
        }

        $taskFile = ContractTaskFiles::findOne($id);
        if ($taskFile) {
            $contractId = $taskFile->contract_id;
            // Eliminar archivo local físico si corresponde
            $relativeUrl = preg_replace('#^https?://[^/]+#', '', $taskFile->file_url);
            if (strpos($relativeUrl, '/uploads/') === 0) {
                $filePath = Yii::getAlias('@webroot' . $relativeUrl);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            $taskFile->delete();
            Yii::$app->session->setFlash('success', 'Archivo de evidencia eliminado del hito.');
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
        $errors = [];

        if (!empty($files)) {
            foreach ($files as $i => $file) {
                if ($file->hasError) {
                    $errorDesc = match ($file->error) {
                        UPLOAD_ERR_INI_SIZE => "El archivo '{$file->name}' supera el tamaño máximo permitido por PHP (" . ini_get('upload_max_filesize') . ").",
                        UPLOAD_ERR_FORM_SIZE => "El archivo '{$file->name}' excede el límite del formulario.",
                        UPLOAD_ERR_PARTIAL => "La subida del archivo '{$file->name}' fue interrumpida.",
                        UPLOAD_ERR_NO_FILE => "No se recibió el archivo '{$file->name}'.",
                        UPLOAD_ERR_NO_TMP_DIR => "Falta la carpeta temporal en el servidor para almacenar la subida.",
                        UPLOAD_ERR_CANT_WRITE => "No se pudo escribir el archivo '{$file->name}' en el disco del servidor (permisos insuficientes).",
                        UPLOAD_ERR_EXTENSION => "Una extensión del servidor detuvo la subida del archivo '{$file->name}'.",
                        default => "Error en la subida del archivo '{$file->name}' (código PHP {$file->error}).",
                    };
                    $errors[] = $errorDesc;
                    Yii::error("actionUploadDocument file upload error for contract {$contract->code}: {$errorDesc}", __METHOD__);
                    continue;
                }

                $fileUrl = null;

                // 1. Intentar subir mediante GoogleDriveService (con fallback local automático si no hay credenciales)
                if (Yii::$app->has('googleDrive')) {
                    try {
                        $fileUrl = Yii::$app->googleDrive->upload($file, $contract->code, 'contracts');
                    } catch (\Exception $e) {
                        Yii::error("GoogleDrive upload exception: " . $e->getMessage(), __METHOD__);
                    }
                }

                // 2. Fallback local directo si GoogleDrive no devolvió URL
                if (!$fileUrl) {
                    $uploadDir = Yii::getAlias('@webroot/uploads/contracts/docs/');
                    if (!is_dir($uploadDir)) {
                        FileHelper::createDirectory($uploadDir, 0777);
                    }
                    $ext = !empty($file->extension) ? '.' . $file->extension : '';
                    $fileName = 'doc_' . time() . '_' . $i . '_' . rand(1000, 9999) . $ext;
                    $filePath = $uploadDir . $fileName;

                    if ($file->saveAs($filePath)) {
                        $fileUrl = '/uploads/contracts/docs/' . $fileName;
                    } else {
                        $isWritable = is_writable($uploadDir) ? 'sí' : 'no';
                        $errMsg = "No se pudo guardar el archivo '{$file->name}' en el servidor (Ruta: {$uploadDir}, Escritura permitida: {$isWritable}).";
                        $errors[] = $errMsg;
                        Yii::error("actionUploadDocument saveAs failed: {$errMsg}", __METHOD__);
                        continue;
                    }
                }

                // 3. Guardar registro en la base de datos
                $doc = new ContractDocuments();
                $doc->contract_id = $contract->id;
                $docTitle = isset($titles[$i]) && !empty(trim($titles[$i])) ? trim($titles[$i]) : $file->name;
                $doc->title = mb_substr($docTitle, 0, 255);
                $doc->file_url = $fileUrl;

                if ($doc->save()) {
                    $uploadedCount++;
                } else {
                    $dbErr = implode(', ', $doc->getFirstErrors());
                    $errors[] = "No se pudo vincular el documento '{$docTitle}': {$dbErr}";
                    Yii::error("ContractDocuments save failed: {$dbErr}", __METHOD__);
                    // Si el archivo quedó guardado localmente, eliminarlo para evitar huérfanos
                    $relativeUrl = preg_replace('#^https?://[^/]+#', '', $fileUrl);
                    if (strpos($relativeUrl, '/uploads/') === 0) {
                        @unlink(Yii::getAlias('@webroot' . $relativeUrl));
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
            }

            if (!empty($errors)) {
                Yii::$app->session->setFlash('error', implode('<br>', $errors));
            }
        } else {
            Yii::$app->session->setFlash('error', 'No se seleccionó ningún archivo o el tamaño excedió el límite total permitido por el servidor (post_max_size: ' . ini_get('post_max_size') . ').');
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
            // Eliminar archivo local si corresponde (relativo o absoluto con dominio)
            $relativeUrl = preg_replace('#^https?://[^/]+#', '', $doc->file_url);
            if (strpos($relativeUrl, '/uploads/') === 0) {
                $filePath = Yii::getAlias('@webroot' . $relativeUrl);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
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
            $replyToEmail = Yii::$app->params['departmentEmails']['commercial'] ?? 'hola@atsys.co';

            $mailer = Yii::$app->mailer->compose(['html' => 'contract_notification-html'], ['model' => $model])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setReplyTo($replyToEmail)
                ->setTo($model->customer->email)
                ->setSubject("Nuevo Contrato Activo: " . $model->code . " - " . $model->title);

            if ($model->contract_file) {
                $filePath = Yii::getAlias('@webroot' . $model->contract_file);
                if (file_exists($filePath)) {
                    $mailer->attach($filePath);
                }
            }

            return $mailer->send();
        } catch (\Throwable $e) {
            Yii::error("Error enviando email de contrato " . $model->code . ": " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía notificación por correo electrónico del hito al cliente y una copia de respaldo a administración.
     * @param ContractTasks $task
     * @param string $actionType 'created' | 'updated' | 'completed' | 'manual'
     * @param string|null $targetEmail Correo de destino opcional (por defecto el del cliente)
     * @param string|null $customMessage Mensaje adicional personalizado opcional
     * @return bool
     */
    protected function sendTaskEmail($task, $actionType = 'updated', $targetEmail = null, $customMessage = null)
    {
        $contract = $task->contract;
        if (!$contract || !$contract->customer) {
            return false;
        }

        $targetEmail = !empty($targetEmail) ? trim($targetEmail) : ($contract->customer->email ?? null);
        if (empty($targetEmail) || strcasecmp($targetEmail, 'hola@atsys.co') === 0) {
            return false;
        }

        try {
            $statusLabels = [
                ContractTasks::STATUS_PENDING => 'Pendiente',
                ContractTasks::STATUS_IN_PROGRESS => 'En Progreso',
                ContractTasks::STATUS_COMPLETED => 'Completada',
            ];
            $statusLabel = $statusLabels[$task->status] ?? 'Actualizado';

            if ($actionType === 'created') {
                $subject = "Nuevo Hito Registrado: " . $task->title . " | Contrato " . $contract->code;
            } elseif ($actionType === 'completed' || $task->status == ContractTasks::STATUS_COMPLETED) {
                $subject = "Hito Completado: " . $task->title . " | Contrato " . $contract->code;
            } else {
                $subject = "Avance en Hito: " . $task->title . " (" . number_format($task->progress_percentage, 1) . "%) | Contrato " . $contract->code;
            }

            $adminEmail = Yii::$app->params['adminEmail'] ?? 'gerencia@atsys.co';
            if (strcasecmp(trim($adminEmail), 'hola@atsys.co') === 0) {
                $adminEmail = 'gerencia@atsys.co';
            }

            $adminEmails = !empty($adminEmail)
                ? array_map('trim', explode(',', $adminEmail))
                : ['gerencia@atsys.co'];

            if (!empty(Yii::$app->user->identity->email)) {
                $userEmail = trim(Yii::$app->user->identity->email);
                if (strcasecmp($userEmail, 'hola@atsys.co') !== 0) {
                    $adminEmails[] = $userEmail;
                }
            }

            // Excluir estrictamente hola@atsys.co para evitar generación automática de tickets
            $adminEmails = array_values(array_unique(array_filter($adminEmails, function($email) {
                $clean = strtolower(trim($email));
                return !empty($clean) && $clean !== 'hola@atsys.co';
            })));

            if (empty($adminEmails)) {
                $adminEmails = ['gerencia@atsys.co'];
            }

            // 1. Enviar correo al destinatario (cliente o correo personalizado)
            $customerReplyTo = Yii::$app->params['departmentEmails']['commercial'] ?? 'hola@atsys.co';
            $mailSent = Yii::$app->mailer->compose(['html' => 'contract_task_notification-html'], [
                'task' => $task,
                'contract' => $contract,
                'actionType' => $actionType,
                'statusLabel' => $statusLabel,
                'targetEmail' => $targetEmail,
                'customMessage' => $customMessage,
                'isAdminCopy' => false,
            ])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setReplyTo($customerReplyTo)
                ->setTo($targetEmail)
                ->setSubject($subject)
                ->send();

            // 2. Enviar copia explícita de respaldo al administrador para reenvío (evita bloqueos SMTP en BCC)
            try {
                $adminEmailsToSend = array_filter($adminEmails, function($e) use ($targetEmail) {
                    return strcasecmp($e, $targetEmail) !== 0 && strcasecmp(trim($e), 'hola@atsys.co') !== 0;
                });
                if (!empty($adminEmailsToSend)) {
                    $adminSubject = "[Copia Admin] " . $subject . " (" . $targetEmail . ")";
                    Yii::$app->mailer->compose(['html' => 'contract_task_notification-html'], [
                        'task' => $task,
                        'contract' => $contract,
                        'actionType' => $actionType,
                        'statusLabel' => $statusLabel,
                        'targetEmail' => $targetEmail,
                        'customMessage' => $customMessage,
                        'isAdminCopy' => true,
                    ])
                        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                        ->setReplyTo($targetEmail) // Al responder desde la copia, responderá directamente al cliente
                        ->setTo($adminEmailsToSend)
                        ->setSubject($adminSubject)
                        ->send();
                }
            } catch (\Throwable $adminEx) {
                Yii::error("Error enviando copia de hito a administración: " . $adminEx->getMessage());
            }

            return $mailSent;
        } catch (\Throwable $e) {
            Yii::error("Error enviando email de hito #{$task->id} ({$task->title}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sube archivos de evidencia asociados a un hito (ContractTasks)
     * Utiliza Google Drive si está configurado, o almacenamiento local con fallback.
     * 
     * @param ContractTasks $task
     * @param UploadedFile[] $files
     * @param Contracts $contract
     * @return array ['uploaded' => int, 'errors' => array]
     */
    protected function uploadTaskFiles($task, $files, $contract)
    {
        $uploadedCount = 0;
        $errors = [];

        if (empty($files)) {
            return ['uploaded' => 0, 'errors' => []];
        }

        foreach ($files as $i => $file) {
            if ($file->hasError) {
                $errorDesc = match ($file->error) {
                    UPLOAD_ERR_INI_SIZE => "El archivo '{$file->name}' supera el tamaño máximo permitido por PHP (" . ini_get('upload_max_filesize') . ").",
                    UPLOAD_ERR_FORM_SIZE => "El archivo '{$file->name}' excede el límite del formulario.",
                    UPLOAD_ERR_PARTIAL => "La subida del archivo '{$file->name}' fue interrumpida.",
                    UPLOAD_ERR_NO_FILE => "No se recibió el archivo '{$file->name}'.",
                    UPLOAD_ERR_NO_TMP_DIR => "Falta la carpeta temporal en el servidor para almacenar la subida.",
                    UPLOAD_ERR_CANT_WRITE => "No se pudo escribir el archivo '{$file->name}' en el disco del servidor (permisos insuficientes).",
                    UPLOAD_ERR_EXTENSION => "Una extensión del servidor detuvo la subida del archivo '{$file->name}'.",
                    default => "Error en la subida del archivo '{$file->name}' (código PHP {$file->error}).",
                };
                $errors[] = $errorDesc;
                Yii::error("uploadTaskFiles error for task #{$task->id}: {$errorDesc}", __METHOD__);
                continue;
            }

            $fileUrl = null;
            $fileSize = $file->size;

            // 1. Intentar subir mediante GoogleDriveService
            if (Yii::$app->has('googleDrive')) {
                try {
                    $subfolder = $contract->code . '/Hitos/' . $task->id;
                    $fileUrl = Yii::$app->googleDrive->upload($file, $subfolder, 'contracts');
                } catch (\Exception $e) {
                    Yii::error("GoogleDrive upload exception for task #{$task->id}: " . $e->getMessage(), __METHOD__);
                }
            }

            // 2. Fallback local directo si GoogleDrive no devolvió URL
            if (!$fileUrl) {
                $uploadDir = Yii::getAlias('@webroot/uploads/contracts/tasks/' . $task->id . '/');
                if (!is_dir($uploadDir)) {
                    FileHelper::createDirectory($uploadDir, 0777);
                }
                $ext = !empty($file->extension) ? '.' . $file->extension : '';
                $cleanBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($file->name, PATHINFO_FILENAME));
                $fileName = 'evid_' . time() . '_' . $i . '_' . rand(1000, 9999) . '_' . substr($cleanBase, 0, 30) . $ext;
                $filePath = $uploadDir . $fileName;

                if ($file->saveAs($filePath)) {
                    $fileUrl = '/uploads/contracts/tasks/' . $task->id . '/' . $fileName;
                } else {
                    $errMsg = "No se pudo guardar el archivo '{$file->name}' en el servidor.";
                    $errors[] = $errMsg;
                    Yii::error("uploadTaskFiles saveAs failed: {$errMsg}", __METHOD__);
                    continue;
                }
            }

            // 3. Guardar registro en la base de datos
            $taskFile = new ContractTaskFiles();
            $taskFile->task_id = $task->id;
            $taskFile->contract_id = $contract->id;
            $taskFile->title = mb_substr($file->name, 0, 255);
            $taskFile->file_url = $fileUrl;
            $taskFile->file_size = $fileSize;

            if ($taskFile->save()) {
                $uploadedCount++;
            } else {
                $dbErr = implode(', ', $taskFile->getFirstErrors());
                $errors[] = "No se pudo vincular la evidencia '{$file->name}': {$dbErr}";
                Yii::error("ContractTaskFiles save failed: {$dbErr}", __METHOD__);
                $relativeUrl = preg_replace('#^https?://[^/]+#', '', $fileUrl);
                if (strpos($relativeUrl, '/uploads/') === 0) {
                    @unlink(Yii::getAlias('@webroot' . $relativeUrl));
                }
            }
        }

        return ['uploaded' => $uploadedCount, 'errors' => $errors];
    }

    protected function findModel($id)
    {
        if (($model = Contracts::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('El contrato solicitado no existe.');
    }
}
