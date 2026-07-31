-- 添加活动监控所需的数据库表
-- 请以erph_user身份运行此脚本

USE erph;

-- 用户会话表
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,
    session_duration INT DEFAULT 0 COMMENT '会话时长（秒）',
    status ENUM('active', 'expired', 'logged_out') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_sessions_user (user_id),
    INDEX idx_user_sessions_status (status),
    INDEX idx_user_sessions_login_time (login_time)
);

-- 用户活动日志表
CREATE TABLE IF NOT EXISTS user_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL COMMENT '活动类型',
    description TEXT COMMENT '活动描述',
    target_type VARCHAR(50) COMMENT '目标类型（如：course, lesson_plan等）',
    target_id INT COMMENT '目标ID',
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('success', 'pending', 'failed') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_activities_user (user_id),
    INDEX idx_user_activities_action (action),
    INDEX idx_user_activities_created_at (created_at),
    INDEX idx_user_activities_status (status)
);

-- 系统访问统计表
CREATE TABLE IF NOT EXISTS access_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    hour INT NOT NULL COMMENT '小时（0-23）',
    total_visits INT DEFAULT 0 COMMENT '总访问次数',
    unique_users INT DEFAULT 0 COMMENT '独立用户数',
    new_users INT DEFAULT 0 COMMENT '新用户数',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date_hour (date, hour),
    INDEX idx_access_statistics_date (date),
    INDEX idx_access_statistics_hour (hour)
);

-- 插入一些示例数据
INSERT INTO user_activities (user_id, action, description, target_type, target_id, status) VALUES
(1, 'login', '用户登录系统', 'system', NULL, 'success'),
(1, 'view_dashboard', '查看管理员仪表板', 'system', NULL, 'success'),
(1, 'view_users', '查看用户列表', 'user_management', NULL, 'success'),
(1, 'add_user', '添加新用户', 'user_management', NULL, 'success'),
(1, 'view_courses', '查看课程列表', 'course_management', NULL, 'success'),
(1, 'edit_course', '编辑课程信息', 'course_management', 1, 'success'),
(1, 'view_reports', '查看教课报告', 'teaching_reports', NULL, 'success'),
(1, 'view_logs', '查看系统日志', 'system_logs', NULL, 'success'),
(1, 'view_monitor', '查看活动监控', 'activity_monitor', NULL, 'success')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- 插入今日访问统计示例数据
INSERT INTO access_statistics (date, hour, total_visits, unique_users, new_users) VALUES
(CURDATE(), 8, 5, 3, 0),
(CURDATE(), 9, 12, 8, 1),
(CURDATE(), 10, 18, 12, 2),
(CURDATE(), 11, 15, 10, 0),
(CURDATE(), 12, 8, 6, 0),
(CURDATE(), 13, 6, 4, 0),
(CURDATE(), 14, 20, 15, 1),
(CURDATE(), 15, 22, 18, 0),
(CURDATE(), 16, 16, 12, 0),
(CURDATE(), 17, 10, 8, 0),
(CURDATE(), 18, 7, 5, 0),
(CURDATE(), 19, 4, 3, 0)
ON DUPLICATE KEY UPDATE 
    total_visits = VALUES(total_visits),
    unique_users = VALUES(unique_users),
    new_users = VALUES(new_users),
    updated_at = CURRENT_TIMESTAMP;

-- 显示创建结果
SELECT '活动监控表创建完成！' as message;
SELECT COUNT(*) as user_activities_count FROM user_activities;
SELECT COUNT(*) as access_statistics_count FROM access_statistics;
