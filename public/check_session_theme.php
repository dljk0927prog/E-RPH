<?php
// check_session_theme.php - 检查会话主题状态
session_start();

if (isset($_SESSION['theme'])) {
    echo json_encode(['success' => true, 'theme' => $_SESSION['theme']]);
} else {
    echo json_encode(['success' => false, 'theme' => 'light']);
}
?>

