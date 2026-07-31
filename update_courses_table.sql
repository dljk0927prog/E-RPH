-- 更新courses表结构
USE erph;

-- 添加is_active字段
ALTER TABLE courses ADD COLUMN is_active BOOLEAN DEFAULT TRUE;

-- 添加更多课程相关字段
ALTER TABLE courses ADD COLUMN course_code VARCHAR(50) UNIQUE;
ALTER TABLE courses ADD COLUMN credits INT DEFAULT 0;
ALTER TABLE courses ADD COLUMN max_students INT DEFAULT 30;
ALTER TABLE courses ADD COLUMN semester VARCHAR(20);
ALTER TABLE courses ADD COLUMN academic_year VARCHAR(10);

-- 更新现有课程数据
UPDATE courses SET 
    is_active = TRUE,
    course_code = CONCAT('COURSE', id),
    credits = 3,
    max_students = 30,
    semester = '2025春季',
    academic_year = '2024-2025'
WHERE is_active IS NULL;

-- 显示更新后的表结构
DESCRIBE courses;

-- 显示课程数据
SELECT * FROM courses;
