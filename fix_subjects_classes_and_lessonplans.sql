-- 修复/创建 科目、班级 表，并为 lesson_plans 增加所需列
-- 在MySQL中以管理员身份执行

USE erph;

-- 1. subjects 表
CREATE TABLE IF NOT EXISTS subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. classes 表
CREATE TABLE IF NOT EXISTS classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. lesson_plans 表增加所需列（如不存在）
SET @add_subject = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='erph' AND TABLE_NAME='lesson_plans' AND COLUMN_NAME='subject_id')=0,
    'ALTER TABLE lesson_plans ADD COLUMN subject_id INT NULL AFTER course_id',
    'SELECT "subject_id 已存在" as msg'
  )
);
PREPARE stmt FROM @add_subject; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @add_class = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='erph' AND TABLE_NAME='lesson_plans' AND COLUMN_NAME='class_id')=0,
    'ALTER TABLE lesson_plans ADD COLUMN class_id INT NULL AFTER subject_id',
    'SELECT "class_id 已存在" as msg'
  )
);
PREPARE stmt FROM @add_class; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @add_ldate = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='erph' AND TABLE_NAME='lesson_plans' AND COLUMN_NAME='lesson_date')=0,
    'ALTER TABLE lesson_plans ADD COLUMN lesson_date DATE NULL AFTER class_id',
    'SELECT "lesson_date 已存在" as msg'
  )
);
PREPARE stmt FROM @add_ldate; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @add_stime = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='erph' AND TABLE_NAME='lesson_plans' AND COLUMN_NAME='start_time')=0,
    'ALTER TABLE lesson_plans ADD COLUMN start_time TIME NULL AFTER lesson_date',
    'SELECT "start_time 已存在" as msg'
  )
);
PREPARE stmt FROM @add_stime; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @add_etime = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='erph' AND TABLE_NAME='lesson_plans' AND COLUMN_NAME='end_time')=0,
    'ALTER TABLE lesson_plans ADD COLUMN end_time TIME NULL AFTER start_time',
    'SELECT "end_time 已存在" as msg'
  )
);
PREPARE stmt FROM @add_etime; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @add_notes = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='erph' AND TABLE_NAME='lesson_plans' AND COLUMN_NAME='notes')=0,
    'ALTER TABLE lesson_plans ADD COLUMN notes TEXT NULL AFTER version',
    'SELECT "notes 已存在" as msg'
  )
);
PREPARE stmt FROM @add_notes; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. 外键（若不存在则添加，失败忽略）
ALTER TABLE lesson_plans
  ADD CONSTRAINT fk_lp_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_lp_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL;

-- 5. 示例数据（仅当表为空时）
INSERT INTO subjects (name) SELECT '语文' WHERE NOT EXISTS (SELECT 1 FROM subjects);
INSERT INTO classes (name)  SELECT '一年级一班' WHERE NOT EXISTS (SELECT 1 FROM classes);

-- 6. 结果
SELECT '修复完成' AS message;
DESCRIBE lesson_plans;
SELECT COUNT(*) as subjects_count FROM subjects;
SELECT COUNT(*) as classes_count FROM classes;
