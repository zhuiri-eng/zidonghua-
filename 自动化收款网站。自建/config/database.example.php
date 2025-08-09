<?php
/**
 * 数据库配置示例文件
 * 部署时请复制此文件为 database.php 并修改配置
 */

// 数据库配置
return [
    'host' => 'localhost',
    'username' => 'your_username',
    'password' => 'your_password',
    'database' => 'payment_system',
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
    
    // API密钥
    'api_key' => 'your_api_key_here',
    
    // API配置
    'api_timeout' => 30,
    'max_retry_times' => 3
];
