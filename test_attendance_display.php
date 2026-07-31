<?php
// test_attendance_display.php - 测试签到和签退时间显示
require_once 'config.php';

try {
    // 获取数据库连接
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    echo "数据库连接成功！\n\n";
    
    // 检查attendance表结构
    echo "=== 检查attendance表结构 ===\n";
    $stmt = $pdo->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        echo "字段: {$column['Field']} - 类型: {$column['Type']} - 允许NULL: {$column['Null']}\n";
    }
    
    echo "\n=== 检查attendance表中的数据 ===\n";
    $stmt = $pdo->query("
        SELECT 
            a.id,
            a.date,
            a.status,
            a.check_in,
            a.check_out,
            a.created_at,
            u.name as user_name,
            c.title as course_title
        FROM attendance a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN courses c ON a.course_id = c.id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    
    $records = $stmt->fetchAll();
    
    if (empty($records)) {
        echo "attendance表中没有数据\n";
    } else {
        foreach ($records as $record) {
            echo "ID: {$record['id']}\n";
            echo "用户: {$record['user_name']}\n";
            echo "日期: {$record['date']}\n";
            echo "状态: {$record['status']}\n";
            echo "签到时间: " . ($record['check_in'] ?: '未设置') . "\n";
            echo "签退时间: " . ($record['check_out'] ?: '未设置') . "\n";
            echo "课程: " . ($record['course_title'] ?: '未分配') . "\n";
            echo "创建时间: {$record['created_at']}\n";
            echo "---\n";
        }
    }
    
    // 检查是否有签到和签退时间的数据
    echo "\n=== 签到签退时间统计 ===\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_records,
            COUNT(CASE WHEN check_in IS NOT NULL THEN 1 END) as with_checkin,
            COUNT(CASE WHEN check_out IS NOT NULL THEN 1 END) as with_checkout,
            COUNT(CASE WHEN check_in IS NOT NULL AND check_out IS NOT NULL THEN 1 END) as with_both
        FROM attendance
    ");
    
    $stats = $stmt->fetch();
    echo "总记录数: {$stats['total_records']}\n";
    echo "有签到时间的记录: {$stats['with_checkin']}\n";
    echo "有签退时间的记录: {$stats['with_checkout']}\n";
    echo "同时有签到和签退时间的记录: {$stats['with_both']}\n";
    
    // 如果没有签到签退时间数据，建议添加一些测试数据
    if ($stats['with_checkin'] == 0) {
        echo "\n=== 建议添加测试数据 ===\n";
        echo "attendance表中没有签到时间数据，建议添加一些测试数据来验证显示效果。\n";
        echo "可以通过以下方式添加：\n";
        echo "1. 在教课报告页面手动添加记录\n";
        echo "2. 或者运行SQL语句添加测试数据\n";
    }
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "系统错误: " . $e->getMessage() . "\n";
}
?>
