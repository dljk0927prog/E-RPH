-- 修复profile.php页面数据库查询问题的SQL脚本
-- 请在phpMyAdmin中执行此脚本

-- 1. 检查courses表结构
DESCRIBE courses;

-- 2. 检查attendance表结构
DESCRIBE attendance;

-- 3. 检查lesson_plans表结构
DESCRIBE lesson_plans;

-- 4. 检查users表结构
DESCRIBE users;

-- 5. 如果courses表缺少teacher_id字段，添加它
-- 先检查是否存在
SHOW COLUMNS FROM courses LIKE 'teacher_id';

-- 如果不存在，则添加
ALTER TABLE courses ADD COLUMN IF NOT EXISTS teacher_id INT AFTER id;

-- 6. 如果courses表缺少user_id字段，添加它
SHOW COLUMNS FROM courses LIKE 'user_id';

-- 如果不存在，则添加
ALTER TABLE courses ADD COLUMN IF NOT EXISTS user_id INT AFTER id;

-- 7. 如果courses表缺少created_by字段，添加它
SHOW COLUMNS FROM courses LIKE 'created_by';

-- 如果不存在，则添加
ALTER TABLE courses ADD COLUMN IF NOT EXISTS created_by INT AFTER id;

-- 8. 为现有记录设置默认的teacher_id（如果字段为空）
-- 注意：这里假设第一个用户是老师，请根据实际情况调整
UPDATE courses SET teacher_id = (SELECT id FROM users WHERE role = 'teacher' LIMIT 1) WHERE teacher_id IS NULL;

-- 9. 检查attendance表是否有course_id字段
SHOW COLUMNS FROM attendance LIKE 'course_id';

-- 如果不存在，则添加
ALTER TABLE attendance ADD COLUMN IF NOT EXISTS course_id INT AFTER id;

-- 10. 检查lesson_plans表是否有created_by字段
SHOW COLUMNS FROM lesson_plans LIKE 'created_by';

-- 如果不存在，则添加
ALTER TABLE lesson_plans ADD COLUMN IF NOT EXISTS created_by INT AFTER id;

-- 11. 显示修复后的表结构
SELECT '=== 修复后的表结构 ===' as info;
DESCRIBE courses;
DESCRIBE attendance;
DESCRIBE lesson_plans;

-- 12. 测试查询（可选）
-- 测试老师统计查询
SELECT 
    COUNT(DISTINCT c.id) as total_courses,
    COUNT(DISTINCT a.id) as total_reports,
    COUNT(DISTINCT lp.id) as total_lesson_plans
FROM users u
LEFT JOIN courses c ON c.teacher_id = u.id
LEFT JOIN attendance a ON a.user_id = u.id
LEFT JOIN lesson_plans lp ON lp.created_by = u.id
WHERE u.role = 'teacher'
LIMIT 1;
