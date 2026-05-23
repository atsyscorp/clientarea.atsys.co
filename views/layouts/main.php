<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use yii\helpers\Html;

AppAsset::register($this);

// Nos aseguramos de tener el meta viewport para móviles
$this->registerCsrfMetaTags();
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">

<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <title><?= Html::encode($this->title) ?></title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#134C42">
    <script>
        // Immediate system theme detection to avoid flash on load (unconditional OS theme matching)
        (function() {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const systemTheme = prefersDark ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', systemTheme);
            if (systemTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    <?php $this->head() ?>
    <!-- Driver.js (Tour) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@0.9.8/dist/driver.min.css">
    <script src="https://cdn.jsdelivr.net/npm/driver.js@0.9.8/dist/driver.min.js"></script>
    <style>
        /* High-contrast styles for Driver.js Tour Popover */
        .driver-popover {
            background-color: hsl(var(--b1)) !important;
            color: hsl(var(--bc)) !important;
            border: 1px solid hsl(var(--bc) / 0.1) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            padding: 15px !important;
            border-radius: 12px !important;
        }
        .driver-popover-title {
            color: hsl(var(--p)) !important;
            font-size: 16px !important;
            font-weight: 700 !important;
            margin-bottom: 5px !important;
        }
        .driver-popover-description {
            color: hsl(var(--bc) / 0.8) !important;
            font-size: 13px !important;
            line-height: 1.5 !important;
        }
        .driver-popover-navigation-btns {
            margin-top: 15px !important;
        }
        .driver-popover-footer button {
            background: hsl(var(--p)) !important;
            color: hsl(var(--pc)) !important;
            border: none !important;
            text-shadow: none !important;
            font-weight: 600 !important;
            border-radius: 6px !important;
            padding: 6px 12px !important;
            font-size: 12px !important;
            transition: all 0.2s !important;
        }
        .driver-popover-footer button:hover {
            opacity: 0.9 !important;
        }
        .driver-popover-footer .driver-close-btn {
            background: hsl(var(--er) / 0.1) !important;
            color: hsl(var(--er)) !important;
        }
        .driver-popover-footer .driver-close-btn:hover {
            background: hsl(var(--er) / 0.2) !important;
        }
        .driver-popover-footer .driver-prev-btn {
            background: hsl(var(--n) / 0.1) !important;
            color: hsl(var(--bc)) !important;
        }
        .driver-popover-footer .driver-prev-btn:hover {
            background: hsl(var(--n) / 0.2) !important;
        }
        /* Elemento resaltado: fondo semitransparente + borde blanco para que sea visible */
        .driver-highlighted-element {
            background: rgba(255, 238, 6, 1) !important;
            border: 2px solid rgba(255, 255, 255, 1) !important;
            border-radius: 8px !important;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.15), 0 0 20px rgba(255, 255, 255, 0.1) !important;
        }
        /* Área recortada del overlay: también semi-opaca para ver el contenido */
        .driver-stage-no-animation, #driver-highlighted-element-stage {
            background: rgba(255, 255, 255, 0.06) !important;
        }
    </style>
</head>

<body class="bg-base-100 text-base-content min-h-screen">
    <?php $this->beginBody() ?>

    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex flex-col bg-base-200 min-h-screen">

            <div class="navbar bg-base-100 shadow-md px-4 w-full">
                <!-- Mobile navbar content -->
                <div class="flex-none lg:hidden">
                    <label for="my-drawer" aria-label="open sidebar" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            class="inline-block w-6 h-6 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </label>
                </div>
                
                <!-- Logo / Title -->
                <div class="flex-1">
                    <span class="font-extrabold text-xl tracking-tight text-primary px-2">
                        <a href="/">Inicio</a> 
                        <span class="text-base font-normal text-base-content/60 hidden sm:inline"> &raquo; 
                    <?php 
                        echo $this->title ?? 'Área de Clientes'
                    ?>    
                    </span></span>
                </div>

                <!-- Mobile Actions (visible only on mobile) -->
                <div class="flex-none lg:hidden flex items-center gap-2">
                    <?php if (!Yii::$app->user->isGuest): ?>
                        <?php if (!Yii::$app->user->identity->isAdmin): ?>
                            <button id="start-tour-btn-mobile" class="btn btn-ghost btn-sm btn-circle text-primary" title="Guía Rápida">
                                💡
                            </button>
                        <?php endif; ?>
                        
                        <!-- Avatar / Link to profile -->
                        <a href="/profile" class="btn btn-ghost btn-sm btn-circle avatar placeholder">
                            <div class="bg-primary text-primary-content rounded-full w-8">
                                <span class="text-xs font-bold"><?= strtoupper(substr(Yii::$app->user->identity->username ?? 'U', 0, 1)) ?></span>
                            </div>
                        </a>

                        <!-- Logout -->
                        <?= Html::a(
                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>',
                            ['/site/logout'],
                            [
                                'data-method' => 'post',
                                'class' => 'btn btn-ghost btn-sm btn-circle text-error',
                                'title' => 'Cerrar Sesión',
                                'encode' => false
                            ]
                        ) ?>
                    <?php endif; ?>
                </div>

                <!-- Desktop Actions (visible only on desktop) -->
                <div class="flex-none hidden lg:flex items-center gap-4">
                    <?php if (!Yii::$app->user->isGuest): ?>
                        <?php if (!Yii::$app->user->identity->isAdmin): ?>
                            <button id="start-tour-btn" class="btn btn-primary btn-sm rounded-full gap-2 shadow-md hover:scale-105 active:scale-95 transition-all text-white font-semibold">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l8.904-.813a1.996 1.996 0 001.484-1.484L21 9.813a1.996 1.996 0 00-1.484-1.484L9.813 15.904z" />
                                </svg>
                                Guía Rápida 💡
                            </button>
                        <?php endif; ?>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown dropdown-end">
                            <label tabindex="0" class="btn btn-ghost btn-circle avatar placeholder hover:ring-2 hover:ring-primary transition-all">
                                <div class="bg-primary text-primary-content rounded-full w-10 shadow-inner">
                                    <span class="text-sm font-bold"><?= strtoupper(substr(Yii::$app->user->identity->username ?? 'U', 0, 2)) ?></span>
                                </div>
                            </label>
                            <ul tabindex="0" class="menu dropdown-content z-[30] p-2 shadow-2xl bg-base-100 rounded-2xl w-60 mt-4 border border-base-200">
                                <div class="px-4 py-3">
                                    <p class="font-bold text-base text-base-content leading-tight"><?= Html::encode(Yii::$app->user->identity->username) ?></p>
                                    <p class="text-xs text-base-content/60 mt-0.5"><?= Yii::$app->user->identity->isAdmin ? 'Administrador' : 'Cliente ATSYS' ?></p>
                                </div>
                                <div class="divider my-0 opacity-50"></div>
                                <li>
                                    <a href="/profile" class="flex items-center gap-3 py-2.5 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-base-content/60">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Mi Perfil
                                    </a>
                                </li>
                                <?php if (Yii::$app->user->identity->isAdmin): ?>
                                    <li>
                                        <a href="/site/settings" class="flex items-center gap-3 py-2.5 rounded-lg">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-base-content/60">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.212 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Configuración
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <div class="divider my-0 opacity-50"></div>
                                <li>
                                    <?= Html::a(
                                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-error/80"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg> Cerrar Sesión',
                                        ['/site/logout'],
                                        [
                                            'data-method' => 'post',
                                            'class' => 'text-error font-semibold flex items-center gap-3 py-2.5 rounded-lg hover:bg-error/10',
                                            'encode' => false
                                        ]
                                    ) ?>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            $urgentAlert = \app\models\Announcements::findActive()
                ->andWhere(['type' => 'danger']) // Solo las rojas/urgentes
                ->orderBy(['created_at' => SORT_DESC]) // La más reciente
                ->one();
            ?>

            <?php if ($urgentAlert): ?>
                <div class="container mx-auto px-4 mt-4">
                    <div class="alert alert-error shadow-lg text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="w-full">
                            <h3 class="font-bold text-lg"><?= \yii\helpers\Html::encode($urgentAlert->title) ?></h3>
                            <div class="text-sm opacity-90">
                                <?= \yii\helpers\Html::decode($urgentAlert->content) // Usamos decode si guardas HTML básico ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (
                !Yii::$app->user->isGuest && !Yii::$app->user->identity->isAdmin &&
                (Yii::$app->user->identity->role == 10 || Yii::$app->user->identity->role == 12)
            ): ?>
                <?php if (Yii::$app->user->identity->mobile == '') { ?>
                    <div class="container mx-auto px-4 mt-4">
                        <div class="alert alert-warning shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div class="w-full">
                                <h3 class="font-bold text-lg">Alerta de Seguridad</h3>
                                <div class="text-sm opacity-90">
                                    Tu cuenta no tiene un número de teléfono registrado. Por favor, actualiza tu perfil para
                                    recibir notificaciones de seguridad.
                                </div>
                                <div class="mt-2">
                                    <?= Html::a('Actualizar Perfil', ['/profile'], ['class' => 'btn btn-primary text-white shadow-lg']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php endif; ?>

            <div class="p-6">
                <?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?>
                    <?php
                    $alertClass = 'alert-info';
                    $icon = '';

                    switch ($type) {
                        case 'success':
                            $alertClass = 'alert-success text-white'; // Verde
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                            break;
                        case 'error':
                        case 'danger':
                            $alertClass = 'alert-error text-white'; // Rojo
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                            break;
                        case 'warning':
                            $alertClass = 'alert-warning'; // Amarillo
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>';
                            break;
                        default:
                            // Info por defecto
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                    }
                    ?>

                    <div role="alert" class="alert <?= $alertClass ?> mb-5 shadow-lg flex items-center">
                        <?= $icon ?>
                        <span class="font-medium"><?= $message ?></span>

                        <button onclick="this.parentElement.style.display='none'"
                            class="btn btn-sm btn-ghost btn-circle ml-auto">✕</button>
                    </div>
                <?php endforeach; ?>
                <?= $content ?>
            </div>

        </div>

        <div class="drawer-side z-20">
            <label for="my-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            <div class="menu p-4 w-80 min-h-full bg-base-100 text-base-content flex flex-col justify-between">
                <div>
                    <!-- Logo ATSYS -->
                    <div class="mb-6 px-4 py-2 flex justify-center">
                        <img src="https://static.atsys.co/img/email/atsys-email-customer-tpl.png" alt="Logo ATSYS" class="w-1/2 px-0" />
                    </div>

                    <?php
                    // Helper to check active state
                    $controllerId = Yii::$app->controller->id;
                    $actionId = Yii::$app->controller->action->id;

                    $isDashboardActive = ($controllerId === 'site' && $actionId === 'index');
                    $isTicketsActive = ($controllerId === 'tickets');
                    $isCustomersActive = ($controllerId === 'customers');
                    $isServersActive = ($controllerId === 'servers');
                    $isProductsActive = ($controllerId === 'products');
                    $isServicesActive = ($controllerId === 'customer-services');
                    $isTeamActive = ($controllerId === 'subaccounts');
                    $isWorkOrdersActive = ($controllerId === 'work-orders');
                    $isAnnouncementsActive = ($controllerId === 'announcements');

                    // Compute badge counts
                    $ticketBadgeCount = 0;
                    $workOrderBadgeCount = 0;
                    if (!Yii::$app->user->isGuest) {
                        if (Yii::$app->user->identity->isAdmin) {
                            $ticketBadgeCount = (int) \app\models\Tickets::find()
                                ->where(['in', 'status', ['open', 'customer_reply']])
                                ->count();
                            $workOrderBadgeCount = (int) \app\models\WorkOrders::find()
                                ->where(['is_request' => 1])
                                ->count();
                        } else {
                            $realCustomerId = Yii::$app->user->identity->getRealCustomerId() ?? -1;
                            $ticketBadgeCount = (int) \app\models\Tickets::find()
                                ->where(['customer_id' => $realCustomerId])
                                ->andWhere(['status' => 'answered'])
                                ->count();
                            $workOrderBadgeCount = (int) \app\models\WorkOrders::find()
                                ->where(['customer_id' => $realCustomerId])
                                ->andWhere(['status' => 1]) // Pending approval
                                ->count();
                        }
                    }
                    ?>

                    <ul class="space-y-1.5 font-semibold text-sm">
                        <!-- Dashboard -->
                        <li>
                            <a href="/" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isDashboardActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25a2.25 2.25 0 01-2.25 2.25h-2.25A2.25 2.25 0 0113.5 6V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25h-2.25a2.25 2.25 0 01-2.25-2.25v-2.25z" /></svg>
                                Dashboard
                            </a>
                        </li>

                        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin): ?>
                            <!-- Admin Tickets -->
                            <li>
                                <a href="/tickets/" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isTicketsActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v9.632c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" /></svg>
                                    Tickets
                                    <?php if ($ticketBadgeCount > 0): ?>
                                        <span class="badge badge-error badge-sm font-bold ml-auto animate-pulse text-white shadow-sm"><?= $ticketBadgeCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>

                            <!-- Admin Clientes -->
                            <li>
                                <a href="/customers/" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isCustomersActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                    Clientes
                                </a>
                            </li>

                            <!-- Admin Servidores -->
                            <li>
                                <a href="/servers/" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isServersActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a2.25 2.25 0 01-2.25-2.25V4.5A2.25 2.25 0 014.5 2.25h15a2.25 2.25 0 012.25 2.25v7.5a2.25 2.25 0 01-2.25 2.25m-13.5 0h13.5m-13.5 0a2.25 2.25 0 00-2.25 2.25v2.25a2.25 2.25 0 002.25 2.25h13.5a2.25 2.25 0 002.25-2.25V16.5a2.25 2.25 0 00-2.25-2.25" /></svg>
                                    Servidores
                                </a>
                            </li>

                            <!-- Admin Productos -->
                            <li>
                                <a href="/products/" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isProductsActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                                    Productos
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (!Yii::$app->user->isGuest && !Yii::$app->user->identity->isAdmin): ?>
                            <div class="divider text-xs font-bold opacity-50 px-4 my-2 uppercase tracking-wider">Mi Cuenta</div>

                            <?php if (Yii::$app->user->identity->role == 10 || Yii::$app->user->identity->role == 12): ?>
                                <!-- Client Services -->
                                <li>
                                    <a href="/customer-services/index" id="tour-services" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isServicesActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.25l.07-.11a.75.75 0 01.71-.41h15.64a.75.75 0 01.71.41l.07.11a4.5 4.5 0 01.9 2.25M3.75 14.25V6m16.5 8.25V6" /></svg>
                                        Mis Servicios
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if (Yii::$app->user->identity->role == 10 || Yii::$app->user->identity->role == 12): ?>
                                <!-- Client Team -->
                                <li>
                                    <a href="/subaccounts/index" id="tour-team" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isTeamActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                        Mi Equipo (Sub-cuentas)
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Client Tickets -->
                            <li>
                                <a href="/tickets/index" id="tour-tickets" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isTicketsActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" /></svg>
                                    Tickets
                                    <?php if ($ticketBadgeCount > 0): ?>
                                        <span class="badge badge-accent badge-sm font-bold ml-auto animate-pulse text-white shadow-sm"><?= $ticketBadgeCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (!Yii::$app->user->isGuest): ?>
                            <?php if (Yii::$app->user->identity->isAdmin || Yii::$app->user->identity->role == 10 || Yii::$app->user->identity->role == 12): ?>
                                <!-- Work Orders -->
                                <li>
                                    <a href="/work-orders/index" id="tour-orders" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isWorkOrdersActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125 1.125 1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" /></svg>
                                        Órdenes de Trabajo
                                        <?php if ($workOrderBadgeCount > 0): ?>
                                            <span class="badge badge-accent badge-sm font-bold ml-auto animate-pulse text-white shadow-sm"><?= $workOrderBadgeCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin): ?>
                            <div class="divider text-xs font-bold opacity-50 px-4 my-2 uppercase tracking-wider">Ajustes</div>

                            <!-- Admin Announcements -->
                            <li>
                                <a href="/announcements/index" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isAnnouncementsActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                                    Novedades
                                </a>
                            </li>

                            <!-- Admin Settings -->
                            <li>
                                <a href="/site/settings" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= ($controllerId === 'site' && $actionId === 'settings') ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.212 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    Configuración
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Bottom Part: Cerrar Sesión quick access -->
                <?php if (!Yii::$app->user->isGuest): ?>
                    <div class="px-2 mt-auto pt-4 border-t border-base-200/50">
                        <?= Html::a(
                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg> Cerrar Sesión',
                            ['/site/logout'],
                            [
                                'data-method' => 'post',
                                'class' => 'btn btn-error btn-outline btn-sm w-full rounded-xl flex items-center justify-center font-bold',
                                'encode' => false
                            ]
                        ) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin): ?>
        <script type="module">
            import { initializeApp } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-app.js";
            import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging.js";

            const firebaseConfig = {
                apiKey: "AIzaSyBDs6Nnkad5JaCPLh7_b_FPEyRFUGHUTTg",
                authDomain: "atsys-client-area.firebaseapp.com",
                projectId: "atsys-client-area",
                storageBucket: "atsys-client-area.firebasestorage.app",
                messagingSenderId: "171390167252",
                appId: "1:171390167252:web:9036a477a8e6bd4942b341",
                measurementId: "G-FGSGR9B5MT"
            };

            const app = initializeApp(firebaseConfig);
            const messaging = getMessaging(app);

            // Solicitar permiso al cargar la página
            Notification.requestPermission().then((permission) => {
                if (permission === 'granted') {
                    console.log('Permiso de notificación concedido.');

                    // Obtener el Token
                    getToken(messaging, { vapidKey: 'BMkkCkbfEgkmxKZ2s7-ygaV2MDlnqcNn6bvWrlzDmsa-o7TTpdMrn9DaHYaRsx8S814sNPF7nvuUFtpLWM71ET8' }).then((currentToken) => {
                        if (currentToken) {
                            // ENVIAR TOKEN A TU SERVIDOR YII2
                            saveTokenToDatabase(currentToken);
                        } else {
                            console.log('No se pudo obtener el token.');
                        }
                    }).catch((err) => {
                        console.log('Error al obtener token: ', err);
                    });
                }
            });

            // Función para guardar en BD vía AJAX
            function saveTokenToDatabase(token) {
                const formData = new FormData();
                formData.append('token', token);
                formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

                fetch('<?= \yii\helpers\Url::to(['/site/save-push-token']) ?>', {
                    method: 'POST',
                    body: formData
                });
            }

            onMessage(messaging, (payload) => {
                // Extraemos la URL (Firebase v9 mapea fcm_options.link a fcmOptions.link)
                const linkDestino = payload.fcmOptions?.link || '/';

                // 1. Notificación Nativa del Sistema Operativo
                if (Notification.permission === 'granted') {
                    navigator.serviceWorker.ready.then((registration) => {
                        registration.showNotification(payload.notification.title, {
                            body: payload.notification.body,
                            icon: '/images/icon-192.png',
                            requireInteraction: true,
                            data: { url: linkDestino } // <-- ¡Crucial! Para que el SW lo abra al hacer clic
                        });
                    });
                }

                // 2. Notificación Visual con SweetAlert2
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: payload.notification.title,
                        text: payload.notification.body,
                        icon: 'info',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: !!linkDestino, // Mostrar botón solo si hay enlace
                        confirmButtonText: 'Ver más',
                        timer: 7000, // Le damos 2 segundos extras
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            // Pausar el temporizador si el usuario pasa el mouse por encima
                            toast.addEventListener('mouseenter', Swal.stopTimer);
                            toast.addEventListener('mouseleave', Swal.resumeTimer);

                            // Hacer que el área de la notificación cambie el cursor a la "mano"
                            if (linkDestino) {
                                toast.style.cursor = 'pointer';
                                toast.addEventListener('click', (e) => {
                                    // Redirigir si hace clic en cualquier parte, excepto si hizo clic justo en el botón (para evitar doble ejecución)
                                    if (!e.target.matches('.swal2-confirm')) {
                                        window.location.href = linkDestino;
                                    }
                                });
                            }
                        }
                    }).then((result) => {
                        // Redirigir si el usuario hizo clic explícitamente en el botón "Ver más"
                        if (result.isConfirmed && linkDestino) {
                            window.location.href = linkDestino;
                        }
                    });
                }
            });
        </script>
    <?php endif; ?>

    <!-- Global Helper Scripts (Theme, Preloader, Tour) -->
    <script>
        const getCsrf = () => {
            return {
                param: document.querySelector('meta[name="csrf-param"]')?.getAttribute('content'),
                token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            };
        };

        // Real-time system theme preference listener
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
            const isDark = event.matches;
            const newTheme = isDark ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            document.documentElement.classList.toggle('dark', isDark);

            // Re-initialize TinyMCE on-the-fly if active
            if (typeof tinymce !== 'undefined') {
                tinymce.editors.forEach(function(editor) {
                    const editorId = editor.id;
                    const currentContent = editor.getContent();
                    tinymce.remove('#' + editorId);
                    
                    tinymce.init({
                        selector: '#' + editorId,
                        height: 300,
                        menubar: false,
                        statusbar: false,
                        language: 'es',
                        skin: isDark ? 'oxide-dark' : 'oxide',
                        content_css: isDark ? 'dark' : 'default',
                        plugins: 'lists link autolink fullscreen image code',
                        toolbar: 'bold italic underline | bullist numlist | link image | removeformat | fullscreen | blockquote',
                        branding: false,
                        setup: function (editorInstance) {
                            editorInstance.on('init', function() {
                                editorInstance.setContent(currentContent);
                            });
                            editorInstance.on('change', function () {
                                editorInstance.save();
                            });
                        },
                        paste_data_images: true,
                        automatic_uploads: true,
                        paste_preprocess: (plugin, args) => {
                            if (args.content.indexOf('src="data:image') !== -1) {
                                args.content = args.content.replace(/<img[^>]*src="data:image[^>]*>/gi, ' [Imagen bloqueada: Por favor usa el botón de subir imagen] ');
                                alert("No se permite pegar imágenes directamente. Por favor, usa la opción de 'Insertar Imagen'.");
                            }
                        },
                        images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
                            const xhr = new XMLHttpRequest();
                            xhr.withCredentials = false;
                            xhr.open('POST', '/tickets/upload-image', true);
                            const csrf = getCsrf();
                            if (csrf.token) {
                                xhr.setRequestHeader("X-CSRF-Token", csrf.token);
                            }
                            xhr.upload.onprogress = (e) => {
                                progress(e.loaded / e.total * 100);
                            };
                            xhr.onload = () => {
                                if (xhr.status < 200 || xhr.status >= 300) {
                                    reject('Error del servidor (Código: ' + xhr.status + ')');
                                    return;
                                }
                                const json = JSON.parse(xhr.responseText);
                                if (json && json.error) {
                                    reject(json.error);
                                    return;
                                }
                                if (!json || typeof json.location != 'string') {
                                    reject('Respuesta del servidor inválida');
                                    return;
                                }
                                resolve(json.location);
                            };
                            xhr.onerror = () => {
                                reject('Error de red o conexión fallida.');
                            };
                            const formData = new FormData();
                            if (csrf.param) {
                                formData.append(csrf.param, csrf.token);
                            }
                            formData.append('file', blobInfo.blob(), blobInfo.filename());
                            xhr.send(formData);
                        })
                    });
                });
            }
        });

        // Global Preloader and Submit Button Disabler
        document.addEventListener('DOMContentLoaded', function() {
            // Function to disable button and show spinner
            function disableSubmitButton(btn) {
                if (!btn || btn.disabled) return;
                btn.disabled = true;
                btn.classList.add('loading', 'btn-disabled', 'opacity-80', 'cursor-not-allowed');
                if (btn.tagName === 'BUTTON') {
                    btn.innerHTML = '<span class="loading loading-spinner loading-sm mr-2"></span> Procesando...';
                } else {
                    btn.value = 'Procesando...';
                }
            }

            // 1. Hook into jQuery for Yii 2 ActiveForms (only triggers after validation passes!)
            if (typeof jQuery !== 'undefined') {
                jQuery(document).on('beforeSubmit', 'form', function() {
                    const form = jQuery(this);
                    if (form.attr('method')?.toLowerCase() === 'get') return;
                    const btn = form.find('button[type="submit"], input[type="submit"]');
                    if (btn.length) {
                        disableSubmitButton(btn[0]);
                    }
                });
            }

            // 2. Fallback for non-Yii or simple submit forms (e.g. Logout button, delete actions)
            document.addEventListener('submit', function(e) {
                const form = e.target;
                // Skip search forms (GET) and forms already handled by ActiveForm to prevent double disabling
                if (form.getAttribute('method')?.toLowerCase() === 'get' || (typeof jQuery !== 'undefined' && jQuery(form).data('yiiActiveForm'))) {
                    return;
                }
                const btn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (btn) {
                    disableSubmitButton(btn);
                }
            });
        });
    </script>

    <?php if (!Yii::$app->user->isGuest && !Yii::$app->user->identity->isAdmin): ?>
        <!-- Tour Interactivo script -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const driver = new Driver({
                    animate: true,
                    opacity: 0.4,
                    padding: 5,
                    allowClose: true,
                    overlayClickNext: false,
                    doneBtnText: '¡Entendido!',
                    closeBtnText: 'Cerrar',
                    nextBtnText: 'Siguiente',
                    prevBtnText: 'Atrás'
                });

                const allSteps = [
                    {
                        element: '#tour-services',
                        popover: {
                            title: '⚡ Mis Servicios',
                            description: 'Consulta los servicios de hosting, dominios y licencias contratados.',
                            position: 'right'
                        }
                    },
                    {
                        element: '#tour-team',
                        popover: {
                            title: '👥 Mi Equipo (Sub-cuentas)',
                            description: 'Permite que otros usuarios puedan enviar tickets y delegar tus mismas funciones sin compartir tu contraseña.',
                            position: 'right'
                        }
                    },
                    {
                        element: '#tour-tickets',
                        popover: {
                            title: '🎫 Soporte y Tickets',
                            description: 'Reporta cualquier fallo con tus servicios o desarrollos.',
                            position: 'right'
                        }
                    },
                    {
                        element: '#tour-orders',
                        popover: {
                            title: '📋 Órdenes de Trabajo',
                            description: 'Solicita un nuevo servicio o consulta los avances que hay sobre un proyecto en curso.',
                            position: 'right'
                        }
                    }
                ];

                // Filtrar dinámicamente los pasos para registrar únicamente los elementos presentes en el DOM.
                // Esto previene que Driver.js aborte o falle al intentar enfocar un elemento ausente (ej. subcuentas sin privilegios).
                const tourSteps = allSteps.filter(step => document.querySelector(step.element) !== null);

                if (tourSteps.length > 0) {
                    driver.defineSteps(tourSteps);
                }

                function startTour(e) {
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation(); // Detiene la propagación del evento para evitar que el overlay del Driver se cierre de inmediato
                    }
                    if (tourSteps.length === 0) return;

                    const drawerCheckbox = document.getElementById('my-drawer');
                    if (window.innerWidth < 1024 && drawerCheckbox && !drawerCheckbox.checked) {
                        drawerCheckbox.checked = true;
                        setTimeout(() => {
                            driver.start();
                        }, 300);
                    } else {
                        driver.start();
                    }
                }

                const btnDesktop = document.getElementById('start-tour-btn');
                const btnMobile = document.getElementById('start-tour-btn-mobile');

                if (btnDesktop) btnDesktop.addEventListener('click', startTour);
                if (btnMobile) btnMobile.addEventListener('click', startTour);

                // Auto-run tour once
                if (!localStorage.getItem('atsys_tour_done')) {
                    setTimeout(() => {
                        // Lanzamiento automático seguro
                        if (tourSteps.length > 0) {
                            startTour();
                        }
                    }, 1500);
                    localStorage.setItem('atsys_tour_done', 'true');
                }
            });
        </script>
    <?php endif; ?>
    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>