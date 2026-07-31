<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== 修复现有教案和教课报告关联关系 ===\n\n";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=erph", "erph_user", "123456");
    echo "✓ 数据库连接成功\n\n";
    
    // 1. 检查当前状态
    echo "1. 检查当前状态...\n";
    $total_attendance = $pdo->query("SELECT COUNT(*) as count FROM attendance")->fetch()['count'];
    $total_lesson_plans = $pdo->query("SELECT COUNT(*) as count FROM lesson_plans")->fetch()['count'];
    $linked_records = $pdo->query("SELECT COUNT(*) as count FROM attendance WHERE lesson_plan_id IS NOT NULL")->fetch()['count'];
    
    echo "  总教课报告数: $total_attendance\n";
    echo "  总教案数: $total_lesson_plans\n";
    echo "  已关联记录数: $linked_records\n";
    echo "  未关联记录数: " . ($total_attendance - $linked_records) . "\n\n";
    
    // 2. 查找可以关联的记录
    echo "2. 查找可以关联的记录...\n";
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
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  找到 " . count($potential_links) . " 条可以关联的记录\n";
    
    if (count($potential_links) > 0) {
        echo "  可以关联的记录详情:\n";
        foreach ($potential_links as $link) {
            echo "    教课报告ID: {$link['attendance_id']} -> 教案: {$link['lesson_plan_id']} ({$link['title']})\n";
        }
        
        // 3. 执行关联
        echo "\n3. 执行关联...\n";
        $updated_count = 0;
        
        foreach ($potential_links as $link) {
            try {
                $stmt = $pdo->prepare("UPDATE attendance SET lesson_plan_id = ? WHERE id = ?");
                $stmt->execute([$link['lesson_plan_id'], $link['attendance_id']]);
                
                if ($stmt->rowCount() > 0) {
                    $updated_count++;
                    echo "  ✓ 关联成功: 教课报告 {$link['attendance_id']} -> 教案 {$link['lesson_plan_id']}\n";
                }
            } catch (Exception $e) {
                echo "  ❌ 关联失败: 教课报告 {$link['attendance_id']} -> 教案 {$link['lesson_plan_id']}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n  成功关联 $updated_count 条记录\n";
    } else {
        echo "  没有找到可以关联的记录\n";
    }
    
    // 4. 检查关联后的状态
    echo "\n4. 检查关联后的状态...\n";
    $linked_records_after = $pdo->query("SELECT COUNT(*) as count FROM attendance WHERE lesson_plan_id IS NOT NULL")->fetch()['count'];
    echo "  关联后记录数: $linked_records_after\n";
    echo "  新增关联数: " . ($linked_records_after - $linked_records) . "\n";
    
    // 5. 显示关联后的示例数据
    echo "\n5. 关联后的示例数据...\n";
    $sample_data = $pdo->query("
        SELECT 
            a.id as attendance_id,
            a.date,
            a.status,
            lp.title as lesson_plan_title,
            c.title as course_title
        FROM attendance a
        LEFT JOIN lesson_plans lp ON a.lesson_plan_id = lp.id
        LEFT JOIN courses c ON a.course_id = c.id
        WHERE a.lesson_plan_id IS NOT NULL
        ORDER BY a.date DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($sample_data as $data) {
        echo "  教课报告ID: {$data['attendance_id']}, 日期: {$data['date']}, 教案: {$data['lesson_plan_title']}, 课程: {$data['course_title']}\n";
    }
    
    echo "\n✅ 关联修复完成！\n";
    
} catch (Exception $e) {
    echo "❌ 修复失败: " . $e->getMessage() . "\n";
    echo "错误位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n修复完成\n";
?>
