<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== 逐步检查数据库 ===\n";

try {
    echo "1. 连接数据库...\n";
    $dsn = "mysql:host=localhost;dbname=erph;charset=utf8mb4";
    $pdo = new PDO($dsn, 'erph_user', '123456', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✓ 连接成功\n";
    
    echo "2. 检查attendance表...\n";
    $result = $pdo->query("SELECT COUNT(*) as count FROM attendance")->fetch();
    echo "✓ attendance表记录数: " . $result['count'] . "\n";
    
    echo "3. 检查lesson_plans表...\n";
    $result = $pdo->query("SELECT COUNT(*) as count FROM lesson_plans")->fetch();
    echo "✓ lesson_plans表记录数: " . $result['count'] . "\n";
    
    echo "4. 检查关联字段...\n";
    $result = $pdo->query("SELECT COUNT(*) as count FROM attendance WHERE lesson_plan_id IS NOT NULL")->fetch();
    echo "✓ 已关联记录数: " . $result['count'] . "\n";
    
    echo "5. 检查未关联记录...\n";
    $result = $pdo->query("SELECT COUNT(*) as count FROM attendance WHERE lesson_plan_id IS NULL")->fetch();
    echo "✓ 未关联记录数: " . $result['count'] . "\n";
    
    echo "6. 检查最近数据...\n";
    $reports = $pdo->query("SELECT id, user_id, course_id, lesson_plan_id, date FROM attendance ORDER BY date DESC LIMIT 3")->fetchAll();
    foreach ($reports as $report) {
        echo "  报告ID: {$report['id']}, 用户: {$report['user_id']}, 课程: {$report['course_id']}, 教案: {$report['lesson_plan_id']}, 日期: {$report['date']}\n";
    }
    
    echo "7. 检查教案数据...\n";
    $plans = $pdo->query("SELECT id, title, course_id, lesson_date, created_by FROM lesson_plans ORDER BY created_at DESC LIMIT 3")->fetchAll();
    foreach ($plans as $plan) {
        echo "  教案ID: {$plan['id']}, 标题: {$plan['title']}, 课程: {$plan['course_id']}, 日期: {$plan['lesson_date']}, 创建者: {$plan['created_by']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
}

echo "\n检查完成\n";
?>
