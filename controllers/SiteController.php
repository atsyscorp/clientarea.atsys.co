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
        if(Yii::$app->user->isGuest) {
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

            if(Yii::$app->user->identity->isAdmin) {
                $countOpen     = Tickets::find()->where(['status' => 'open'])->count();
                $countAnswered = Tickets::find()->where(['status' => 'answered'])->count();
                $countTotal    = Tickets::find()->count();
            }

            $recentTickets = Tickets::find();

            if(!Yii::$app->user->identity->isAdmin) {
                $recentTickets = $recentTickets->where(['customer_id' => Yii::$app->user->id]);
            }

            $recentTickets =
                $recentTickets->orderBy(['created_at' => SORT_DESC])
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
            Yii::$app->session->setFlash('success', 'Gracias por registrarte. Se ha enviado un mensaje de verificación a tu correo electrónico.');
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
            Yii::$app->session->setFlash('success', '¡Tu correo ha sido confirmado! Hemos enviado un mensaje de bienvenida.');
            return $this->redirect(['site/login']);
        }

        Yii::$app->session->setFlash('error', 'Lo sentimos, no pudimos verificar tu cuenta o el token ha expirado.');
        return $this->goHome();
    }

    // Función auxiliar privada para activar y enviar bienvenida
    protected function activateUser($user)
    {
        $user->status = User::STATUS_ACTIVE;
        $user->removeEmailVerificationToken(); // Tener este método en User o simplemente: $user->verification_token = null;
        $user->verification_token = null;
        
        if ($user->save(false)) {
            Yii::$app->mailer->compose(['html' => 'welcome-html'], ['user' => $user])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setTo($user->email)
                ->setBcc(Yii::$app->params['adminEmail'])
                ->setSubject('¡Bienvenido a la familia ATSYS!')
                ->send();
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

        $user = Yii::$app->user->identity;
        $model = new \app\models\ProfileForm($user);
        $isAdmin = !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin;

        $customer = null;
        if(!$isAdmin) {
            $customer = $user->customer;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Tu perfil ha sido actualizado correctamente.');
            return $this->refresh();
        }

        if(!$isAdmin) {
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
}
