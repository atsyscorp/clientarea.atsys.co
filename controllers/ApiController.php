<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use app\models\Tickets;
use app\models\TicketReplies;
use app\models\Customers;

class ApiController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Forzar autenticación HTTP Bearer Token
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    /**
     * POST /api/tickets
     * 
     * Crea un nuevo ticket a nombre de la empresa/cliente autenticado.
     * Limita la creación a un máximo de 20 tickets diarios por cliente.
     */
    public function actionCreateTicket()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $user = Yii::$app->user->identity;
        $customerId = $user->getRealCustomerId();

        if (!$customerId) {
            Yii::$app->response->statusCode = 403;
            return [
                'status' => 'error',
                'message' => 'El usuario autenticado no tiene una empresa asociada en el sistema.',
            ];
        }

        // 1. Control de Límite Diario: Máximo 20 tickets al día por cliente
        $startOfDay = date('Y-m-d 00:00:00');
        $ticketsCreatedToday = Tickets::find()
            ->where(['customer_id' => $customerId])
            ->andWhere(['>=', 'created_at', $startOfDay])
            ->count();

        if ($ticketsCreatedToday >= 20) {
            Yii::$app->response->statusCode = 429;
            return [
                'status' => 'error',
                'message' => 'Límite diario de tickets alcanzado para este cliente (Máximo 20 por día).',
            ];
        }

        // 2. Obtener datos de la petición (JSON gracias a application/json parser en config/web.php)
        $body = Yii::$app->request->getBodyParams();

        if (empty($body['subject']) || empty($body['message'])) {
            Yii::$app->response->statusCode = 422;
            return [
                'status' => 'error',
                'message' => 'Faltan parámetros requeridos: "subject" y "message" son obligatorios.',
            ];
        }

        // 3. Crear instancia de Ticket
        $model = new Tickets(['scenario' => 'create']);
        $model->customer_id = $customerId;
        $model->email = $user->email;
        $model->status = Tickets::STATUS_OPEN;
        $model->created_at = date('Y-m-d H:i:s');
        $model->ticket_code = 'TKT-' . strtoupper(substr(uniqid(), -5));

        $model->subject = trim($body['subject']);
        $model->message = trim($body['message']);

        // Establecer departamento (por defecto soporte)
        $allowedDepartments = [Tickets::DEPT_SUPPORT, Tickets::DEPT_COMMERCIAL, Tickets::DEPT_BILLING];
        if (!empty($body['department']) && in_array($body['department'], $allowedDepartments)) {
            $model->department = $body['department'];
        } else {
            $model->department = Tickets::DEPT_SUPPORT;
        }

        // 4. Guardar Transaccionalmente (Ticket + Primera Respuesta)
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($model->save()) {
                $reply = new TicketReplies();
                $reply->ticket_id = $model->id;
                $reply->message = $model->message;
                $reply->sender_type = 'customer';
                $reply->user_id = $user->id;
                $reply->created_at = date('Y-m-d H:i:s');

                if ($reply->save()) {
                    $transaction->commit();

                    // Disparar notificaciones completas (Email, In-App, y Push N8N)
                    try {
                        $model->sendNewTicketNotifications($model->message, $user, false);
                    } catch (\Exception $notifEx) {
                        Yii::error("Error enviando notificaciones de ticket API (" . $model->ticket_code . "): " . $notifEx->getMessage(), 'api_ticket');
                    }

                    return [
                        'status' => 'success',
                        'message' => 'Ticket creado de manera exitosa.',
                        'ticket_id' => $model->id,
                        'ticket_code' => $model->ticket_code,
                    ];
                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 422;
                    return [
                        'status' => 'error',
                        'message' => 'No se pudo guardar el mensaje inicial del ticket.',
                        'errors' => $reply->getErrors(),
                    ];
                }
            } else {
                $transaction->rollBack();
                Yii::$app->response->statusCode = 422;
                return [
                    'status' => 'error',
                    'message' => 'No se pudo guardar el ticket.',
                    'errors' => $model->getErrors(),
                ];
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->response->statusCode = 500;
            return [
                'status' => 'error',
                'message' => 'Ocurrió un error inesperado al registrar el ticket: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Envía notificaciones de correo para el nuevo ticket.
     */
    protected function sendNewTicketEmails($ticket, $messageContent, $user)
    {
        $adminEmail = Yii::$app->params['adminEmail'] ?? 'gerencia@atsys.co';
        $senderEmail = Yii::$app->params['senderEmail'] ?? 'no-reply@atsys.co';

        // Intentar pasar todas las variables posibles a la vista de correos para evitar errores
        $customer = $ticket->customer;
        $customerName = $customer ? $customer->business_name : 'Cliente Registrado';

        Yii::$app->mailer->compose(
            ['html' => 'newTicket-html'],
            [
                'ticket' => $ticket,
                'message' => $messageContent,
                'user' => $user,
                'customer' => $customer,
                'customerName' => $customerName
            ]
        )
            ->setFrom([$senderEmail => Yii::$app->name])
            ->setTo($ticket->email)
            ->setReplyTo(Yii::$app->params['departmentEmails'][$ticket->department] ?? $senderEmail)
            ->setSubject('[#' . $ticket->ticket_code . '] ' . $ticket->subject)
            ->send();

        Yii::$app->mailer->compose(
            ['html' => 'adminNewTicket-html'],
            [
                'ticket' => $ticket,
                'message' => $messageContent,
                'user' => $user,
                'customer' => $customer
            ]
        )
            ->setFrom([$senderEmail => Yii::$app->name])
            ->setTo($adminEmail)
            ->setSubject('Nuevo Ticket API [' . $ticket->ticket_code . '] - ' . $ticket->subject)
            ->send();
    }

    /**
     * Envía una señal a N8N para procesar la notificación Push a los Administradores.
     */
    protected function triggerN8nNotification($title, $body, $ticketId)
    {
        $tokens = \app\models\AdminTokens::find()->select('token')->column();

        if (empty($tokens)) {
            return;
        }

        $payload = [
            'tokens' => $tokens,
            'title' => $title,
            'body' => $body,
            'message' => $body,
            'link' => "https://clientarea.atsys.co/tickets/view?id=" . $ticketId,
            'image' => 'https://clientarea.atsys.co/images/atsys-clientarea-og.webp',
            'type' => 'ticket'
        ];

        $n8nUrl = Yii::$app->params['n8n_admin_push_url'] ?? 'https://n8n-new.atsys.co/webhook/send-admin-push';

        try {
            $ch = curl_init($n8nUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            Yii::error("Error enviando push a N8N desde ApiController: " . $e->getMessage(), 'n8n_push');
        }
    }
}
