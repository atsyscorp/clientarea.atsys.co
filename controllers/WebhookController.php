<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response; // Importante para respuestas API
use app\models\Tickets;
use app\models\Customers;
use app\models\TicketReplies;
use app\models\AdminTokens;

class WebhookController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionEmailToTicket()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // 1. SEGURIDAD
        $secretKey = Yii::$app->params['webhookSecretKey'] ?? null;
        if (empty($secretKey)) {
            Yii::error('WEBHOOK_SECRET_KEY no esta configurada; se rechaza la peticion.', __METHOD__);
            Yii::$app->response->statusCode = 503;
            return ['status' => 'error', 'message' => 'Webhook no configurado.'];
        }
        if (!hash_equals($secretKey, (string) Yii::$app->request->headers->get('X-API-KEY'))) {
            Yii::$app->response->statusCode = 401;
            return ['status' => 'error', 'message' => 'API Key inválida.'];
        }

        // 2. RECIBIR DATOS
        $data = Yii::$app->request->post();
        if (empty($data)) $data = json_decode(Yii::$app->request->getRawBody(), true);
        
        if (empty($data['email']) || empty($data['subject']) || empty($data['body'])) {
            return ['status' => 'error', 'message' => 'Faltan datos.'];
        }

        // 2.5 COMPROBAR LISTA NEGRA DE SPAM
        if (!empty($data['email'])) {
            $isBlacklisted = \app\models\TicketSpamBlacklist::find()
                ->where(['email' => strtolower(trim($data['email']))])
                ->exists();
            if ($isBlacklisted) {
                return [
                    'status' => 'ignored',
                    'message' => 'El remitente está registrado en la lista negra de SPAM. Ticket ignorado.'
                ];
            }
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 3. LOGICA DE CLIENTE
            $userData = \app\models\User::find()->where(['email' => $data['email']])->one();
            $customerId = null;
            $customerName = 'Usuario Externo';
            
            if ($userData) {
                $customerId = $userData->getRealCustomerId();
                $ownerId = $userData->parent_id ?: $userData->id;
                $customer = \app\models\Customers::findOne(['user_id' => $ownerId]);
                $customerName = $customer ? $customer->business_name : $userData->username;
            } else {
                $customer = \app\models\Customers::find()->where(['email' => $data['email']])->one();
                if ($customer) {
                    $customerId = $customer->id;
                    $customerName = $customer->business_name;
                } else {
                    $customerName = $data['name'] ?? 'Usuario Externo';
                }
            }

            // =================================================================================
            // 4. DETECCIÓN INTELIGENTE (MEJORADA)
            // =================================================================================
            $existingTicket = null;
            $incomingSubject = trim($data['subject']);
            
            // Definimos el asunto limpio desde el principio para que esté disponible siempre
            $cleanIncomingSubject = preg_replace('/^((Re|Fwd|Rv|R|Tr)\s*:\s*)+/i', '', $incomingSubject);
            $cleanIncomingSubject = trim($cleanIncomingSubject);

            // PASO A: Buscar Código TKT en el asunto (Con o sin corchetes)
            preg_match('/(TKT-[A-Z0-9]{5})/', $incomingSubject, $matches);
            
            if (!empty($matches[1])) {
                $ticketCode = $matches[1];
                $existingTicket = Tickets::findOne(['ticket_code' => $ticketCode]);
            }

            // PASO B: Búsqueda "Fuzzy" por Asunto (Si no hay código)
            if (!$existingTicket) {
                // Buscamos tickets abiertos de este email que contengan el asunto limpio
                $existingTicket = Tickets::find()
                    ->where(['email' => $data['email']])
                    ->andWhere(['!=', 'status', 'closed']) 
                    ->andWhere(['LIKE', 'subject', $cleanIncomingSubject]) 
                    ->orderBy(['created_at' => SORT_DESC])
                    ->one();
            }

            // VARIABLES DE RESPUESTA
            $notifTitle = "";
            $notifBody = "";
            $finalTicketId = 0;
            $finalTicketCode = "";

            if ($existingTicket) {

                // Si el ticket está bloqueado para respuestas del cliente, responder de forma transparente sin procesar
                if ($existingTicket->isLocked()) {
                    $transaction->commit();
                    return [
                        'status' => 'success',
                        'message' => 'El ticket se encuentra cerrado y no acepta nuevas respuestas.'
                    ];
                }

                // Si ha alcanzado el límite de 3 respuestas consecutivas sin atención o respuesta
                if (!$existingTicket->canCustomerReply(false)) {
                    $transaction->commit();
                    return [
                        'status' => 'success',
                        'message' => 'Límite de 3 respuestas consecutivas alcanzado para este ticket.'
                    ];
                }

                // ----------------------------------------------------------------
                // CASO: ES RESPUESTA (Agregamos al hilo existente)
                // ----------------------------------------------------------------
                
                if ($existingTicket->status === 'closed' || $existingTicket->status === 'answered') {
                    $existingTicket->status = 'open';
                    $existingTicket->updated_at = date('Y-m-d H:i:s');
                    $existingTicket->save(false);
                }

                $reply = new TicketReplies();
                $reply->ticket_id = $existingTicket->id;
                $reply->message = $data['body'];
                $reply->sender_type = 'customer'; 
                $reply->created_at = date('Y-m-d H:i:s');
                
                if (!$reply->save()) throw new \Exception('Error guardando respuesta.');

                $notifTitle = "💬 Respuesta a ticket: " . $existingTicket->ticket_code;
                
                // Usamos un operador ternario seguro por si el asunto limpio quedó vacío
                $asuntoMostrar = !empty($cleanIncomingSubject) ? $cleanIncomingSubject : $incomingSubject;
                $notifBody = $customerName . ": " . mb_substr(strip_tags($reply->message), 0, 50, 'UTF-8') . "...";
                
                $finalTicketId = $existingTicket->id;
                $finalTicketCode = $existingTicket->ticket_code;

                // Enviar notificaciones in-app y push a los admins
                try {
                    \app\models\Notifications::notifyAdmins(
                        "💬 Respuesta en Ticket: " . $existingTicket->ticket_code,
                        "Respuesta de $customerName en el ticket #" . $existingTicket->ticket_code,
                        "/tickets/view?id=" . $existingTicket->id,
                        \app\models\Notifications::TYPE_INFO
                    );
                } catch (\Throwable $notifEx) {
                    Yii::error("Error registrando notificación in-app para respuesta: " . $notifEx->getMessage());
                }

                $tokens = \app\models\AdminTokens::find()->column();
                $webhookUrl = Yii::$app->params['n8n_admin_push_url'] ?? 'https://n8n-new.atsys.co/webhook/send-admin-push';

                if (!empty($tokens)) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $webhookUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        'tokens' => $tokens,
                        'title' => "Nueva respuesta en Ticket",
                        'body' => "Respuesta de $customerName en el ticket #{$existingTicket->ticket_code}.",
                        'message' => "Respuesta de $customerName en el ticket #{$existingTicket->ticket_code}.",
                        'link' => "https://clientarea.atsys.co/tickets/view?id=" . $existingTicket->id,
                        'type' => 'ticket'
                    ]));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_exec($ch);
                    curl_close($ch);
                }

                // Enviar email a admin
                try {
                    Yii::$app->mailer->compose([
                        'html' => 'ticket_reply'
                    ],[
                        'ticket' => $existingTicket,
                        'reply' => $reply
                    ])
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                    ->setTo(Yii::$app->params['adminEmail'])
                    ->setSubject("[Ticket #{$existingTicket->ticket_code}] Nueva respuesta: {$existingTicket->subject}")
                    ->send();
                } catch (\Throwable $mailEx) {
                    Yii::error("Error enviando email de respuesta al admin: " . $mailEx->getMessage());
                }

            } else {

                // ----------------------------------------------------------------
                // CASO: TICKET NUEVO (Limpieza profunda)
                // ----------------------------------------------------------------
                
                // IMPORTANTE: Limpiamos el asunto para no guardar "[Ticket TKT-VIEJO] Ayuda" como nuevo asunto
                // 1. Quitamos Re:, Fwd:
                $cleanSubject = preg_replace('/^((Re|Fwd|Rv|R|Tr)\s*:\s*)+/i', '', $incomingSubject);
                // 2. Quitamos cualquier rastro de códigos viejos [TKT-XXXXX] o TKT-XXXXX
                $cleanSubject = preg_replace('/\[?TKT-[A-Z0-9]{5}\]?/', '', $cleanSubject);
                // 3. Quitamos corchetes vacíos o espacios extra que hayan quedado "[] "
                $cleanSubject = trim(str_replace(['[]', '  '], ['', ' '], $cleanSubject));
                
                // Si borramos todo y quedó vacío (raro), usamos el original, si no, usamos el limpio
                $finalSubject = empty($cleanSubject) ? $incomingSubject : $cleanSubject;

                $model = new Tickets(['scenario' => 'create']);
                $model->customer_id = $customerId;
                $model->email = $data['email'];
                $model->subject = $finalSubject; // Guardamos el asunto LIMPIO
                $model->message = $data['body']; 
                
                // Departamento
                if (isset($data['target_email']) && strpos($data['target_email'], 'hola') !== false) {
                    $model->department = Tickets::DEPT_COMMERCIAL;
                } else {
                    $model->department = Tickets::DEPT_SUPPORT;
                }

                $model->status = defined('Tickets::STATUS_OPEN') ? Tickets::STATUS_OPEN : 'open';
                $model->created_at = date('Y-m-d H:i:s');
                $model->ticket_code = 'TKT-' . strtoupper(substr(uniqid(), -5));

                if (!$model->save()) throw new \Exception('Error ticket: ' . json_encode($model->getErrors()));

                // Reply inicial
                $reply = new TicketReplies();
                $reply->ticket_id = $model->id;
                $reply->message = $data['body'];
                $reply->sender_type = 'customer';
                $reply->user_id = $userData ? $userData->id : null;
                $reply->created_at = date('Y-m-d H:i:s');
                $reply->save();

                // Disparar notificaciones completas (Email, In-App, y Push N8N)
                $model->sendNewTicketNotifications($data['body'], $userData, false);

                $notifTitle = "🎟️ Nuevo Ticket: " . $model->ticket_code;
                $notifBody = $model->subject;
                $finalTicketId = $model->id;
                $finalTicketCode = $model->ticket_code;
            }

            $transaction->commit();

            // 5. TOKENS PUSH
            // Usamos ruta absoluta para evitar errores de importación
            $adminTokens = \app\models\AdminTokens::find()->select('token')->column();

            return [
                'status' => 'success',
                'type' => $existingTicket ? 'reply' : 'new_ticket',
                'ticket_id' => $finalTicketId, 
                'ticket_code' => $finalTicketCode,
                'notif_title' => $notifTitle,
                'notif_body' => $notifBody,
                'admin_tokens' => $adminTokens,
                'message' => 'Procesado exitosamente.'
            ];

        } catch (\Throwable $e) {
            $transaction->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Función auxiliar para enviar las notificaciones
     * @param Tickets $ticket
     * @param string $messageContent
     * @param Customers|null $customerObj (Puede ser null si es un lead no registrado)
     */
    protected function sendNewTicketEmails($ticket, $messageContent, $customerObj)
    {
        $adminEmail = Yii::$app->params['adminEmail'] ?? 'hola@atsys.co';
        $senderEmail = Yii::$app->params['senderEmail'] ?? 'no-reply@atsys.co';

        // Determinar nombre del cliente para el correo
        // Si $customerObj es null, usamos el email o un nombre genérico
        $customerName = $customerObj ? $customerObj->username : 'Usuario Externo';

        // 1. Correo al Cliente (Confirmación)
        // Asegúrate de tener la vista: views/mail/newTicket-html.php
        try {
            Yii::$app->mailer->compose(
                ['html' => 'newTicket-html'],
                [
                    'ticket' => $ticket, 
                    'message' => $messageContent,
                    'customerName' => $customerName // Pasamos el nombre para usarlo en la vista
                ]
            )
            ->setFrom([$senderEmail => Yii::$app->name])
            ->setTo($ticket->email)
            ->setReplyTo(Yii::$app->params['departmentEmails'][$ticket->department] ?? ($senderEmail ?? 'soporte@atsys.co'))
            ->setSubject("[Ticket #{$ticket->ticket_code}] Recibido: {$ticket->subject}")
            ->send();
        } catch (\Throwable $e) {
            Yii::error("Error enviando email al cliente: " . $e->getMessage());
        }

        // 2. Correo al Admin (Aviso)
        // Asegúrate de tener la vista: views/mail/adminNewTicket-html.php
        try {
            Yii::$app->mailer->compose(
                ['html' => 'adminNewTicket-html'],
                [
                    'ticket' => $ticket, 
                    'message' => $messageContent, 
                    'user' => $customerObj
                ]
            )
            ->setFrom([$senderEmail => Yii::$app->name])
            ->setTo($adminEmail)
            ->setSubject("[Nuevo Ticket] #{$ticket->ticket_code} - {$ticket->subject}")
            ->send();
        } catch (\Throwable $e) {
             Yii::error("Error enviando email al admin: " . $e->getMessage());
        }
    }

    /**
     * Webhook en segundo plano para actualizar órdenes con transacciones de Wompi
     */
    public function actionWompi()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $rawBody = Yii::$app->request->getRawBody();
        $payload = json_decode($rawBody, true);

        if (empty($payload)) {
            $payload = Yii::$app->request->post();
        }

        if (empty($payload)) {
            return ['status' => 'error', 'message' => 'Payload de webhook vacío'];
        }

        // Estructura de evento de Wompi: { "event": "transaction.updated", "data": { "transaction": { "id": "...", "reference": "...", "status": "APPROVED", ... } } }
        $transactionData = $payload['data']['transaction'] ?? null;

        // Si viene directamente la estructura de transacción
        if (!$transactionData && isset($payload['id']) && isset($payload['reference'])) {
            $transactionData = $payload;
        }

        if (!$transactionData || empty($transactionData['id'])) {
            return ['status' => 'error', 'message' => 'Datos de transacción no encontrados en el payload'];
        }

        $transactionId = $transactionData['id'];

        // Verificación de seguridad: Consultar estado oficial directamente a la API de Wompi
        $wompiUrl = "https://production.wompi.co/v1/transactions/" . $transactionId;

        try {
            $response = @file_get_contents($wompiUrl);
            if ($response === false) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $wompiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $response = curl_exec($ch);
                curl_close($ch);
            }

            $json = json_decode($response, true);
            if (!isset($json['data'])) {
                return ['status' => 'error', 'message' => 'Respuesta inválida al verificar con la API de Wompi'];
            }

            $verifiedData = $json['data'];
            $reference = $verifiedData['reference'] ?? null;
            $status = $verifiedData['status'] ?? null;
            $paymentMethod = $verifiedData['payment_method_type'] ?? 'WOMPI';

            if ($status === 'APPROVED' && $reference) {
                $order = \app\models\Orders::findOne(['code' => $reference]);
                if (!$order) {
                    return ['status' => 'error', 'message' => 'Orden no encontrada con código: ' . $reference];
                }

                if ($order->status == 0) {
                    \app\controllers\OrdersController::processSuccessfulPayment($order, $transactionId, $paymentMethod);
                    return [
                        'status' => 'success',
                        'message' => "La orden {$order->code} fue procesada y activada correctamente mediante Webhook de Wompi.",
                        'order_code' => $order->code
                    ];
                } else {
                    return [
                        'status' => 'ignored',
                        'message' => "La orden {$order->code} ya se encontraba en estado procesado ({$order->status}).",
                        'order_code' => $order->code
                    ];
                }
            }

            return [
                'status' => 'info',
                'message' => "Estado de la transacción Wompi: {$status}"
            ];

        } catch (\Throwable $e) {
            Yii::error("Error procesando Webhook Wompi: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}