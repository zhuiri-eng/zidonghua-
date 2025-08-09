<?php
/**
 * 支付回调系统测试脚本
 * 用于验证系统基本功能是否正常
 */

echo "=== 支付回调系统测试 ===\n\n";

// 1. 测试数据库配置
echo "1. 测试数据库配置...\n";
require_once 'config/database.php';
require_once 'includes/functions.php';

try {
    $conn = getDbConnection();
    echo "✓ 数据库连接成功\n";
} catch (Exception $e) {
    echo "✗ 数据库连接失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. 测试数据库初始化
echo "\n2. 测试数据库初始化...\n";
if (initDatabase()) {
    echo "✓ 数据库表初始化成功\n";
} else {
    echo "✗ 数据库表初始化失败\n";
    exit(1);
}

// 3. 测试配置功能
echo "\n3. 测试配置功能...\n";
$testKey = 'test_config_' . time();
$testValue = 'test_value_' . time();

if (setConfig($testKey, $testValue, '测试配置')) {
    echo "✓ 配置设置成功\n";
    
    $retrievedValue = getConfig($testKey);
    if ($retrievedValue === $testValue) {
        echo "✓ 配置获取成功\n";
    } else {
        echo "✗ 配置获取失败\n";
    }
} else {
    echo "✗ 配置设置失败\n";
}

// 4. 测试订单创建
echo "\n4. 测试订单创建...\n";
$orderData = [
    'order_no' => 'TEST_' . time(),
    'amount' => 100.00,
    'currency' => 'CNY',
    'payment_method' => 'test',
    'callback_url' => 'https://example.com/callback',
    'notify_url' => 'https://example.com/notify'
];

$orderId = createOrder($orderData);
if ($orderId) {
    echo "✓ 订单创建成功，ID: $orderId\n";
    
    // 测试订单查询
    $order = getOrder($orderData['order_no']);
    if ($order) {
        echo "✓ 订单查询成功\n";
        
        // 测试订单状态更新
        if (updateOrderStatus($orderData['order_no'], 'paid', 'TEST_TXN_' . time())) {
            echo "✓ 订单状态更新成功\n";
        } else {
            echo "✗ 订单状态更新失败\n";
        }
    } else {
        echo "✗ 订单查询失败\n";
    }
} else {
    echo "✗ 订单创建失败\n";
}

// 5. 测试日志功能
echo "\n5. 测试日志功能...\n";
if (logPayment('系统测试', 'info')) {
    echo "✓ 日志写入成功\n";
} else {
    echo "✗ 日志写入失败\n";
}

// 6. 测试签名验证
echo "\n6. 测试签名验证...\n";
$testData = [
    'order_no' => 'TEST_ORDER',
    'amount' => '100.00',
    'status' => 'paid',
    'sign' => 'test_signature'
];

$config = require 'config/database.php';
$secret = $config['payment_secret'];

// 生成正确的签名
$signData = $testData;
unset($signData['sign']);
ksort($signData);
$query = http_build_query($signData);
$correctSign = md5($query . $secret);
$testData['sign'] = $correctSign;

if (verifySign($testData, $secret)) {
    echo "✓ 签名验证成功\n";
} else {
    echo "✗ 签名验证失败\n";
}

// 7. 测试IP白名单
echo "\n7. 测试IP白名单...\n";
if (isFromTrustedIp()) {
    echo "✓ IP白名单验证通过\n";
} else {
    echo "✗ IP白名单验证失败\n";
}

// 8. 测试订单统计
echo "\n8. 测试订单统计...\n";
$stats = getOrderStats();
if ($stats) {
    echo "✓ 订单统计获取成功\n";
    echo "  总订单数: " . ($stats['total_orders'] ?? 0) . "\n";
    echo "  已支付订单: " . ($stats['paid_orders'] ?? 0) . "\n";
    echo "  总金额: " . ($stats['total_amount'] ?? 0) . "\n";
} else {
    echo "✗ 订单统计获取失败\n";
}

echo "\n=== 测试完成 ===\n";
echo "系统基本功能测试完成。如果所有测试都通过，说明系统配置正确。\n";
echo "请根据实际需求调整数据库配置和安全设置。\n";
?>
