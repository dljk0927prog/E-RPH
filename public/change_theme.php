<?php
// change_theme.php - 主题切换处理
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])) {
    $theme = $_POST['theme'];
    
    // 验证主题值
    if (in_array($theme, ['light', 'dark'])) {
        $_SESSION['theme'] = $theme;
        
        // 返回成功响应
        echo json_encode(['success' => true, 'theme' => $theme]);
    } else {
        echo json_encode(['success' => false, 'error' => '无效的主题值']);
    }
} else {
    echo json_encode(['success' => false, 'error' => '无效的请求']);
}
?>
