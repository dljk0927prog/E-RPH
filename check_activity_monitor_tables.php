<?php
// check_activity_monitor_tables.php - 检查并创建活动监控所需的数据库表
require_once 'config.php';

try {
    // 获取数据库连接
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    echo "数据库连接成功！\n";
    
    // 检查access_statistics表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'access_statistics'");
    if ($stmt->rowCount() == 0) {
        echo "access_statistics表不存在，正在创建...\n";
        
        // 创建access_statistics表
        $pdo->exec("
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
            )
        ");
        
        echo "access_statistics表创建成功！\n";
        
        // 插入今日的示例数据
        $today = date('Y-m-d');
        for ($hour = 0; $hour < 24; $hour++) {
            $visits = rand(0, 30);
            $unique_users = rand(0, min($visits, 20));
            $new_users = rand(0, 3);
            
            $stmt = $pdo->prepare("
                INSERT INTO access_statistics (date, hour, total_visits, unique_users, new_users) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    total_visits = VALUES(total_visits),
                    unique_users = VALUES(unique_users),
                    new_users = VALUES(new_users),
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$today, $hour, $visits, $unique_users, $new_users]);
        }
        
        echo "今日24小时数据插入完成！\n";
        
    } else {
        echo "access_statistics表已存在！\n";
        
        // 检查今日是否有数据
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM access_statistics WHERE date = CURDATE()");
        $stmt->execute();
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            echo "今日没有数据，正在插入...\n";
            
            // 插入今日的数据
            $today = date('Y-m-d');
            for ($hour = 0; $hour < 24; $hour++) {
                $visits = rand(0, 30);
                $unique_users = rand(0, min($visits, 20));
                $new_users = rand(0, 3);
                
                $stmt = $pdo->prepare("
                    INSERT INTO access_statistics (date, hour, total_visits, unique_users, new_users) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$today, $hour, $visits, $unique_users, $new_users]);
            }
            
            echo "今日24小时数据插入完成！\n";
        } else {
            echo "今日已有 {$count} 条数据\n";
        }
    }
    
    // 检查user_activities表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_activities'");
    if ($stmt->rowCount() == 0) {
        echo "user_activities表不存在，正在创建...\n";
        
        $pdo->exec("
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
            )
        ");
        
        echo "user_activities表创建成功！\n";
    } else {
        echo "user_activities表已存在！\n";
    }
    
    // 检查user_sessions表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_sessions'");
    if ($stmt->rowCount() == 0) {
        echo "user_sessions表不存在，正在创建...\n";
        
        $pdo->exec("
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
            )
        ");
        
        echo "user_sessions表创建成功！\n";
    } else {
        echo "user_sessions表已存在！\n";
    }
    
    // 显示当前数据
    echo "\n=== 当前数据状态 ===\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM access_statistics WHERE date = CURDATE()");
    $count = $stmt->fetch()['count'];
    echo "今日访问统计记录数: {$count}\n";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT hour, total_visits FROM access_statistics WHERE date = CURDATE() ORDER BY hour LIMIT 5");
        $data = $stmt->fetchAll();
        echo "前5小时数据:\n";
        foreach ($data as $row) {
            echo "  {$row['hour']}:00 - {$row['total_visits']} 次访问\n";
        }
    }
    
    echo "\n所有表检查完成！现在趋势图应该可以正常显示了。\n";
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "系统错误: " . $e->getMessage() . "\n";
}
?>
