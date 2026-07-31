-- 修复profile.php页面"负责课程显示0"问题的完整SQL脚本
-- 请在phpMyAdmin中执行此脚本

USE erph;

-- 1. 检查并创建course_teachers表（如果不存在）
CREATE TABLE IF NOT EXISTS course_teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_course_teacher (course_id, teacher_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. 检查courses表是否有teacher_id字段
SHOW COLUMNS FROM courses LIKE 'teacher_id';

-- 如果不存在，添加teacher_id字段
ALTER TABLE courses ADD COLUMN IF NOT EXISTS teacher_id INT AFTER description;

-- 3. 检查courses表是否有is_active字段
SHOW COLUMNS FROM courses LIKE 'is_active';

-- 如果不存在，添加is_active字段
ALTER TABLE courses ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE AFTER teacher_id;

-- 4. 检查attendance表是否有course_id字段
SHOW COLUMNS FROM attendance LIKE 'course_id';

-- 如果不存在，添加course_id字段
ALTER TABLE attendance ADD COLUMN IF NOT EXISTS course_id INT AFTER user_id;

-- 5. 检查lesson_plans表是否有created_by字段
SHOW COLUMNS FROM lesson_plans LIKE 'created_by';

-- 如果不存在，添加created_by字段
ALTER TABLE lesson_plans ADD COLUMN IF NOT EXISTS created_by INT AFTER file_path;

-- 6. 为现有课程分配老师（如果courses表中有teacher_id字段但为空）
-- 首先检查是否有老师用户
SELECT COUNT(*) as teacher_count FROM users WHERE role = 'teacher';

-- 如果有老师，为没有teacher_id的课程分配第一个老师
UPDATE courses 
SET teacher_id = (SELECT id FROM users WHERE role = 'teacher' LIMIT 1) 
WHERE teacher_id IS NULL AND (SELECT COUNT(*) FROM users WHERE role = 'teacher') > 0;

-- 7. 将现有课程数据迁移到course_teachers表
-- 如果courses表中有teacher_id数据，将其复制到course_teachers表
INSERT IGNORE INTO course_teachers (course_id, teacher_id)
SELECT id, teacher_id FROM courses 
WHERE teacher_id IS NOT NULL 
AND NOT EXISTS (SELECT 1 FROM course_teachers WHERE course_id = courses.id);

-- 8. 显示修复后的表结构
SELECT '=== 修复后的表结构 ===' as info;
DESCRIBE courses;
DESCRIBE course_teachers;
DESCRIBE attendance;
DESCRIBE lesson_plans;

-- 9. 显示课程分配情况
SELECT '=== 课程分配情况 ===' as info;
SELECT c.id, c.title, c.teacher_id, u.name as teacher_name
FROM courses c
LEFT JOIN users u ON c.teacher_id = u.id
ORDER BY c.id;

SELECT '=== course_teachers表数据 ===' as info;
SELECT ct.course_id, c.title, ct.teacher_id, u.name as teacher_name
FROM course_teachers ct
JOIN courses c ON ct.course_id = c.id
JOIN users u ON ct.teacher_id = u.id
ORDER BY ct.course_id;

-- 10. 测试profile.php查询（替换USER_ID为实际的老师用户ID）
-- 假设第一个老师用户ID为2，请根据实际情况调整
SELECT '=== 测试老师统计查询 ===' as info;
SELECT 
    COUNT(DISTINCT c.id) as total_courses,
    COUNT(DISTINCT a.id) as total_reports,
    COUNT(DISTINCT lp.id) as total_lesson_plans,
    COUNT(DISTINCT CASE WHEN DATE(a.date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN a.id END) as month_reports
FROM users u
LEFT JOIN course_teachers ct ON ct.teacher_id = u.id
LEFT JOIN courses c ON ct.course_id = c.id
LEFT JOIN attendance a ON a.user_id = u.id
LEFT JOIN lesson_plans lp ON lp.created_by = u.id
WHERE u.role = 'teacher'
LIMIT 1;

-- 11. 如果使用旧结构（courses.teacher_id），也测试一下
SELECT '=== 测试旧结构查询 ===' as info;
SELECT 
    COUNT(DISTINCT c.id) as total_courses,
    COUNT(DISTINCT a.id) as total_reports,
    COUNT(DISTINCT lp.id) as total_lesson_plans,
    COUNT(DISTINCT CASE WHEN DATE(a.date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN a.id END) as month_reports
FROM users u
LEFT JOIN courses c ON c.teacher_id = u.id
LEFT JOIN attendance a ON a.user_id = u.id
LEFT JOIN lesson_plans lp ON lp.created_by = u.id
WHERE u.role = 'teacher'
LIMIT 1;
