<?php
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';
require_once '../db.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 检查用户是否已登录且具有管理员权限
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => t('errors.permission_denied')]);
    exit;
}

// 检查CSRF令牌（如果启用了CSRF保护）
if (isset($_SESSION['csrf_token']) && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => t('errors.csrf_failed', 'CSRF令牌验证失败')]);
    exit;
}

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => t('errors.method_not_allowed', '请求方法不允许')]);
    exit;
}

// 获取课程ID
$course_id = $_POST['course_id'] ?? null;

if (!$course_id || !is_numeric($course_id)) {
    echo json_encode(['success' => false, 'message' => t('course_management.invalid_course_id', '无效的课程ID')]);
    exit;
}

try {
    // 开始事务
    $pdo->beginTransaction();
    
    // 检查课程是否存在
    $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        throw new Exception('课程不存在');
    }
    
    // 检查是否有相关的课程计划
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lesson_plans WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $lesson_plans_count = $stmt->fetchColumn();
    
    // 检查是否有相关的出勤记录
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $attendance_count = $stmt->fetchColumn();
    
    // 如果有相关数据，可以选择级联删除或阻止删除
    if ($lesson_plans_count > 0 || $attendance_count > 0) {
        // 选择级联删除相关数据
        if ($lesson_plans_count > 0) {
            $stmt = $pdo->prepare("DELETE FROM lesson_plans WHERE course_id = ?");
            $stmt->execute([$course_id]);
        }
        
        if ($attendance_count > 0) {
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE course_id = ?");
            $stmt->execute([$course_id]);
        }
    }
    
    // 删除课程
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    
    // 记录系统日志（如果system_logs表存在）
    try {
        $user_id = $_SESSION['user']['id'];
        $action = "删除课程";
        $details = "删除了课程：{$course['title']} (ID: {$course_id})";
        $additional_info = "相关数据：课程计划 {$lesson_plans_count} 条，出勤记录 {$attendance_count} 条";
        $full_details = $details . "。{$additional_info}";
        
        // 检查system_logs表是否存在
        $table_exists = $pdo->query("SHOW TABLES LIKE 'system_logs'")->fetch();
        if ($table_exists) {
            $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $action, $full_details, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        }
    } catch (Exception $log_error) {
        // 如果记录日志失败，不影响课程删除
        error_log("记录系统日志失败: " . $log_error->getMessage());
    }
    
    // 提交事务
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => '课程删除成功',
        'course_id' => $course_id
    ]);
    
} catch (Exception $e) {
    // 回滚事务
    $pdo->rollBack();
    
    echo json_encode([
        'success' => false, 
        'message' => '删除失败：' . $e->getMessage()
    ]);
}
?>
