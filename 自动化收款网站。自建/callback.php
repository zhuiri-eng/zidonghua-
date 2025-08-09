<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// 处理同步回调
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyPaymentRequest($_POST)) {
        echo renderTemplate('failure', [
            'orderId' => $_POST['order_id'] ?? '未知',
            'reason' => '支付验证失败'
        ]);
        exit;
    }
    
    // 查询订单状态
    $conn = getDbConnection();
    $orderId = $_POST['order_id'];
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        if ($order['status'] === 'paid') {
            echo renderTemplate('success', [
                'orderId' => $order['order_id'],
                'amount' => $order['amount'],
                'payTime' => $order['update_time']
            ]);
        } else {
            echo renderTemplate('failure', [
                'orderId' => $order['order_id'],
                'reason' => '订单未支付'
            ]);
        }
    } else {
        echo renderTemplate('failure', [
            'orderId' => $orderId,
            'reason' => '订单不存在'
        ]);
    }
    exit;
}

// 默认显示支付页面或重定向
header("Location: /");
?>
