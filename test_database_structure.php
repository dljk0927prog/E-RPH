<?php
// 测试数据库表结构
require_once 'db.php';

echo "=== 检查数据库表结构 ===\n\n";

try {
    // 检查 attendance 表结构
    echo "1. 检查 attendance 表结构:\n";
    $columns = $pdo->query("DESCRIBE attendance")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "   - {$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']}\n";
    }
    
    echo "\n2. 检查 lesson_plans 表结构:\n";
    $columns = $pdo->query("DESCRIBE lesson_plans")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "   - {$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']}\n";
    }
    
    echo "\n3. 检查数据:\n";
    $attendance_count = $pdo->query("SELECT COUNT(*) as count FROM attendance")->fetch()['count'];
    $lesson_plans_count = $pdo->query("SELECT COUNT(*) as count FROM lesson_plans")->fetch()['count'];
    $linked_count = $pdo->query("SELECT COUNT(*) as count FROM attendance WHERE lesson_plan_id IS NOT NULL")->fetch()['count'];
    
    echo "   - attendance 表记录数: {$attendance_count}\n";
    echo "   - lesson_plans 表记录数: {$lesson_plans_count}\n";
    echo "   - 已关联的记录数: {$linked_count}\n";
    
    if ($attendance_count > 0) {
        echo "\n4. 检查最新的 attendance 记录:\n";
        $latest = $pdo->query("SELECT * FROM attendance ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        foreach ($latest as $key => $value) {
            echo "   - {$key}: {$value}\n";
        }
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
?>
