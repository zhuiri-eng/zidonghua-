<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

// 验证API密钥
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$config = require __DIR__ . '/../config/database.php';

if (!hash_equals($config['api_key'], $apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// 获取订单ID
$orderId = $_GET['order_id'] ?? '';
if (empty($orderId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

// 查询订单
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->bind_param("s", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $order = $result->fetch_assoc();
    echo json_encode([
        'order_id' => $order['order_id'],
        'amount' => $order['amount'],
        'status' => $order['status'],
        'create_time' => $order['create_time'],
        'update_time' => $order['update_time'],
        'trade_no' => $order['trade_no']
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
}
?>
