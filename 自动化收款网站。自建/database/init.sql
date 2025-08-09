CREATE DATABASE IF NOT EXISTS `payment_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `payment_system`;

CREATE TABLE `orders` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(32) NOT NULL COMMENT '商户订单号',
  `amount` decimal(10,2) NOT NULL COMMENT '订单金额',
  `status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending' COMMENT '订单状态',
  `trade_no` varchar(64) DEFAULT NULL COMMENT '支付平台交易号',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_id` (`order_id`),
  KEY `idx_trade_no` (`trade_no`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

CREATE TABLE `payment_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(32) NOT NULL COMMENT '关联订单号',
  `action` varchar(20) NOT NULL COMMENT '操作类型',
  `data` text COMMENT '请求/响应数据',
  `ip` varchar(45) NOT NULL COMMENT 'IP地址',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付日志表';
