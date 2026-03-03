<?php

return [
    'adminEmail' => 'gerencia@atsys.co',
    'senderEmail' => 'noreply@atsys.co',
    'senderName' => 'Área de clientes ATSYS',
    'user.passwordResetTokenExpire' => 3600,
    'paginationStyles' => [
    	'activePageCssClass' => 'page-item active',
        'options' => ['class'=>'pagination'],   // set clas name used in ui list of pagination
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
        'commercial' => 'hola@atsys.co'
    ],
    'wmpi_pubKey' => 'pub_prod_UbGVrJOt3EZ6xBKQaPy8lah9pFQchr0T',
    'wmpi_integrity' => 'prod_integrity_qGF2hvg6bUCrUAY2qEK7yefE5soM5JZ0',
    'fbase_senderId' => '171390167252',
    'n8n' => [
        'webhookUrl' => 'https://n8n.atsys.co/webhook/atsys-clientarea-alert',
    ],
    'whatsapp' => [
        'token' => 'EAA89uZCgaiuIBQ4BxnqMtwrXUSZBgE5htYZC6ehJVEJOeBtqYji9cG9zQGfZBjKYnmdAUrhG8DqKifUzCc3xchTb70IHZA1JZB0aFmqQ5auZCK1d3eh4XBaIoZAUyTUUZCN3CeKhNVG3tfSkMXEQ5aXRK3He0B0AsbZBccJqxcNej4fskQdTeZAZB2dGnDd7HYqk8AZDZD',
        'phoneId' => '974241165778303',
        'businessId' => '1885925668728489'
    ]
];
