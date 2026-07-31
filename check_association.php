<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== 检查教案和教课报告关联关系 ===\n\n";

try {
    $dsn = "mysql:host=localhost;dbname=erph;charset=utf8mb4";
    $pdo = new PDO($dsn, 'erph_user', '123456', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✓ 数据库连接成功\n\n";
    
    // 1. 检查attendance表结构
    echo "1. attendance表结构:\n";
    $columns = $pdo->query("SHOW COLUMNS FROM attendance")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "  {$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}\n";
    }
    
    // 2. 检查lesson_plans表结构
    echo "\n2. lesson_plans表结构:\n";
    $columns = $pdo->query("SHOW COLUMNS FROM lesson_plans")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "  {$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}\n";
    }
    
    // 3. 检查数据关联
    echo "\n3. 数据关联检查:\n";
    
    // 检查有教案ID的教课报告
    $linked_reports = $pdo->query("
        SELECT COUNT(*) as count 
        FROM attendance 
        WHERE lesson_plan_id IS NOT NULL
    ")->fetch()['count'];
    echo "  有教案ID的教课报告: $linked_reports\n";
    
    // 检查没有教案ID的教课报告
    $unlinked_reports = $pdo->query("
        SELECT COUNT(*) as count 
        FROM attendance 
        WHERE lesson_plan_id IS NULL
    ")->fetch()['count'];
    echo "  没有教案ID的教课报告: $unlinked_reports\n";
    
    // 4. 检查具体数据
    echo "\n4. 具体数据检查:\n";
    
    // 检查最近的教课报告
    echo "  最近的教课报告:\n";
    $reports = $pdo->query("
        SELECT id, user_id, course_id, lesson_plan_id, date, status
        FROM attendance 
        ORDER BY date DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($reports as $report) {
        echo "    ID: {$report['id']}, 用户: {$report['user_id']}, 课程: {$report['course_id']}, 教案: {$report['lesson_plan_id']}, 日期: {$report['date']}, 状态: {$report['status']}\n";
    }
    
    // 检查最近的教案
    echo "\n  最近的教案:\n";
    $plans = $pdo->query("
        SELECT id, title, course_id, lesson_date, created_by
        FROM lesson_plans 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($plans as $plan) {
        echo "    ID: {$plan['id']}, 标题: {$plan['title']}, 课程: {$plan['course_id']}, 日期: {$plan['lesson_date']}, 创建者: {$plan['created_by']}\n";
    }
    
    // 5. 尝试手动关联
    echo "\n5. 尝试手动关联:\n";
    
    // 查找可以关联的记录
    $potential_links = $pdo->query("
        SELECT 
            a.id as attendance_id,
            a.user_id,
            a.course_id,
            a.date,
            lp.id as lesson_plan_id,
            lp.title,
            lp.lesson_date
        FROM attendance a
        LEFT JOIN lesson_plans lp ON a.user_id = lp.created_by 
            AND a.course_id = lp.course_id 
            AND a.date = lp.lesson_date
        WHERE a.lesson_plan_id IS NULL 
            AND lp.id IS NOT NULL
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($potential_links) > 0) {
        echo "  可以关联的记录:\n";
        foreach ($potential_links as $link) {
            echo "    教课报告ID: {$link['attendance_id']} 可以关联到教案: {$link['lesson_plan_id']} ({$link['title']})\n";
        }
    } else {
        echo "  没有找到可以关联的记录\n";
    }
    
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo "错误位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n检查完成\n";
?>
