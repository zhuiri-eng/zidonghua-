<?php
/**
 * 数据库配置文件
 * 支付回调系统数据库连接和配置
 */

// 数据库配置
return [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'payment_callback',
    'charset' => 'utf8mb4',
    
    // 日志配置
    'log_path' => __DIR__ . '/../logs/',
    
    // IP白名单配置
    'ip_whitelist' => [
        '127.0.0.1',
        '::1'
        // 添加您的支付网关IP地址
    ],
    
    // 支付密钥
    'payment_secret' => 'your_payment_secret_key_here',
    
    // API配置
    'api_timeout' => 30,
    'max_retry_times' => 3
];
