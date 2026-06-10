<?php

/** @var yii\web\View $this */

$this->title = 'Manual de CyberPanel';
?>

<div class="container mx-auto px-4 py-8 max-w-lg text-center">
    <!-- Breadcrumbs -->
    <div class="text-sm breadcrumbs mb-10 text-base-content/60 flex justify-center">
        <ul>
            <li><a href="/" class="hover:text-primary transition-colors">Inicio</a></li>
            <li><a href="/help" class="hover:text-primary transition-colors">Centro de Ayuda</a></li>
            <li class="text-base-content font-semibold">CyberPanel</li>
        </ul>
    </div>

    <!-- Main Card -->
    <div class="card bg-base-100 border border-base-200 shadow-xl p-8 rounded-3xl">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-secondary/10 flex items-center justify-center text-secondary mb-6 animate-bounce">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
            </svg>
        </div>

        <h1 class="text-2xl font-bold mb-4">Guía de CyberPanel</h1>
        <span class="badge badge-accent badge-lg text-white font-bold mx-auto mb-4">Próximamente</span>
        
        <p class="text-sm text-base-content/75 leading-relaxed mb-8">
            Estamos preparando un manual completo y amigable en español sobre el uso de CyberPanel con OpenLiteSpeed. Pronto aprenderás a gestionar tu administrador de archivos, bases de datos optimizadas y certificados SSL con un solo clic.
        </p>

        <div class="flex flex-col gap-3">
            <a href="/help" class="btn btn-primary text-white rounded-xl shadow-md font-semibold">
                Volver al Centro de Ayuda
            </a>
            <a href="/tickets/create" class="btn btn-ghost text-xs hover:bg-base-200 rounded-xl">
                ¿Tienes una urgencia con tu hosting? Abre un Ticket
            </a>
        </div>
    </div>
</div>
