-- 修复users表created_at字段的SQL脚本
-- 请在phpMyAdmin中执行此脚本

-- 1. 检查users表结构
DESCRIBE users;

-- 2. 检查是否存在created_at字段
SHOW COLUMNS FROM users LIKE 'created_at';

-- 3. 如果不存在created_at字段，则添加它
ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 4. 如果字段存在但值为NULL或1970-01-01，则更新为当前时间
UPDATE users SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL OR created_at = '1970-01-01 00:00:00';

-- 5. 检查更新后的数据
SELECT id, name, email, role, created_at FROM users LIMIT 10;

-- 6. 为现有记录设置合理的创建时间（可选）
-- 如果所有记录都是1970-01-01，可以设置为不同的时间
-- UPDATE users SET created_at = DATE_SUB(CURRENT_TIMESTAMP, INTERVAL FLOOR(RAND() * 365) DAY) WHERE created_at = '1970-01-01 00:00:00';
