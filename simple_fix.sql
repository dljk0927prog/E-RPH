-- 简单修复courses表
USE erph;

-- 添加teacher_id字段
ALTER TABLE courses ADD COLUMN teacher_id INT AFTER description;

-- 添加is_active字段（如果还没有的话）
ALTER TABLE courses ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER teacher_id;

-- 添加外键约束
ALTER TABLE courses ADD CONSTRAINT fk_courses_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL;

-- 查看表结构
DESCRIBE courses;
