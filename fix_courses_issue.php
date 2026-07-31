<?php
// fix_courses_issue.php - 自动修复课程数据问题
require_once __DIR__ . '/db.php';

echo "<h2>课程数据修复工具</h2>\n";
echo "<pre>\n";

try {
    echo "开始诊断和修复...\n\n";
    
    // 1. 检查course_teachers表是否存在
    $table_check = $pdo->query("SHOW TABLES LIKE 'course_teachers'")->fetch();
    
    if (!$table_check) {
        echo "=== 第一步：创建course_teachers表 ===\n";
        
        $sql = "CREATE TABLE course_teachers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            teacher_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_course_teacher (course_id, teacher_id)
        )";
        
        $pdo->exec($sql);
        echo "✅ course_teachers表创建成功\n\n";
        
        // 2. 迁移现有数据
        echo "=== 第二步：迁移现有数据 ===\n";
        
        // 检查courses表是否有teacher_id字段
        $stmt = $pdo->query("DESCRIBE courses");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('teacher_id', $columns)) {
            echo "发现teacher_id字段，开始迁移数据...\n";
            
            // 迁移数据
            $stmt = $pdo->query("SELECT id, teacher_id FROM courses WHERE teacher_id IS NOT NULL");
            $courses_with_teachers = $stmt->fetchAll();
            
            $insert_stmt = $pdo->prepare("INSERT IGNORE INTO course_teachers (course_id, teacher_id) VALUES (?, ?)");
            $migrated_count = 0;
            
            foreach ($courses_with_teachers as $course) {
                $insert_stmt->execute([$course['id'], $course['teacher_id']]);
                $migrated_count++;
            }
            
            echo "✅ 成功迁移 $migrated_count 条课程-老师关系\n";
            
            // 备份原teacher_id字段
            $pdo->exec("ALTER TABLE courses CHANGE COLUMN teacher_id old_teacher_id INT");
            echo "✅ teacher_id字段已重命名为old_teacher_id（用于备份）\n";
        } else {
            echo "未发现teacher_id字段，跳过数据迁移\n";
        }
        echo "\n";
    } else {
        echo "=== course_teachers表已存在 ===\n";
        
        // 检查是否有数据
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM course_teachers");
        $relation_count = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
        $course_count = $stmt->fetch()['count'];
        
        echo "course_teachers表记录数：$relation_count\n";
        echo "courses表记录数：$course_count\n";
        
        if ($relation_count == 0 && $course_count > 0) {
            echo "⚠️ 发现课程没有分配老师，尝试修复...\n";
            
            // 检查是否有old_teacher_id字段
            $stmt = $pdo->query("DESCRIBE courses");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('old_teacher_id', $columns)) {
                echo "发现old_teacher_id字段，恢复课程-老师关系...\n";
                
                $stmt = $pdo->query("SELECT id, old_teacher_id FROM courses WHERE old_teacher_id IS NOT NULL");
                $courses_with_teachers = $stmt->fetchAll();
                
                $insert_stmt = $pdo->prepare("INSERT IGNORE INTO course_teachers (course_id, teacher_id) VALUES (?, ?)");
                $restored_count = 0;
                
                foreach ($courses_with_teachers as $course) {
                    $insert_stmt->execute([$course['id'], $course['old_teacher_id']]);
                    $restored_count++;
                }
                
                echo "✅ 成功恢复 $restored_count 条课程-老师关系\n";
            } elseif (in_array('teacher_id', $columns)) {
                echo "发现teacher_id字段，迁移数据...\n";
                
                $stmt = $pdo->query("SELECT id, teacher_id FROM courses WHERE teacher_id IS NOT NULL");
                $courses_with_teachers = $stmt->fetchAll();
                
                $insert_stmt = $pdo->prepare("INSERT IGNORE INTO course_teachers (course_id, teacher_id) VALUES (?, ?)");
                $migrated_count = 0;
                
                foreach ($courses_with_teachers as $course) {
                    $insert_stmt->execute([$course['id'], $course['teacher_id']]);
                    $migrated_count++;
                }
                
                echo "✅ 成功迁移 $migrated_count 条课程-老师关系\n";
            }
        }
        echo "\n";
    }
    
    // 3. 验证修复结果
    echo "=== 第三步：验证修复结果 ===\n";
    
    // 测试课程查询
    $sql = "SELECT c.*, 
                   GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') as teacher_names,
                   GROUP_CONCAT(u.id ORDER BY u.name) as teacher_ids
            FROM courses c 
            LEFT JOIN course_teachers ct ON c.id = ct.course_id
            LEFT JOIN users u ON ct.teacher_id = u.id 
            GROUP BY c.id
            ORDER BY c.created_at DESC LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $courses = $stmt->fetchAll();
    
    echo "课程查询测试结果：\n";
    if (empty($courses)) {
        echo "❌ 仍然没有找到课程数据\n";
        
        // 检查是否真的有课程数据
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
        $total_courses = $stmt->fetch()['count'];
        echo "数据库中总课程数：$total_courses\n";
        
        if ($total_courses > 0) {
            echo "课程数据存在，但查询可能有问题\n";
            
            // 简单查询测试
            $stmt = $pdo->query("SELECT id, title FROM courses LIMIT 3");
            $simple_courses = $stmt->fetchAll();
            echo "简单查询结果：\n";
            foreach ($simple_courses as $course) {
                echo "- ID:{$course['id']} - {$course['title']}\n";
            }
        }
    } else {
        echo "✅ 找到 " . count($courses) . " 个课程：\n";
        foreach ($courses as $course) {
            $teacher_info = $course['teacher_names'] ?: '未分配';
            echo "- {$course['title']} (老师: $teacher_info)\n";
        }
    }
    
    echo "\n=== 修复完成 ===\n";
    echo "请刷新课程管理页面查看结果\n";
    
} catch (Exception $e) {
    echo "❌ 修复过程中出现错误: " . $e->getMessage() . "\n";
    echo "错误位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "请检查数据库连接和权限\n";
}

echo "</pre>\n";
echo "<p><a href='public/course_management.php'>返回课程管理</a> | <a href='diagnose_courses.php'>重新诊断</a></p>\n";
?>
