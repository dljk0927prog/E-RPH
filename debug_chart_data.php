<?php
// debug_chart_data.php - 简化诊断脚本
echo "诊断图表数据问题...\n";

// 1. 检查配置文件
if (!file_exists('config.php')) {
    echo "✗ config.php 文件不存在\n";
    exit;
}
$config = require 'config.php';

// 2. 测试数据库连接
try {
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset=utf8";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
    echo "✓ 数据库连接成功\n";
} catch (PDOException $e) {
    echo "✗ 数据库连接失败\n";
    exit;
}

// 3. 检查getHourlyActivity方法
echo "\n=== 检查getHourlyActivity方法 ===\n";
try {
    require_once 'public/inc/activity_monitor_db.php';
    $monitorDB = new ActivityMonitorDB($pdo);
    $hourly_data = $monitorDB->getHourlyActivity();
    
    echo "8-11点数据:\n";
    for ($hour = 8; $hour <= 11; $hour++) {
        $hour_key = sprintf('%02d:00', $hour);
        $count = $hourly_data[$hour_key] ?? 0;
        echo "  {$hour}:00 - {$count}\n";
    }
} catch (Exception $e) {
    echo "✗ 测试失败\n";
}

// 4. 检查数据库数据
echo "\n=== 检查数据库数据 ===\n";
try {
    $stmt = $pdo->query("SELECT date, COUNT(*) as records FROM access_statistics GROUP BY date ORDER BY date DESC LIMIT 3");
    $stats = $stmt->fetchAll();
    foreach($stats as $stat) {
        echo "  日期: {$stat['date']}, 记录数: {$stat['records']}\n";
    }
} catch (PDOException $e) {
    echo "✗ 查询失败\n";
}

echo "\n诊断完成！\n";
?>
