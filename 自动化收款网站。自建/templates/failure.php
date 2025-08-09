<?php
/**
 * 支付失败页面模板
 */

// 设置页面变量
$page_title = '支付失败';
$page_description = '支付处理失败，请重试';
$show_breadcrumb = true;
$breadcrumb_items = [
    ['url' => 'index.php', 'text' => '首页'],
    ['text' => '支付失败']
];

// 获取错误信息
$order_no = $_GET['order_no'] ?? '';
$error_message = $_GET['error'] ?? '支付处理失败，请重试';
$amount = $_GET['amount'] ?? '';

// 包含头部
include 'templates/header.php';
?>

<div class="payment-result failure">
    <div class="payment-icon">✗</div>
    <h1>支付失败</h1>
    <p>订单号: <?php echo htmlspecialchars($orderId); ?></p>
    <p>失败原因: <?php echo htmlspecialchars($reason); ?></p>
    
    <div class="action-buttons">
        <a href="/" class="btn">返回首页</a>
        <a href="/payment/retry/<?php echo htmlspecialchars($orderId); ?>" class="btn btn-danger">重新支付</a>
    </div>
</div>

<style>
.failure-page {
    text-align: center;
    padding: 40px 0;
}

.failure-icon {
    font-size: 4rem;
    color: #e74c3c;
    margin-bottom: 20px;
    animation: shake 0.5s ease-in-out;
}

.failure-title {
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: 700;
}

.failure-message {
    font-size: 1.2rem;
    color: #7f8c8d;
    margin-bottom: 30px;
    line-height: 1.6;
}

.error-details {
    background: #fdf2f2;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
    padding: 25px;
    margin: 30px 0;
    text-align: left;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border-left: 4px solid #e74c3c;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f5c6cb;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 600;
    color: #2c3e50;
    min-width: 120px;
}

.detail-value {
    color: #7f8c8d;
    font-family: 'Courier New', monospace;
    background: white;
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #f5c6cb;
}

.action-buttons {
    margin: 30px 0;
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.failure-tips {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
    text-align: left;
}

.failure-tips h3 {
    color: #856404;
    margin-bottom: 15px;
    font-size: 1.1rem;
    margin-top: 20px;
}

.failure-tips h3:first-child {
    margin-top: 0;
}

.failure-tips ul {
    list-style: none;
    padding: 0;
    margin-bottom: 20px;
}

.failure-tips ul:last-child {
    margin-bottom: 0;
}

.failure-tips ul li {
    color: #856404;
    margin-bottom: 8px;
    padding-left: 20px;
    position: relative;
}

.failure-tips ul li::before {
    content: '•';
    position: absolute;
    left: 0;
    color: #f39c12;
    font-weight: bold;
    font-size: 1.2rem;
}

@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-5px);
    }
    75% {
        transform: translateX(5px);
    }
}

@media (max-width: 768px) {
    .failure-title {
        font-size: 2rem;
    }
    
    .failure-message {
        font-size: 1.1rem;
    }
    
    .error-details {
        margin: 20px 10px;
    }
    
    .detail-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .detail-label {
        min-width: auto;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
    }
}

@media (max-width: 480px) {
    .failure-title {
        font-size: 1.8rem;
    }
    
    .failure-icon {
        font-size: 3rem;
    }
}
</style>

<script>
// 页面加载动画
window.addEventListener('load', function() {
    const elements = document.querySelectorAll('.failure-icon, .failure-title, .failure-message, .error-details, .action-buttons, .failure-tips');
    
    elements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            element.style.transition = 'all 0.6s ease';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 200);
    });
});

// 自动重试功能
let retryCount = 0;
const maxRetries = 3;

function autoRetry() {
    if (retryCount < maxRetries) {
        retryCount++;
        console.log(`自动重试第 ${retryCount} 次`);
        
        // 显示重试提示
        const retryMessage = document.createElement('div');
        retryMessage.className = 'alert alert-info';
        retryMessage.innerHTML = `<strong>自动重试:</strong> 正在进行第 ${retryCount} 次重试...`;
        
        const container = document.querySelector('.container');
        container.insertBefore(retryMessage, container.firstChild);
        
        // 延迟重试
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    }
}

// 5秒后自动重试
setTimeout(autoRetry, 5000);
</script>

<?php
// 包含底部
include 'templates/footer.php';
?>
