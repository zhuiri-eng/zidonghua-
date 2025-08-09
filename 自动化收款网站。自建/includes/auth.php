<?php
/**
 * 验证逻辑文件
 * 支付回调系统安全验证功能
 */

require_once __DIR__ . '/functions.php';

/**
 * 验证API密钥
 * @param string $apiKey 提供的API密钥
 * @return bool 验证结果
 */
function validateApiKey($apiKey) {
    $validApiKey = getConfig('api_key', 'your_api_key_here');
    return $apiKey === $validApiKey;
}

/**
 * 验证IP白名单
 * @param string $ip IP地址
 * @return bool 验证结果
 */
function validateIPWhitelist($ip) {
    $whitelist = getConfig('ip_whitelist', '');
    if (empty($whitelist)) {
        return true; // 如果未设置白名单，则允许所有IP
    }
    
    $allowedIPs = explode(',', $whitelist);
    $allowedIPs = array_map('trim', $allowedIPs);
    
    foreach ($allowedIPs as $allowedIP) {
        if (ipInRange($ip, $allowedIP)) {
            return true;
        }
    }
    
    writeLog('warning', 'IP不在白名单中', ['ip' => $ip, 'whitelist' => $whitelist]);
    return false;
}

/**
 * 检查IP是否在指定范围内
 * @param string $ip 要检查的IP
 * @param string $range IP范围（支持CIDR格式）
 * @return bool 是否在范围内
 */
function ipInRange($ip, $range) {
    if (strpos($range, '/') !== false) {
        // CIDR格式
        list($subnet, $mask) = explode('/', $range);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);
        $subnetLong &= $maskLong;
        return ($ipLong & $maskLong) == $subnetLong;
    } else {
        // 单个IP
        return $ip === $range;
    }
}

/**
 * 验证请求频率限制
 * @param string $identifier 标识符（如IP或用户ID）
 * @param int $maxRequests 最大请求次数
 * @param int $timeWindow 时间窗口（秒）
 * @return bool 是否允许请求
 */
function validateRateLimit($identifier, $maxRequests = 100, $timeWindow = 3600) {
    $cacheFile = __DIR__ . '/../cache/rate_limit_' . md5($identifier) . '.json';
    $cacheDir = dirname($cacheFile);
    
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $now = time();
    $data = [];
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    
    // 清理过期的记录
    $data = array_filter($data, function($timestamp) use ($now, $timeWindow) {
        return $timestamp > ($now - $timeWindow);
    });
    
    // 检查是否超过限制
    if (count($data) >= $maxRequests) {
        writeLog('warning', '请求频率超限', [
            'identifier' => $identifier,
            'requests' => count($data),
            'max_requests' => $maxRequests
        ]);
        return false;
    }
    
    // 添加当前请求记录
    $data[] = $now;
    file_put_contents($cacheFile, json_encode($data));
    
    return true;
}

/**
 * 验证请求签名
 * @param array $data 请求数据
 * @param string $signature 签名
 * @return bool 验证结果
 */
function validateRequestSignature($data, $signature) {
    $secret = getConfig('payment_secret', 'your_payment_secret_key_here');
    return verifySignature($data, $signature, $secret);
}

/**
 * 验证订单数据完整性
 * @param array $orderData 订单数据
 * @return array 验证结果
 */
function validateOrderData($orderData) {
    $errors = [];
    
    // 检查必需字段
    $requiredFields = ['order_no', 'amount'];
    foreach ($requiredFields as $field) {
        if (empty($orderData[$field])) {
            $errors[] = "缺少必需字段: {$field}";
        }
    }
    
    // 验证金额
    if (!empty($orderData['amount'])) {
        if (!is_numeric($orderData['amount']) || $orderData['amount'] <= 0) {
            $errors[] = "金额格式不正确";
        }
    }
    
    // 验证订单号格式
    if (!empty($orderData['order_no'])) {
        if (!preg_match('/^[A-Z0-9_]{6,64}$/', $orderData['order_no'])) {
            $errors[] = "订单号格式不正确";
        }
    }
    
    // 验证回调URL
    if (!empty($orderData['callback_url'])) {
        if (!filter_var($orderData['callback_url'], FILTER_VALIDATE_URL)) {
            $errors[] = "回调URL格式不正确";
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * 验证支付回调数据
 * @param array $callbackData 回调数据
 * @return array 验证结果
 */
function validateCallbackData($callbackData) {
    $errors = [];
    
    // 检查必需字段
    $requiredFields = ['order_no', 'status'];
    foreach ($requiredFields as $field) {
        if (empty($callbackData[$field])) {
            $errors[] = "缺少必需字段: {$field}";
        }
    }
    
    // 验证状态值
    if (!empty($callbackData['status'])) {
        $validStatuses = ['success', 'failed', 'pending'];
        if (!in_array($callbackData['status'], $validStatuses)) {
            $errors[] = "状态值不正确";
        }
    }
    
    // 验证金额（如果提供）
    if (!empty($callbackData['amount'])) {
        if (!is_numeric($callbackData['amount']) || $callbackData['amount'] <= 0) {
            $errors[] = "金额格式不正确";
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * 执行完整的安全验证
 * @param array $requestData 请求数据
 * @return array 验证结果
 */
function performSecurityValidation($requestData) {
    $errors = [];
    
    // 获取客户端IP
    $clientIP = getClientIP();
    
    // 验证IP白名单
    if (!validateIPWhitelist($clientIP)) {
        $errors[] = "IP地址不在白名单中";
    }
    
    // 验证请求频率
    if (!validateRateLimit($clientIP)) {
        $errors[] = "请求频率超限";
    }
    
    // 验证API密钥（如果提供）
    if (!empty($requestData['api_key'])) {
        if (!validateApiKey($requestData['api_key'])) {
            $errors[] = "API密钥无效";
        }
    }
    
    // 验证签名（如果提供）
    if (!empty($requestData['signature'])) {
        if (!validateRequestSignature($requestData, $requestData['signature'])) {
            $errors[] = "签名验证失败";
        }
    }
    
    writeLog('info', '安全验证完成', [
        'ip' => $clientIP,
        'valid' => empty($errors),
        'errors' => $errors
    ]);
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'ip' => $clientIP
    ];
}

/**
 * 生成API密钥
 * @return string 生成的API密钥
 */
function generateApiKey() {
    return bin2hex(random_bytes(32));
}

/**
 * 生成支付密钥
 * @return string 生成的支付密钥
 */
function generatePaymentSecret() {
    return bin2hex(random_bytes(16));
}

/**
 * 验证支付请求
 * @param array $request 请求数据
 * @return array 验证结果
 */
function verifyPaymentRequest($request) {
    $errors = [];
    $warnings = [];
    
    // 基本参数验证
    $requiredFields = ['order_no', 'amount', 'sign'];
    foreach ($requiredFields as $field) {
        if (empty($request[$field])) {
            $errors[] = "缺少必需参数: {$field}";
        }
    }
    
    if (!empty($errors)) {
        return [
            'valid' => false,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    // 验证订单号格式
    if (!preg_match('/^[A-Z0-9_]{6,64}$/', $request['order_no'])) {
        $errors[] = "订单号格式不正确";
    }
    
    // 验证金额
    if (!is_numeric($request['amount']) || $request['amount'] <= 0) {
        $errors[] = "金额格式不正确";
    }
    
    // 验证签名
    $config = require __DIR__ . '/../config/database.php';
    if (!verifySign($request, $config['payment_secret'])) {
        $errors[] = "签名验证失败";
    }
    
    // 验证IP白名单
    if (!isFromTrustedIp()) {
        $warnings[] = "请求来源IP不在白名单中";
    }
    
    // 验证时间戳（防止重放攻击）
    if (!empty($request['timestamp'])) {
        $timestamp = intval($request['timestamp']);
        $currentTime = time();
        if (abs($currentTime - $timestamp) > 300) { // 5分钟时间差
            $errors[] = "请求时间戳过期";
        }
    }
    
    // 记录验证日志
    logPayment('payment_verify', [
        'order_no' => $request['order_no'],
        'amount' => $request['amount'],
        'ip' => getClientIP(),
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ]);
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

?>
