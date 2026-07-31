-- 修复教案和教课报告关联问题
-- 请以管理员身份运行此脚本

USE erph;

-- 1. 检查并添加lesson_plan_id字段到attendance表
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'lesson_plan_id') = 0,
        'ALTER TABLE attendance ADD COLUMN lesson_plan_id INT AFTER course_id',
        'SELECT "lesson_plan_id字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. 添加外键约束（如果不存在）
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'attendance' 
         AND COLUMN_NAME = 'lesson_plan_id' AND REFERENCED_TABLE_NAME = 'lesson_plans') = 0,
        'ALTER TABLE attendance ADD CONSTRAINT fk_attendance_lesson_plan 
         FOREIGN KEY (lesson_plan_id) REFERENCES lesson_plans(id) ON DELETE SET NULL',
        'SELECT "lesson_plan_id外键约束已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. 检查并修复lesson_plans表结构
-- 添加缺失的字段
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'lesson_plans' AND COLUMN_NAME = 'subject_id') = 0,
        'ALTER TABLE lesson_plans ADD COLUMN subject_id INT AFTER course_id',
        'SELECT "subject_id字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'lesson_plans' AND COLUMN_NAME = 'class_id') = 0,
        'ALTER TABLE lesson_plans ADD COLUMN class_id INT AFTER subject_id',
        'SELECT "class_id字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'lesson_plans' AND COLUMN_NAME = 'lesson_date') = 0,
        'ALTER TABLE lesson_plans ADD COLUMN lesson_date DATE AFTER class_id',
        'SELECT "lesson_date字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'lesson_plans' AND COLUMN_NAME = 'start_time') = 0,
        'ALTER TABLE lesson_plans ADD COLUMN start_time TIME AFTER lesson_date',
        'SELECT "start_time字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'lesson_plans' AND COLUMN_NAME = 'end_time') = 0,
        'ALTER TABLE lesson_plans ADD COLUMN end_time TIME AFTER start_time',
        'SELECT "end_time字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'lesson_plans' AND COLUMN_NAME = 'notes') = 0,
        'ALTER TABLE lesson_plans ADD COLUMN notes TEXT AFTER end_time',
        'SELECT "notes字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. 移除不需要的file_path和version字段（如果存在且不需要）
-- 注意：这里我们保留这些字段，以防其他地方需要

-- 5. 添加外键约束
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'lesson_plans' 
         AND COLUMN_NAME = 'subject_id' AND REFERENCED_TABLE_NAME = 'subjects') = 0,
        'ALTER TABLE lesson_plans ADD CONSTRAINT fk_lesson_plans_subject 
         FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL',
        'SELECT "subject_id外键约束已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
         WHERE TABLE_SCHEMA = 'erph' AND TABLE_NAME = 'lesson_plans' 
         AND COLUMN_NAME = 'class_id' AND REFERENCED_TABLE_NAME = 'classes') = 0,
        'ALTER TABLE lesson_plans ADD CONSTRAINT fk_lesson_plans_class 
         FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL',
        'SELECT "class_id外键约束已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. 显示修复后的表结构
SELECT '=== attendance表结构 ===' as message;
DESCRIBE attendance;

SELECT '=== lesson_plans表结构 ===' as message;
DESCRIBE lesson_plans;

-- 7. 显示修复结果
SELECT '教案和教课报告关联修复完成！' as message;
SELECT COUNT(*) as total_attendance_records FROM attendance;
SELECT COUNT(*) as total_lesson_plans FROM lesson_plans;
SELECT COUNT(*) as linked_records FROM attendance WHERE lesson_plan_id IS NOT NULL;
