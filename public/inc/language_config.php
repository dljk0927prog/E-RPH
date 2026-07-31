<?php
// language_config.php - 语言配置文件
// 确保会话已启动（与 session_config 保持一致的 Cookie 路径）
if (session_status() === PHP_SESSION_NONE) {
    if (function_exists('session_set_cookie_params')) {
        session_set_cookie_params([
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    session_start();
}

// 处理 URL 语言切换（须在加载翻译之前）
if (isset($_GET['lang']) && in_array($_GET['lang'], ['zh', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

// 默认语言：英文
if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], ['zh', 'en'], true)) {
    $_SESSION['lang'] = 'en';
}

// 获取当前语言
$current_language = $_SESSION['lang'];

// 加载翻译文件
function loadTranslations($language) {
    $translations_file = __DIR__ . "/translations/{$language}.php";
    if (file_exists($translations_file)) {
        return include $translations_file;
    }
    // 如果文件不存在，返回英文作为后备
    return include __DIR__ . "/translations/en.php";
}

// 加载当前语言的翻译
$translations = loadTranslations($current_language);

// 翻译函数
function t($key, $params = null, $default = null) {
    global $translations;
    
    // 支持嵌套键，如 'dashboard.welcome'
    $keys = explode('.', $key);
    $value = $translations;
    
    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $default ?? $key;
        }
    }
    
    // 如果提供了参数，进行参数替换
    if (is_array($params) && is_string($value)) {
        foreach ($params as $param => $replacement) {
            $value = str_replace('{' . $param . '}', strval($replacement), $value);
        }
    }
    
    return $value;
}

// 语言切换按钮HTML生成函数
function renderLanguageSwitch($show_text = true) {
    global $current_language;
    
    $zh_class = $current_language === 'zh' ? 'active' : '';
    $en_class = $current_language === 'en' ? 'active' : '';
    
    $current_url = $_SERVER['PHP_SELF'];
    if (!empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $params);
        unset($params['lang']);
        if (!empty($params)) {
            $current_url .= '?' . http_build_query($params);
        }
    }
    
    $zh_url = $current_url . (strpos($current_url, '?') !== false ? '&' : '?') . 'lang=zh';
    $en_url = $current_url . (strpos($current_url, '?') !== false ? '&' : '?') . 'lang=en';
    
    ob_start();
    ?>
    <div class="language-switch">
        <?php if ($show_text): ?>
        <span class="language-label"><?= t('common.language') ?>:</span>
        <?php endif; ?>
        <a href="<?= $zh_url ?>" class="lang-btn <?= $zh_class ?>">中文</a>
        <a href="<?= $en_url ?>" class="lang-btn <?= $en_class ?>">English</a>
    </div>
    
    <style>
    .language-switch {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }
    
    .language-label {
        color: rgba(255,255,255,0.8);
        font-size: 13px;
    }
    
    .lang-btn {
        padding: 6px 12px;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 4px;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        font-size: 12px;
        transition: all 0.3s ease;
        background: rgba(255,255,255,0.1);
    }
    
    .lang-btn:hover {
        background: rgba(255,255,255,0.2);
        color: white;
        border-color: rgba(255,255,255,0.5);
    }
    
    .lang-btn.active {
        background: rgba(255,255,255,0.9);
        color: #4a90e2;
        border-color: rgba(255,255,255,0.9);
        font-weight: 600;
    }
    
    /* 登录页面样式调整 */
    .login-card .language-switch {
        margin-bottom: 20px;
        justify-content: center;
    }
    
    .login-card .language-label {
        color: #666;
    }
    
    .login-card .lang-btn {
        color: #666;
        border-color: #ddd;
        background: #f8f9fa;
    }
    
    .login-card .lang-btn:hover {
        background: #e9ecef;
        color: #4a90e2;
        border-color: #4a90e2;
    }
    
    .login-card .lang-btn.active {
        background: #4a90e2;
        color: white;
        border-color: #4a90e2;
    }
    
    /* 确保登录页面的语言切换按钮样式正确 */
    body:not([data-theme="dark"]) .language-switch {
        margin-bottom: 20px;
        justify-content: center;
    }
    
    body:not([data-theme="dark"]) .language-label {
        color: #666;
    }
    
    body:not([data-theme="dark"]) .lang-btn {
        color: #666;
        border-color: #ddd;
        background: #f8f9fa;
    }
    
    body:not([data-theme="dark"]) .lang-btn:hover {
        background: #e9ecef;
        color: #4a90e2;
        border-color: #4a90e2;
    }
    
    body:not([data-theme="dark"]) .lang-btn.active {
        background: #4a90e2;
        color: white;
        border-color: #4a90e2;
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * 自动注入全站页脚（仅 HTML 页面）。
 * 兼容共享主机环境：不依赖额外扩展，不改写非 HTML 响应。
 */
function erphGetGlobalFooterMarkup() {
    return '<style id="erph-global-footer-style">'
        . '.erph-global-footer-wrap{margin-top:32px;padding:18px 12px;text-align:center;}'
        . '.erph-global-footer{display:inline-block;padding:10px 16px;border-radius:999px;background:rgba(74,144,226,.08);color:#4f5b66;font-size:13px;line-height:1.4;box-shadow:0 2px 10px rgba(0,0,0,.05);}'
        . 'body[data-theme="dark"] .erph-global-footer{background:rgba(255,255,255,.12);color:rgba(255,255,255,.9);}'
        . '@media (max-width:768px){.erph-global-footer-wrap{margin-top:24px;padding:14px 10px;}.erph-global-footer{font-size:12px;padding:9px 12px;}}'
        . '</style>'
        . '<div class="erph-global-footer-wrap"><div class="erph-global-footer">Copyright &copy; 2026 Desmond Liew. All Rights Reserved.</div></div>';
}

function erphShouldInjectGlobalFooter() {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptBaseName = basename($scriptName);
    $contentTypeHeader = '';

    foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type:') === 0) {
            $contentTypeHeader = trim(substr($header, 13));
            break;
        }
    }

    if (stripos($uri, '/ajax/') !== false || stripos($scriptName, '/ajax/') !== false) {
        return false;
    }
    if ($scriptBaseName === 'index.php') {
        return false;
    }
    if ($contentTypeHeader !== '' && stripos($contentTypeHeader, 'text/html') === false) {
        return false;
    }
    return true;
}

if (!defined('ERPH_GLOBAL_FOOTER_OB_STARTED')) {
    define('ERPH_GLOBAL_FOOTER_OB_STARTED', true);

    if (erphShouldInjectGlobalFooter()) {
        ob_start(function ($buffer) {
            if (!is_string($buffer) || $buffer === '') {
                return $buffer;
            }
            if (stripos($buffer, 'erph-global-footer-wrap') !== false) {
                return $buffer;
            }
            $looksLikeHtml = stripos($buffer, '<html') !== false || stripos($buffer, '</body>') !== false;
            if (!$looksLikeHtml) {
                return $buffer;
            }

            $footerMarkup = erphGetGlobalFooterMarkup();
            if (stripos($buffer, '</body>') !== false) {
                return preg_replace('/<\/body>/i', $footerMarkup . '</body>', $buffer, 1);
            }
            return $buffer . $footerMarkup;
        });
    }
}
?>
