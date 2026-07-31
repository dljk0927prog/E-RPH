-- 为subjects表添加课程关联字段
USE erph;

-- 1. 添加course_id字段到subjects表
ALTER TABLE subjects ADD COLUMN course_id INT NULL AFTER name;

-- 2. 添加外键约束
ALTER TABLE subjects ADD CONSTRAINT fk_subjects_course 
FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL;

-- 3. 查看更新后的表结构
DESCRIBE subjects;

-- 4. 显示现有数据
SELECT * FROM subjects;
