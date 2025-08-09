<?php
/**
 * 公用函数库
 * 支付回调系统核心功能函数
 */

/**
 * 获取数据库连接
 * @return mysqli 数据库连接对象
 */
function getDbConnection() {
    static $conn;
    if (!$conn) {
        $config = require __DIR__ . '/../config/database.php';
        $conn = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database']
        );
        
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            die("系统维护中，请稍后再试");
        }
        
        $conn->set_charset($config['charset']);
    }
    return $conn;
}

/**
 * 验证签名
 * @param array $params 参数数组
 * @param string $key 密钥
 * @return bool 验证结果
 */
function verifySign($params, $key) {
    $sign = $params['sign'];
    unset($params['sign']);
    
    ksort($params);
    $query = http_build_query($params);
    $localSign = md5($query . $key);
    
    return hash_equals($localSign, $sign);
}

/**
 * 记录支付日志
 * @param string $message 日志消息
 * @param string $level 日志级别
 */
function logPayment($message, $level = 'info') {
    $config = require __DIR__ . '/../config/database.php';
    $logFile = $config['log_path'] . date('Y-m-d') . '.log';
    
    $logMsg = sprintf(
        "[%s] %s: %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message
    );
    
    file_put_contents($logFile, $logMsg, FILE_APPEND);
}

/**
 * 检查是否来自可信IP
 * @return bool 是否可信
 */
function isFromTrustedIp() {
    $config = require __DIR__ . '/../config/database.php';
    $clientIp = $_SERVER['REMOTE_ADDR'];
    
    if (empty($config['ip_whitelist'])) {
        return true; // 如果没有设置白名单，则允许所有IP
    }
    
    return in_array($clientIp, $config['ip_whitelist']);
}

/**
 * 渲染模板
 * @param string $template 模板名称
 * @param array $data 模板数据
 * @return string 渲染后的HTML
 */
function renderTemplate($template, $data = []) {
    extract($data);
    ob_start();
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/../templates/' . $template . '.php';
    include __DIR__ . '/../templates/footer.php';
    return ob_get_clean();
}

/**
 * 生成订单号
 * @return string 订单号
 */
function generateOrderNo() {
    return date('YmdHis') . mt_rand(1000, 9999);
}

/**
 * 创建订单
 * @param array $orderData 订单数据
 * @return int|false 订单ID或false
 */
function createOrder($orderData) {
    try {
        $conn = getDbConnection();
        
        if (empty($orderData['order_no'])) {
            $orderData['order_no'] = generateOrderNo();
        }
        
        $sql = "INSERT INTO orders (order_no, amount, currency, payment_method, callback_url, notify_url) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdssss", 
            $orderData['order_no'],
            $orderData['amount'],
            $orderData['currency'],
            $orderData['payment_method'],
            $orderData['callback_url'],
            $orderData['notify_url']
        );
        
        if ($stmt->execute()) {
            logPayment("订单创建成功: " . $orderData['order_no']);
            return $conn->insert_id;
        } else {
            logPayment("订单创建失败: " . $stmt->error, 'error');
            return false;
        }
    } catch (Exception $e) {
        logPayment("订单创建异常: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * 更新订单状态
 * @param string $orderId 订单号
 * @param string $status 新状态
 * @param string|null $tradeNo 交易号
 * @return bool 是否成功
 */
function updateOrderStatus($orderId, $status, $tradeNo = null) {
    try {
        $conn = getDbConnection();
        
        // 开始事务
        $conn->begin_transaction();
        
        // 更新订单状态
        $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE order_no = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $status, $orderId);
        
        if (!$stmt->execute()) {
            throw new Exception("更新订单状态失败: " . $stmt->error);
        }
        
        // 如果有交易号，更新交易号
        if ($tradeNo) {
            $updateTradeSql = "UPDATE orders SET trade_no = ? WHERE order_no = ?";
            $tradeStmt = $conn->prepare($updateTradeSql);
            $tradeStmt->bind_param("ss", $tradeNo, $orderId);
            
            if (!$tradeStmt->execute()) {
                throw new Exception("更新交易号失败: " . $tradeStmt->error);
            }
        }
        
        // 记录状态变更日志
        $logSql = "INSERT INTO payment_logs (order_no, transaction_id, status, payment_data, created_at) 
                  VALUES (?, ?, ?, ?, NOW())";
        $logStmt = $conn->prepare($logSql);
        
        $logData = [
            'status_change' => $status,
            'trade_no' => $tradeNo,
            'update_time' => date('Y-m-d H:i:s')
        ];
        
        $logStmt->bind_param("ssss", 
            $orderId,
            $tradeNo ?? '',
            $status,
            json_encode($logData)
        );
        
        if (!$logStmt->execute()) {
            throw new Exception("记录日志失败: " . $logStmt->error);
        }
        
        // 提交事务
        $conn->commit();
        
        logPayment("订单状态更新成功: {$orderId} -> {$status}" . ($tradeNo ? " (交易号: {$tradeNo})" : ""));
        return true;
        
    } catch (Exception $e) {
        // 回滚事务
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        
        logPayment("订单状态更新异常: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * 获取订单信息
 * @param string $orderNo 订单号
 * @return array|false 订单信息或false
 */
function getOrder($orderNo) {
    try {
        $conn = getDbConnection();
        
        $sql = "SELECT * FROM orders WHERE order_no = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $orderNo);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        logPayment("获取订单信息异常: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * 发送异步通知
 * @param string $url 通知地址
 * @param array $data 通知数据
 * @return bool 是否成功
 */
function sendNotify($url, $data) {
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response === 'SUCCESS') {
            logPayment("异步通知发送成功: {$url}");
            return true;
        } else {
            logPayment("异步通知发送失败: {$url}, HTTP: {$httpCode}, Response: {$response}", 'error');
            return false;
        }
    } catch (Exception $e) {
        logPayment("异步通知发送异常: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * 返回JSON响应
 * @param array $data 响应数据
 * @param int $statusCode HTTP状态码
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 获取客户端IP
 * @return string IP地址
 */
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * 验证订单数据
 * @param array $data 订单数据
 * @return array 验证结果
 */
function validateOrderData($data) {
    $errors = [];
    
    if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
        $errors[] = '订单金额无效';
    }
    
    if (!empty($data['callback_url']) && !filter_var($data['callback_url'], FILTER_VALIDATE_URL)) {
        $errors[] = '回调地址格式无效';
    }
    
    if (!empty($data['notify_url']) && !filter_var($data['notify_url'], FILTER_VALIDATE_URL)) {
        $errors[] = '异步通知地址格式无效';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * 获取最近订单
 * @param int $limit 限制数量
 * @return array 订单列表
 */
function getRecentOrders($limit = 10) {
    try {
        $conn = getDbConnection();
        
        $sql = "SELECT * FROM orders ORDER BY created_at DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        logPayment("获取最近订单异常: " . $e->getMessage(), 'error');
        return [];
    }
}

/**
 * 获取订单统计
 * @return array 统计数据
 */
function getOrderStats() {
    try {
        $conn = getDbConnection();
        
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_orders,
                    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_amount
                FROM orders";
        
        $result = $conn->query($sql);
        return $result->fetch_assoc();
    } catch (Exception $e) {
        logPayment("获取订单统计异常: " . $e->getMessage(), 'error');
        return [];
    }
}

/**
 * 初始化数据库表
 * @return bool 是否成功
 */
function initDatabase() {
    try {
        $conn = getDbConnection();
        
        // 创建订单表
        $sql = "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_no VARCHAR(64) UNIQUE NOT NULL COMMENT '订单号',
            amount DECIMAL(10,2) NOT NULL COMMENT '订单金额',
            currency VARCHAR(10) DEFAULT 'CNY' COMMENT '货币类型',
            status ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending' COMMENT '订单状态',
            payment_method VARCHAR(50) COMMENT '支付方式',
            callback_url TEXT COMMENT '回调地址',
            notify_url TEXT COMMENT '异步通知地址',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
            INDEX idx_order_no (order_no),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表'";
        
        $conn->query($sql);
        
        // 创建支付记录表
        $sql = "CREATE TABLE IF NOT EXISTS payment_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_no VARCHAR(64) NOT NULL COMMENT '订单号',
            transaction_id VARCHAR(128) COMMENT '交易流水号',
            amount DECIMAL(10,2) NOT NULL COMMENT '支付金额',
            status ENUM('success', 'failed', 'pending') NOT NULL COMMENT '支付状态',
            payment_data TEXT COMMENT '支付原始数据',
            callback_data TEXT COMMENT '回调数据',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            INDEX idx_order_no (order_no),
            INDEX idx_transaction_id (transaction_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付记录表'";
        
        $conn->query($sql);
        
        // 创建系统配置表
        $sql = "CREATE TABLE IF NOT EXISTS system_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            config_key VARCHAR(100) UNIQUE NOT NULL COMMENT '配置键',
            config_value TEXT COMMENT '配置值',
            description VARCHAR(255) COMMENT '配置描述',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表'";
        
        $conn->query($sql);
        
        logPayment("数据库表初始化成功");
        return true;
    } catch (Exception $e) {
        logPayment("数据库表初始化失败: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * 获取系统配置
 * @param string $key 配置键
 * @param mixed $default 默认值
 * @return mixed 配置值
 */
function getConfig($key, $default = null) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? $row['config_value'] : $default;
    } catch (Exception $e) {
        logPayment("获取配置失败: " . $e->getMessage(), 'error');
        return $default;
    }
}

/**
 * 设置系统配置
 * @param string $key 配置键
 * @param mixed $value 配置值
 * @param string $description 配置描述
 * @return bool 是否成功
 */
function setConfig($key, $value, $description = '') {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("INSERT INTO system_config (config_key, config_value, description) 
                              VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE config_value = ?, description = ?");
        $stmt->bind_param("sssss", $key, $value, $description, $value, $description);
        
        if ($stmt->execute()) {
            logPayment("配置设置成功: {$key} = {$value}");
            return true;
        } else {
            logPayment("配置设置失败: " . $stmt->error, 'error');
            return false;
        }
    } catch (Exception $e) {
        logPayment("配置设置异常: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * 兼容性函数：验证签名（旧版本兼容）
 * @param array $data 数据
 * @param string $signature 签名
 * @param string $secret 密钥
 * @return bool 验证结果
 */
function verifySignature($data, $signature, $secret) {
    return verifySign($data, $secret);
}

/**
 * 兼容性函数：获取数据库连接（旧版本兼容）
 * @return mysqli 数据库连接对象
 */
function getDBConnection() {
    return getDbConnection();
}
?>
