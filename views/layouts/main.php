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
<html lang="<?= Yii::$app->language ?>" data-theme="atsys_theme">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <title><?= Html::encode($this->title) ?></title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#134C42">
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="drawer lg:drawer-open"> <input id="my-drawer" type="checkbox" class="drawer-toggle" />
    
    <div class="drawer-content flex flex-col bg-base-200 min-h-screen">
        
        <div class="navbar bg-base-100 shadow-sm w-full">
            <div class="flex-none lg:hidden">
                <label for="my-drawer" aria-label="open sidebar" class="btn btn-square btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </label>
            </div>
            <div class="flex-1 px-2 mx-2 font-bold text-xl">
                <ul class="menu menu-horizontal">
                    <li><a href="/">Inicio</a></li>
                </ul>
            </div>
            <div class="flex-none hidden lg:block">
                <ul class="menu menu-horizontal">
                    <li><a href="/profile">Perfil</a></li>
                </ul>
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
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <div class="w-full">
                        <h3 class="font-bold text-lg"><?= \yii\helpers\Html::encode($urgentAlert->title) ?></h3>
                        <div class="text-sm opacity-90">
                            <?= \yii\helpers\Html::decode($urgentAlert->content) // Usamos decode si guardas HTML básico ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="p-6">
            <?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?>
                <?php
                    $alertClass = 'alert-info';
                    $icon = '';
                    
                    switch ($type) {
                        case 'success':
                            $alertClass = 'alert-success text-white'; // Verde
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                            break;
                        case 'error':
                        case 'danger':
                            $alertClass = 'alert-error text-white'; // Rojo
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                            break;
                        case 'warning':
                            $alertClass = 'alert-warning'; // Amarillo
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>';
                            break;
                        default:
                            // Info por defecto
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                    }
                ?>
                
                <div role="alert" class="alert <?= $alertClass ?> mb-5 shadow-lg flex items-center">
                    <?= $icon ?>
                    <span class="font-medium"><?= $message ?></span>
                    
                    <button onclick="this.parentElement.style.display='none'" class="btn btn-sm btn-ghost btn-circle ml-auto">✕</button>
                </div>
            <?php endforeach; ?>
            <?= $content ?>
        </div>
        
    </div> 
    
    <div class="drawer-side z-20">
        <label for="my-drawer" aria-label="close sidebar" class="drawer-overlay"></label> 
        <ul class="menu p-4 w-80 min-h-full bg-base-100 text-base-content">
            <li class="mb-4 text-2xl font-bold px-4 text-primary text-center">
                <img src="https://static.atsys.co/img/email/atsys-email-customer-tpl.png" alt="Logo ATSYS" class="w-1/2 px-0" />
            </li>
            
            <li>
                <?= Html::a(
                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg> Dashboard',
                    ['/'],
                    ['encode' => false, 'class' => 'flex items-center']
                ) ?>
            </li>

            <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin): ?>
                <li>
                    <?= Html::a(
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v9.632c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" /></svg> Tickets',
                        ['/tickets/'],
                        ['encode' => false, 'class' => 'flex items-center']
                    ) ?>
                </li>
                <li>
                    <?= Html::a(
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg> Clientes',
                        ['/customers/'],
                        ['encode' => false, 'class' => 'flex items-center']
                    ) ?>
                </li>

                <li>
                    <?= Html::a(
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg> Productos', 
                        ['/products/'], 
                        ['encode' => false, 'class' => 'flex items-center']
                    ) ?>
                </li>
            <?php endif; ?>

            <?php if (!Yii::$app->user->isGuest && !Yii::$app->user->identity->isAdmin): ?>
                
                <div class="divider text-xs font-bold opacity-50">MI CUENTA</div>

                <li>
                    <?= Html::a(
                        // Ícono de Servidores / Stack
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.25l.07-.11a.75.75 0 01.71-.41h15.64a.75.75 0 01.71.41l.07.11a4.5 4.5 0 01.9 2.25M3.75 14.25V6m16.5 8.25V6" />
                        </svg> 
                        Mis Servicios',
                        ['/customer-services/index'],
                        ['encode' => false, 'class' => 'flex items-center font-semibold']
                    ) ?>
                </li>

                <li>
                    <?= Html::a(
                        // Ícono de Salvavidas / Ayuda
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                        </svg> 
                        Soporte Técnico',
                        ['/tickets/index'],
                        ['encode' => false, 'class' => 'flex items-center font-semibold']
                    ) ?>
                </li>

            <?php endif; ?>

            <?php if (!Yii::$app->user->isGuest): ?>
                
                <li>
                    <?= Html::a(
                        // Ícono: Portapapeles / Orden de Trabajo
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                        </svg>
                        Órdenes de Trabajo',
                        ['/work-orders/index'],
                        ['encode' => false, 'class' => 'flex items-center font-semibold']
                    ) ?>
                </li>

            <?php endif; ?>
            
            <div class="divider"></div>
            
            <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin): ?>
            <li>
                <?= Html::a(
                    // 1. EL CONTENIDO (Ícono + Texto)
                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                    </svg> 
                    Novedades',
                    ['announcements/index'], 
                    ['encode' => false, 'class' => 'flex items-center']
                ) ?>
            </li>
            <li>
                <?= Html::a(
                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.212 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg> Configuración',
                    ['/site/settings'],
                    ['encode' => false, 'class' => 'flex items-center']
                ) ?>
            </li>
            <?php endif; ?>
            
            <li>
                <?= Html::a(
                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg> Cerrar Sesión',
                    ['/site/logout'],
                    [
                        'data-method' => 'post',
                        'class' => 'text-error flex items-center',
                        'encode' => false // ¡Importante!
                    ]
                ) ?>
            </li>
        </ul>
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
    // Verificamos si el navegador tiene permiso
    if (Notification.permission === 'granted') {
        navigator.serviceWorker.ready.then((registration) => {
            registration.showNotification(payload.notification.title, {
                body: payload.notification.body,
                icon: '/images/icon-192.png',
                requireInteraction: true // Hace que la notificación no desaparezca sola rápido
            });
        });

        // Si usas SweetAlert2
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: payload.notification.title,
                text: payload.notification.body,
                icon: 'info',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000
            });
        }
    }
});
</script>
<?php endif; ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>