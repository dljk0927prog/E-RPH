-- 修复courses表结构 - 完整版本
USE erph;

-- 检查并添加缺失的字段
-- 1. 添加teacher_id字段（如果不存在）
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'teacher_id') = 0,
    'ALTER TABLE courses ADD COLUMN teacher_id INT AFTER description',
    'SELECT "teacher_id字段已存在" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. 添加is_active字段（如果不存在）
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'is_active') = 0,
    'ALTER TABLE courses ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER teacher_id',
    'SELECT "is_active字段已存在" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. 添加外键约束（如果不存在）
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'teacher_id' AND REFERENCED_TABLE_NAME = 'users') = 0,
    'ALTER TABLE courses ADD CONSTRAINT fk_courses_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "外键约束已存在" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. 更新现有课程数据，为没有teacher_id的课程设置默认值
UPDATE courses SET teacher_id = NULL WHERE teacher_id IS NULL;
UPDATE courses SET is_active = TRUE WHERE is_active IS NULL;

-- 显示更新后的表结构
DESCRIBE courses;

-- 显示课程数据
SELECT * FROM courses;

-- 显示外键约束
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'courses';
