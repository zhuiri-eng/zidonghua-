<?php
/**
 * 支付回调系统主入口文件
 * 提供订单创建和查询功能
 */

// 启动会话
session_start();

// 包含必要文件
require_once 'config/database.php';
require_once 'includes/functions.php';


// 初始化数据库
if (!initDatabase()) {
    die('数据库初始化失败');
}

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = handleOrderCreation();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// 设置页面变量
$page_title = '支付回调系统';
$page_description = '创建和管理支付订单';
$show_breadcrumb = false;

// 包含头部
include 'templates/header.php';
?>

<div class="row">
    <!-- 创建订单表单 -->
    <div class="col-md-6">
        <div class="card fade-in">
            <div class="card-header">
                <h2 class="card-title">创建新订单</h2>
                <p class="card-subtitle">填写订单信息，创建支付订单</p>
            </div>
            
            <form id="orderForm" data-validate="true">
                <div class="form-group">
                    <label for="order_no" class="form-label">订单号</label>
                    <input type="text" id="order_no" name="order_no" class="form-input" 
                           data-validate="required|minlength:6|maxlength:64" 
                           placeholder="请输入订单号或留空自动生成">
                    <small class="form-text">留空将自动生成订单号</small>
                </div>
                
                <div class="form-group">
                    <label for="amount" class="form-label">订单金额 *</label>
                    <input type="number" id="amount" name="amount" class="form-input" 
                           data-validate="required|amount" 
                           step="0.01" min="0.01" 
                           placeholder="请输入订单金额">
                </div>
                
                <div class="form-group">
                    <label for="currency" class="form-label">货币类型</label>
                    <select id="currency" name="currency" class="form-select">
                        <option value="CNY">人民币 (CNY)</option>
                        <option value="USD">美元 (USD)</option>
                        <option value="EUR">欧元 (EUR)</option>
                        <option value="JPY">日元 (JPY)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="payment_method" class="form-label">支付方式</label>
                    <select id="payment_method" name="payment_method" class="form-select">
                        <option value="">请选择支付方式</option>
                        <option value="alipay">支付宝</option>
                        <option value="wechat">微信支付</option>
                        <option value="unionpay">银联支付</option>
                        <option value="bank">银行转账</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="callback_url" class="form-label">回调地址</label>
                    <input type="url" id="callback_url" name="callback_url" class="form-input" 
                           data-validate="url" 
                           placeholder="https://example.com/callback">
                    <small class="form-text">支付完成后的同步回调地址</small>
                </div>
                
                <div class="form-group">
                    <label for="notify_url" class="form-label">异步通知地址</label>
                    <input type="url" id="notify_url" name="notify_url" class="form-input" 
                           data-validate="url" 
                           placeholder="https://example.com/notify">
                    <small class="form-text">支付完成后的异步通知地址</small>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">订单描述</label>
                    <textarea id="description" name="description" class="form-input" 
                              rows="3" maxlength="200" 
                              placeholder="请输入订单描述信息"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" id="createOrderBtn" class="btn btn-primary">
                        创建订单
                    </button>
                    <button type="reset" class="btn btn-secondary">重置表单</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 订单查询 -->
    <div class="col-md-6">
        <div class="card fade-in">
            <div class="card-header">
                <h2 class="card-title">查询订单</h2>
                <p class="card-subtitle">输入订单号查询订单状态</p>
            </div>
            
            <form id="queryForm">
                <div class="form-group">
                    <label for="queryOrderNo" class="form-label">订单号 *</label>
                    <input type="text" id="queryOrderNo" name="order_no" class="form-input" 
                           data-validate="required" 
                           placeholder="请输入要查询的订单号">
                </div>
                
                <div class="form-actions">
                    <button type="button" id="queryOrderBtn" class="btn btn-primary">
                        查询订单
                    </button>
                </div>
            </form>
            
            <!-- 查询结果显示区域 -->
            <div id="orderResult"></div>
        </div>
        
        <!-- 系统信息 -->
        <div class="card fade-in">
            <div class="card-header">
                <h3 class="card-title">系统信息</h3>
            </div>
            
            <div class="system-info">
                <div class="info-item">
                    <span class="info-label">系统版本:</span>
                    <span class="info-value">1.0.0</span>
                </div>
                <div class="info-item">
                    <span class="info-label">运行时间:</span>
                    <span class="info-value" id="uptime">计算中...</span>
                </div>
                <div class="info-item">
                    <span class="info-label">数据库状态:</span>
                    <span class="info-value status-success">正常</span>
                </div>
                <div class="info-item">
                    <span class="info-label">最后更新:</span>
                    <span class="info-value"><?php echo date('Y-m-d H:i:s'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 最近订单列表 -->
<div class="row">
    <div class="col-12">
        <div class="card fade-in">
            <div class="card-header">
                <h3 class="card-title">最近订单</h3>
                <a href="query_order.php" class="btn btn-secondary btn-sm">查看全部</a>
            </div>
            
            <div id="recentOrders">
                <div class="loading-container">
                    <div class="loading"></div>
                    <p>加载中...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.col-md-6 {
    min-width: 0;
}

.col-12 {
    grid-column: 1 / -1;
}

.form-text {
    font-size: 0.85rem;
    color: #7f8c8d;
    margin-top: 5px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.system-info {
    display: grid;
    gap: 10px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e1e8ed;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #2c3e50;
}

.info-value {
    color: #7f8c8d;
    font-family: 'Courier New', monospace;
}

.loading-container {
    text-align: center;
    padding: 40px;
    color: #7f8c8d;
}

.loading-container p {
    margin-top: 10px;
}

@media (max-width: 768px) {
    .row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<script>
// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 自动生成订单号
    const orderNoInput = document.getElementById('order_no');
    if (orderNoInput && !orderNoInput.value) {
        orderNoInput.value = 'PAY' + Date.now() + Math.random().toString(36).substr(2, 4).toUpperCase();
    }
    
    // 加载最近订单
    loadRecentOrders();
    
    // 更新运行时间
    updateUptime();
    setInterval(updateUptime, 1000);
});

/**
 * 处理订单创建
 */
function handleOrderCreation() {
    const form = document.getElementById('orderForm');
    const formData = new FormData(form);
    
    // 如果没有填写订单号，自动生成
    if (!formData.get('order_no')) {
        formData.set('order_no', 'PAY' + Date.now() + Math.random().toString(36).substr(2, 4).toUpperCase());
    }
    
    return fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('订单创建成功', 'success');
            form.reset();
            loadRecentOrders();
            return data;
        } else {
            showToast(data.error || '订单创建失败', 'error');
            throw new Error(data.error);
        }
    })
    .catch(error => {
        console.error('创建订单错误:', error);
        showToast('网络错误，请重试', 'error');
    });
}

/**
 * 加载最近订单
 */
async function loadRecentOrders() {
    try {
        const response = await fetch('query_order.php?recent=5');
        const data = await response.json();
        
        const container = document.getElementById('recentOrders');
        
        if (data.success && data.orders.length > 0) {
            container.innerHTML = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>订单号</th>
                            <th>金额</th>
                            <th>状态</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.orders.map(order => `
                            <tr>
                                <td>${order.order_no}</td>
                                <td>¥${order.amount}</td>
                                <td><span class="status-indicator ${getStatusClass(order.status)}">${getStatusText(order.status)}</span></td>
                                <td>${formatDateTime(order.created_at)}</td>
                                <td>
                                    <a href="query_order.php?order_no=${order.order_no}" class="btn btn-sm btn-secondary">查看</a>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } else {
            container.innerHTML = '<p class="text-center text-muted">暂无订单数据</p>';
        }
    } catch (error) {
        console.error('加载最近订单错误:', error);
        document.getElementById('recentOrders').innerHTML = '<p class="text-center text-danger">加载失败</p>';
    }
}

/**
 * 更新运行时间
 */
function updateUptime() {
    const uptimeElement = document.getElementById('uptime');
    if (uptimeElement) {
        const startTime = new Date('2024-01-01T00:00:00');
        const now = new Date();
        const diff = now - startTime;
        
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        uptimeElement.textContent = `${days}天 ${hours}小时 ${minutes}分钟 ${seconds}秒`;
    }
}

/**
 * 获取状态样式类
 */
function getStatusClass(status) {
    const statusMap = {
        'pending': 'status-pending',
        'paid': 'status-success',
        'failed': 'status-failed',
        'cancelled': 'status-failed'
    };
    return statusMap[status] || 'status-pending';
}

/**
 * 获取状态文本
 */
function getStatusText(status) {
    const statusMap = {
        'pending': '待支付',
        'paid': '已支付',
        'failed': '支付失败',
        'cancelled': '已取消'
    };
    return statusMap[status] || '未知状态';
}

/**
 * 格式化日期时间
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('zh-CN', {
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * 显示提示消息
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type} fade-in`;
    toast.textContent = message;
    
    const bgColors = {
        'success': '#27ae60',
        'error': '#e74c3c',
        'warning': '#f39c12',
        'info': '#3498db'
    };
    
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 6px;
        color: white;
        font-weight: 500;
        z-index: 9999;
        max-width: 300px;
        word-wrap: break-word;
        background-color: ${bgColors[type] || bgColors.info};
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}
</script>

<?php
/**
 * 处理订单创建请求
 */
function handleOrderCreation() {
    try {
        // 验证请求数据
        $orderData = [
            'order_no' => $_POST['order_no'] ?? '',
            'amount' => $_POST['amount'] ?? '',
            'currency' => $_POST['currency'] ?? 'CNY',
            'payment_method' => $_POST['payment_method'] ?? '',
            'callback_url' => $_POST['callback_url'] ?? '',
            'notify_url' => $_POST['notify_url'] ?? '',
            'description' => $_POST['description'] ?? ''
        ];
        
        // 如果没有提供订单号，自动生成
        if (empty($orderData['order_no'])) {
            $orderData['order_no'] = generateOrderNo();
        }
        
        // 验证订单数据
        $validation = validateOrderData($orderData);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => implode(', ', $validation['errors'])
            ];
        }
        
        // 创建订单
        $orderId = createOrder($orderData);
        
        if ($orderId) {
            // 获取完整的订单信息
            $order = getOrder($orderData['order_no']);
            
            return [
                'success' => true,
                'order' => $order,
                'message' => '订单创建成功'
            ];
        } else {
            return [
                'success' => false,
                'error' => '订单创建失败'
            ];
        }
    } catch (Exception $e) {
        logPayment('订单创建异常: ' . $e->getMessage(), 'error');
        return [
            'success' => false,
            'error' => '系统错误，请稍后重试'
        ];
    }
}

// 包含底部
include 'templates/footer.php';
?>
