<?php
// 测试教课报告的完整流程
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== 测试教课报告完整流程 ===\n\n";

try {
    require_once 'db.php';
    echo "数据库连接成功\n";
    
    // 1. 检查当前数据
    echo "1. 检查当前数据:\n";
    $attendance_count = $pdo->query("SELECT COUNT(*) as count FROM attendance")->fetch()['count'];
    $lesson_plans_count = $pdo->query("SELECT COUNT(*) as count FROM lesson_plans")->fetch()['count'];
    $linked_count = $pdo->query("SELECT COUNT(*) as count FROM attendance WHERE lesson_plan_id IS NOT NULL")->fetch()['count'];
    
    echo "   - attendance 表记录数: {$attendance_count}\n";
    echo "   - lesson_plans 表记录数: {$lesson_plans_count}\n";
    echo "   - 已关联的记录数: {$linked_count}\n";
    
    // 2. 检查 teaching_reports.php 的查询逻辑
    echo "\n2. 检查 teaching_reports.php 查询逻辑:\n";
    
    // 模拟 teaching_reports.php 的查询
    $sql = "SELECT a.*, u.name as teacher_name, c.title as course_title, 
                   c.description as course_description, a.check_in, a.check_out,
                   a.notes, a.created_at,
                   lp.id as lesson_plan_id, lp.title as lesson_plan_title, 
                   lp.description as lesson_plan_description
            FROM attendance a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN courses c ON a.course_id = c.id
            LEFT JOIN lesson_plans lp ON a.lesson_plan_id = lp.id
            WHERE a.user_id = ?
            ORDER BY a.date DESC, a.created_at DESC 
            LIMIT 100";
    
    // 获取一个用户ID进行测试
    $user = $pdo->query("SELECT id FROM users WHERE role = 'teacher' LIMIT 1")->fetch();
    if ($user) {
        $user_id = $user['id'];
        echo "   测试用户ID: {$user_id}\n";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $reports = $stmt->fetchAll();
        
        echo "   查询到的报告数: " . count($reports) . "\n";
        
        if (count($reports) > 0) {
            echo "   第一个报告详情:\n";
            $first_report = $reports[0];
            foreach ($first_report as $key => $value) {
                echo "     - {$key}: {$value}\n";
            }
        }
    } else {
        echo "   没有找到教师用户\n";
    }
    
    // 3. 检查可能的问题
    echo "\n3. 检查可能的问题:\n";
    
    // 检查是否有缺少必要字段的记录
    $missing_fields = $pdo->query("SELECT COUNT(*) as count FROM attendance WHERE date IS NULL OR status IS NULL")->fetch()['count'];
    echo "   - 缺少必要字段的记录数: {$missing_fields}\n";
    
    // 检查是否有无效的 lesson_plan_id
    $invalid_lesson_plans = $pdo->query("SELECT COUNT(*) as count FROM attendance a LEFT JOIN lesson_plans lp ON a.lesson_plan_id = lp.id WHERE a.lesson_plan_id IS NOT NULL AND lp.id IS NULL")->fetch()['count'];
    echo "   - 无效的 lesson_plan_id 记录数: {$invalid_lesson_plans}\n";
    
    // 检查是否有无效的 course_id
    $invalid_courses = $pdo->query("SELECT COUNT(*) as count FROM attendance a LEFT JOIN courses c ON a.course_id = c.id WHERE a.course_id IS NOT NULL AND c.id IS NULL")->fetch()['count'];
    echo "   - 无效的 course_id 记录数: {$invalid_courses}\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "错误位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>
