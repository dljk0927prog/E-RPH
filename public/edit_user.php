<?php
// edit_user.php - 编辑用户页面
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
$user_data = null;

// 获取用户ID
$user_id = $_GET['id'] ?? 0;
if (!$user_id) {
    header('Location: user_management.php');
    exit;
}

// 获取用户信息
try {
    require_once __DIR__ . '/../db.php';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        $error = t('edit_user.error_user_not_found');
    }
} catch (Exception $e) {
    $error = t('edit_user.error_get_user_failed') . ": " . $e->getMessage();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_data) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 验证输入
    if (empty($name) || empty($email) || empty($role)) {
        $error = t('edit_user.error_all_fields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('edit_user.error_invalid_email');
    } elseif (!in_array($role, ['admin', 'teacher', 'student'])) {
        $error = t('edit_user.error_invalid_role');
    } elseif ($new_password && strlen($new_password) < 6) {
        $error = t('edit_user.error_password_length');
    } elseif ($new_password && $new_password !== $confirm_password) {
        $error = t('edit_user.error_password_mismatch');
    } else {
        try {
            // 检查邮箱是否已被其他用户使用
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = t('edit_user.error_email_exists');
            } else {
                // 更新用户信息
                if ($new_password) {
                    // 更新密码
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $role, $password_hash, $user_id]);
                } else {
                    // 不更新密码
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $role, $user_id]);
                }
                
                $message = t('edit_user.success_updated');
                
                // 重新获取用户信息
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_data = $stmt->fetch();
            }
        } catch (Exception $e) {
            $error = t('edit_user.error_update_failed') . ": " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="<?= t('common.language_code') ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('edit_user.title') ?> - ERPH</title>
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
        
        .password-note {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 5px;
            font-style: italic;
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
        
        [data-theme="dark"] .password-note {
            color: var(--text-muted);
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
        <h1><?= t('edit_user.title') ?></h1>
    </div>

    <div class="form-container">
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($user_data): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="name"><?= t('edit_user.name') ?> *</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($user_data['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email"><?= t('edit_user.email') ?> *</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="role"><?= t('edit_user.role') ?> *</label>
                    <select id="role" name="role" required>
                        <option value="admin" <?= $user_data['role'] === 'admin' ? 'selected' : '' ?>><?= t('roles.admin') ?></option>
                        <option value="teacher" <?= $user_data['role'] === 'teacher' ? 'selected' : '' ?>><?= t('roles.teacher') ?></option>
                        <option value="student" <?= $user_data['role'] === 'student' ? 'selected' : '' ?>><?= t('roles.student') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="new_password"><?= t('edit_user.new_password') ?></label>
                    <input type="password" id="new_password" name="new_password">
                    <div class="password-note"><?= t('edit_user.password_note') ?></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password"><?= t('edit_user.confirm_password') ?></label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>

                <button type="submit" class="btn"><?= t('edit_user.update_user') ?></button>
            </form>
        <?php else: ?>
            <div class="message error"><?= t('edit_user.error_cannot_load') ?></div>
        <?php endif; ?>

        <a href="user_management.php" class="back-link">← <?= t('edit_user.back_to_management') ?></a>
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
