<?php
// fix_sync_final.php - 最终修复同步问题
echo "开始修复同步问题...\n\n";

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

// 3. 检查所有教课报告数据
echo "\n=== 步骤3: 检查所有教课报告数据 ===\n";
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
        
        // 检查所有日期的数据
        $stmt = $pdo->query("SELECT DISTINCT date FROM access_statistics ORDER BY date DESC LIMIT 5");
        $dates = $stmt->fetchAll();
        
        echo "有数据的日期:\n";
        foreach ($dates as $date_row) {
            $date = $date_row['date'];
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM access_statistics WHERE date = ?");
            $stmt->execute([$date]);
            $count = $stmt->fetch()['total'];
            echo "  {$date}: {$count} 条记录\n";
        }
    } else {
        echo "✗ access_statistics表不存在\n";
    }
} catch (PDOException $e) {
    echo "✗ 检查access_statistics表失败: " . $e->getMessage() . "\n";
}

// 5. 修复同步逻辑 - 使用正确的日期
echo "\n=== 步骤5: 修复同步逻辑 ===\n";
try {
    // 获取最新的教课报告日期
    $stmt = $pdo->query("SELECT MAX(date) as latest_date FROM attendance");
    $latest_date = $stmt->fetch()['latest_date'];
    echo "最新教课报告日期: {$latest_date}\n";
    
    // 获取该日期的教课报告数据
    $stmt = $pdo->prepare("
        SELECT 
            HOUR(check_in) as hour,
            COUNT(*) as count
        FROM attendance 
        WHERE date = ? AND check_in IS NOT NULL
        GROUP BY HOUR(check_in)
        ORDER BY hour
    ");
    $stmt->execute([$latest_date]);
    $attendance_data = $stmt->fetchAll();
    
    echo "找到 " . count($attendance_data) . " 个小时的教课数据:\n";
    foreach ($attendance_data as $row) {
        echo "  {$row['hour']}:00 - {$row['count']} 次签到\n";
    }
    
    if (!empty($attendance_data)) {
        echo "\n开始执行同步...\n";
        
        // 清空该日期的数据
        $stmt = $pdo->prepare("DELETE FROM access_statistics WHERE date = ?");
        $stmt->execute([$latest_date]);
        echo "✓ 清空 {$latest_date} 数据完成\n";
        
        // 插入教课数据
        foreach ($attendance_data as $row) {
            $hour = $row['hour'];
            $count = $row['count'];
            
            $stmt = $pdo->prepare("
                INSERT INTO access_statistics (date, hour, total_visits, unique_users, new_users)
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->execute([$latest_date, $hour, $count, min($count, 5)]);
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
                $stmt->execute([$latest_date, $i]);
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
        echo "✗ 没有找到教课数据\n";
    }
    
} catch (PDOException $e) {
    echo "✗ 同步修复失败: " . $e->getMessage() . "\n";
}

echo "\n修复完成！\n";
?>
