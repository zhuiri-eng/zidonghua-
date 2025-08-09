# 支付回调系统安装指南

## 系统要求

- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本 / MariaDB 10.2 或更高版本
- Apache 2.4 或更高版本 / Nginx 1.18 或更高版本
- 支持 PDO 和 PDO_MySQL 扩展
- 支持 cURL 扩展（用于异步通知）

## 安装步骤

### 1. 环境准备

确保您的服务器满足以下要求：

```bash
# 检查 PHP 版本
php -v

# 检查 PHP 扩展
php -m | grep -E "(pdo|curl|json|mbstring)"

# 检查 MySQL 连接
mysql --version
```

### 2. 文件部署

1. 将所有文件上传到您的 Web 服务器目录
2. 确保文件权限正确：

```bash
# 设置目录权限
chmod 755 /path/to/payment_callback
chmod 644 /path/to/payment_callback/*.php
chmod 644 /path/to/payment_callback/static/css/*.css
chmod 644 /path/to/payment_callback/static/js/*.js

# 创建日志目录（如果不存在）
mkdir -p /path/to/payment_callback/logs
chmod 777 /path/to/payment_callback/logs
```

### 3. 数据库配置

1. 创建数据库：

```sql
CREATE DATABASE payment_callback CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. 修改 `config/database.php` 文件中的数据库配置：

```php
$dbConfig = [
    'host' => 'localhost',           // 数据库主机
    'dbname' => 'payment_callback',  // 数据库名
    'username' => 'your_username',   // 数据库用户名
    'password' => 'your_password',   // 数据库密码
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
```

### 4. 系统初始化

1. 运行测试脚本验证系统：

```bash
php test_system.php
```

2. 如果测试通过，访问主页面初始化数据库：

```
http://your-domain.com/payment_callback/
```

### 5. 安全配置

#### 5.1 修改默认密钥

1. 登录系统后，修改默认的支付密钥：

```php
// 在 includes/functions.php 中修改
$secret = 'your_secure_payment_secret_key_here';
```

#### 5.2 配置 IP 白名单

在 `includes/auth.php` 中配置允许的 IP 地址：

```php
$allowedIPs = [
    '127.0.0.1',
    '192.168.1.0/24',
    'your_payment_gateway_ip'
];
```

#### 5.3 配置 API 密钥

设置 API 访问密钥：

```php
$apiKeys = [
    'your_secure_api_key_here'
];
```

### 6. Web 服务器配置

#### Apache 配置

确保 `.htaccess` 文件正常工作，或者添加以下配置到 Apache 虚拟主机：

```apache
<Directory /path/to/payment_callback>
    AllowOverride All
    Require all granted
    
    # 安全设置
    <Files "*.php">
        Require all granted
    </Files>
    
    # 禁止访问敏感文件
    <FilesMatch "\.(log|sql|bak)$">
        Require all denied
    </FilesMatch>
</Directory>
```

#### Nginx 配置

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/payment_callback;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 安全设置
    location ~ /\. {
        deny all;
    }

    location ~ \.(log|sql|bak)$ {
        deny all;
    }
}
```

### 7. SSL 证书配置

强烈建议配置 SSL 证书以确保数据传输安全：

```bash
# 使用 Let's Encrypt 免费证书
sudo certbot --apache -d your-domain.com
```

### 8. 防火墙配置

配置服务器防火墙，只允许必要的端口：

```bash
# UFW (Ubuntu)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw enable

# iptables (CentOS)
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 22 -j ACCEPT
```

## 验证安装

### 1. 功能测试

访问以下页面验证功能：

- 主页面：`http://your-domain.com/payment_callback/`
- 订单查询：`http://your-domain.com/payment_callback/query_order.php`
- 回调处理：`http://your-domain.com/payment_callback/callback.php`
- 异步通知：`http://your-domain.com/payment_callback/notify.php`

### 2. API 测试

测试订单查询 API：

```bash
curl -X GET "http://your-domain.com/payment_callback/query_order.php?action=status&order_no=TEST_ORDER"
```

### 3. 回调测试

模拟支付回调：

```bash
curl -X POST "http://your-domain.com/payment_callback/callback.php" \
  -d "order_no=TEST_ORDER&amount=100.00&status=paid&signature=YOUR_SIGNATURE"
```

## 故障排除

### 常见问题

1. **数据库连接失败**
   - 检查数据库配置信息
   - 确认数据库服务正在运行
   - 验证数据库用户权限

2. **权限错误**
   - 检查文件和目录权限
   - 确认 Web 服务器用户有读写权限

3. **页面无法访问**
   - 检查 Web 服务器配置
   - 确认 PHP 模块已启用
   - 查看错误日志

4. **回调失败**
   - 检查签名算法
   - 验证回调地址可访问性
   - 查看系统日志

### 日志查看

系统日志位置：
- 错误日志：`logs/error.log`
- 访问日志：`logs/access.log`
- 支付日志：`logs/payment.log`

查看日志：
```bash
tail -f logs/error.log
tail -f logs/payment.log
```

## 维护建议

1. **定期备份**
   - 数据库备份
   - 代码文件备份
   - 配置文件备份

2. **安全更新**
   - 定期更新 PHP 版本
   - 更新依赖库
   - 监控安全漏洞

3. **性能优化**
   - 启用 PHP OPcache
   - 配置 MySQL 查询缓存
   - 使用 CDN 加速静态资源

4. **监控告警**
   - 设置系统监控
   - 配置错误告警
   - 监控支付成功率

## 技术支持

如果遇到问题，请：

1. 查看系统日志
2. 运行测试脚本
3. 检查配置文件
4. 联系技术支持

---

安装完成后，请删除 `test_system.php` 文件以确保系统安全。
