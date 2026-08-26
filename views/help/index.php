<?php

/** @var yii\web\View $this */

$this->title = 'Centro de Ayuda';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Hero Header -->
    <div class="relative overflow-hidden rounded-3xl bg-primary text-primary-content p-8 md:p-12 shadow-xl mb-12">
        <!-- Decorative subtle background shapes -->
        <div class="absolute -right-10 -bottom-10 w-40 h-40 rounded-full bg-base-100/10 blur-xl"></div>
        <div class="absolute -left-10 -top-10 w-40 h-40 rounded-full bg-base-100/10 blur-xl"></div>
        
        <div class="relative z-10 max-w-2xl">
            <span class="inline-block px-3 py-1 rounded-full bg-base-100/20 text-xs font-semibold uppercase tracking-wider mb-4">Soporte Técnico</span>
            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">¿Cómo podemos ayudarte hoy?</h1>
            <p class="text-lg opacity-90 leading-relaxed mb-6">
                Bienvenido a nuestro Centro de Ayuda. Aquí encontrarás guías paso a paso para configurar tu servicio de hosting, correos corporativos, bases de datos y resolver las dudas más frecuentes.
            </p>
        </div>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Card 1: Virtualmin -->
        <div class="card bg-base-100 border border-base-200/60 shadow-md hover:-translate-y-1.5 hover:shadow-xl transition-all duration-300 rounded-2xl flex flex-col justify-between">
            <div class="card-body p-6">
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-4">
                    <!-- Linux/Server icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a2.25 2.25 0 01-2.25-2.25V4.5A2.25 2.25 0 014.5 2.25h15a2.25 2.25 0 012.25 2.25v7.5a2.25 2.25 0 01-2.25 2.25m-13.5 0h13.5m-13.5 0a2.25 2.25 0 00-2.25 2.25v2.25a2.25 2.25 0 002.25 2.25h13.5a2.25 2.25 0 002.25-2.25V16.5a2.25 2.25 0 00-2.25-2.25" />
                    </svg>
                </div>
                <h2 class="card-title text-xl font-bold mb-2">Manual de Virtualmin</h2>
                <p class="text-base-content/70 text-sm leading-relaxed mb-6">
                    Aprende a gestionar tu servidor de hosting Linux con Virtualmin. Configura cuentas FTP, bases de datos MariaDB/MySQL y comprende las métricas del panel.
                </p>
            </div>
            <div class="p-6 pt-0">
                <a href="/help/virtualmin" class="btn btn-primary btn-block text-white rounded-xl shadow-md gap-2 font-semibold">
                    Ver Guía de Virtualmin
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Card 2: CyberPanel -->
        <div class="card bg-base-100 border border-base-200/60 shadow-md hover:-translate-y-1.5 hover:shadow-xl transition-all duration-300 rounded-2xl flex flex-col justify-between">
            <div class="card-body p-6">
                <div class="w-12 h-12 rounded-xl bg-secondary/10 flex items-center justify-center text-secondary mb-4">
                    <!-- Lightning Bolt icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
                <div class="flex items-center gap-2 mb-2">
                    <h2 class="card-title text-xl font-bold">Manual de CyberPanel</h2>
                    <span class="badge badge-accent badge-sm font-bold">Próximamente</span>
                </div>
                <p class="text-base-content/70 text-sm leading-relaxed mb-6">
                    Guías para la administración de hosting con OpenLiteSpeed mediante CyberPanel. Administra archivos web, bases de datos y certificados SSL de forma veloz.
                </p>
            </div>
            <div class="p-6 pt-0">
                <a href="/help/cyberpanel" class="btn btn-outline btn-block rounded-xl font-semibold">
                    Ver Guía de CyberPanel
                </a>
            </div>
        </div>

        <!-- Card 3: Preguntas Generales -->
        <div class="card bg-base-100 border border-base-200/60 shadow-md hover:-translate-y-1.5 hover:shadow-xl transition-all duration-300 rounded-2xl flex flex-col justify-between">
            <div class="card-body p-6">
                <div class="w-12 h-12 rounded-xl bg-info/10 flex items-center justify-center text-info mb-4">
                    <!-- Question Mark icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <h2 class="card-title text-xl font-bold mb-2">Soporte & Facturación</h2>
                <p class="text-base-content/70 text-sm leading-relaxed mb-6">
                    Resuelve dudas generales sobre renovación de servicios, ciclos de facturación, suspensión automática y cómo crear tickets de soporte técnico de manera eficiente.
                </p>
            </div>
            <div class="p-6 pt-0">
                <a href="/tickets/index" class="btn btn-ghost btn-block bg-base-200 hover:bg-base-300 rounded-xl font-semibold gap-2">
                    Ir a Tickets
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
