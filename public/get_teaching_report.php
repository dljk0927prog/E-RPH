<?php
// get_teaching_report.php - 获取教课报告详情的API接口
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

// 设置响应头为JSON
header('Content-Type: application/json');

// 添加调试信息
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查是否登录且是管理员
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => '权限不足，只有管理员可以查看教课报告详情'
    ]);
    exit;
}

// 获取报告ID
$report_id = $_GET['id'] ?? 0;
if (!$report_id) {
    echo json_encode([
        'success' => false,
        'message' => '报告ID不能为空'
    ]);
    exit;
}

try {
    require_once __DIR__ . '/../db.php';
    
    // 获取教课报告详细信息
    $sql = "
        SELECT 
            a.id,
            a.date,
            a.status,
            a.check_in,
            a.check_out,
            a.notes,
            a.created_at,
            u.name as teacher_name,
            u.email as teacher_email,
            u.id as teacher_id,
            c.title as course_title,
            c.description as course_description,
            c.id as course_id
        FROM attendance a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN courses c ON a.course_id = c.id
        WHERE a.id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();
    
    if (!$report) {
        echo json_encode([
            'success' => false,
            'message' => '教课报告不存在'
        ]);
        exit;
    }
    
    // 返回成功结果
    echo json_encode([
        'success' => true,
        'report' => $report
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '获取教课报告失败：' . $e->getMessage()
    ]);
}
?>
