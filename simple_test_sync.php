<?php
// simple_test_sync.php - 简单测试同步问题
echo "开始测试同步问题...\n\n";

// 1. 检查配置文件
echo "=== 步骤1: 检查配置文件 ===\n";
if (file_exists('config.php')) {
    echo "✓ config.php 文件存在\n";
    $config = require 'config.php';
    if (isset($config['db'])) {
        echo "✓ 数据库配置存在\n";
        echo "主机: " . $config['db']['host'] . "\n";
        echo "数据库: " . $config['db']['dbname'] . "\n";
        echo "用户: " . $config['db']['user'] . "\n";
    } else {
        echo "✗ 数据库配置缺失\n";
        exit;
    }
} else {
    echo "✗ config.php 文件不存在\n";
    exit;
}

// 2. 测试数据库连接
echo "\n=== 步骤2: 测试数据库连接 ===\n";
try {
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset=utf8";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✓ 数据库连接成功\n";
} catch (PDOException $e) {
    echo "✗ 数据库连接失败: " . $e->getMessage() . "\n";
    exit;
}

// 3. 检查attendance表
echo "\n=== 步骤3: 检查attendance表 ===\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM attendance");
    $total = $stmt->fetch()['total'];
    echo "✓ attendance表总记录数: {$total}\n";
    
    if ($total > 0) {
        $stmt = $pdo->query("
            SELECT 
                id, date, check_in, check_out, status, user_id
            FROM attendance 
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        $records = $stmt->fetchAll();
        
        echo "最新3条记录:\n";
        foreach ($records as $record) {
            echo "ID: {$record['id']}, 日期: {$record['date']}, 签到: {$record['check_in']}, 签退: {$record['check_out']}, 状态: {$record['status']}\n";
        }
    }
} catch (PDOException $e) {
    echo "✗ 检查attendance表失败: " . $e->getMessage() . "\n";
}

// 4. 检查access_statistics表
echo "\n=== 步骤4: 检查access_statistics表 ===\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'access_statistics'");
    if ($stmt->rowCount() > 0) {
        echo "✓ access_statistics表存在\n";
        
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM access_statistics WHERE date = ?");
        $stmt->execute([$today]);
        $today_count = $stmt->fetch()['total'];
        echo "今日记录数: {$today_count}\n";
        
        if ($today_count > 0) {
            $stmt = $pdo->prepare("
                SELECT hour, total_visits, unique_users 
                FROM access_statistics 
                WHERE date = ? 
                ORDER BY hour
            ");
            $stmt->execute([$today]);
            $stats = $stmt->fetchAll();
            
            echo "今日统计:\n";
            foreach ($stats as $stat) {
                $hour = str_pad($stat['hour'], 2, '0', STR_PAD_LEFT);
                echo "{$hour}:00 - 访问: {$stat['total_visits']}, 用户: {$stat['unique_users']}\n";
            }
        }
    } else {
        echo "✗ access_statistics表不存在\n";
    }
} catch (PDOException $e) {
    echo "✗ 检查access_statistics表失败: " . $e->getMessage() . "\n";
}

// 5. 测试同步逻辑
echo "\n=== 步骤5: 测试同步逻辑 ===\n";
try {
    $today = date('Y-m-d');
    
    // 获取今日的教课报告数据
    $stmt = $pdo->prepare("
        SELECT 
            HOUR(check_in) as hour,
            COUNT(*) as count
        FROM attendance 
        WHERE DATE(date) = ? AND check_in IS NOT NULL
        GROUP BY HOUR(check_in)
        ORDER BY hour
    ");
    $stmt->execute([$today]);
    $attendance_data = $stmt->fetchAll();
    
    echo "找到 " . count($attendance_data) . " 个小时的教课数据:\n";
    foreach ($attendance_data as $row) {
        echo "{$row['hour']}:00 - {$row['count']} 次签到\n";
    }
    
    if (!empty($attendance_data)) {
        echo "\n开始执行同步...\n";
        
        // 清空今日数据
        $stmt = $pdo->prepare("DELETE FROM access_statistics WHERE date = ?");
        $stmt->execute([$today]);
        echo "✓ 清空今日数据完成\n";
        
        // 插入教课数据
        foreach ($attendance_data as $row) {
            $hour = $row['hour'];
            $count = $row['count'];
            
            $stmt = $pdo->prepare("
                INSERT INTO access_statistics (date, hour, total_visits, unique_users, new_users)
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->execute([$today, $hour, $count, min($count, 5), 0]);
            echo "✓ 插入 {$hour}:00 数据，数量: {$count}\n";
        }
        
        // 填充其他小时为0
        for ($i = 0; $i < 24; $i++) {
            $found = false;
            foreach ($attendance_data as $row) {
                if ($row['hour'] == $i) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $stmt = $pdo->prepare("
                    INSERT INTO access_statistics (date, hour, total_visits, unique_users, new_users)
                    VALUES (?, ?, 0, 0, 0)
                ");
                $stmt->execute([$today, $i]);
            }
        }
        
        echo "✓ 同步完成！\n";
        
        // 验证结果
        echo "\n验证结果:\n";
        $stmt = $pdo->prepare("
            SELECT hour, total_visits 
            FROM access_statistics 
            WHERE date = ? 
            ORDER BY hour
        ");
        $stmt->execute([$today]);
        $result = $stmt->fetchAll();
        
        foreach ($result as $row) {
            $hour = str_pad($row['hour'], 2, '0', STR_PAD_LEFT);
            echo "{$hour}:00 - 访问: {$row['total_visits']}\n";
        }
    } else {
        echo "✗ 没有找到今日的教课数据\n";
    }
    
} catch (PDOException $e) {
    echo "✗ 同步测试失败: " . $e->getMessage() . "\n";
}

echo "\n测试完成！\n";
?>
