-- 检查courses表结构，找出"负责课程显示0"的原因
USE erph;

-- 1. 检查courses表的完整结构
DESCRIBE courses;

-- 2. 检查courses表中的数据
SELECT * FROM courses;

-- 3. 检查是否有teacher_id字段
SHOW COLUMNS FROM courses LIKE 'teacher_id';

-- 4. 检查是否有user_id字段
SHOW COLUMNS FROM courses LIKE 'user_id';

-- 5. 检查是否有created_by字段
SHOW COLUMNS FROM courses LIKE 'created_by';

-- 6. 检查course_teachers表是否存在（新的多老师结构）
SHOW TABLES LIKE 'course_teachers';

-- 7. 如果course_teachers表存在，检查其结构
SHOW TABLES LIKE 'course_teachers';
-- 如果存在，则：
-- DESCRIBE course_teachers;
-- SELECT * FROM course_teachers;

-- 8. 检查当前登录用户的ID和角色
-- 这个查询需要在profile.php页面中执行，但我们可以先检查users表
SELECT id, name, email, role FROM users WHERE role = 'teacher';

-- 9. 测试profile.php中使用的查询（替换USER_ID为实际的用户ID）
-- 假设用户ID为1，请根据实际情况调整
SELECT 
    COUNT(DISTINCT c.id) as total_courses,
    COUNT(DISTINCT a.id) as total_reports,
    COUNT(DISTINCT lp.id) as total_lesson_plans,
    COUNT(DISTINCT CASE WHEN DATE(a.date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN a.id END) as month_reports
FROM users u
LEFT JOIN courses c ON c.teacher_id = u.id
LEFT JOIN attendance a ON a.user_id = u.id
LEFT JOIN lesson_plans lp ON lp.created_by = u.id
WHERE u.id = 1;  -- 请替换为实际的用户ID

-- 10. 检查是否有课程分配给老师
-- 如果使用teacher_id字段
SELECT c.id, c.title, c.teacher_id, u.name as teacher_name
FROM courses c
LEFT JOIN users u ON c.teacher_id = u.id
WHERE c.teacher_id IS NOT NULL;

-- 如果使用course_teachers表
-- SELECT ct.course_id, c.title, ct.teacher_id, u.name as teacher_name
-- FROM course_teachers ct
-- JOIN courses c ON ct.course_id = c.id
-- JOIN users u ON ct.teacher_id = u.id;
