<?php
// debug_sync_issue.php - 调试同步教课数据的问题
require_once 'config.php';

try {
    // 获取数据库连接
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset=utf8";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    echo "数据库连接成功！\n\n";
    
    // 1. 检查attendance表中的签到数据
    echo "=== 步骤1: 检查attendance表数据 ===\n";
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            date,
            check_in,
            check_out,
            status,
            user_id,
            created_at
        FROM attendance 
        WHERE DATE(date) = ?
        ORDER BY check_in
    ");
    $stmt->execute([$today]);
    $attendance_records = $stmt->fetchAll();
    
    if (empty($attendance_records)) {
        echo "今日没有教课报告记录！\n";
    } else {
        echo "今日教课报告记录：\n";
        foreach ($attendance_records as $record) {
            echo "ID: {$record['id']}, 日期: {$record['date']}, 签到: {$record['check_in']}, 签退: {$record['check_out']}, 状态: {$record['status']}\n";
        }
    }
    
    // 2. 检查签到时间的小时分布
    echo "\n=== 步骤2: 检查签到时间小时分布 ===\n";
    $stmt = $pdo->prepare("
        SELECT 
            HOUR(check_in) as hour,
            COUNT(*) as count,
            GROUP_CONCAT(check_in) as times
        FROM attendance 
        WHERE DATE(date) = ? AND check_in IS NOT NULL
        GROUP BY HOUR(check_in)
        ORDER BY hour
    ");
    $stmt->execute([$today]);
    $hour_distribution = $stmt->fetchAll();
    
    if (empty($hour_distribution)) {
        echo "没有找到有效的签到时间数据！\n";
    } else {
        echo "签到时间小时分布：\n";
        foreach ($hour_distribution as $row) {
            echo "{$row['hour']}:00 - {$row['count']} 次签到 (时间: {$row['times']})\n";
        }
    }
    
    // 3. 检查access_statistics表当前状态
    echo "\n=== 步骤3: 检查access_statistics表状态 ===\n";
    $stmt = $pdo->prepare("
        SELECT hour, total_visits, unique_users, updated_at
        FROM access_statistics 
        WHERE date = ?
        ORDER BY hour
    ");
    $stmt->execute([$today]);
    $stats_data = $stmt->fetchAll();
    
    if (empty($stats_data)) {
        echo "access_statistics表中没有今日数据！\n";
    } else {
        echo "今日访问统计：\n";
        foreach ($stats_data as $row) {
            $hour = str_pad($row['hour'], 2, '0', STR_PAD_LEFT);
            echo "{$hour}:00 - 访问: {$row['total_visits']}, 用户: {$row['unique_users']}, 更新: {$row['updated_at']}\n";
        }
    }
    
    // 4. 手动执行同步逻辑
    echo "\n=== 步骤4: 手动执行同步逻辑 ===\n";
    
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
    
    echo "准备同步的数据：\n";
    if (empty($attendance_data)) {
        echo "没有找到需要同步的数据！\n";
    } else {
        foreach ($attendance_data as $row) {
            echo "小时: {$row['hour']}, 数量: {$row['count']}\n";
        }
        
        // 执行同步
        echo "\n开始执行同步...\n";
        foreach ($attendance_data as $row) {
            $hour = $row['hour'];
            $count = $row['count'];
            
            // 检查是否已有该小时的记录
            $stmt = $pdo->prepare("
                SELECT id FROM access_statistics 
                WHERE date = ? AND hour = ?
            ");
            $stmt->execute([$today, $hour]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // 更新现有记录
                $stmt = $pdo->prepare("
                    UPDATE access_statistics 
                    SET total_visits = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$count, $existing['id']]);
                echo "✓ 更新 {$hour}:00 的记录，访问量设为 {$count}\n";
            } else {
                // 创建新记录
                $stmt = $pdo->prepare("
                    INSERT INTO access_statistics (date, hour, total_visits, unique_users, new_users)
                    VALUES (?, ?, ?, ?, 0)
                ");
                $stmt->execute([$today, $hour, $count, min($count, 5), 0]);
                echo "✓ 创建 {$hour}:00 的记录，访问量设为 {$count}\n";
            }
        }
        
        echo "\n同步完成！现在检查结果：\n";
        
        // 重新检查结果
        $stmt = $pdo->prepare("
            SELECT hour, total_visits, unique_users, updated_at
            FROM access_statistics 
            WHERE date = ?
            ORDER BY hour
        ");
        $stmt->execute([$today]);
        $updated_stats = $stmt->fetchAll();
        
        foreach ($updated_stats as $row) {
            $hour = str_pad($row['hour'], 2, '0', STR_PAD_LEFT);
            echo "{$hour}:00 - 访问: {$row['total_visits']}, 用户: {$row['unique_users']}, 更新: {$row['updated_at']}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "系统错误: " . $e->getMessage() . "\n";
}
?>
