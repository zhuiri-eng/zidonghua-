<?php
/**
 * 支付成功页面模板
 */

// 设置页面变量
$page_title = '支付成功';
$page_description = '您的支付已成功处理';
$show_breadcrumb = true;
$breadcrumb_items = [
    ['url' => 'index.php', 'text' => '首页'],
    ['text' => '支付成功']
];

// 获取订单信息
$order_no = $_GET['order_no'] ?? '';
$amount = $_GET['amount'] ?? '';
$transaction_id = $_GET['transaction_id'] ?? '';

// 包含头部
include 'templates/header.php';
?>

<div class="payment-result success">
    <div class="payment-icon">✓</div>
    <h1>支付成功</h1>
    <p>订单号: <?php echo htmlspecialchars($orderId); ?></p>
    <p>支付金额: <?php echo htmlspecialchars($amount); ?>元</p>
    <p>交易时间: <?php echo htmlspecialchars($payTime); ?></p>
    
    <div class="action-buttons">
        <a href="/" class="btn btn-success">返回首页</a>
        <a href="/order/detail/<?php echo htmlspecialchars($orderId); ?>" class="btn">查看订单</a>
    </div>
</div>

<style>
.success-page {
    text-align: center;
    padding: 40px 0;
}

.success-icon {
    font-size: 4rem;
    color: #27ae60;
    margin-bottom: 20px;
    animation: bounce 1s ease-in-out;
}

.success-title {
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: 700;
}

.success-message {
    font-size: 1.2rem;
    color: #7f8c8d;
    margin-bottom: 30px;
    line-height: 1.6;
}

.order-details {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 25px;
    margin: 30px 0;
    text-align: left;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #e1e8ed;
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
    border: 1px solid #e1e8ed;
}

.copy-btn {
    cursor: pointer;
    position: relative;
    transition: all 0.3s ease;
}

.copy-btn:hover {
    background: #667eea !important;
    color: white !important;
}

.copy-btn::after {
    content: '点击复制';
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
    white-space: nowrap;
}

.copy-btn:hover::after {
    opacity: 1;
}

.action-buttons {
    margin: 30px 0;
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.success-tips {
    background: #e8f5e8;
    border: 1px solid #c3e6cb;
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
    text-align: left;
}

.success-tips h3 {
    color: #155724;
    margin-bottom: 15px;
    font-size: 1.1rem;
}

.success-tips ul {
    list-style: none;
    padding: 0;
}

.success-tips ul li {
    color: #155724;
    margin-bottom: 8px;
    padding-left: 20px;
    position: relative;
}

.success-tips ul li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #27ae60;
    font-weight: bold;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

@media (max-width: 768px) {
    .success-title {
        font-size: 2rem;
    }
    
    .success-message {
        font-size: 1.1rem;
    }
    
    .order-details {
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
    .success-title {
        font-size: 1.8rem;
    }
    
    .success-icon {
        font-size: 3rem;
    }
}
</style>

<script>
// 复制功能
document.addEventListener('DOMContentLoaded', function() {
    const copyButtons = document.querySelectorAll('.copy-btn');
    
    copyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const textToCopy = this.dataset.copy;
            
            // 使用现代API
            if (navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    showCopySuccess(this);
                }).catch(() => {
                    fallbackCopyTextToClipboard(textToCopy, this);
                });
            } else {
                fallbackCopyTextToClipboard(textToCopy, this);
            }
        });
    });
});

function fallbackCopyTextToClipboard(text, element) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showCopySuccess(element);
    } catch (err) {
        console.error('复制失败:', err);
        showCopyError(element);
    }
    
    document.body.removeChild(textArea);
}

function showCopySuccess(element) {
    const originalText = element.textContent;
    element.textContent = '已复制!';
    element.style.background = '#27ae60 !important';
    element.style.color = 'white !important';
    
    setTimeout(() => {
        element.textContent = originalText;
        element.style.background = '';
        element.style.color = '';
    }, 2000);
}

function showCopyError(element) {
    const originalText = element.textContent;
    element.textContent = '复制失败';
    element.style.background = '#e74c3c !important';
    element.style.color = 'white !important';
    
    setTimeout(() => {
        element.textContent = originalText;
        element.style.background = '';
        element.style.color = '';
    }, 2000);
}

// 页面加载动画
window.addEventListener('load', function() {
    const elements = document.querySelectorAll('.success-icon, .success-title, .success-message, .order-details, .action-buttons, .success-tips');
    
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
</script>

<?php
// 包含底部
include 'templates/footer.php';
?>
