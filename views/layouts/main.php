<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

AppAsset::register($this);

// Nos aseguramos de tener el meta viewport para móviles
$this->registerCsrfMetaTags();
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);

// Cargar notificaciones para el usuario actual
$unreadNotificationsCount = 0;
$recentNotifications = [];
if (!Yii::$app->user->isGuest) {
    try {
        $unreadNotificationsCount = \app\models\Notifications::find()->where(['user_id' => Yii::$app->user->id, 'is_read' => 0])->count();
        $recentNotifications = \app\models\Notifications::find()->where(['user_id' => Yii::$app->user->id])->orderBy(['created_at' => SORT_DESC])->limit(5)->all();
    } catch (\Exception $e) {
        // En caso de que la tabla aún no esté migrada
    }
}
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

        /* Estilos para colapsar/expandir el sidebar en escritorio */
        @media (min-width: 1024px) {
            .drawer.lg:drawer-open {
                display: grid;
                grid-template-columns: 20rem 1fr;
                transition: grid-template-columns 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .drawer.lg:drawer-open.sidebar-collapsed {
                grid-template-columns: 0rem 1fr;
            }
            .drawer-side {
                width: 20rem !important;
                overflow: hidden;
                transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                border-right: 1px solid hsl(var(--bc) / 0.12);
                box-shadow: 6px 0 25px -4px rgba(0, 0, 0, 0.12), 2px 0 10px -2px rgba(0, 0, 0, 0.06);
                z-index: 45;
            }
            .drawer.sidebar-collapsed .drawer-side {
                width: 0rem !important;
                opacity: 0;
                box-shadow: none !important;
                border-right-color: transparent !important;
            }
            
            /* Botón de control del sidebar */
            #sidebar-toggle-btn {
                position: fixed;
                top: 50%;
                left: 320px; /* Ancho del sidebar w-80 = 20rem = 320px */
                transform: translateY(-50%) translateX(-50%);
                z-index: 50;
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s, color 0.2s, box-shadow 0.2s, border-color 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                background-color: hsl(var(--b1));
                border: 1px solid hsl(var(--bc) / 0.18);
                border-radius: 50%;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12), 0 1px 3px rgba(0, 0, 0, 0.08);
                color: hsl(var(--bc) / 0.75);
            }
            #sidebar-toggle-btn:hover {
                background-color: hsl(var(--p));
                color: hsl(var(--pc));
                border-color: hsl(var(--p));
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
                transform: translateY(-50%) translateX(-50%) scale(1.08);
            }
            
            .sidebar-collapsed #sidebar-toggle-btn {
                left: 0px;
                transform: translateY(-50%) translateX(16px);
            }

            .sidebar-collapsed #sidebar-toggle-btn:hover {
                transform: translateY(-50%) translateX(16px) scale(1.08);
            }
            
            /* Rotación del icono de la flecha */
            .sidebar-collapsed #toggle-arrow {
                transform: rotate(180deg);
            }
        }
        @media (max-width: 1023px) {
            #sidebar-toggle-btn {
                display: none !important;
            }
        }
        
        /* Top Preloader Animation (Virtualmin style) */
        #top-preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            z-index: 99999;
            display: none;
            background-color: transparent;
        }
        #top-preloader .preloader-bar {
            position: absolute;
            height: 100%;
            background-color: #dc2626; /* red-600 */
            animation: virtualmin-loader 1.2s ease-in-out infinite alternate;
        }
        @keyframes virtualmin-loader {
            0% { left: 0%; width: 10%; }
            50% { width: 30%; }
            100% { left: 90%; width: 10%; }
        }
        
        /* Offline Overlay Styles */
        .offline-bg-animation {
            background: linear-gradient(90deg, #991b1b 0%, #ef4444 50%, #991b1b 100%);
            background-size: 200% 100%;
            animation: offline-slide-bg 2s ease-in-out infinite alternate;
        }
        @keyframes offline-slide-bg {
            0% { background-position: 0% 0%; }
            100% { background-position: 100% 0%; }
        }
    </style>
</head>

<body class="bg-base-100 text-base-content min-h-screen">
    <?php $this->beginBody() ?>

    <!-- Offline Overlay -->
    <div id="offline-overlay" style="display: none; z-index: 999999; backdrop-filter: blur(5px);" class="fixed inset-0 flex-col items-center justify-center bg-base-100/50">
        <div class="relative overflow-hidden rounded-2xl shadow-2xl px-10 py-8 max-w-md text-center">
            <div class="absolute inset-0 offline-bg-animation opacity-95"></div>
            <div class="relative z-10 text-white flex flex-col items-center gap-4">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" class="w-16 h-16 opacity-90 mb-2">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <line x1="12" y1="20" x2="12.01" y2="20" stroke-width="3" />
                    <path d="M8.5 16.5a5 5 0 0 1 7 0" />
                    <path d="M7 11.5a8 8 0 0 1 10 0" />
                    <path d="M5.5 6.5a11 11 0 0 1 13 0" />
                    <line x1="3" y1="3" x2="21" y2="21" />
                </svg>
                <h2 class="text-3xl font-bold tracking-tight">Sin Conexión</h2>
                <p class="text-base opacity-90 font-medium">Se ha perdido la conexión a internet. Esperando a que se restablezca...</p>
            </div>
        </div>
    </div>

    <!-- Top Preloader -->
    <div id="top-preloader">
        <div class="preloader-bar"></div>
    </div>

    <div class="drawer lg:drawer-open" id="main-drawer">
        <script>
            // Apply sidebar state immediately to prevent layout shift
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                document.getElementById('main-drawer').classList.add('sidebar-collapsed');
            }
        </script>
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />
        
        <!-- Toggle button for desktop sidebar -->
        <button id="sidebar-toggle-btn" class="hidden lg:flex" aria-label="Toggle Sidebar" onclick="toggleSidebar()">
            <svg id="toggle-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 transition-transform duration-300">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </button>

        <div class="drawer-content flex flex-col bg-base-200 min-h-screen">

            <div class="navbar bg-base-100/80 backdrop-blur-md border-b border-base-200/50 shadow-sm px-4 w-full sticky top-0 z-40">
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
                        
                        <!-- Notifications Dropdown (Mobile) -->
                        <div class="dropdown dropdown-end">
                            <label tabindex="0" class="btn btn-ghost btn-circle btn-sm relative hover:scale-105 active:scale-95 transition-all text-base-content/85">
                                <div class="indicator">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                    </svg>
                                    <span id="notifications-badge-mobile" class="badge badge-xs badge-error indicator-item font-bold <?= $unreadNotificationsCount > 0 ? '' : 'hidden' ?>"><?= $unreadNotificationsCount ?></span>
                                </div>
                            </label>
                            <div tabindex="0" class="dropdown-content z-[100] card card-compact w-72 p-2 shadow-2xl bg-base-100 rounded-2xl border border-base-200 mt-2">
                                <div class="card-body p-2">
                                    <h3 class="font-bold text-sm text-base-content flex justify-between items-center px-1">
                                        <span>Notificaciones</span>
                                        <?php if ($unreadNotificationsCount > 0): ?>
                                            <a href="/notifications/mark-all-read" class="text-[11px] text-primary hover:underline font-semibold">Marcar todo leído</a>
                                        <?php endif; ?>
                                    </h3>
                                    <div class="divider my-1 opacity-40"></div>
                                    <ul id="notifications-list-mobile" class="menu p-0 max-h-64 overflow-y-auto gap-0.5">
                                        <?= $this->render('@app/views/notifications/_dropdown', ['recentNotifications' => $recentNotifications, 'isMobile' => true]) ?>
                                    </ul>
                                    <div class="divider my-1 opacity-40"></div>
                                    <div class="text-center">
                                        <a href="/notifications" class="text-xs font-bold text-primary hover:underline">Ver todas</a>
                                    </div>
                                </div>
                            </div>
                        </div>

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

                        <!-- Notifications Dropdown (Desktop) -->
                        <div class="dropdown dropdown-end">
                            <label tabindex="0" class="btn btn-ghost btn-circle relative hover:scale-105 active:scale-95 transition-all text-base-content/85">
                                <div class="indicator">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                    </svg>
                                    <span id="notifications-badge-desktop" class="badge badge-sm badge-error indicator-item font-bold <?= $unreadNotificationsCount > 0 ? '' : 'hidden' ?>"><?= $unreadNotificationsCount ?></span>
                                </div>
                            </label>
                            <div tabindex="0" class="dropdown-content z-[100] card card-compact w-80 p-2 shadow-2xl bg-base-100 rounded-2xl border border-base-200 mt-4">
                                <div class="card-body p-2">
                                    <h3 class="font-bold text-base text-base-content flex justify-between items-center px-2 py-0.5">
                                        <span>Notificaciones</span>
                                        <?php if ($unreadNotificationsCount > 0): ?>
                                            <a href="/notifications/mark-all-read" class="text-xs text-primary hover:underline font-semibold">Marcar todo como leído</a>
                                        <?php endif; ?>
                                    </h3>
                                    <div class="divider my-1 opacity-50"></div>
                                    <ul id="notifications-list-desktop" class="menu p-0 max-h-80 overflow-y-auto gap-0.5">
                                        <?= $this->render('@app/views/notifications/_dropdown', ['recentNotifications' => $recentNotifications, 'isMobile' => false]) ?>
                                    </ul>
                                    <div class="divider my-1 opacity-50"></div>
                                    <div class="text-center py-1">
                                        <a href="/notifications" class="btn btn-ghost btn-sm w-full text-xs font-bold text-primary rounded-xl">Ver todas las notificaciones</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown dropdown-end">
                            <label tabindex="0" class="btn btn-ghost btn-circle avatar placeholder hover:ring-2 hover:ring-primary transition-all">
                                <div class="bg-primary text-primary-content rounded-full w-10 shadow-inner">
                                    <span class="text-sm font-bold"><?= strtoupper(substr(Yii::$app->user->identity->username ?? 'U', 0, 2)) ?></span>
                                </div>
                            </label>
                            <ul tabindex="0" class="menu dropdown-content z-[100] p-2 shadow-2xl bg-base-100 rounded-2xl w-60 mt-4 border border-base-200">
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
                                        <a href="/notifications/create" class="flex items-center gap-3 py-2.5 rounded-lg">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-base-content/60">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.357.205a1.125 1.125 0 01-1.424-.282l-2.078-2.497m5.337-1.724a18.396 18.396 0 010-9.18m0 9.18a18.425 18.425 0 003.38 1.11m-3.38-10.29a18.425 18.425 0 013.38-1.11M19.5 12h.008v.008H19.5V12z" />
                                            </svg>
                                            Nueva Campaña
                                        </a>
                                    </li>
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

            <?php if (Yii::$app->session->has('original_admin_id')): ?>
                <div class="container mx-auto px-4 mt-4">
                    <div class="alert alert-warning shadow-lg border border-warning/20 bg-warning/10 text-warning-content flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6 text-warning" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <div>
                                <span class="font-bold">Modo Impersonación:</span> Estás navegando como el cliente <strong><?= Html::encode(Yii::$app->user->identity->username) ?></strong>.
                            </div>
                        </div>
                        <div>
                            <?= Html::a(
                                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg> Volver a Admin',
                                ['/customers/stop-impersonating'],
                                ['class' => 'btn btn-sm btn-warning font-bold flex items-center shadow-md']
                            ) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            $topAlert = \app\models\Announcements::findActive()
                ->andWhere(['OR', ['is_pinned' => 1], ['type' => 'danger']])
                ->orderBy(['created_at' => SORT_DESC]) // El más reciente
                ->one();
            ?>

            <?php if ($topAlert): ?>
                <?php
                $alertStyle = match ($topAlert->type) {
                    'danger' => ['class' => 'alert-error', 'icon' => '🚨'],
                    'success' => ['class' => 'alert-success', 'icon' => '🎉'],
                    'warning' => ['class' => 'alert-warning', 'icon' => '🛠️'],
                    default => ['class' => 'alert-info', 'icon' => '📢'],
                };
                ?>
                <div class="container mx-auto px-4 mt-4">
                    <div class="alert <?= $alertStyle['class'] ?> shadow-lg">
                        <span class="text-2xl shrink-0"><?= $alertStyle['icon'] ?></span>
                        <div class="w-full">
                            <h3 class="font-bold text-lg"><?= \yii\helpers\Html::encode($topAlert->title) ?></h3>
                            <div class="text-sm opacity-90 prose prose-sm max-w-none text-current">
                                <?= $topAlert->getFormattedContent() ?>
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
                            $alertClass = 'alert-success'; // Verde
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                            break;
                        case 'error':
                        case 'danger':
                            $alertClass = 'alert-error'; // Rojo
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
                        <span class="font-medium"><?= HtmlPurifier::process($message, [
                            // Los mensajes flash se pintan como HTML porque unos pocos usan <b>
                            // para resaltar. Varios interpolan datos del usuario (nombre de
                            // dominio, correo, errores de validacion), asi que se saneen aqui:
                            // HtmlPurifier deja el formato y elimina etiquetas y atributos
                            // ejecutables, incluidos los manejadores de eventos.
                            'HTML.Allowed' => 'b,strong,i,em,br',
                        ]) ?></span>

                        <button onclick="this.parentElement.style.display='none'"
                            class="btn btn-sm btn-ghost btn-circle ml-auto">✕</button>
                    </div>
                <?php endforeach; ?>
                <?= $content ?>
            </div>

        </div>

        <div class="drawer-side z-50">
            <label for="my-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            <div class="menu p-4 w-80 min-h-full bg-base-100 text-base-content flex flex-col justify-between border-r border-base-300 shadow-xl lg:shadow-none">
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
                    $isUsersActive = ($controllerId === 'users');
                    $isServersActive = ($controllerId === 'servers');
                    $isProductsActive = ($controllerId === 'products');
                    $isOrdersActive = ($controllerId === 'orders');
                    $isServicesActive = ($controllerId === 'customer-services');
                    $isTeamActive = ($controllerId === 'subaccounts');
                    $isWorkOrdersActive = ($controllerId === 'work-orders');
                    $isContractsActive = ($controllerId === 'contracts');
                    $isProjectsActive = ($controllerId === 'projects');
                    $isAnnouncementsActive = ($controllerId === 'announcements');

                    $isSpamBlacklistActive = ($controllerId === 'ticket-spam-blacklist');
                    $isHelpActive = ($controllerId === 'help');
                    $isDomainSearchActive = ($controllerId === 'site' && $actionId === 'domain-search');
                    $isNotificationsActive = ($controllerId === 'notifications');
                    $isFeedbackActive = ($controllerId === 'feedback');

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

                        <!-- Buscar Dominio -->
                        <li>
                            <a href="/site/domain-search" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isDomainSearchActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                                Buscar Dominio
                            </a>
                        </li>

                        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin): ?>
                            <!-- Admin Tickets -->
                            <li>
                                <a href="/tickets/" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isTicketsActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v9.632c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" /></svg>
                                    Tickets
                                    <span id="ticket-badge-count" class="badge badge-error badge-sm font-bold ml-auto animate-pulse shadow-sm <?= $ticketBadgeCount > 0 ? '' : 'hidden' ?>"><?= $ticketBadgeCount ?></span>
                                </a>
                            </li>

                            <!-- Admin Clientes -->
                            <li>
                                <a href="/customers/" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isCustomersActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                    Clientes
                                </a>
                            </li>

                            <!-- Admin Usuarios -->
                            <li>
                                <a href="/users/" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isUsersActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A11.386 11.386 0 0 1 10.089 20M4.121 18.548a11.386 11.386 0 0 1 4.968-3.07M14.214 16.058A4.125 4.125 0 0 0 7.533 13.5M14.214 16.058A9.38 9.38 0 0 1 12.625 16.5a9.337 9.337 0 0 1-4.121-.952A4.125 4.125 0 0 0 12 18.125c1.238 0 2.392-.544 3.214-1.488z" />
                                        <circle cx="10" cy="8" r="4" />
                                        <circle cx="18" cy="8" r="3" />
                                    </svg>
                                    Usuarios
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

                            <!-- Admin Órdenes de Pago -->
                            <li>
                                <a href="/orders/" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isOrdersActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                                    Órdenes de Pago
                                </a>
                            </li>

                            <!-- Admin Lista Negra SPAM -->
                            <li>
                                <a href="/ticket-spam-blacklist/" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isSpamBlacklistActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                    Filtro SPAM
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
                                    <span id="ticket-badge-count" class="badge badge-accent badge-sm font-bold ml-auto animate-pulse shadow-sm <?= $ticketBadgeCount > 0 ? '' : 'hidden' ?>"><?= $ticketBadgeCount ?></span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (!Yii::$app->user->isGuest): ?>
                            <!-- Projects -->
                            <li>
                                <a href="/projects/index" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isProjectsActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" /></svg>
                                    Proyectos
                                </a>
                            </li>

                            <!-- Contracts -->
                            <li>
                                <a href="/contracts/index" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isContractsActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                    Contratos
                                </a>
                            </li>
                        <?php endif; ?>


                        <?php if (!Yii::$app->user->isGuest && (Yii::$app->user->identity->isAdmin || Yii::$app->user->identity->role == 10 || Yii::$app->user->identity->role == 12)): ?>
                            <!-- Work Orders -->
                            <li>
                                <a href="/work-orders/index" id="tour-orders" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isWorkOrdersActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125 1.125 1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" /></svg>
                                    Órdenes de Trabajo
                                    <?php if ($workOrderBadgeCount > 0): ?>
                                        <span class="badge badge-accent badge-sm font-bold ml-auto animate-pulse shadow-sm"><?= $workOrderBadgeCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin): ?>
                            <div class="divider text-xs font-bold opacity-50 px-4 my-2 uppercase tracking-wider">Ajustes</div>

                            <!-- Admin Notifications (Campaigns) -->
                            <li>
                                <a href="/notifications/create" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isNotificationsActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.357.205a1.125 1.125 0 01-1.424-.282l-2.078-2.497m5.337-1.724a18.396 18.396 0 010-9.18m0 9.18a18.425 18.425 0 003.38 1.11m-3.38-10.29a18.425 18.425 0 013.38-1.11M19.5 12h.008v.008H19.5V12z" /></svg>
                                    Campañas Publicitarias
                                </a>
                            </li>

                            <!-- Admin Announcements -->
                            <li>
                                <a href="/announcements/index" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isAnnouncementsActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                                    Novedades
                                </a>
                            </li>

                            <!-- Admin Feedback / Encuestas -->
                            <li>
                                <a href="/feedback/index" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isFeedbackActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                                    Encuestas de Satisfacción
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

                        <!-- Help Center (Visible to everyone) -->
                        <li>
                            <a href="/help" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $isHelpActive ? 'active bg-primary text-primary-content shadow-md' : 'hover:bg-base-200 text-base-content/85' ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>
                                Centro de Ayuda
                            </a>
                        </li>
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

                // 3. Actualizar el contador de tickets en tiempo real
                if (typeof window.updateTicketBadge === 'function') {
                    window.updateTicketBadge();
                }
            });
        </script>
    <?php endif; ?>

    <!-- Global Helper Scripts (Theme, Preloader, Tour) -->
    <script>
        function toggleSidebar() {
            const drawer = document.getElementById('main-drawer');
            if (drawer) {
                drawer.classList.toggle('sidebar-collapsed');
                const isCollapsed = drawer.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', isCollapsed ? 'true' : 'false');
                
                // Dispatch resize event to let responsive widgets/charts recalculate size
                window.dispatchEvent(new Event('resize'));
                setTimeout(() => {
                    window.dispatchEvent(new Event('resize'));
                }, 300);
            }
        }

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
    <?php if (!Yii::$app->user->isGuest): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                function pollNotifications() {
                    fetch('/notifications/poll')
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            // 1. Update unread count badges
                            const count = parseInt(data.unreadCount) || 0;
                            
                            const badgeMobile = document.getElementById('notifications-badge-mobile');
                            const badgeDesktop = document.getElementById('notifications-badge-desktop');
                            
                            if (badgeMobile) {
                                badgeMobile.textContent = count;
                                if (count > 0) {
                                    badgeMobile.classList.remove('hidden');
                                } else {
                                    badgeMobile.classList.add('hidden');
                                }
                            }
                            if (badgeDesktop) {
                                badgeDesktop.textContent = count;
                                if (count > 0) {
                                    badgeDesktop.classList.remove('hidden');
                                } else {
                                    badgeDesktop.classList.add('hidden');
                                }
                            }
                            
                            // 2. Update list contents
                            const listMobile = document.getElementById('notifications-list-mobile');
                            const listDesktop = document.getElementById('notifications-list-desktop');
                            
                            if (listMobile && data.htmlMobile !== undefined) {
                                listMobile.innerHTML = data.htmlMobile;
                            }
                            if (listDesktop && data.htmlDesktop !== undefined) {
                                listDesktop.innerHTML = data.htmlDesktop;
                            }
                        })
                        .catch(err => {
                            // Fail silently to avoid spamming logs on session timeout or offline states
                        });
                }
                
                // Poll every 30 seconds
                setInterval(pollNotifications, 30000);
            });
        </script>
    <?php endif; ?>
    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>