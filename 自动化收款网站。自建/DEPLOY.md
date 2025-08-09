# 部署指南

## GitHub 推送

1. 确保已安装 Git
2. 配置 Git 用户信息：
   ```bash
   git config --global user.name "Your Name"
   git config --global user.email "your.email@example.com"
   ```

3. 推送代码到 GitHub：
   ```bash
   git add .
   git commit -m "Initial commit: 支付回调系统"
   git push -u origin master
   ```

## Netlify 部署

### 方法一：通过 GitHub 连接部署

1. 登录 [Netlify](https://netlify.com)
2. 点击 "New site from Git"
3. 选择 GitHub 并授权
4. 选择仓库 `zhuiri-eng/zidonghua-`
5. 配置部署设置：
   - **Build command**: 留空
   - **Publish directory**: `.`
   - **Base directory**: 留空

### 方法二：手动上传

1. 登录 Netlify
2. 点击 "New site from Git" → "Deploy manually"
3. 将项目文件夹拖拽到上传区域

## 环境配置

### 1. 数据库配置

在 Netlify 的环境变量中设置：

```
DB_HOST=your_database_host
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_NAME=payment_system
PAYMENT_SECRET=your_payment_secret
API_KEY=your_api_key
```

### 2. 创建配置文件

在 Netlify 的 Functions 目录下创建 `config/database.php`：

```php
<?php
return [
    'host' => $_ENV['DB_HOST'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'database' => $_ENV['DB_NAME'],
    'charset' => 'utf8mb4',
    'log_path' => __DIR__ . '/../logs/',
    'ip_whitelist' => ['127.0.0.1', '::1'],
    'payment_secret' => $_ENV['PAYMENT_SECRET'],
    'api_key' => $_ENV['API_KEY'],
    'api_timeout' => 30,
    'max_retry_times' => 3
];
```

## 数据库设置

1. 创建 MySQL 数据库
2. 导入 `database/init.sql` 文件
3. 确保数据库可以从 Netlify 访问

## 域名配置

1. 在 Netlify 中设置自定义域名
2. 配置 SSL 证书（Netlify 自动提供）
3. 更新支付平台的回调 URL

## 测试部署

1. 访问部署的网站
2. 测试订单创建功能
3. 测试支付回调功能
4. 检查日志文件

## 注意事项

- 确保数据库连接信息正确
- 支付密钥和API密钥要保密
- 定期备份数据库
- 监控网站性能和错误日志
