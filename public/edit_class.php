<?php
// edit_class.php - 处理班级编辑请求
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login_roles.php');
    exit;
}

require_once __DIR__ . '/../db.php';

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $name = trim($_POST['name'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '') {
        $error = t('classes.enter_name');
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE classes SET name = ?, is_active = ? WHERE id = ?');
            $stmt->execute([$name, $is_active, $edit_id]);
            $msg = t('classes.update_success');
        } catch (Throwable $e) {
            $error = t('errors.update_failed') . '：' . $e->getMessage();
        }
    }
}

// 重定向回班级管理页面
if ($error) {
    $_SESSION['error'] = $error;
} else {
    $_SESSION['msg'] = $msg;
}

header('Location: classes.php');
exit;
?>
