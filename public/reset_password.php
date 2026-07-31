<?php
// reset_password.php - 密码重置页面
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif ($new_password !== $confirm_password) {
        $error = "The passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        try {
            require_once __DIR__ . '/../db.php';
            
            // 检查用户是否存在
            $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // 生成新的密码哈希
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // 更新密码
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->execute([$hashed_password, $email]);
                
                $message = "Password reset successful! The password for user " . ($user['name'] ?? 'Unknown') . " (" . ($user['role'] ?? 'Unknown') . ") has been updated.";
            } else {
                $error = "User not found";
            }
        } catch (Exception $e) {
            $error = "System error: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="<?= t('common.language_code') ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
    <meta charset="utf-8">
    <title>Reset Password - ERPH</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme-sync.js"></script>
    <style>
        .reset-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .reset-header h1 {
            color: #4a90e2;
            margin-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #4a90e2;
        }
        .btn {
            width: 100%;
            background: linear-gradient(135deg, #4a90e2, #7bb3f0);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #4a90e2;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>Reset Password</h1>
            <p>Please enter your email and new password</p>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn">Reset Password</button>
        </form>

        <a href="login_roles.php" class="back-link">← Back to Login Page</a>
    </div>

    <script src="assets/js/theme-sync.js"></script>
</body>
</html>
