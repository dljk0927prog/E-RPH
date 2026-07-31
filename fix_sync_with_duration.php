<?php
// fix_sync_with_duration.php - 考虑课程持续时间的同步脚本
echo "开始修复同步问题（考虑课程持续时间）...\n\n";

// 1. 检查配置文件
echo "=== 步骤1: 检查配置文件 ===\n";
if (file_exists('config.php')) {
    echo "✓ config.php 文件存在\n";
    $config = require 'config.php';
    if (isset($config['db'])) {
        echo "✓ 数据库配置存在\n";
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

// 3. 检查教课报告数据
echo "\n=== 步骤3: 检查教课报告数据 ===\n";
try {
    $stmt = $pdo->query("
        SELECT 
            id, date, check_in, check_out, status, user_id, created_at
        FROM attendance 
        ORDER BY created_at DESC
    ");
    $records = $stmt->fetchAll();
    
    echo "总记录数: " . count($records) . "\n";
    foreach ($records as $record) {
        echo "ID: {$record['id']}, 日期: {$record['date']}, 签到: {$record['check_in']}, 签退: {$record['check_out']}, 状态: {$record['status']}\n";
        
        // 计算课程持续时间
        if ($record['check_in'] && $record['check_out']) {
            $check_in = strtotime($record['check_in']);
            $check_out = strtotime($record['check_out']);
            $duration_hours = round(($check_out - $check_in) / 3600, 2);
            echo "  课程持续时间: {$duration_hours} 小时\n";
        }
    }
} catch (PDOException $e) {
    echo "✗ 检查attendance表失败: " . $e->getMessage() . "\n";
}

// 4. 修复同步逻辑 - 考虑课程持续时间
echo "\n=== 步骤4: 修复同步逻辑（考虑持续时间） ===\n";
try {
    // 获取最新的教课报告日期
    $stmt = $pdo->query("SELECT MAX(date) as latest_date FROM attendance");
    $latest_date = $stmt->fetch()['latest_date'];
    echo "最新教课报告日期: {$latest_date}\n";
    
    // 获取该日期的教课报告数据
    $stmt = $pdo->prepare("
        SELECT 
            id, check_in, check_out, status
        FROM attendance 
        WHERE date = ? AND check_in IS NOT NULL AND check_out IS NOT NULL
        ORDER BY check_in
    ");
    $stmt->execute([$latest_date]);
    $attendance_records = $stmt->fetchAll();
    
    echo "找到 " . count($attendance_records) . " 条教课记录:\n";
    
    // 创建小时活动映射
    $hour_activity = array_fill(0, 24, 0);
    
    foreach ($attendance_records as $record) {
        $check_in_hour = (int)date('H', strtotime($record['check_in']));
        $check_out_hour = (int)date('H', strtotime($record['check_out']));
        
        echo "  签到: {$check_in_hour}:00, 签退: {$check_out_hour}:00\n";
        
        // 将签到到签退之间的所有小时都标记为有活动
        for ($hour = $check_in_hour; $hour <= $check_out_hour; $hour++) {
            if ($hour >= 0 && $hour < 24) {
                $hour_activity[$hour]++;
                echo "    {$hour}:00 活动 +1\n";
            }
        }
    }
    
    if (array_sum($hour_activity) > 0) {
        echo "\n开始执行同步...\n";
        
        // 清空该日期的数据
        $stmt = $pdo->prepare("DELETE FROM access_statistics WHERE date = ?");
        $stmt->execute([$latest_date]);
        echo "✓ 清空 {$latest_date} 数据完成\n";
        
        // 插入所有24小时的数据
        for ($hour = 0; $hour < 24; $hour++) {
            $activity_count = $hour_activity[$hour];
            
            $stmt = $pdo->prepare("
                INSERT INTO access_statistics (date, hour, total_visits, unique_users, new_users)
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->execute([$latest_date, $hour, $activity_count, min($activity_count, 5)]);
            echo "✓ 插入 {$hour}:00 数据，活动量: {$activity_count}\n";
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
        $stmt->execute([$latest_date]);
        $result = $stmt->fetchAll();
        
        foreach ($result as $row) {
            $hour = str_pad($row['hour'], 2, '0', STR_PAD_LEFT);
            echo "{$hour}:00 - 访问: {$row['total_visits']}\n";
        }
        
        // 特别显示8-11点的数据
        echo "\n8-11点数据:\n";
        for ($hour = 8; $hour <= 11; $hour++) {
            $stmt = $pdo->prepare("
                SELECT total_visits 
                FROM access_statistics 
                WHERE date = ? AND hour = ?
            ");
            $stmt->execute([$latest_date, $hour]);
            $data = $stmt->fetch();
            $visits = $data ? $data['total_visits'] : 0;
            echo "  {$hour}:00 - 访问: {$visits}\n";
        }
        
    } else {
        echo "✗ 没有找到有效的教课数据\n";
    }
    
} catch (PDOException $e) {
    echo "✗ 同步修复失败: " . $e->getMessage() . "\n";
}

echo "\n修复完成！\n";
?>
