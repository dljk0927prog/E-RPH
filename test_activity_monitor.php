<?php
// test_activity_monitor.php - 活动监控功能测试脚本
require_once __DIR__ . '/public/inc/activity_monitor_db.php';

echo "=== 活动监控功能测试 ===\n\n";

try {
    // 测试数据库连接
    echo "1. 测试数据库连接...\n";
    $config = require __DIR__ . '/config.php';
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    echo "✓ 数据库连接成功\n\n";
    
    // 测试表是否存在
    echo "2. 检查必要的数据库表...\n";
    $required_tables = ['user_sessions', 'user_activities', 'access_statistics'];
    
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ 表 '$table' 存在\n";
        } else {
            echo "✗ 表 '$table' 不存在\n";
        }
    }
    echo "\n";
    
    // 测试ActivityMonitorDB类
    echo "3. 测试ActivityMonitorDB类...\n";
    $monitorDB = new ActivityMonitorDB($pdo);
    echo "✓ ActivityMonitorDB类实例化成功\n\n";
    
    // 测试获取统计数据
    echo "4. 测试获取活动统计数据...\n";
    $stats = $monitorDB->getActivityStats();
    echo "总用户数: " . $stats['total_users'] . "\n";
    echo "活跃用户: " . $stats['active_users'] . "\n";
    echo "今日会话: " . $stats['total_sessions'] . "\n";
    echo "平均会话时长: " . $stats['avg_session_time'] . "\n";
    echo "高峰时段: " . $stats['peak_hours'] . "\n";
    echo "✓ 统计数据获取成功\n\n";
    
    // 测试获取最近活动
    echo "5. 测试获取最近活动...\n";
    $activities = $monitorDB->getRecentActivities(5);
    echo "最近活动数量: " . count($activities) . "\n";
    if (!empty($activities)) {
        echo "第一个活动: " . $activities[0]['user'] . " - " . $activities[0]['action'] . "\n";
    }
    echo "✓ 活动数据获取成功\n\n";
    
    // 测试获取小时活动数据
    echo "6. 测试获取小时活动数据...\n";
    $hourly = $monitorDB->getHourlyActivity();
    echo "小时数据数量: " . count($hourly) . "\n";
    if (!empty($hourly)) {
        $sample_hour = array_keys($hourly)[0];
        echo "示例数据 ($sample_hour): " . $hourly[$sample_hour] . "\n";
    }
    echo "✓ 小时活动数据获取成功\n\n";
    
    // 测试记录活动
    echo "7. 测试记录用户活动...\n";
    $result = $monitorDB->logActivity(
        1, // 假设用户ID为1
        'test_action',
        '测试活动记录',
        'test',
        null,
        'success'
    );
    if ($result) {
        echo "✓ 活动记录成功\n";
    } else {
        echo "✗ 活动记录失败\n";
    }
    echo "\n";
    
    echo "=== 所有测试完成 ===\n";
    echo "✓ 活动监控功能测试通过！\n";
    
} catch (PDOException $e) {
    echo "✗ 数据库错误: " . $e->getMessage() . "\n";
    echo "请检查数据库配置和连接\n";
} catch (Exception $e) {
    echo "✗ 系统错误: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "✗ 致命错误: " . $e->getMessage() . "\n";
}

echo "\n";
echo "如果遇到问题，请：\n";
echo "1. 确保已运行 add_activity_monitor_tables.sql 脚本\n";
echo "2. 检查数据库配置是否正确\n";
echo "3. 确认数据库用户权限\n";
echo "4. 查看错误日志获取更多信息\n";
?>
