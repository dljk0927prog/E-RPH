<?php
// diagnose_courses.php - 诊断课程数据问题
require_once __DIR__ . '/db.php';

echo "<h2>课程数据诊断报告</h2>\n";
echo "<pre>\n";

try {
    // 1. 检查courses表
    echo "=== 1. 检查courses表 ===\n";
    $stmt = $pdo->query("DESCRIBE courses");
    $columns = $stmt->fetchAll();
    echo "courses表字段：\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
    $count = $stmt->fetch()['count'];
    echo "courses表记录数：$count\n\n";
    
    // 2. 检查course_teachers表
    echo "=== 2. 检查course_teachers表 ===\n";
    $table_check = $pdo->query("SHOW TABLES LIKE 'course_teachers'")->fetch();
    if ($table_check) {
        echo "course_teachers表存在\n";
        $stmt = $pdo->query("DESCRIBE course_teachers");
        $columns = $stmt->fetchAll();
        echo "course_teachers表字段：\n";
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']})\n";
        }
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM course_teachers");
        $count = $stmt->fetch()['count'];
        echo "course_teachers表记录数：$count\n";
    } else {
        echo "⚠️ course_teachers表不存在！\n";
    }
    echo "\n";
    
    // 3. 检查用户表中的老师
    echo "=== 3. 检查老师用户 ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
    $count = $stmt->fetch()['count'];
    echo "老师用户数：$count\n";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'teacher' ORDER BY name LIMIT 5");
        $teachers = $stmt->fetchAll();
        echo "前5个老师：\n";
        foreach ($teachers as $teacher) {
            echo "- ID:{$teacher['id']} - {$teacher['name']}\n";
        }
    }
    echo "\n";
    
    // 4. 检查具体的课程数据
    echo "=== 4. 检查课程数据详情 ===\n";
    $stmt = $pdo->query("SELECT * FROM courses ORDER BY created_at DESC LIMIT 5");
    $courses = $stmt->fetchAll();
    if (empty($courses)) {
        echo "⚠️ 没有找到任何课程记录！\n";
    } else {
        echo "最近5个课程：\n";
        foreach ($courses as $course) {
            echo "- ID:{$course['id']} - {$course['title']}";
            if (isset($course['teacher_id'])) {
                echo " (旧teacher_id:{$course['teacher_id']})";
            }
            if (isset($course['old_teacher_id'])) {
                echo " (old_teacher_id:{$course['old_teacher_id']})";
            }
            echo "\n";
        }
    }
    echo "\n";
    
    // 5. 测试课程查询
    echo "=== 5. 测试课程查询 ===\n";
    if ($table_check) {
        echo "使用新查询（多老师）：\n";
        $sql = "SELECT c.*, 
                       GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') as teacher_names,
                       GROUP_CONCAT(u.id ORDER BY u.name) as teacher_ids
                FROM courses c 
                LEFT JOIN course_teachers ct ON c.id = ct.course_id
                LEFT JOIN users u ON ct.teacher_id = u.id 
                GROUP BY c.id
                ORDER BY c.created_at DESC LIMIT 3";
    } else {
        echo "使用旧查询（单老师）：\n";
        $sql = "SELECT c.*, u.name as teacher_names, u.id as teacher_ids
                FROM courses c 
                LEFT JOIN users u ON c.teacher_id = u.id 
                ORDER BY c.created_at DESC LIMIT 3";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    if (empty($results)) {
        echo "❌ 查询结果为空！\n";
    } else {
        echo "✅ 查询成功，返回 " . count($results) . " 条记录：\n";
        foreach ($results as $result) {
            echo "- {$result['title']} (老师: " . ($result['teacher_names'] ?: '未分配') . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo "错误位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "</pre>\n";
echo "<p><a href='public/course_management.php'>返回课程管理</a></p>\n";
?>
