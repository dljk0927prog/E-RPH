<?php
// 测试教案创建和教课报告关联
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "开始测试...\n";

try {
    require_once 'db.php';
    echo "数据库连接成功\n";
} catch (Exception $e) {
    echo "数据库连接失败: " . $e->getMessage() . "\n";
    exit;
}

echo "=== 测试教案创建和教课报告关联 ===\n\n";

try {
    // 1. 检查表结构
    echo "1. 检查表结构...\n";
    
    echo "attendance表字段:\n";
    $attendance_columns = $pdo->query("SHOW COLUMNS FROM attendance")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($attendance_columns as $column) {
        echo "  - $column\n";
    }
    
    echo "\nlesson_plans表字段:\n";
    $lesson_plans_columns = $pdo->query("SHOW COLUMNS FROM lesson_plans")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($lesson_plans_columns as $column) {
        echo "  - $column\n";
    }
    
    // 2. 检查现有数据
    echo "\n2. 检查现有数据...\n";
    
    $total_attendance = $pdo->query("SELECT COUNT(*) as count FROM attendance")->fetch()['count'];
    $total_lesson_plans = $pdo->query("SELECT COUNT(*) as count FROM lesson_plans")->fetch()['count'];
    $linked_records = $pdo->query("SELECT COUNT(*) as count FROM attendance WHERE lesson_plan_id IS NOT NULL")->fetch()['count'];
    
    echo "总教课报告数: $total_attendance\n";
    echo "总教案数: $total_lesson_plans\n";
    echo "已关联记录数: $linked_records\n";
    
    // 3. 检查关联关系
    echo "\n3. 检查关联关系...\n";
    
    if ($linked_records > 0) {
        $linked_data = $pdo->query("
            SELECT 
                a.id as attendance_id,
                a.date,
                a.status,
                lp.id as lesson_plan_id,
                lp.title as lesson_plan_title,
                c.title as course_title
            FROM attendance a
            LEFT JOIN lesson_plans lp ON a.lesson_plan_id = lp.id
            LEFT JOIN courses c ON a.course_id = c.id
            WHERE a.lesson_plan_id IS NOT NULL
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "关联数据示例:\n";
        foreach ($linked_data as $data) {
            echo "  教课报告ID: {$data['attendance_id']}, 日期: {$data['date']}, 教案: {$data['lesson_plan_title']}, 课程: {$data['course_title']}\n";
        }
    } else {
        echo "暂无关联记录\n";
    }
    
    // 4. 检查课程数据
    echo "\n4. 检查课程数据...\n";
    
    $courses = $pdo->query("SELECT id, title FROM courses LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "可用课程:\n";
    foreach ($courses as $course) {
        echo "  - ID: {$course['id']}, 标题: {$course['title']}\n";
    }
    
    // 5. 检查用户数据
    echo "\n5. 检查用户数据...\n";
    
    $teachers = $pdo->query("SELECT id, name, role FROM users WHERE role = 'teacher' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "老师用户:\n";
    foreach ($teachers as $teacher) {
        echo "  - ID: {$teacher['id']}, 姓名: {$teacher['name']}, 角色: {$teacher['role']}\n";
    }
    
    echo "\n✅ 测试完成！\n";
    
} catch (Exception $e) {
    echo "❌ 测试失败: " . $e->getMessage() . "\n";
    echo "错误位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>
