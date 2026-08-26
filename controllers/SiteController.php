<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;

use app\models\LoginForm;
use app\models\ContactForm;
use app\models\SignupForm;
use app\models\PasswordResetRequestForm;
use app\models\Tickets;
use app\models\User;
use app\models\ProfileForm;
use app\components\DomainChecker;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return $this->actionLogin();
        } else {

            $user = Yii::$app->user->identity;
            if ($user->role === \app\models\User::ROLE_CLIENT && !$user->customer) {
                Yii::$app->session->setFlash('warning', '👋 ¡Hola! Antes de continuar, por favor completa la información de tu empresa/perfil.');
                return $this->redirect(['customers/create']);
            }

            $countOpen = 0;
            $countAnswered = 0;
            $countTotal = 0;

            if (Yii::$app->user->identity->isAdmin) {
                $countOpen = Tickets::find()->where(['status' => 'open'])->count();
                $countAnswered = Tickets::find()->where(['status' => 'answered'])->count();
                $countTotal = Tickets::find()->count();
            }

            $recentTickets = Tickets::find();

            if (!Yii::$app->user->identity->isAdmin) {
                $recentTickets = $recentTickets->where([
                    'customer_id' => Yii::$app->user->id
                ]);
            } else {
                $recentTickets = $recentTickets->where([
                    'status' => [
                        Tickets::STATUS_OPEN,
                        Tickets::STATUS_ANSWERED,
                        Tickets::STATUS_CUSTOMER_REPLY,
                    ]
                ]);
            }

            $recentTickets = $recentTickets
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(5)
                ->all();

            return $this->render('index', [
                'countOpen' => $countOpen,
                'countAnswered' => $countAnswered,
                'countTotal' => $countTotal,
                'recentTickets' => $recentTickets,
            ]);
        }
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'blank';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionSignup()
    {
        $model = new SignupForm();
        $this->layout = 'blank';

        if ($model->load(Yii::$app->request->post()) && $model->signup()) {
            if ($model->emailSent) {
                Yii::$app->session->setFlash('success', 'Gracias por registrarte|Se ha enviado un mensaje de verificación a tu correo electrónico, esto puede tomar unos minutos. Revisa también la carpeta de spam en caso tal de no recibirlo en la bandeja de entrada.');
            } else {
                // La cuenta quedó creada pero el correo de verificación falló:
                // avisamos en vez de dejar al usuario esperando un mensaje que no llega.
                $supportEmail = Yii::$app->params['departmentEmails']['support'] ?? Yii::$app->params['adminEmail'];
                Yii::$app->session->setFlash('warning', 'Tu cuenta fue creada|No pudimos enviar el correo de verificación en este momento. Escríbenos a ' . $supportEmail . ' para activarla.');
            }
            return $this->redirect(['site/login']);
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    public function actionVerifyEmail($token)
    {
        try {
            $user = User::findByVerificationToken($token);
        } catch (\InvalidArgumentException $e) {
            throw new \yii\web\BadRequestHttpException($e->getMessage());
        }

        if ($user && $this->activateUser($user)) {
            Yii::$app->session->setFlash('success', '¡Tu correo ha sido confirmado!|Hemos enviado un mensaje de bienvenida a tu correo electrónico.');
            return $this->redirect(['site/login']);
        }

        Yii::$app->session->setFlash('error', 'Lo sentimos|No pudimos verificar tu cuenta o el token ha expirado. Si crees que es un error, por favor contacta a soporte.');
        return $this->goHome();
    }

    // Función auxiliar privada para activar y enviar bienvenida
    protected function activateUser($user)
    {
        $user->status = User::STATUS_ACTIVE;
        $user->removeEmailVerificationToken(); // Tener este método en User o simplemente: $user->verification_token = null;
        $user->verification_token = null;

        if ($user->save(false)) {
            try {
                Yii::$app->mailer->compose(['html' => 'welcome-html'], ['user' => $user])
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                    ->setReplyTo(Yii::$app->params['departmentEmails']['support'] ?? 'soporte@atsys.co')
                    ->setTo($user->email)
                    ->setSubject('¡Bienvenid@ a la familia ATSYS!')
                    ->send();
            } catch (\Throwable $e) {
                Yii::error("Fallo enviando correo de bienvenida a {$user->email}: " . $e->getMessage());
            }
            return true;
        }
        return false;
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $this->layout = 'blank';

        $model = new PasswordResetRequestForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {

                $emailParts = explode('@', $model->email);
                $namePart = $emailParts[0];
                $domainPart = $emailParts[1];

                $maskedName = substr($namePart, 0, 1) . str_repeat('*', max(1, strlen($namePart) - 2)) . substr($namePart, -1);
                $maskedEmail = $maskedName . '@' . $domainPart;

                Yii::$app->session->setFlash('success', 'Hemos enviado las instrucciones al correo ' . $maskedEmail);

                return $this->redirect(['site/login']);
            } else {
                Yii::$app->session->setFlash('error', 'No pudimos enviar el correo. Contacta a soporte.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        $this->layout = 'blank';

        try {
            $model = new \app\models\ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'Nueva contraseña guardada. Ya puedes iniciar sesión.');
            return $this->redirect(['login']);
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    public function actionProfile()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['login']);
        }

        if (Yii::$app->request->get('change') == '1') {
            if (Yii::$app->session->has('whatsapp_otp') && Yii::$app->session->has('whatsapp_mobile')) {
                Yii::$app->session->remove('whatsapp_otp');
                Yii::$app->session->remove('whatsapp_mobile');
            }
            return $this->redirect(['/profile']);
        }

        $user = Yii::$app->user->identity;
        $model = new \app\models\ProfileForm($user);
        $isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;

        $customer = null;
        if (!$isAdmin) {
            $customer = $user->customer;
        }

        if ($this->request->isPost && $model->load(Yii::$app->request->post())) {
            if(!$model->save()) {
                Yii::$app->session->setFlash('error', 'No pudimos actualizar tu perfil: ' . json_encode($model->getErrors()));
            } else {
                Yii::$app->session->setFlash('success', 'Perfil actualizado correctamente.');
                Yii::$app->session->remove('whatsapp_otp');
                Yii::$app->session->remove('whatsapp_mobile');
            }
            /*
            if (Yii::$app->session->has('whatsapp_otp') && Yii::$app->session->has('whatsapp_mobile')) {

                if (Yii::$app->request->post('ProfileForm')['otp'] == Yii::$app->session['whatsapp_otp']) {

                    $model->otpVerified = true;
                    $model->mobile = Yii::$app->session['whatsapp_mobile'];

                    Yii::$app->session->remove('whatsapp_otp');
                    Yii::$app->session->remove('whatsapp_mobile');

                    if ($model->save()) {
                        Yii::$app->session->setFlash('success', 'Tu número de celular ha sido actualizado correctamente.');
                    } else {
                        Yii::$app->session->setFlash('error', 'No pudimos actualizar tu número de celular.' . json_encode($model->getErrors()));
                    }
                    return $this->refresh();
                } else {
                    Yii::$app->session->setFlash('error', 'El código de verificación es incorrecto.');
                    return $this->refresh();
                }

            } else {

                if ($model->load(Yii::$app->request->post()) && $model->save()) {
                    Yii::$app->session->setFlash(
                        Yii::$app->session->has('whatsapp_otp') ? 'warning' : 'success',
                        Yii::$app->session->has('whatsapp_otp') ? 'Enviamos un código de verificación a tu número de celular.' : 'Tu perfil ha sido actualizado correctamente.'
                    );
                } else {
                    Yii::$app->session->setFlash('error', 'No pudimos actualizar tu perfil.' . json_encode($model->getErrors()));
                }

            }
            */

            return $this->refresh();
        }

        if (!$isAdmin) {
            if ($customer && $customer->load(Yii::$app->request->post()) && $customer->save()) {
                Yii::$app->session->setFlash('success', 'Datos de facturación actualizados.');
                return $this->refresh();
            }
        }

        return $this->render('profile', [
            'model' => $model,
            'customer' => $customer,
        ]);
    }

    public function actionSuspendedAccount()
    {
        $this->layout = 'blank';
        return $this->render('suspended-account');
    }

    public function actionSavePushToken()
    {
        // Solo permitimos esto a usuarios logueados y administradores
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin) {
            return;
        }

        $token = Yii::$app->request->post('token');

        if ($token) {
            // Evitamos duplicados: Si ya existe este token, no hacemos nada
            $exists = \app\models\AdminTokens::find()->where(['token' => $token])->exists();

            if (!$exists) {
                $model = new \app\models\AdminTokens();
                $model->user_id = Yii::$app->user->id;
                $model->token = $token;
                $model->device_info = Yii::$app->request->userAgent; // Guardamos qué navegador es
                $model->created_at = date('Y-m-d H:i:s');
                $model->save();
            }
        }
    }

    public function actionTestAlert()
    {
        // En un controlador de prueba o consola:
        $job = new \app\jobs\WhatsappJob([
            'phone' => '573026496656',
            'message' => 'TOKEN_ACCESO_TEST',
            'webhookUrl' => 'https://n8n.atsys.co/webhook/atsys-clientarea-alert' // Usamos TEST para debug
        ]);

        // Enviamos a la cola
        Yii::$app->queue->push($job);
        echo "Job enviado a la cola correctamente.";
    }

    public function actionSetOtp()
    {
        $job = new \app\jobs\WhatsappJob([
            'phone' => '573026496656',
            'message' => '123456',
            'webhookUrl' => 'https://n8n.atsys.co/webhook/atsys-otp-alert'
        ]);
        Yii::$app->queue->push($job);
        echo "Job enviado a la cola correctamente.";
    }

    public function actionDomainSearch()
    {
        // 1. CORS headers configuration
        $origin = Yii::$app->request->headers->get('Origin');
        $isCrossOrigin = false;
        if ($origin) {
            if (preg_match('/^https?:\/\/(?:[a-z0-9-]+\.)*atsys\.co(?::\d+)?$/i', $origin) || 
                preg_match('/^https?:\/\/localhost(?::\d+)?$/i', $origin)) {
                Yii::$app->response->headers->set('Access-Control-Allow-Origin', $origin);
                Yii::$app->response->headers->set('Access-Control-Allow-Credentials', 'true');
                Yii::$app->response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
                Yii::$app->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
                $isCrossOrigin = true;
            }
        }

        // 2. Preflight check
        if (Yii::$app->request->isOptions) {
            Yii::$app->response->statusCode = 200;
            Yii::$app->response->content = '';
            return Yii::$app->response;
        }

        $q = Yii::$app->request->get('q');
        $results = [];
        $pricesMap = [];

        // Fetch active domain products to display pricing and configure suggestions
        try {
            $domainProducts = \app\models\Products::find()->where(['type' => 'domain', 'status' => 1])->all();
            foreach ($domainProducts as $product) {
                if (preg_match('/(\.[a-z.]+)/i', $product->name, $matches)) {
                    $ext = strtolower($matches[1]);
                    $pricesMap[$ext] = [
                        'price' => (float)$product->price,
                        'id' => $product->id,
                        'name' => $product->name
                    ];
                }
            }
        } catch (\Exception $e) {
            Yii::error("Error fetching domain products: " . $e->getMessage());
        }

        // List of default extensions to check or suggest
        $defaultTlds = ['.com', '.co', '.com.co', '.net', '.org'];

        if ($q !== null) {
            $q = strtolower(trim($q));
            // Remove protocol and subdomains or paths if they entered a URL
            $q = preg_replace('/^https?:\/\/(www\.)?/i', '', $q);
            $q = preg_replace('/\/.*$/', '', $q);

            if (!empty($q)) {
                $hasExtension = (strpos($q, '.') !== false);

                if ($hasExtension) {
                    // Check the specific domain entered
                    $mainResult = DomainChecker::isAvailable($q);
                    
                    // Extract name and extension
                    $parts = explode('.', $q);
                    $tld = array_pop($parts);
                    $sld = array_pop($parts);
                    $name = implode('.', $parts);
                    if (empty($name)) {
                        $name = $sld;
                        $ext = '.' . $tld;
                    } else {
                        $ext = '.' . $sld . '.' . $tld;
                    }

                    $results[] = array_merge([
                        'domain' => $q,
                        'domain_name' => $name,
                        'extension' => $ext,
                        'is_main' => true,
                        'price' => isset($pricesMap[$ext]) ? $pricesMap[$ext]['price'] : null,
                        'product_id' => isset($pricesMap[$ext]) ? $pricesMap[$ext]['id'] : null,
                    ], $mainResult);

                    // Generate alternative suggestions
                    foreach ($defaultTlds as $altExt) {
                        if ($altExt !== $ext) {
                            $altDomain = $name . $altExt;
                            $altResult = DomainChecker::isAvailable($altDomain);
                            $results[] = array_merge([
                                'domain' => $altDomain,
                                'domain_name' => $name,
                                'extension' => $altExt,
                                'is_main' => false,
                                'price' => isset($pricesMap[$altExt]) ? $pricesMap[$altExt]['price'] : null,
                                'product_id' => isset($pricesMap[$altExt]) ? $pricesMap[$altExt]['id'] : null,
                            ], $altResult);
                        }
                    }
                } else {
                    // No extension entered: check the name with all default extensions
                    foreach ($defaultTlds as $ext) {
                        $domainToCheck = $q . $ext;
                        $checkResult = DomainChecker::isAvailable($domainToCheck);
                        $results[] = array_merge([
                            'domain' => $domainToCheck,
                            'domain_name' => $q,
                            'extension' => $ext,
                            'is_main' => ($ext === '.com'), // Treat .com as main by default if no ext
                            'price' => isset($pricesMap[$ext]) ? $pricesMap[$ext]['price'] : null,
                            'product_id' => isset($pricesMap[$ext]) ? $pricesMap[$ext]['id'] : null,
                        ], $checkResult);
                    }
                }
            }
        }

        // Return JSON if requested via format parameter, Accept header, AJAX or Cross-Origin
        $format = Yii::$app->request->get('format');
        $accept = Yii::$app->request->headers->get('Accept');
        $wantsJson = ($format === 'json' || 
                      (strpos($accept ?? '', 'application/json') !== false) || 
                      Yii::$app->request->isAjax || 
                      $isCrossOrigin);

        if ($wantsJson) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'success' => true,
                'query' => $q,
                'results' => $results,
                'prices' => $pricesMap
            ];
        }

        return $this->render('domain-search', [
            'q' => $q,
            'results' => $results,
            'pricesMap' => $pricesMap,
            'defaultTlds' => $defaultTlds
        ]);
    }

    public function actionUpdateTheme()
    {
        if (Yii::$app->request->isPost && !Yii::$app->user->isGuest) {
            $theme = Yii::$app->request->post('theme');
            $user = Yii::$app->user->identity;
            $user->theme_preference = $theme;
            if ($user->save(false)) {
                return $this->asJson(['success' => true]);
            }
        }
        return $this->asJson(['success' => false]);
    }

    public function actionSettings()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['login']);
        }
        if (!Yii::$app->user->identity->isAdmin) {
            throw new \yii\web\ForbiddenHttpException('No tienes permiso para acceder a esta página.');
        }

        $settings = \app\models\SystemSettings::find()->indexBy('id')->all();

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post('SystemSettings', []);
            $success = true;
            
            $transaction = Yii::$app->db->beginTransaction();
            try {
                foreach ($postData as $id => $data) {
                    if (isset($settings[$id])) {
                        $setting = $settings[$id];
                        $setting->value = $data['value'];
                        if (!$setting->save()) {
                            $success = false;
                        }
                    }
                }
                if ($success) {
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Configuraciones guardadas correctamente.');
                    // Recargar params dinámicos
                    \app\models\SystemSettings::loadToParams();
                    return $this->refresh();
                } else {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'Hubo un error al guardar algunas configuraciones.');
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Error al procesar la solicitud: ' . $e->getMessage());
            }
        }

        return $this->render('settings', [
            'settings' => $settings,
        ]);
    }
}
