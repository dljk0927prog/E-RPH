<?php
// change_language.php - 处理语言切换
require_once __DIR__ . '/inc/session_config.php';

// 设置响应头
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lang'])) {
            $language = $_POST['lang'];
        
        // 验证语言代码
        $valid_languages = ['zh', 'en'];
        if (!in_array($language, $valid_languages)) {
            throw new Exception('不支持的语言代码');
        }
        
        // 保存到会话
        $_SESSION['lang'] = $language;
        
        // 这里可以添加数据库保存逻辑，如果需要持久化存储
        
        echo json_encode([
            'success' => true,
            'message' => '语言切换成功',
            'language' => $language
        ]);
        
    } else {
        throw new Exception('无效的请求');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
