<?php
// delete_user.php - 删除用户
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

// 设置响应头为JSON
header('Content-Type: application/json');

// 检查是否登录且是管理员
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => '权限不足，只有管理员可以删除用户'
    ]);
    exit;
}

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '无效的请求方法'
    ]);
    exit;
}

// 获取用户ID
$user_id = $_POST['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode([
        'success' => false,
        'message' => '用户ID不能为空'
    ]);
    exit;
}

try {
    require_once __DIR__ . '/../db.php';
    
    // 获取要删除的用户信息
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_to_delete = $stmt->fetch();
    
    if (!$user_to_delete) {
        echo json_encode([
            'success' => false,
            'message' => '用户不存在'
        ]);
        exit;
    }
    
    // 检查是否试图删除自己
    if ($user_to_delete['id'] == $_SESSION['user']['id']) {
        echo json_encode([
            'success' => false,
            'message' => '不能删除自己的账户'
        ]);
        exit;
    }
    
    // 检查是否试图删除最后一个管理员
    if ($user_to_delete['role'] === 'admin') {
        $stmt = $pdo->query("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
        $admin_count = $stmt->fetch()['admin_count'];
        
        if ($admin_count <= 1) {
            echo json_encode([
                'success' => false,
                'message' => '不能删除最后一个管理员账户'
            ]);
            exit;
        }
    }
    
    // 开始事务
    $pdo->beginTransaction();
    
    // 检查是否有相关的课程分配（使用新的多老师支持结构）
    $course_count = 0;
    try {
        // 检查course_teachers表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'course_teachers'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as course_count FROM course_teachers WHERE teacher_id = ?");
            $stmt->execute([$user_id]);
            $course_count = $stmt->fetch()['course_count'];
        }
    } catch (Exception $e) {
        // 如果表不存在，忽略错误
        $course_count = 0;
    }
    
    // 检查是否有相关的出勤记录（使用user_id字段）
    $stmt = $pdo->prepare("SELECT COUNT(*) as attendance_count FROM attendance WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $attendance_count = $stmt->fetch()['attendance_count'];
    
    // 检查是否有相关的教案（使用created_by字段）
    $stmt = $pdo->prepare("SELECT COUNT(*) as lesson_plan_count FROM lesson_plans WHERE created_by = ?");
    $stmt->execute([$user_id]);
    $lesson_plan_count = $stmt->fetch()['lesson_plan_count'];
    
    // 如果有相关数据，可以选择级联删除或阻止删除
    if ($course_count > 0 || $attendance_count > 0 || $lesson_plan_count > 0) {
        // 选择级联删除相关数据
        if ($course_count > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM course_teachers WHERE teacher_id = ?");
                $stmt->execute([$user_id]);
            } catch (Exception $e) {
                // 如果表不存在，忽略错误
                error_log("删除course_teachers记录失败: " . $e->getMessage());
            }
        }
        
        if ($attendance_count > 0) {
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        
        if ($lesson_plan_count > 0) {
            $stmt = $pdo->prepare("DELETE FROM lesson_plans WHERE created_by = ?");
            $stmt->execute([$user_id]);
        }
    }
    
    // 删除用户
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // 检查删除是否成功
    if ($stmt->rowCount() > 0) {
        // 记录活动日志
        try {
            $action = "删除用户";
            $details = "删除了用户：{$user_to_delete['name']} ({$user_to_delete['email']}) (ID: {$user_id})";
            
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user']['id'],
                $action,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // 如果记录日志失败，不影响用户删除
            error_log("记录删除用户活动日志失败: " . $e->getMessage());
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '用户删除成功',
            'deleted_user' => [
                'id' => $user_to_delete['id'],
                'name' => $user_to_delete['name'],
                'email' => $user_to_delete['email'],
                'role' => $user_to_delete['role']
            ],
            'cascaded_data' => [
                'courses' => $course_count,
                'attendance' => $attendance_count,
                'lesson_plans' => $lesson_plan_count
            ]
        ]);
    } else {
        throw new Exception('删除用户失败，可能用户已被删除');
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => '删除失败：' . $e->getMessage()
    ]);
}
?>
