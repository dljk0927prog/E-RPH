-- 修复出勤表结构，支持教课报告功能
-- 请以管理员身份运行此脚本

USE erph;

-- 1. 检查并添加notes字段（如果不存在）
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'notes') = 0,
        'ALTER TABLE attendance ADD COLUMN notes TEXT AFTER check_out',
        'SELECT "notes字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. 检查并添加check_in字段（如果不存在）
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'check_in') = 0,
        'ALTER TABLE attendance ADD COLUMN check_in TIME AFTER status',
        'SELECT "check_in字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. 检查并添加check_out字段（如果不存在）
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'check_out') = 0,
        'ALTER TABLE attendance ADD COLUMN check_out TIME AFTER check_in',
        'SELECT "check_out字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. 检查并添加created_at字段（如果不存在）
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'created_at') = 0,
        'ALTER TABLE attendance ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER notes',
        'SELECT "created_at字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. 更新现有记录的created_at字段（如果为NULL）
UPDATE attendance SET created_at = NOW() WHERE created_at IS NULL;

-- 6. 显示当前表结构
DESCRIBE attendance;

-- 7. 显示修复结果
SELECT '出勤表结构修复完成！' as message;
SELECT COUNT(*) as total_records FROM attendance;
SELECT COUNT(*) as records_with_notes FROM attendance WHERE notes IS NOT NULL AND notes != '';
SELECT COUNT(*) as records_with_checkin FROM attendance WHERE check_in IS NOT NULL;
SELECT COUNT(*) as records_with_checkout FROM attendance WHERE check_out IS NOT NULL;
