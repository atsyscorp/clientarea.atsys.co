<?php
$file = 'controllers/TicketsController.php';
$content = file_get_contents($file);

$actionEdit = <<<PHP
    /**
     * Edita los datos básicos de un ticket
     */
    public function actionEdit(\$id)
    {
        \$model = \$this->findModel(\$id);
        \$isAdmin = !Yii::\$app->user->isGuest && Yii::\$app->user->identity->isAdmin;

        if (!\$isAdmin) {
            throw new \yii\web\ForbiddenHttpException('No tienes permiso para editar este ticket.');
        }

        if (\$this->request->isPost && \$model->load(\$this->request->post())) {
            
            // Optionally, handle "notificar al cliente" here if needed.
            // As per request "sin la opción de poder notificar", we just save without notifying.
            if (\$model->save()) {
                Yii::\$app->session->setFlash('success', 'Ticket actualizado correctamente.');
            } else {
                Yii::\$app->session->setFlash('error', 'Error al actualizar el ticket.');
            }
            return \$this->redirect(['view', 'id' => \$model->id]);
        }
        
        // This action only accepts POST requests. If accessed via GET, redirect to view
        return \$this->redirect(['view', 'id' => \$model->id]);
    }

PHP;

if (strpos($content, 'public function actionEdit') === false) {
    // Insert after actionView
    $pos = strpos($content, 'public function actionGetNewReplies');
    if ($pos !== false) {
        // find the start of the docblock for actionGetNewReplies
        $docblockPos = strrpos(substr($content, 0, $pos), '/**');
        $insertPos = $docblockPos !== false ? $docblockPos : $pos;
        
        $newContent = substr($content, 0, $insertPos) . $actionEdit . "\n" . substr($content, $insertPos);
        file_put_contents($file, $newContent);
        echo "actionEdit added.\n";
    }
}
