<?php
// 修复数据库结构问题
require_once 'db.php';

echo "开始修复数据库结构...\n";

try {
    // 1. 检查并添加lesson_plan_id字段到attendance表
    $check_column = $pdo->query("SHOW COLUMNS FROM attendance LIKE 'lesson_plan_id'");
    if ($check_column->rowCount() == 0) {
        echo "添加 lesson_plan_id 字段到 attendance 表...\n";
        $pdo->exec("ALTER TABLE attendance ADD COLUMN lesson_plan_id INT AFTER course_id");
        echo "✓ lesson_plan_id 字段添加成功\n";
    } else {
        echo "✓ lesson_plan_id 字段已存在\n";
    }

    // 2. 检查并添加缺失的字段到lesson_plans表
    $required_columns = [
        'subject_id' => 'INT AFTER course_id',
        'class_id' => 'INT AFTER subject_id',
        'lesson_date' => 'DATE AFTER class_id',
        'start_time' => 'TIME AFTER lesson_date',
        'end_time' => 'TIME AFTER start_time',
        'notes' => 'TEXT AFTER end_time'
    ];

    foreach ($required_columns as $column => $definition) {
        $check_column = $pdo->query("SHOW COLUMNS FROM lesson_plans LIKE '$column'");
        if ($check_column->rowCount() == 0) {
            echo "添加 $column 字段到 lesson_plans 表...\n";
            $pdo->exec("ALTER TABLE lesson_plans ADD COLUMN $column $definition");
            echo "✓ $column 字段添加成功\n";
        } else {
            echo "✓ $column 字段已存在\n";
        }
    }

    // 3. 显示修复后的表结构
    echo "\n=== attendance表结构 ===\n";
    $attendance_structure = $pdo->query("DESCRIBE attendance")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($attendance_structure as $column) {
        echo sprintf("%-20s %-20s %-10s %-10s %-10s %-10s\n", 
            $column['Field'], $column['Type'], $column['Null'], 
            $column['Key'], $column['Default'], $column['Extra']);
    }

    echo "\n=== lesson_plans表结构 ===\n";
    $lesson_plans_structure = $pdo->query("DESCRIBE lesson_plans")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($lesson_plans_structure as $column) {
        echo sprintf("%-20s %-20s %-10s %-10s %-10s %-10s\n", 
            $column['Field'], $column['Type'], $column['Null'], 
            $column['Key'], $column['Default'], $column['Extra']);
    }

    // 4. 显示修复结果
    echo "\n=== 修复结果 ===\n";
    $total_attendance = $pdo->query("SELECT COUNT(*) as count FROM attendance")->fetch()['count'];
    $total_lesson_plans = $pdo->query("SELECT COUNT(*) as count FROM lesson_plans")->fetch()['count'];
    $linked_records = $pdo->query("SELECT COUNT(*) as count FROM attendance WHERE lesson_plan_id IS NOT NULL")->fetch()['count'];

    echo "总教课报告数: $total_attendance\n";
    echo "总教案数: $total_lesson_plans\n";
    echo "已关联记录数: $linked_records\n";

    echo "\n✅ 数据库结构修复完成！\n";

} catch (Exception $e) {
    echo "❌ 修复失败: " . $e->getMessage() . "\n";
}
?>
