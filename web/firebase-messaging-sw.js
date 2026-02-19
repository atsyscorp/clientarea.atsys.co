// Importar scripts de Firebase (versión compat para SW)
importScripts('https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js');

// Configuración (Pega aquí lo que copiaste de Firebase Console)
firebase.initializeApp({
    apiKey: "AIzaSyBDs6Nnkad5JaCPLh7_b_FPEyRFUGHUTTg",
    authDomain: "atsys-client-area.firebaseapp.com",
    projectId: "atsys-client-area",
    storageBucket: "atsys-client-area.firebasestorage.app",
    messagingSenderId: "171390167252",
    appId: "1:171390167252:web:9036a477a8e6bd4942b341",
    measurementId: "G-FGSGR9B5MT"
});

const messaging = firebase.messaging();

// Manejar notificaciones en segundo plano
messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Notificación recibida:', payload);

    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: '/images/icon-192.png', // Icono de la notificación
        data: { url: payload.data.click_action } // Para abrir el ticket al hacer clic
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});

// Evento al hacer clic en la notificación
self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url || '/')
    );
});