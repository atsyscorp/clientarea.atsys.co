<?php

/** @var yii\web\View $this */

$this->title = 'Manual de Virtualmin';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumbs -->
    <div class="text-sm breadcrumbs mb-6 text-base-content/60">
        <ul class="flex flex-wrap gap-2">
            <li><a href="/" class="hover:text-primary transition-colors">Inicio</a></li>
            <li><a href="/help" class="hover:text-primary transition-colors">Centro de Ayuda</a></li>
            <li class="text-base-content font-semibold">Virtualmin</li>
        </ul>
    </div>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
            <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight mb-2">Administración de Virtualmin</h1>
            <p class="text-base-content/70 text-base">
                Guía completa para la administración y uso de tus servicios de hosting aprovisionados bajo Virtualmin.
            </p>
        </div>
        <div>
            <a href="/help" class="btn btn-outline btn-sm rounded-xl gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al Centro de Ayuda
            </a>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- FAQ and Collapse Accordion (Left/Center Column) -->
        <div class="lg:col-span-2 space-y-6">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                </svg>
                Preguntas Frecuentes y Guías Rápidas
            </h3>

            <div class="join join-vertical w-full bg-base-100 border border-base-200 rounded-2xl shadow-sm overflow-hidden">
                <!-- Accordion Item 1: Acceso al Panel -->
                <div class="collapse collapse-arrow join-item border-b border-base-200">
                    <input type="radio" name="virtualmin-accordion" checked="checked" />
                    <div class="collapse-title text-base md:text-lg font-bold flex items-center gap-3 py-4">
                        <span class="badge badge-primary badge-sm text-white">1</span>
                        ¿Cómo accedo al panel de control remoto de Virtualmin?
                    </div>
                    <div class="collapse-content text-sm text-base-content/85 space-y-3 pb-6">
                        <p>
                            Virtualmin cuenta con un panel gráfico muy potente accesible de forma directa desde la web. Puedes acceder de la siguiente manera:
                        </p>
                        <ul class="list-disc list-inside pl-4 space-y-1.5">
                            <li><strong>Enlace de acceso:</strong> Utiliza el dominio o dirección de tu servidor asignado seguido del puerto <code class="bg-base-200 px-1.5 py-0.5 rounded text-secondary font-semibold font-mono">:10000</code> (Ej: <code class="bg-base-200 px-1.5 py-0.5 rounded font-mono">https://tu-dominio.com:10000</code> o <code class="bg-base-200 px-1.5 py-0.5 rounded font-mono">https://nexus01.atsys.co:10000</code>).</li>
                            <li><strong>Credenciales:</strong> Utiliza el usuario y contraseña del servicio de hosting que fueron enviados a tu correo electrónico de activación y que también puedes consultar en tu <em>Ficha de Servicio</em> en esta Área de Clientes.</li>
                        </ul>
                        <div class="alert alert-info bg-info/10 border-info/20 text-info-content rounded-xl p-3 text-xs flex items-start gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mt-0.5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.08 1.04l-.42.416c-.198.196-.445.346-.717.437l-.76.257a.75.75 0 01-.937-.937l.257-.76c.09-.272.24-.52.437-.717l.416-.42a.75.75 0 011.04 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span><strong>Nota sobre SSL:</strong> La primera vez que ingreses, es posible que tu navegador muestre una advertencia de certificado auto-firmado. Puedes omitirla con seguridad haciendo clic en "Configuración Avanzada" y luego en "Proceder".</span>
                        </div>
                    </div>
                </div>

                <!-- Accordion Item 2: Conexión FTP -->
                <div class="collapse collapse-arrow join-item border-b border-base-200">
                    <input type="radio" name="virtualmin-accordion" />
                    <div class="collapse-title text-base md:text-lg font-bold flex items-center gap-3 py-4">
                        <span class="badge badge-primary badge-sm text-white">2</span>
                        ¿Cómo configuro y me conecto por FTP / SFTP?
                    </div>
                    <div class="collapse-content text-sm text-base-content/85 space-y-3 pb-6">
                        <p>
                            Para subir los archivos de tu sitio web (código HTML, PHP, imágenes, etc.), te recomendamos usar un cliente como <strong>FileZilla</strong> o <strong>WinSCP</strong>:
                        </p>
                        <div class="bg-base-200/50 p-4 rounded-xl space-y-2">
                            <div><span class="font-semibold text-base-content">Servidor / Host:</span> <code class="font-mono text-xs">tu-dominio.com</code> o el host físico del servidor (ej: <code class="font-mono text-xs">nexus01.atsys.co</code>).</div>
                            <div><span class="font-semibold text-base-content">Protocolo recomendado:</span> <strong>SFTP</strong> (Puerto <code class="font-mono text-xs">22</code>) para transferencia segura o <strong>FTP</strong> estándar (Puerto <code class="font-mono text-xs">21</code>).</div>
                            <div><span class="font-semibold text-base-content">Usuario:</span> El usuario del servicio de hosting.</div>
                            <div><span class="font-semibold text-base-content">Contraseña:</span> La contraseña asignada a tu servicio de hosting.</div>
                        </div>
                        <p class="text-xs text-base-content/70">
                            <strong>Importante:</strong> Todos los archivos públicos de tu sitio web deben ser cargados estrictamente dentro del directorio <code class="bg-base-200 px-1 py-0.5 rounded font-mono">public_html</code>.
                        </p>
                    </div>
                </div>

                <!-- Accordion Item 3: Bases de Datos -->
                <div class="collapse collapse-arrow join-item border-b border-base-200">
                    <input type="radio" name="virtualmin-accordion" />
                    <div class="collapse-title text-base md:text-lg font-bold flex items-center gap-3 py-4">
                        <span class="badge badge-primary badge-sm text-white">3</span>
                        ¿Cómo gestiono mis bases de datos MySQL / MariaDB?
                    </div>
                    <div class="collapse-content text-sm text-base-content/85 space-y-3 pb-6">
                        <p>
                            Por defecto, cada cuenta de Virtualmin viene con soporte para base de datos.
                        </p>
                        <ul class="list-disc list-inside pl-4 space-y-1.5">
                            <li><strong>Nombre de Base de Datos y Usuario:</strong> En Virtualmin, por motivos de seguridad, el nombre de la base de datos principal y el usuario MySQL inicial suelen coincidir con el usuario de tu cuenta de hosting.</li>
                            <li><strong>Administración visual (phpMyAdmin):</strong> Si el servidor cuenta con phpMyAdmin activo, puedes acceder desde <code class="bg-base-200 px-1 py-0.5 rounded font-mono">https://tu-dominio.com/phpmyadmin</code> o desde la sección <em>Bases de Datos</em> del panel de Virtualmin.</li>
                            <li><strong>Conexión local:</strong> Si tu sitio web corre en PHP sobre el mismo servidor, utiliza siempre <code class="bg-base-200 px-1 py-0.5 rounded font-mono">localhost</code> como host de conexión.</li>
                        </ul>
                    </div>
                </div>

                <!-- Accordion Item 4: Métricas del panel -->
                <div class="collapse collapse-arrow join-item border-b border-base-200">
                    <input type="radio" name="virtualmin-accordion" />
                    <div class="collapse-title text-base md:text-lg font-bold flex items-center gap-3 py-4">
                        <span class="badge badge-primary badge-sm text-white">4</span>
                        Entendiendo las métricas en mi Área de Clientes
                    </div>
                    <div class="collapse-content text-sm text-base-content/85 space-y-3 pb-6">
                        <p>
                            En tu listado de <strong>"Mis Servicios"</strong>, nuestro sistema se conecta de forma directa vía API con Virtualmin para mostrarte información en tiempo real:
                        </p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>Uso de Disco (Cuota):</strong> Muestra qué porcentaje del espacio asignado en tu plan has consumido. Si llegas al 100%, tu web podría dejar de funcionar temporalmente (por ejemplo, impidiendo subir archivos o guardar sesiones).</li>
                            <li><strong>CPU & RAM:</strong> Indica el uso relativo del procesador y memoria asignada a tu contenedor de hosting web.</li>
                        </ul>
                    </div>
                </div>

                <!-- Accordion Item 5: Estados de servicio -->
                <div class="collapse collapse-arrow join-item">
                    <input type="radio" name="virtualmin-accordion" />
                    <div class="collapse-title text-base md:text-lg font-bold flex items-center gap-3 py-4">
                        <span class="badge badge-primary badge-sm text-white">5</span>
                        Estados del servicio: Activo vs Suspendido
                    </div>
                    <div class="collapse-content text-sm text-base-content/85 space-y-3 pb-6">
                        <p>
                            El estado de tu hosting está sincronizado directamente con la base de datos física del servidor:
                        </p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><span class="badge badge-success badge-sm font-semibold">Activo</span> El servicio opera con normalidad, todas las webs, accesos de correo e email están disponibles.</li>
                            <li><span class="badge badge-warning badge-sm font-semibold">Suspendido</span> La cuenta ha sido desactivada (usualmente por falta de pago o mantenimiento). En este estado, Virtualmin redirige automáticamente las visitas web a una página de aviso y detiene los servicios de correo y bases de datos temporalmente. Podrás reactivarlo al generar y completar la orden de pago en tu Área de Clientes.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Tips & Stats (Right Column) -->
        <div class="space-y-6">
            <!-- Alert/Tips Box -->
            <div class="card bg-primary text-primary-content p-6 shadow-md rounded-2xl">
                <h4 class="font-bold text-lg mb-3 flex items-center gap-2">
                    💡 Tips de Rendimiento
                </h4>
                <div class="text-xs opacity-90 leading-relaxed space-y-3">
                    <p>
                        <strong>Limpia correos:</strong> La cuota de disco también incluye tus buzones de correo electrónico. Eliminar correos con adjuntos pesados liberará espacio rápidamente.
                    </p>
                    <p>
                        <strong>Usa FTP pasivo:</strong> Si tienes problemas al listar directorios desde FileZilla, ve a los ajustes de conexión de tu cliente y activa el <em>Modo Pasivo (Passive Mode)</em>.
                    </p>
                    <p>
                        <strong>Versión de PHP:</strong> Puedes cambiar la versión de PHP de tu dominio de forma independiente ingresando a Virtualmin -> <em>Server Configuration</em> -> <em>PHP Versions</em>.
                    </p>
                </div>
            </div>

            <!-- Need More Help Box -->
            <div class="card bg-base-100 border border-base-200 p-6 shadow-sm rounded-2xl">
                <h4 class="font-bold text-lg text-base-content mb-2">
                    ¿Aún tienes dudas?
                </h4>
                <p class="text-xs text-base-content/75 mb-4">
                    Si tienes problemas técnicos que no se resuelven en esta guía, nuestro equipo de soporte está listo para ayudarte.
                </p>
                <a href="/tickets/create" class="btn btn-outline btn-primary btn-sm rounded-xl font-bold w-full">
                    Crear Ticket de Soporte
                </a>
            </div>
        </div>
    </div>
</div>
