# 支付回调系统

一个专业的支付回调处理系统，提供安全、可靠的支付回调服务。

## 功能特性

- ✅ 订单创建和管理
- ✅ 支付回调处理
- ✅ 异步通知处理
- ✅ 订单查询和统计
- ✅ 安全验证和签名
- ✅ 响应式Web界面
- ✅ 完整的日志记录
- ✅ 数据库支持

## 系统架构

```
/payment_callback/
├── config/
│   └── database.php      # 数据库配置
├── includes/
│   ├── functions.php     # 公用函数
│   └── auth.php          # 验证逻辑
├── static/
│   ├── css/
│   │   └── style.css     # 样式文件
│   └── js/
│       └── main.js       # 前端脚本
├── templates/
│   ├── header.php        # 页面头部
│   ├── footer.php        # 页面底部
│   ├── success.php       # 成功页面模板
│   └── failure.php       # 失败页面模板
├── index.php             # 主入口文件
├── callback.php          # 支付回调处理
├── notify.php            # 异步通知处理
├── query_order.php       # 订单查询接口
└── .htaccess             # Apache配置
```

## 安装说明

### 1. 环境要求

- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- 启用 mod_rewrite (Apache)

### 2. 安装步骤

1. 下载代码到Web服务器目录
2. 配置数据库连接 (`config/database.php`)
3. 设置文件权限：
   ```bash
   chmod 755 logs/
   chmod 755 cache/
   chmod 644 config/database.php
   ```
4. 访问 `index.php` 初始化数据库

### 3. 数据库配置

编辑 `config/database.php` 文件：

```php
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'payment_callback',
    'username' => 'your_username',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
```

## 使用说明

### 创建订单

访问首页，填写订单信息：
- 订单号（可选，自动生成）
- 订单金额
- 支付方式
- 回调地址
- 异步通知地址

### 支付回调

支付平台回调地址：`https://yourdomain.com/callback.php`

回调参数：
- `order_no`: 订单号
- `status`: 支付状态 (success/failed)
- `amount`: 支付金额
- `transaction_id`: 交易流水号
- `signature`: 签名

### 异步通知

异步通知地址：`https://yourdomain.com/notify.php`

### 订单查询

API接口：`https://yourdomain.com/query_order.php?order_no=订单号`

## 安全配置

### 1. 密钥设置

在数据库中设置支付密钥：
```sql
UPDATE system_config SET config_value = 'your_secret_key' WHERE config_key = 'payment_secret';
```

### 2. IP白名单

设置允许的IP地址：
```sql
UPDATE system_config SET config_value = '192.168.1.1,10.0.0.1' WHERE config_key = 'ip_whitelist';
```

### 3. 签名验证

系统使用MD5签名验证，签名算法：
1. 按键名排序
2. 拼接参数
3. 添加密钥
4. MD5加密

## API文档

### 创建订单

**POST** `/index.php`

参数：
- `order_no`: 订单号（可选）
- `amount`: 金额
- `currency`: 货币类型
- `payment_method`: 支付方式
- `callback_url`: 回调地址
- `notify_url`: 异步通知地址

### 查询订单

**GET** `/query_order.php?order_no=订单号`

### 查询订单状态

**GET** `/query_order.php?action=status&order_no=订单号`

### 获取最近订单

**GET** `/query_order.php?action=list&recent=10`

## 日志文件

系统日志位于 `logs/` 目录：
- `payment_YYYY-MM-DD.log`: 支付相关日志
- `php_errors.log`: PHP错误日志

## 故障排除

### 常见问题

1. **数据库连接失败**
   - 检查数据库配置
   - 确认数据库服务运行

2. **回调验证失败**
   - 检查签名密钥设置
   - 确认回调参数格式

3. **文件权限错误**
   - 设置正确的文件权限
   - 确保logs和cache目录可写

### 调试模式

启用调试日志：
```sql
UPDATE system_config SET config_value = 'debug' WHERE config_key = 'log_level';
```

## 更新日志

### v1.0.0 (2024-01-01)
- 初始版本发布
- 基础支付回调功能
- Web管理界面
- 安全验证机制

## 技术支持

如有问题，请联系技术支持：
- 邮箱: support@example.com
- 文档: https://docs.example.com

## 许可证

MIT License
