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
        'token' => 'EAA89uZCgaiuIBQ8Gv3eBWGh0G8CXUkcUBF7MhnyTQiW6mp9Hpy1n7T7prqHIhjLlKHsS2bc7nQ2dgaZB1bg1lXJ74pBnKGJxHIJF4YZAZCFoOfkynjqrgfTdnuee3ntwYJZCiZCE5GmepJemh3yuwTtZAW17LhIqEPEksH6Oo8ekvl353bdqZBH6eJAbvdC9YQZDZD',
        'phoneId' => '974241165778303',
        'businessId' => '1885925668728489'
    ]
];
