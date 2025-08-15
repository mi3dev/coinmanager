<?php
// v1.0
return [
    'db' => [
        'host' => 'db.dw188.webglobe.com',
        'dbname' => 'sbirkaminci',
        'user' => 'dcddb',
        'pass' => 'mcjx.XDCDDB74',
        'charset' => 'utf8mb4'
    ],
    'app' => [
        'base_url' => '/public',
        'users_per_page' => 20 // počet záznamů na stránku
    ],
    'uploads' => [
        'coins' => __DIR__.'/../../public/uploads/coins',
        'collection' => __DIR__.'/../../public/uploads/collection',
    ],
];
