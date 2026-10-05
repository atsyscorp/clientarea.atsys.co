<?php

namespace app\controllers;

use Yii;
use app\models\Meetings;
use app\models\MeetingsSearch;
use app\models\Customers;
use app\services\N8NService;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\Html;

/**
 * MeetingsController gestiona la programación y registro de reuniones con Google Meet y n8n.
 */
class MeetingsController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['request'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    [
                        'actions' => ['index', 'view'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (Yii::$app->user->isGuest) {
                                return false;
                            }
                            return (bool) Yii::$app->user->identity->isAdmin;
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST', 'GET'],
                    'complete' => ['POST', 'GET'],
                    'cancel' => ['POST', 'GET'],
                    'approve' => ['POST', 'GET'],
                    'reject' => ['POST', 'GET'],
                    'save-notes' => ['POST'],
                    'resend-invitation' => ['POST', 'GET'],
                ],
            ],
        ];
    }

    /**
     * Listado general de reuniones
     */
    public function actionIndex()
    {
        $searchModel = new MeetingsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;
        $user = Yii::$app->user->identity;
        $realCustomerId = $isAdmin ? null : ($user->getRealCustomerId() ?? -1);

        $baseQuery = Meetings::find();
        if (!$isAdmin) {
            $baseQuery->where([
                'or',
                ['customer_id' => $realCustomerId],
                ['client_email' => $user->email],
            ]);
        }

        // Métricas rápidas
        $now = date('Y-m-d H:i:s');
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');

        $upcomingCount = (clone $baseQuery)
            ->andWhere(['>=', 'start_time', $now])
            ->andWhere(['!=', 'status', Meetings::STATUS_CANCELED])
            ->count();

        $todayCount = (clone $baseQuery)
            ->andWhere(['between', 'start_time', $todayStart, $todayEnd])
            ->count();

        $completedCount = (clone $baseQuery)
            ->andWhere(['status' => Meetings::STATUS_COMPLETED])
            ->count();

        $pendingCount = (clone $baseQuery)
            ->andWhere(['status' => Meetings::STATUS_PENDING])
            ->count();

        $totalCount = (clone $baseQuery)->count();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'upcomingCount' => $upcomingCount,
            'todayCount' => $todayCount,
            'completedCount' => $completedCount,
            'pendingCount' => $pendingCount,
            'totalCount' => $totalCount,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Detalle de la reunión
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        // Si no es administrador, verificar pertenencia de la reunión
        if (!Yii::$app->user->identity->isAdmin) {
            $user = Yii::$app->user->identity;
            $realCustomerId = $user->getRealCustomerId() ?? -1;
            if ($model->customer_id != $realCustomerId && strtolower($model->client_email) !== strtolower($user->email)) {
                throw new ForbiddenHttpException('No tienes permiso para ver esta reunión.');
            }
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Agendar una nueva reunión y enviarla a Google Calendar mediante n8n
     */
    public function actionCreate($customer_id = null)
    {
        $model = new Meetings();
        $model->status = Meetings::STATUS_SCHEDULED;
        $model->created_by = Yii::$app->user->id;

        // Si viene pre-seleccionado un cliente
        if ($customer_id) {
            $customer = Customers::findOne($customer_id);
            if ($customer) {
                $model->customer_id = $customer->id;
                $model->client_name = $customer->contact_name ?: $customer->business_name;
                $model->client_email = $customer->email;
            }
        }

        // Valor por defecto: hoy a la siguiente hora redonda
        $nextHour = ceil(time() / 1800) * 1800; // Bloques de 30 min
        $model->start_time = date('Y-m-d\TH:i', $nextHour);
        $durationMinutes = 30;

        if ($model->load(Yii::$app->request->post())) {
            $durationMinutes = (int) Yii::$app->request->post('duration_minutes', 30);
            if ($durationMinutes <= 0) $durationMinutes = 30;

            // Formatear fechas
            $startTimestamp = strtotime($model->start_time);
            $endTimestamp = $startTimestamp + ($durationMinutes * 60);

            $model->start_time = date('Y-m-d H:i:s', $startTimestamp);
            $model->end_time = date('Y-m-d H:i:s', $endTimestamp);

            // Si se seleccionó cliente registrado, sincronizar su nombre y correo si estaban vacíos
            if (!empty($model->customer_id)) {
                $customer = Customers::findOne($model->customer_id);
                if ($customer) {
                    if (empty($model->client_name)) {
                        $model->client_name = $customer->contact_name ?: $customer->business_name;
                    }
                    if (empty($model->client_email)) {
                        $model->client_email = $customer->email;
                    }
                }
            }

            if ($model->validate()) {
                // 1. Invocar a n8n para crear la reunión en Google Calendar con Google Meet
                $n8n = new N8NService();
                $isoStart = date('c', $startTimestamp);
                $isoEnd = date('c', $endTimestamp);

                $n8nResponse = $n8n->scheduleMeeting([
                    'client_id'      => $model->customer_id,
                    'cliente_nombre' => $model->client_name,
                    'cliente_email'  => $model->client_email,
                    'asunto'         => $model->title,
                    'descripcion'    => $model->description,
                    'inicio'         => $isoStart,
                    'fin'            => $isoEnd,
                ]);

                if ($n8nResponse && !empty($n8nResponse['success'])) {
                    $model->google_event_id = $n8nResponse['event_id'] ?? null;
                    $model->meet_url = $n8nResponse['meet_url'] ?? null;
                    $model->calendar_html_link = $n8nResponse['html_link'] ?? null;

                    if ($model->save(false)) {
                        // Enviar correo corporativo con plantilla de ATSYS y archivo .ics adjunto
                        $emailSent = $this->sendInvitationEmail($model);

                        $msg = '¡Reunión programada exitosamente!';
                        if ($emailSent) {
                            $msg .= ' Se ha enviado la invitación con la plantilla oficial de ATSYS a ' . Html::encode($model->client_email) . '.';
                        } else {
                            $msg .= ' La sala de Meet está lista, pero hubo un detalle enviando el correo de confirmación.';
                        }

                        Yii::$app->session->setFlash('success', $msg);
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                } else {
                    // Si n8n no responde o falla, aún guardamos el registro pero alertamos al usuario
                    if ($model->save(false)) {
                        Yii::$app->session->setFlash('warning', 'La reunión se guardó en ClientArea, pero no se pudo sincronizar automáticamente con Google Calendar vía n8n. Revisa el workflow en n8n.');
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                }
            }
        }

        // Lista de clientes para el selector
        $customers = Customers::find()
            ->select(['id', 'business_name', 'contact_name', 'email'])
            ->orderBy(['business_name' => SORT_ASC])
            ->asArray()
            ->all();

        return $this->render('create', [
            'model' => $model,
            'customers' => $customers,
            'durationMinutes' => $durationMinutes,
        ]);
    }

    /**
     * Guardar notas o minuta de Gemini
     */
    public function actionSaveNotes($id)
    {
        $model = $this->findModel($id);
        $notes = Yii::$app->request->post('notes');
        $status = Yii::$app->request->post('status');

        $model->notes = $notes;
        if (!empty($status) && in_array($status, array_keys(Meetings::optsStatus()))) {
            $model->status = $status;
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('success', 'Notas y minuta guardadas correctamente.');
        } else {
            Yii::$app->session->setFlash('error', 'No se pudieron guardar las notas.');
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Marcar reunión como completada
     */
    public function actionComplete($id)
    {
        $model = $this->findModel($id);
        $model->status = Meetings::STATUS_COMPLETED;
        $model->save(false);

        Yii::$app->session->setFlash('success', 'La reunión ha sido marcada como realizada.');
        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Marcar reunión como cancelada
     */
    public function actionCancel($id)
    {
        $model = $this->findModel($id);
        $model->status = Meetings::STATUS_CANCELED;
        $model->save(false);

        Yii::$app->session->setFlash('info', 'La reunión ha sido marcada como cancelada.');
        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Reenviar invitación por correo electrónico al cliente
     */
    public function actionResendInvitation($id)
    {
        $model = $this->findModel($id);
        $sent = $this->sendInvitationEmail($model);

        if ($sent) {
            Yii::$app->session->setFlash('success', 'Invitación reenviada exitosamente a ' . Html::encode($model->client_email) . '.');
        } else {
            Yii::$app->session->setFlash('error', 'No se pudo enviar la invitación por correo. Revisa la configuración de SMTP.');
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Envía la invitación por correo con la plantilla corporativa de ATSYS y el archivo .ics adjunto
     *
     * @param Meetings $model
     * @return bool
     */
    protected function sendInvitationEmail(Meetings $model)
    {
        try {
            $fromEmail = env('MAIL_USERNAME', 'clientarea@atsys.co');
            $adminEmail = 'gerencia@atsys.co';
            $replyTo = $adminEmail;

            // Generar archivo de calendario iCalendar (.ics)
            $startTimestamp = strtotime($model->start_time);
            $endTimestamp = strtotime($model->end_time);
            $dtStart = gmdate('Ymd\THis\Z', $startTimestamp);
            $dtEnd = gmdate('Ymd\THis\Z', $endTimestamp);
            $dtStamp = gmdate('Ymd\THis\Z');
            $uid = ($model->google_event_id ? preg_replace('/[^a-zA-Z0-9]/', '', $model->google_event_id) : uniqid('meet_')) . '@atsys.co';
            $titleEscaped = addcslashes($model->title, ",;\\");
            $descText = "Reunión Virtual con ATSYS.\n\nEnlace de Google Meet: " . $model->meet_url . "\n\n";
            if (!empty($model->description)) {
                $descText .= "Agenda:\n" . $model->description . "\n\n";
            }
            $descText .= "Aviso: Esta sesión virtual será grabada para efectos de calidad, seguimiento de compromisos y mejora continua del servicio.";
            $descEscaped = addcslashes($descText, ",;\\");
            $locationEscaped = addcslashes($model->meet_url ?: 'Google Meet', ",;\\");

            $ics = "BEGIN:VCALENDAR\r\n";
            $ics .= "VERSION:2.0\r\n";
            $ics .= "PRODID:-//ATSYS Corp//ClientArea Meetings//ES\r\n";
            $ics .= "CALSCALE:GREGORIAN\r\n";
            $ics .= "METHOD:REQUEST\r\n";
            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:{$uid}\r\n";
            $ics .= "DTSTAMP:{$dtStamp}\r\n";
            $ics .= "DTSTART:{$dtStart}\r\n";
            $ics .= "DTEND:{$dtEnd}\r\n";
            $ics .= "SUMMARY:{$titleEscaped}\r\n";
            $ics .= "DESCRIPTION:{$descEscaped}\r\n";
            $ics .= "LOCATION:{$locationEscaped}\r\n";
            $ics .= "ORGANIZER;CN=ATSYS:mailto:{$adminEmail}\r\n";
            $ics .= "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=" . addcslashes($model->client_name, ",;\\") . ":mailto:{$model->client_email}\r\n";
            $ics .= "STATUS:CONFIRMED\r\n";
            $ics .= "SEQUENCE:0\r\n";
            $ics .= "END:VEVENT\r\n";
            $ics .= "END:VCALENDAR\r\n";

            $mailer = Yii::$app->mailer->compose(
                ['html' => 'meeting_invitation-html'],
                ['model' => $model]
            );

            // Copia oculta (BCC) EXCLUSIVAMENTE a gerencia@atsys.co (nunca a hola@atsys.co para evitar crear tickets automáticos)
            $bccEmails = [];
            if (strtolower(trim($model->client_email)) !== strtolower($adminEmail)) {
                $bccEmails[] = $adminEmail;
            }

            $mailer->setTo($model->client_email)
                ->setFrom([$fromEmail => 'ATSYS'])
                ->setReplyTo($replyTo)
                ->setSubject('Invitación a Reunión: ' . $model->title . ' - ATSYS')
                ->attachContent($ics, [
                    'fileName' => 'invitacion-reunion.ics',
                    'contentType' => 'text/calendar; charset=utf-8; method=REQUEST',
                ]);

            if (!empty($bccEmails)) {
                $mailer->setBcc($bccEmails);
            }

            return (bool) $mailer->send();
        } catch (\Exception $e) {
            Yii::error("Error enviando correo de invitación de reunión: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Solicitud pública de reunión con validación anti-bot (Turnstile + Honeypot + Rate Limiting)
     * Ruta: /reuniones/solicitar
     */
    public function actionRequest()
    {
        $this->layout = Yii::$app->user->isGuest ? 'blank' : 'main';

        $model = new \app\models\MeetingRequestForm();
        $isSuccess = false;
        $createdMeeting = null;

        // Auto-completar si el usuario está autenticado
        if (!Yii::$app->user->isGuest) {
            $user = Yii::$app->user->identity;
            $model->name = $user->username;
            $model->email = $user->email;

            $realCustomerId = $user->getRealCustomerId();
            $customer = $realCustomerId ? Customers::findOne($realCustomerId) : $user->customer;
            if ($customer) {
                $model->company = $customer->business_name;
                $model->phone = $customer->primary_phone ?: ($customer->secondary_phone ?: '');
            }
        }

        if ($model->load(Yii::$app->request->post())) {
            // 1. Verificación Honeypot silenciosa (si un bot llenó el campo oculto)
            if (!empty($model->website)) {
                return $this->render('request', [
                    'model' => $model,
                    'isSuccess' => true,
                    'createdMeeting' => (object)[
                        'title' => $model->title,
                        'start_time' => date('Y-m-d H:i:s'),
                        'client_name' => $model->name,
                        'client_email' => $model->email,
                    ],
                ]);
            }

            // 2. Control de frecuencia (Rate Limiting) por IP
            $userIp = Yii::$app->request->userIP ?: '0.0.0.0';
            $cacheKey = 'meeting_req_ip_' . md5($userIp);
            $attempts = (int) Yii::$app->cache->get($cacheKey);
            if ($attempts >= 5) {
                Yii::$app->session->setFlash('error', 'Has alcanzado el límite de solicitudes por el momento. Por favor espera unos minutos o comunícate directamente con soporte.');
                return $this->render('request', [
                    'model' => $model,
                    'isSuccess' => false,
                    'createdMeeting' => null,
                ]);
            }

            if ($model->validate()) {
                // Incrementar contador de intentos en cache (1 hora)
                Yii::$app->cache->set($cacheKey, $attempts + 1, 3600);

                $startTime = $model->requested_date . ' ' . $model->requested_time . ':00';
                $endTime = date('Y-m-d H:i:s', strtotime($startTime . " +{$model->duration_minutes} minutes"));

                // Verificar si el correo pertenece a un cliente registrado o al usuario en sesión
                if (!Yii::$app->user->isGuest) {
                    $user = Yii::$app->user->identity;
                    $realCustomerId = $user->getRealCustomerId();
                    $customer = $realCustomerId ? Customers::findOne($realCustomerId) : $user->customer;
                    if (!$customer) {
                        $customer = $model->findRegisteredCustomer();
                    }
                } else {
                    $customer = $model->findRegisteredCustomer();
                }

                $meeting = new Meetings();
                $meeting->customer_id = $customer ? $customer->id : null;
                $meeting->client_name = $model->name;
                $meeting->client_email = $model->email;
                $meeting->title = $model->title;
                $meeting->description = $model->description;
                $meeting->start_time = $startTime;
                $meeting->end_time = $endTime;
                $meeting->status = Meetings::STATUS_PENDING;

                $metaNotes = "--- SOLICITUD PÚBLICA ---" .
                    "\nFecha recepción: " . date('Y-m-d H:i:s') .
                    "\nIP de origen: " . $userIp .
                    (!empty($model->company) ? "\nEmpresa: " . $model->company : "") .
                    (!empty($model->phone) ? "\nTel/WhatsApp: " . $model->phone : "") .
                    "\nTipo: " . ($customer ? "Cliente Registrado (ID: {$customer->id})" : "Contacto Externo / Prospecto no registrado");

                $meeting->notes = $metaNotes;

                if ($meeting->save(false)) {
                    // Notificar a Gerencia
                    $this->sendMeetingRequestAdminNotification($meeting, $customer, $model->phone, $model->company);

                    // Enviar confirmación al solicitante
                    $this->sendMeetingRequestClientAcknowledgement($meeting);

                    $isSuccess = true;
                    $createdMeeting = $meeting;
                } else {
                    Yii::$app->session->setFlash('error', 'Ocurrió un error al registrar la solicitud. Por favor intenta de nuevo.');
                }
            }
        }

        return $this->render('request', [
            'model' => $model,
            'isSuccess' => $isSuccess,
            'createdMeeting' => $createdMeeting,
        ]);
    }

    /**
     * Aprueba una reunión solicitada, invocando n8n y enviando la invitación oficial con Meet
     */
    public function actionApprove($id)
    {
        $meeting = $this->findModel($id);

        if ($meeting->status === Meetings::STATUS_SCHEDULED && !empty($meeting->meet_url)) {
            Yii::$app->session->setFlash('warning', 'Esta reunión ya había sido aprobada y programada previamente.');
            return $this->redirect(['view', 'id' => $meeting->id]);
        }

        // Llamar a n8n para crear la cita en Google Calendar y sala de Google Meet
        $response = N8NService::scheduleMeeting($meeting);

        if ($response['success']) {
            $meeting->status = Meetings::STATUS_SCHEDULED;
            $meeting->meet_url = $response['meet_url'] ?? null;
            $meeting->calendar_html_link = $response['calendar_html_link'] ?? null;
            $meeting->google_event_id = $response['event_id'] ?? null;
            $meeting->save(false);

            // Enviar invitación oficial por email con .ics al cliente (y BCC gerencia)
            $this->sendInvitationEmail($meeting);

            Yii::$app->session->setFlash('success', '¡Reunión aprobada exitosamente! Se sincronizó con Google Calendar y se envió la invitación con Google Meet a ' . Html::encode($meeting->client_email));
        } else {
            Yii::$app->session->setFlash('error', 'No se pudo generar el enlace de Google Meet con n8n: ' . ($response['error'] ?? 'Error desconocido'));
        }

        return $this->redirect(['view', 'id' => $meeting->id]);
    }

    /**
     * Rechaza una reunión solicitada
     */
    public function actionReject($id)
    {
        $meeting = $this->findModel($id);
        $meeting->status = Meetings::STATUS_REJECTED;
        $meeting->save(false);

        Yii::$app->session->setFlash('info', 'La reunión ha sido marcada como rechazada.');
        return $this->redirect(['view', 'id' => $meeting->id]);
    }

    /**
     * Envía notificación a Gerencia de nueva solicitud de reunión
     */
    protected function sendMeetingRequestAdminNotification($meeting, $customer = null, $phone = null, $company = null)
    {
        try {
            $fromEmail = 'clientarea@atsys.co';
            $adminEmail = 'gerencia@atsys.co';

            $mailer = Yii::$app->mailer->compose(
                ['html' => 'meeting_request_admin-html'],
                [
                    'model' => $meeting,
                    'customer' => $customer,
                    'phone' => $phone,
                    'company' => $company,
                ]
            );

            $subjectType = $customer ? '[Cliente]' : '[Externo]';

            $mailer->setTo($adminEmail)
                ->setFrom([$fromEmail => 'ATSYS'])
                ->setReplyTo([$meeting->client_email => $meeting->client_name])
                ->setSubject("{$subjectType} Solicitud de Reunión: {$meeting->title} - {$meeting->client_name}");

            return (bool) $mailer->send();
        } catch (\Exception $e) {
            Yii::error("Error enviando alerta de solicitud de reunión a gerencia: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Envía acuse de recibo al solicitante de la reunión
     */
    protected function sendMeetingRequestClientAcknowledgement($meeting)
    {
        try {
            $fromEmail = 'clientarea@atsys.co';
            $adminEmail = 'gerencia@atsys.co';

            $mailer = Yii::$app->mailer->compose(
                ['html' => 'meeting_request_client-html'],
                ['model' => $meeting]
            );

            $mailer->setTo($meeting->client_email)
                ->setFrom([$fromEmail => 'ATSYS'])
                ->setReplyTo($adminEmail)
                ->setSubject('Hemos recibido tu solicitud de reunión: ' . $meeting->title . ' - ATSYS');

            return (bool) $mailer->send();
        } catch (\Exception $e) {
            Yii::error("Error enviando acuse de recibo de reunión al cliente: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Eliminar registro de reunión
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();

        Yii::$app->session->setFlash('success', 'Reunión eliminada del historial.');
        return $this->redirect(['index']);
    }

    /**
     * Busca el modelo Meetings según su ID
     */
    protected function findModel($id)
    {
        if (($model = Meetings::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La reunión solicitada no existe.');
    }
}
