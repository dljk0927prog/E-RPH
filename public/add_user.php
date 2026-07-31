<?php
// add_user.php - 添加用户页面
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

// 检查是否登录且是管理员
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login_roles.php');
    exit;
}

$user = $_SESSION['user'];
$message = '';
$error = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    // 验证输入
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = t('add_user.error_all_fields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('add_user.error_invalid_email');
    } elseif (strlen($password) < 6) {
        $error = t('add_user.error_password_length');
    } elseif ($password !== $confirm_password) {
        $error = t('add_user.error_password_mismatch');
    } elseif (!in_array($role, ['admin', 'teacher', 'student'])) {
        $error = t('add_user.error_invalid_role');
    } else {
        try {
            require_once __DIR__ . '/../db.php';
            
            // 检查邮箱是否已存在
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = t('add_user.error_email_exists');
            } else {
                // 创建新用户
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $password_hash, $role]);
                
                $message = t('add_user.success_created', ['name' => $name]);
                
                // 清空表单
                $name = $email = $password = $confirm_password = $role = '';
            }
        } catch (Exception $e) {
            $error = t('add_user.error_create_failed') . ": " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="<?= t('common.language_code') ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('add_user.title') ?> - ERPH</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/mobile-optimization.css">
    <link rel="stylesheet" href="assets/css/dark-mode-unified.css">
    <script src="assets/js/theme-sync.js"></script>
    <style>
        /* 浅色模式CSS变量 */
        :root {
            --bg-primary: #f5f5f5;
            --bg-secondary: #ffffff;
            --text-primary: #333333;
            --text-secondary: #666666;
            --text-muted: #999999;
            --border-color: #e1e5e9;
            --shadow-color: rgba(0, 0, 0, 0.08);
            --accent-color: #4a90e2;
            --accent-hover: #7bb3f0;
            --header-bg: linear-gradient(90deg, #4a90e2, #7bb3f0);
            --card-border: #4a90e2;
            --success-bg: #d4edda;
            --success-text: #155724;
            --success-border: #c3e6cb;
            --error-bg: #f8d7da;
            --error-text: #721c24;
            --error-border: #f5c6cb;
            --warning-bg: #fff3cd;
            --warning-text: #856404;
            --warning-border: #ffeaa7;
        }
        
        /* 深色模式CSS变量 */
        [data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #e0e0e0;
            --text-muted: #b0b0b0;
            --border-color: #404040;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --accent-color: #4a90e2;
            --accent-hover: #7bb3f0;
            --header-bg: linear-gradient(90deg, #2d3748, #4a5568);
            --card-border: #4a90e2;
            --success-bg: #22543d;
            --success-text: #9ae6b4;
            --success-border: #38a169;
            --error-bg: #742a2a;
            --error-text: #feb2b2;
            --error-border: #e53e3e;
            --warning-bg: #744210;
            --warning-text: #faf089;
            --warning-border: #d69e2e;
        }
        
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
            margin: 0;
            font-family: 'Microsoft YaHei', Arial, sans-serif;
        }
        
        .header {
            background: var(--header-bg);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px var(--shadow-color);
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 30px;
            background: var(--bg-secondary);
            border-radius: 12px;
            box-shadow: 0 4px 20px var(--shadow-color);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .form-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px var(--shadow-color);
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(74, 144, 226, 0.4);
        }
        
        .message {
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }
        
        .message.success {
            background: var(--success-bg);
            color: var(--success-text);
            border: 1px solid var(--success-border);
        }
        
        .message.error {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid var(--error-border);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 24px;
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: rgba(74, 144, 226, 0.1);
            transform: translateX(-4px);
        }
        
        /* 深色模式特殊样式覆盖 */
        [data-theme="dark"] .form-container {
            background: var(--bg-secondary);
            border-color: var(--border-color);
            box-shadow: 0 4px 20px var(--shadow-color);
        }
        
        [data-theme="dark"] .form-group label {
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .form-group input,
        [data-theme="dark"] .form-group select {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }
        
        [data-theme="dark"] .message.success {
            background: var(--success-bg);
            color: var(--success-text);
            border-color: var(--success-border);
        }
        
        [data-theme="dark"] .message.error {
            background: var(--error-bg);
            color: var(--error-text);
            border-color: var(--error-border);
        }
        
        [data-theme="dark"] .back-link {
            color: var(--accent-color);
        }
        
        [data-theme="dark"] .back-link:hover {
            background: rgba(74, 144, 226, 0.2);
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .form-container {
                margin: 10px;
                padding: 20px;
            }
            
            .header {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= t('add_user.title') ?></h1>
    </div>

    <div class="form-container">
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="name"><?= t('add_user.name') ?> *</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email"><?= t('add_user.email') ?> *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="password"><?= t('add_user.password') ?> *</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password"><?= t('add_user.confirm_password') ?> *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <div class="form-group">
                <label for="role"><?= t('add_user.role') ?> *</label>
                <select id="role" name="role" required>
                    <option value=""><?= t('add_user.select_role') ?></option>
                    <option value="admin" <?= ($role ?? '') === 'admin' ? 'selected' : '' ?>><?= t('roles.admin') ?></option>
                    <option value="teacher" <?= ($role ?? '') === 'teacher' ? 'selected' : '' ?>><?= t('roles.teacher') ?></option>
                    <option value="student" <?= ($role ?? '') === 'student' ? 'selected' : '' ?>><?= t('roles.student') ?></option>
                </select>
            </div>

            <button type="submit" class="btn"><?= t('add_user.create_user') ?></button>
        </form>

        <a href="user_management.php" class="back-link">← <?= t('add_user.back_to_management') ?></a>
    </div>

    <script src="assets/js/theme-sync.js"></script>
    <script>
        // 页面加载完成后初始化主题
        document.addEventListener('DOMContentLoaded', function() {
            // 从localStorage获取保存的主题
            const savedTheme = localStorage.getItem('theme') || 'light';
            
            // 设置页面主题
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // 从sessionStorage获取主题（如果存在）
            const sessionTheme = sessionStorage.getItem('theme');
            if (sessionTheme) {
                document.documentElement.setAttribute('data-theme', sessionTheme);
            }
            
            console.log('页面主题初始化完成:', savedTheme);
        });
        
        // 监听主题变化事件
        window.addEventListener('themeChanged', function(event) {
            const newTheme = event.detail.theme;
            document.documentElement.setAttribute('data-theme', newTheme);
            console.log('主题已更新:', newTheme);
        });
        
        // 监听BroadcastChannel消息
        if (typeof BroadcastChannel !== 'undefined') {
            try {
                const channel = new BroadcastChannel('theme-sync');
                channel.addEventListener('message', function(event) {
                    const newTheme = event.data.theme;
                    document.documentElement.setAttribute('data-theme', newTheme);
                    console.log('收到BroadcastChannel主题变化:', newTheme);
                });
            } catch (e) {
                console.log('BroadcastChannel监听失败:', e);
            }
        }
    </script>
</body>
</html>
