<?php
// session_config.php - 统一会话配置文件
// 确保所有页面使用相同的会话参数，避免Cookie配置不一致导致的登录问题

if (session_status() === PHP_SESSION_NONE) {
    // 设置安全的会话Cookie参数
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // 启动会话
    session_start();
}

// 确保用户会话数据完整性
if (isset($_SESSION['user'])) {
    // 只在字段真正不存在或为空时才设置默认值
    if (!isset($_SESSION['user']['name']) || empty($_SESSION['user']['name'])) {
        $_SESSION['user']['name'] = 'Unknown User';
    }
    if (!isset($_SESSION['user']['avatar'])) {
        $_SESSION['user']['avatar'] = null;
    }
    if (!isset($_SESSION['user']['role']) || empty($_SESSION['user']['role'])) {
        $_SESSION['user']['role'] = 'student';
    }
}
?>
