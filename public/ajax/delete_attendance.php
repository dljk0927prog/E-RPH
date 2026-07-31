<?php
// delete_attendance.php - 删除教课记录的AJAX处理
require_once __DIR__ . '/../inc/session_config.php';
require_once __DIR__ . '/../inc/language_config.php';

// 检查用户是否登录
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => t('teaching_reports.not_logged_in')]);
    exit;
}

require_once __DIR__ . '/../../db.php';

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => t('teaching_reports.method_not_allowed')]);
    exit;
}

// 获取POST数据
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => t('teaching_reports.invalid_request_data')]);
    exit;
}

$user_id = $_SESSION['user']['id'];
$action = $input['action'] ?? '';

try {
    if ($action === 'delete_multiple') {
        // 批量删除
        $report_ids = $input['report_ids'] ?? [];
        
        if (empty($report_ids)) {
            echo json_encode(['success' => false, 'message' => t('teaching_reports.select_first_to_delete')]);
            exit;
        }
        
        // 验证这些记录是否属于当前用户
        $placeholders = str_repeat('?,', count($report_ids) - 1) . '?';
        $check_stmt = $pdo->prepare("SELECT id FROM attendance WHERE id IN ($placeholders) AND user_id = ?");
        $check_params = array_merge($report_ids, [$user_id]);
        $check_stmt->execute($check_params);
        $valid_ids = $check_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($valid_ids) !== count($report_ids)) {
            echo json_encode(['success' => false, 'message' => t('teaching_reports.partial_not_found_or_forbidden')]);
            exit;
        }
        
        // 开始事务
        $pdo->beginTransaction();
        
        try {
            // 检查是否有教案关联，并获取教案ID
            $check_lesson_plans_stmt = $pdo->prepare("SELECT lesson_plan_id FROM attendance WHERE id IN ($placeholders) AND user_id = ? AND lesson_plan_id IS NOT NULL");
            $check_lesson_plans_stmt->execute($check_params);
            $lesson_plan_ids = $check_lesson_plans_stmt->fetchAll(PDO::FETCH_COLUMN);
            $lesson_plan_ids = array_filter($lesson_plan_ids); // 过滤掉NULL值
            
            // 删除选中的教课记录
            $delete_stmt = $pdo->prepare("DELETE FROM attendance WHERE id IN ($placeholders) AND user_id = ?");
            $delete_stmt->execute($check_params);
            
            $deleted_count = $delete_stmt->rowCount();
            $deleted_lesson_plans = 0;
            
            // 如果有关联的教案，删除教案以保持数据一致性
            if (!empty($lesson_plan_ids)) {
                $lesson_plan_placeholders = str_repeat('?,', count($lesson_plan_ids) - 1) . '?';
                $delete_lesson_plans_stmt = $pdo->prepare("DELETE FROM lesson_plans WHERE id IN ($lesson_plan_placeholders) AND created_by = ?");
                $delete_lesson_plans_stmt->execute(array_merge($lesson_plan_ids, [$user_id]));
                $deleted_lesson_plans = $delete_lesson_plans_stmt->rowCount();
            }
            
            // 提交事务
            $pdo->commit();
            
            // 构建成功消息
            $success_message = t('teaching_reports.delete_success_count', ['count' => $deleted_count]);
            if ($deleted_lesson_plans > 0) {
                $success_message .= t('teaching_reports.delete_with_plans_count', ['count' => $deleted_lesson_plans]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => $success_message,
                'deleted_count' => $deleted_count,
                'deleted_lesson_plans' => $deleted_lesson_plans
            ]);
            
        } catch (Exception $e) {
            // 回滚事务
            $pdo->rollBack();
            throw $e;
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => t('teaching_reports.invalid_action')]);
    }
    
} catch (PDOException $e) {
    error_log("删除教课记录数据库错误: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => t('teaching_reports.database_operation_failed')]);
} catch (Exception $e) {
    error_log("删除教课记录错误: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => t('teaching_reports.operation_failed_prefix') . $e->getMessage()]);
}
?>
