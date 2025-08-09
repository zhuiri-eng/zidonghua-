/**
 * 支付回调系统前端脚本
 */

// 全局变量
let currentOrder = null;
let isSubmitting = false;

// DOM加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * 初始化应用
 */
function initializeApp() {
    initializeFormValidation();
    initializeEventListeners();
    addPageLoadAnimation();
}

/**
 * 初始化表单验证
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                return false;
            }
        });
        
        const inputs = form.querySelectorAll('input[data-validate], select[data-validate]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    });
}

/**
 * 验证表单
 */
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[data-validate], select[data-validate]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

/**
 * 验证单个字段
 */
function validateField(field) {
    const value = field.value.trim();
    const rules = field.dataset.validate ? field.dataset.validate.split('|') : [];
    
    clearFieldError(field);
    
    for (let rule of rules) {
        const [ruleName, ruleValue] = rule.split(':');
        
        switch (ruleName) {
            case 'required':
                if (!value) {
                    showFieldError(field, '此字段为必填项');
                    return false;
                }
                break;
                
            case 'minlength':
                if (value.length < parseInt(ruleValue)) {
                    showFieldError(field, `最少需要 ${ruleValue} 个字符`);
                    return false;
                }
                break;
                
            case 'maxlength':
                if (value.length > parseInt(ruleValue)) {
                    showFieldError(field, `最多允许 ${ruleValue} 个字符`);
                    return false;
                }
                break;
                
            case 'email':
                if (value && !isValidEmail(value)) {
                    showFieldError(field, '请输入有效的邮箱地址');
                    return false;
                }
                break;
                
            case 'url':
                if (value && !isValidUrl(value)) {
                    showFieldError(field, '请输入有效的URL地址');
                    return false;
                }
                break;
                
            case 'numeric':
                if (value && !isNumeric(value)) {
                    showFieldError(field, '请输入有效的数字');
                    return false;
                }
                break;
                
            case 'amount':
                if (value && !isValidAmount(value)) {
                    showFieldError(field, '请输入有效的金额');
                    return false;
                }
                break;
        }
    }
    
    return true;
}

/**
 * 显示字段错误
 */
function showFieldError(field, message) {
    field.classList.add('error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.cssText = 'color: #e74c3c; font-size: 0.85rem; margin-top: 5px;';
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * 清除字段错误
 */
function clearFieldError(field) {
    field.classList.remove('error');
    
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * 验证邮箱格式
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * 验证URL格式
 */
function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

/**
 * 验证数字格式
 */
function isNumeric(value) {
    return !isNaN(value) && !isNaN(parseFloat(value));
}

/**
 * 验证金额格式
 */
function isValidAmount(value) {
    const amountRegex = /^\d+(\.\d{1,2})?$/;
    return amountRegex.test(value) && parseFloat(value) > 0;
}

/**
 * 初始化事件监听器
 */
function initializeEventListeners() {
    const createOrderBtn = document.getElementById('createOrderBtn');
    if (createOrderBtn) {
        createOrderBtn.addEventListener('click', handleCreateOrder);
    }
    
    const queryOrderBtn = document.getElementById('queryOrderBtn');
    if (queryOrderBtn) {
        queryOrderBtn.addEventListener('click', handleQueryOrder);
    }
    
    const backHomeBtn = document.getElementById('backHomeBtn');
    if (backHomeBtn) {
        backHomeBtn.addEventListener('click', () => {
            window.location.href = 'index.php';
        });
    }
    
    const copyButtons = document.querySelectorAll('.copy-btn');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const textToCopy = this.dataset.copy;
            copyToClipboard(textToCopy);
            showToast('已复制到剪贴板', 'success');
        });
    });
}

/**
 * 处理创建订单
 */
async function handleCreateOrder() {
    if (isSubmitting) return;
    
    const form = document.getElementById('orderForm');
    if (!validateForm(form)) {
        showToast('请检查表单输入', 'error');
        return;
    }
    
    isSubmitting = true;
    const submitBtn = document.getElementById('createOrderBtn');
    const originalText = submitBtn.textContent;
    
    try {
        submitBtn.textContent = '创建中...';
        submitBtn.disabled = true;
        
        const formData = new FormData(form);
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('订单创建成功', 'success');
            currentOrder = result.order;
            displayOrderResult(result.order);
        } else {
            showToast(result.error || '订单创建失败', 'error');
        }
    } catch (error) {
        console.error('创建订单错误:', error);
        showToast('网络错误，请重试', 'error');
    } finally {
        isSubmitting = false;
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

/**
 * 处理查询订单
 */
async function handleQueryOrder() {
    const orderNo = document.getElementById('queryOrderNo').value.trim();
    
    if (!orderNo) {
        showToast('请输入订单号', 'error');
        return;
    }
    
    const queryBtn = document.getElementById('queryOrderBtn');
    const originalText = queryBtn.textContent;
    
    try {
        queryBtn.textContent = '查询中...';
        queryBtn.disabled = true;
        
        const response = await fetch(`query_order.php?order_no=${encodeURIComponent(orderNo)}`);
        const result = await response.json();
        
        if (result.success) {
            displayOrderResult(result.order);
        } else {
            showToast(result.error || '订单不存在', 'error');
        }
    } catch (error) {
        console.error('查询订单错误:', error);
        showToast('网络错误，请重试', 'error');
    } finally {
        queryBtn.textContent = originalText;
        queryBtn.disabled = false;
    }
}

/**
 * 显示订单结果
 */
function displayOrderResult(order) {
    const resultContainer = document.getElementById('orderResult');
    if (!resultContainer) return;
    
    const statusClass = getStatusClass(order.status);
    const statusText = getStatusText(order.status);
    
    resultContainer.innerHTML = `
        <div class="card fade-in">
            <div class="card-header">
                <h3>订单详情</h3>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>订单号:</strong> <span class="copy-btn" data-copy="${order.order_no}">${order.order_no}</span></p>
                    <p><strong>金额:</strong> ¥${order.amount}</p>
                    <p><strong>状态:</strong> <span class="status-indicator ${statusClass}">${statusText}</span></p>
                </div>
                <div class="col-md-6">
                    <p><strong>创建时间:</strong> ${formatDateTime(order.created_at)}</p>
                    <p><strong>支付方式:</strong> ${order.payment_method || '未指定'}</p>
                    <p><strong>货币:</strong> ${order.currency}</p>
                </div>
            </div>
            <div class="text-center mt-3">
                <button class="btn btn-primary" onclick="refreshOrderStatus('${order.order_no}')">刷新状态</button>
                <button class="btn btn-secondary" onclick="window.location.reload()">创建新订单</button>
            </div>
        </div>
    `;
    
    resultContainer.scrollIntoView({ behavior: 'smooth' });
}

/**
 * 刷新订单状态
 */
async function refreshOrderStatus(orderNo) {
    try {
        const response = await fetch(`query_order.php?order_no=${encodeURIComponent(orderNo)}`);
        const result = await response.json();
        
        if (result.success) {
            displayOrderResult(result.order);
            showToast('状态已更新', 'success');
        } else {
            showToast(result.error || '更新失败', 'error');
        }
    } catch (error) {
        console.error('刷新状态错误:', error);
        showToast('网络错误，请重试', 'error');
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
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

/**
 * 复制到剪贴板
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
    } catch (err) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
    }
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

/**
 * 添加页面加载动画
 */
function addPageLoadAnimation() {
    const elements = document.querySelectorAll('.card, .alert, .table');
    elements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            element.style.transition = 'all 0.5s ease';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// 导出函数供其他脚本使用
window.PaymentCallback = {
    validateForm,
    showToast,
    copyToClipboard,
    formatDateTime,
    getStatusClass,
    getStatusText
};
