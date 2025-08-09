<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 405 Method Not Allowed");
    exit;
}

// 获取原始POST数据
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true) ?: $_POST;

// 验证请求
if (!verifyPaymentRequest($data)) {
    logPayment("Invalid notification: " . print_r($data, true), 'warning');
    header("HTTP/1.1 400 Bad Request");
    echo 'FAIL';
    exit;
}

// 处理订单
$orderId = $data['order_id'];
$tradeNo = $data['trade_no'] ?? null;
$amount = $data['amount'];

// 更新订单状态
if (updateOrderStatus($orderId, 'paid', $tradeNo)) {
    logPayment("Order $orderId processed successfully");
    echo 'SUCCESS';
} else {
    logPayment("Failed to process order $orderId", 'error');
    echo 'FAIL';
}
?>
