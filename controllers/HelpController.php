<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;

class HelpController extends Controller
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
                        'allow' => true,
                        'actions' => ['index', 'virtualmin', 'cyberpanel'],
                        'roles' => ['?', '@'], // Permitir tanto a invitados (?) como a usuarios registrados (@)
                    ],
                ],
            ],
        ];
    }

    /**
     * Inicio del centro de ayuda (Dashboard)
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Guía detallada para el panel Virtualmin
     * @return string
     */
    public function actionVirtualmin()
    {
        return $this->render('virtualmin');
    }

    /**
     * Guía detallada para el panel CyberPanel
     * @return string
     */
    public function actionCyberpanel()
    {
        return $this->render('cyberpanel');
    }
}
