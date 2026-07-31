-- ERPH系统数据库设置脚本
-- 请以root用户身份运行此脚本

-- 创建数据库
CREATE DATABASE IF NOT EXISTS erph CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 创建用户并授权
CREATE USER IF NOT EXISTS 'erph_user'@'localhost' IDENTIFIED BY '123456';
GRANT ALL PRIVILEGES ON erph.* TO 'erph_user'@'localhost';
FLUSH PRIVILEGES;

-- 使用数据库
USE erph;

-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- 课程表
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    teacher_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 出勤表
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'leave') NOT NULL,
    check_in TIME NULL,
    check_out TIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
);

-- 教案表
CREATE TABLE IF NOT EXISTS lesson_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    title VARCHAR(200) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    version VARCHAR(20) DEFAULT '1.0',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- 插入默认管理员用户 (密码: admin123)
INSERT INTO users (name, email, password, role) VALUES 
('管理员', 'admin@erph.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 插入示例课程
INSERT INTO courses (title, description) VALUES 
('数学基础', '基础数学课程'),
('语文阅读', '语文阅读理解课程'),
('英语口语', '英语口语练习课程')
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- 创建索引
CREATE INDEX idx_attendance_user_date ON attendance(user_id, date);
CREATE INDEX idx_attendance_course_date ON attendance(course_id, date);
CREATE INDEX idx_lesson_plans_course ON lesson_plans(course_id);
CREATE INDEX idx_users_email ON users(email);

-- 显示创建结果
SELECT '数据库设置完成！' as message;
SELECT COUNT(*) as user_count FROM users;
SELECT COUNT(*) as course_count FROM courses;
