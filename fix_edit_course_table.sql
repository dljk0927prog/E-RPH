-- 修复编辑课程页面需要的数据库字段
-- 请以管理员身份运行此脚本

USE erph;

-- 1. 检查并添加is_active字段（如果不存在）
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'is_active') = 0,
        'ALTER TABLE courses ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER teacher_id',
        'SELECT "is_active字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. 检查并添加teacher_id字段（如果不存在）
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'teacher_id') = 0,
        'ALTER TABLE courses ADD COLUMN teacher_id INT AFTER description',
        'SELECT "teacher_id字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. 为teacher_id添加外键约束（如果不存在）
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'teacher_id' AND REFERENCED_TABLE_NAME = 'users') = 0,
        'ALTER TABLE courses ADD CONSTRAINT fk_courses_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL',
        'SELECT "teacher_id外键约束已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. 更新现有记录的is_active字段（如果为NULL）
UPDATE courses SET is_active = TRUE WHERE is_active IS NULL;

-- 5. 显示当前表结构
DESCRIBE courses;

-- 6. 显示修复结果
SELECT '数据库表结构修复完成！' as message;
SELECT COUNT(*) as total_courses FROM courses;
SELECT COUNT(*) as active_courses FROM courses WHERE is_active = TRUE;
SELECT COUNT(*) as inactive_courses FROM courses WHERE is_active = FALSE;
