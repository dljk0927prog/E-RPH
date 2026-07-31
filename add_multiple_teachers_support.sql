-- 添加多老师支持的数据库迁移脚本
USE erph;

-- 1. 创建课程-老师关系表
CREATE TABLE IF NOT EXISTS course_teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_course_teacher (course_id, teacher_id)
);

-- 2. 迁移现有数据：将courses表中的teacher_id数据迁移到关系表
INSERT IGNORE INTO course_teachers (course_id, teacher_id)
SELECT id, teacher_id 
FROM courses 
WHERE teacher_id IS NOT NULL;

-- 3. 备份原有的teacher_id字段（重命名为old_teacher_id，以防需要回滚）
ALTER TABLE courses CHANGE COLUMN teacher_id old_teacher_id INT;

-- 4. 显示迁移结果
SELECT 'courses表记录数' as description, COUNT(*) as count FROM courses
UNION ALL
SELECT 'course_teachers关系记录数' as description, COUNT(*) as count FROM course_teachers
UNION ALL
SELECT '有分配老师的课程数' as description, COUNT(DISTINCT course_id) as count FROM course_teachers;

-- 5. 验证数据迁移
SELECT 
    c.id,
    c.title,
    c.old_teacher_id,
    GROUP_CONCAT(u.name ORDER BY u.name) as assigned_teachers
FROM courses c
LEFT JOIN course_teachers ct ON c.id = ct.course_id
LEFT JOIN users u ON ct.teacher_id = u.id
GROUP BY c.id, c.title, c.old_teacher_id
ORDER BY c.id;

-- 6. 显示新的表结构
DESCRIBE course_teachers;
