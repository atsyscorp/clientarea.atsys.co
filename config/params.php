<?php

require_once __DIR__ . '/env.php';

return [
    'adminEmail' => 'gerencia@atsys.co',
    'senderEmail' => 'noreply@atsys.co',
    'senderName' => 'Área de clientes ATSYS',
    'user.passwordResetTokenExpire' => 3600,
    'paginationStyles' => [
        'activePageCssClass' => 'page-item active',
        'options' => ['class' => 'pagination'],   // set clas name used in ui list of pagination
        'nextPageCssClass' => 'page-item',    // Set CSS class for the “next” page button
        'prevPageCssClass' => 'page-item',    // Set CSS class for the “previous” page button
        'firstPageCssClass' => 'page-item',    // Set CSS class for the “first” page button
        'lastPageCssClass' => 'page-item',    // Set CSS class for the “last” page button
        'maxButtonCount' => 10,    // Set maximum number of page buttons that can be displayed
        'pageCssClass' => 'page-item',
        'linkOptions' => [
            'class' => 'page-link'
        ]
    ],
    'departmentEmails' => [
        'support' => 'soporte@atsys.co',
        'commercial' => 'hola@atsys.co',
        'billing' => 'facturacion@atsys.co'
    ],
    'n8n' => [
        'webhookUrl' => env('N8N_WEBHOOK_URL', 'https://n8n-new.atsys.co/webhook/atsys-clientarea-alert'),
    ],
    'fallback_trm' => 4000.00,
    'fallback_trm_eur' => 4300.00,
    'webhookSecretKey' => getenv('WEBHOOK_SECRET_KEY') ?: 'at_isW52qtEVPZG9Px6Vp1R3kShHyN1Zray',
];

