-- 创建系统设置表
-- 用于存储系统配置，包括登录页面背景设置

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL COMMENT '设置键名',
  `setting_value` text COMMENT '设置值',
  `description` varchar(255) DEFAULT NULL COMMENT '设置描述',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';

-- 插入默认背景设置
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) 
VALUES ('login_background', 'default', '登录页面背景设置') 
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 查看表结构
DESCRIBE `system_settings`;

-- 查看当前设置
SELECT * FROM `system_settings`;
