<?php
// config.php - ERPH系统配置文件
return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'erph',
        'user' => 'erph_user',
        'pass' => '123456',
        'charset' => 'utf8mb4'
    ],
    'upload_dir' => __DIR__ . '/uploads/',
    'app_name' => 'ERPH 系统',
    'version' => '1.0.0'
];
