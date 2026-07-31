-- 添加老师用户到ERPH系统
-- 在MySQL中执行此脚本

USE erph;

-- 添加老师用户 (密码: teacher123)
INSERT INTO users (name, email, password, role) VALUES 
('张老师', 'teacher@erph.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 为老师分配课程
INSERT INTO courses (title, description, teacher_id) VALUES 
('高等数学', '大学高等数学基础课程', (SELECT id FROM users WHERE email = 'teacher@erph.com' AND role = 'teacher')),
('线性代数', '线性代数基础理论', (SELECT id FROM users WHERE email = 'teacher@erph.com' AND role = 'teacher')),
('概率统计', '概率论与数理统计', (SELECT id FROM users WHERE email = 'teacher@erph.com' AND role = 'teacher'))
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- 显示添加结果
SELECT '老师用户添加完成！' as message;
SELECT COUNT(*) as teacher_count FROM users WHERE role = 'teacher';
SELECT COUNT(*) as course_count FROM courses WHERE teacher_id IS NOT NULL;
