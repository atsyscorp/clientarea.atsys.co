<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $q string */
/* @var $results array */
/* @var $pricesMap array */
/* @var $defaultTlds array */

$this->title = 'Buscador de Dominios';
?>

<div class="container mx-auto px-4 py-8 max-w-5xl">
    <!-- Header Hero Section -->
    <div class="text-center mb-10">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">
            Encuentra tu Dominio Ideal
        </h1>
        <p class="mt-3 text-lg text-base-content/60 max-w-xl mx-auto">
            Busca la identidad perfecta para tu negocio en internet. Comprobación instantánea de disponibilidad.
        </p>
    </div>

    <!-- Search Form Card -->
    <div class="card bg-base-100/70 backdrop-blur-md border border-base-200/50 shadow-xl rounded-3xl p-6 md:p-8 mb-8 transition-all duration-300 hover:shadow-2xl">
        <form id="domain-search-form" method="GET" action="<?= Url::to(['site/domain-search']) ?>" class="w-full">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-base-content/40">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12.75 3.03v.568c0 .334.148.65.405.864l4.061 3.385a.562.562 0 01.19.41v2.183a.562.562 0 01-.19.41l-4.061 3.386a.825.825 0 00-.405.681v.568m0-12.435L9 5.25M12.75 3.03l-3.75 2.22M9 5.25H6.75A2.25 2.25 0 004.5 7.5v10.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V13.5M9 5.25v13.5m0-13.5L5.25 9m3.75 9.75L5.25 15" />
                        </svg>
                    </span>
                    <input 
                        type="text" 
                        name="q" 
                        id="search-input"
                        value="<?= Html::encode($q) ?>" 
                        placeholder="ej. miempresa o miempresa.com" 
                        autocomplete="off"
                        autofocus
                        class="input input-bordered input-primary w-full pl-12 input-lg rounded-2xl text-lg font-medium placeholder:text-base-content/30 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary"
                    />
                </div>
                <button type="submit" id="search-btn" class="btn btn-primary btn-lg rounded-2xl text-white font-bold gap-2 px-8 shadow-lg shadow-primary/25 hover:scale-[1.02] active:scale-[0.98] transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5" id="search-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" />
                    </svg>
                    <span>Buscar</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Loading State (AJAX Skeleton loader) -->
    <div id="loading-container" class="hidden space-y-4 mb-8">
        <div class="flex items-center gap-3 justify-center py-6">
            <span class="loading loading-spinner loading-lg text-primary"></span>
            <span class="text-lg font-semibold text-base-content/70">Comprobando disponibilidad...</span>
        </div>
        <div class="grid gap-4">
            <div class="h-20 bg-base-300/30 animate-pulse rounded-2xl"></div>
            <div class="h-20 bg-base-300/30 animate-pulse rounded-2xl"></div>
            <div class="h-20 bg-base-300/30 animate-pulse rounded-2xl"></div>
        </div>
    </div>

    <!-- Results Section -->
    <div id="results-container" class="<?= empty($results) ? 'hidden' : '' ?> space-y-6 mb-8">
        <h2 class="text-xl font-bold px-1 text-base-content/80">Resultados de búsqueda</h2>
        <div class="grid gap-4" id="results-list">
            <?php if (!empty($results)): ?>
                <?php foreach ($results as $res): ?>
                    <?php
                    $isAvailable = $res['available'];
                    $isInvalid = ($res['status'] === 'invalid');
                    $isMain = $res['is_main'];
                    
                    $cardClass = 'border bg-base-100 hover:shadow-md';
                    if ($isMain && $isAvailable) {
                        $cardClass = 'border-success/40 bg-success/5 hover:bg-success/10 shadow-sm';
                    } elseif ($isAvailable) {
                        $cardClass = 'border-base-200 bg-base-100 hover:bg-base-200/40';
                    } elseif ($isInvalid) {
                        $cardClass = 'border-warning/30 bg-warning/5';
                    } else {
                        $cardClass = 'border-base-200 bg-base-100 opacity-80';
                    }
                    ?>
                    <div class="card card-side <?= $cardClass ?> transition-all duration-200 rounded-2xl border p-4 md:p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <!-- Status Icon -->
                            <?php if ($isAvailable): ?>
                                <div class="w-10 h-10 rounded-xl bg-success/20 text-success flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </div>
                            <?php elseif ($isInvalid): ?>
                                <div class="w-10 h-10 rounded-xl bg-warning/20 text-warning flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                </div>
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-xl bg-error/10 text-error flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <!-- Domain Details -->
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xl font-bold tracking-tight text-base-content"><?= Html::encode($res['domain']) ?></span>
                                    <?php if ($isMain): ?>
                                        <span class="badge badge-primary badge-sm text-white font-bold px-2 py-0.5">Principal</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-base-content/50 mt-1">
                                    <?php if ($isAvailable): ?>
                                        <span class="text-success font-semibold">¡Disponible para registro!</span>
                                    <?php elseif ($isInvalid): ?>
                                        <span class="text-warning font-semibold"><?= Html::encode($res['message']) ?></span>
                                    <?php else: ?>
                                        <span class="text-base-content/60">Dominio ya registrado</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <!-- Price and Actions -->
                        <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end border-t md:border-0 pt-3 md:pt-0">
                            <?php if ($isAvailable && !$isInvalid): ?>
                                <div class="text-right">
                                    <?php if (isset($res['price']) && $res['price'] > 0): ?>
                                        <span class="text-2xl font-extrabold text-base-content"><?= Yii::$app->formatter->asCurrency($res['price'], 'COP') ?></span>
                                        <span class="text-xs text-base-content/40 block">/año</span>
                                    <?php else: ?>
                                        <span class="text-sm text-base-content/50 italic block">Precio no configurado</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex gap-2">
                                    <?= Html::a('Contratar Hosting', ['shop/index'], [
                                        'class' => 'btn btn-outline btn-primary btn-sm rounded-xl font-bold'
                                    ]) ?>
                                    <?= Html::a('Registrar Solo Dominio', Url::to(['tickets/create', 'subject' => 'Registro de Dominio: ' . $res['domain'], 'message' => "Hola ATSYS,\n\nDeseo registrar el dominio: " . $res['domain'] . ".\n\nPor favor envíenme los detalles de facturación para proceder."]), [
                                        'class' => 'btn btn-primary btn-sm rounded-xl text-white font-bold'
                                    ]) ?>
                                </div>
                            <?php elseif (!$isAvailable && !$isInvalid): ?>
                                <div class="flex gap-2 ml-auto">
                                    <a href="https://<?= Html::encode($res['domain']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm rounded-xl gap-1.5">
                                        <span>Visitar sitio</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Onboarding / Popular Pricing Section (Shown when no search performed) -->
    <div id="pricing-onboarding" class="<?= !empty($results) ? 'hidden' : '' ?> grid md:grid-cols-3 gap-6">
        <!-- Info Cards -->
        <div class="col-span-1 md:col-span-3">
            <h2 class="text-xl font-bold mb-4 px-1 text-base-content/80">Extensiones más populares</h2>
        </div>

        <?php
        $popularTldsList = ['.com', '.co', '.com.co', '.net', '.org'];
        foreach ($popularTldsList as $tldName):
            $hasPrice = isset($pricesMap[$tldName]);
            $priceData = $hasPrice ? $pricesMap[$tldName] : null;
        ?>
            <div class="card bg-base-100 border border-base-200 rounded-2xl shadow-sm hover:shadow-md transition-all duration-200 p-5 items-center text-center">
                <span class="text-3xl font-black text-primary tracking-tight"><?= $tldName ?></span>
                <p class="text-xs text-base-content/50 mt-1 mb-4">
                    Ideal para <?= ($tldName === '.com') ? 'empresas globales y corporativas' : 
                                (($tldName === '.co' || $tldName === '.com.co') ? 'negocios en Colombia' : 
                                (($tldName === '.org') ? 'organizaciones y fundaciones' : 'tecnología e infraestructura')) ?>
                </p>
                <div class="mt-auto pt-2 w-full border-t border-base-100">
                    <?php if ($hasPrice): ?>
                        <span class="text-xl font-extrabold text-base-content"><?= Yii::$app->formatter->asCurrency($priceData['price'], 'COP') ?></span>
                        <span class="text-xs text-base-content/40">/año</span>
                    <?php else: ?>
                        <span class="text-sm text-base-content/40 italic">Consulte precio</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Helpful Tips Card -->
        <div class="card bg-gradient-to-br from-primary/10 to-secondary/10 border border-primary/20 rounded-2xl p-6 flex flex-col justify-center">
            <h3 class="font-bold text-primary text-lg mb-1">💡 Consejos de registro</h3>
            <p class="text-xs text-base-content/70 leading-relaxed">
                Elige nombres cortos, fáciles de recordar y pronunciar. Evita guiones o caracteres especiales si es posible para mejorar el SEO.
            </p>
        </div>
    </div>
</div>

<!-- AJAX Dynamic Interactivity Scripts -->
<?php
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;

$script = <<<JS
$(document).ready(function() {
    const form = $('#domain-search-form');
    const input = $('#search-input');
    const resultsContainer = $('#results-container');
    const resultsList = $('#results-list');
    const loadingContainer = $('#loading-container');
    const onboardingContainer = $('#pricing-onboarding');
    const searchBtn = $('#search-btn');

    form.on('submit', function(e) {
        e.preventDefault();
        const query = input.val().trim();
        if (!query) return;

        // Update URL path without reloading to support browser history
        const newUrl = window.location.pathname + '?q=' + encodeURIComponent(query);
        window.history.pushState({ path: newUrl }, '', newUrl);

        // UI State: Loading
        onboardingContainer.addClass('hidden');
        resultsContainer.addClass('hidden');
        loadingContainer.removeClass('hidden');
        searchBtn.addClass('btn-disabled').find('#search-icon').addClass('animate-spin');

        // Execute AJAX call
        $.ajax({
            url: form.attr('action'),
            type: 'GET',
            data: { q: query },
            dataType: 'json',
            success: function(data) {
                loadingContainer.addClass('hidden');
                searchBtn.removeClass('btn-disabled').find('#search-icon').removeClass('animate-spin');

                if (data.success && data.results.length > 0) {
                    renderResults(data.results, data.prices);
                    resultsContainer.removeClass('hidden');
                } else {
                    resultsList.html('<div class="p-8 text-center text-base-content/50">Ocurrió un error al procesar la búsqueda.</div>');
                    resultsContainer.removeClass('hidden');
                }
            },
            error: function() {
                loadingContainer.addClass('hidden');
                searchBtn.removeClass('btn-disabled').find('#search-icon').removeClass('animate-spin');
                resultsList.html('<div class="p-8 text-center text-error/80 font-semibold">Error de conexión al buscar el dominio. Inténtalo de nuevo.</div>');
                resultsContainer.removeClass('hidden');
            }
        });
    });

    // Handle browser back/forward buttons
    window.onpopstate = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const qParam = urlParams.get('q');
        if (qParam) {
            input.val(qParam);
            form.submit();
        } else {
            input.val('');
            resultsContainer.addClass('hidden');
            onboardingContainer.removeClass('hidden');
        }
    };

    function renderResults(results, prices) {
        let html = '';
        
        results.forEach(function(res) {
            const isAvailable = res.available;
            const isInvalid = (res.status === 'invalid');
            const isMain = res.is_main;
            const domain = res.domain;
            
            let cardClass = 'border bg-base-100 hover:shadow-md';
            let statusIcon = '';
            let statusText = '';
            
            if (isMain && isAvailable) {
                cardClass = 'border-success/40 bg-success/5 hover:bg-success/10 shadow-sm';
            } else if (isAvailable) {
                cardClass = 'border-base-200 bg-base-100 hover:bg-base-200/40';
            } else if (isInvalid) {
                cardClass = 'border-warning/30 bg-warning/5';
            } else {
                cardClass = 'border-base-200 bg-base-100 opacity-80';
            }

            if (isAvailable) {
                statusIcon = `
                    <div class="w-10 h-10 rounded-xl bg-success/20 text-success flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>`;
                statusText = '<span class="text-success font-semibold">¡Disponible para registro!</span>';
            } else if (isInvalid) {
                statusIcon = `
                    <div class="w-10 h-10 rounded-xl bg-warning/20 text-warning flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>`;
                statusText = `<span class="text-warning font-semibold">\${escapeHtml(res.message)}</span>`;
            } else {
                statusIcon = `
                    <div class="w-10 h-10 rounded-xl bg-error/10 text-error flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>`;
                statusText = '<span class="text-base-content/60">Dominio ya registrado</span>';
            }

            let mainBadge = isMain ? '<span class="badge badge-primary badge-sm text-white font-bold px-2 py-0.5">Principal</span>' : '';
            
            let priceAndActionHtml = '';
            if (isAvailable && !isInvalid) {
                let priceDisplay = '<span class="text-sm text-base-content/50 italic block">Precio no configurado</span>';
                if (res.price !== null) {
                    priceDisplay = `
                        <span class="text-2xl font-extrabold text-base-content">\${formatCurrency(res.price)}</span>
                        <span class="text-xs text-base-content/40 block">/año</span>
                    `;
                }
                
                const ticketUrl = `/tickets/create?subject=Registro%20de%20Dominio%3A%20\${encodeURIComponent(domain)}&message=Hola%20ATSYS%2C%0A%0ADeseo%20registrar%20el%20dominio%3A%20\${encodeURIComponent(domain)}.%0A%0APor%20favor%20env%C3%ADenme%20los%20detalles%20de%20facturaci%C3%B33n%20para%20proceder.`;

                priceAndActionHtml = `
                    <div class="text-right">
                        \${priceDisplay}
                    </div>
                    <div class="flex gap-2">
                        <a href="/shop/index" class="btn btn-outline btn-primary btn-sm rounded-xl font-bold">Contratar Hosting</a>
                        <a href="\${ticketUrl}" class="btn btn-primary btn-sm rounded-xl text-white font-bold">Registrar Solo Dominio</a>
                    </div>
                `;
            } else if (!isAvailable && !isInvalid) {
                priceAndActionHtml = `
                    <div class="flex gap-2 ml-auto">
                        <a href="https://\${escapeHtml(domain)}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm rounded-xl gap-1.5">
                            <span>Visitar sitio</span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                    </div>
                `;
            }

            html += `
                <div class="card card-side \${cardClass} transition-all duration-200 rounded-2xl border p-4 md:p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        \${statusIcon}
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xl font-bold tracking-tight text-base-content">\${escapeHtml(domain)}</span>
                                \${mainBadge}
                            </div>
                            <p class="text-xs text-base-content/50 mt-1">
                                \${statusText}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end border-t md:border-0 pt-3 md:pt-0">
                        \${priceAndActionHtml}
                    </div>
                </div>
            `;
        });

        resultsList.html(html);
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    }

    function escapeHtml(string) {
        return String(string).replace(/[&<>"'`=\/]/g, function (s) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#x2F;',
                '=': '&#x3D;'
            }[s];
        });
    }
});
JS;

$this->registerJs($script);
?>
