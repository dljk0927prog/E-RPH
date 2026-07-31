<?php
// login_roles.php - 支持角色的登录系统
ob_start();
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

// 语言切换后去掉 URL 中的 lang 参数，避免重复处理
if (isset($_GET['lang']) && in_array($_GET['lang'], ['zh', 'en'], true)) {
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 如果已经登录，重定向到仪表板
if (isset($_SESSION['user'])) {
    ob_end_clean();
    if ($_SESSION['user']['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: teacher_dashboard.php');
    }
    exit;
}

// 尝试连接数据库
try {
    require_once __DIR__ . '/../db.php';
} catch (Exception $e) {
    $error = t('login.error_database') . ": " . $e->getMessage();
}

// 获取当前背景设置
$current_background = 'default';

// 预设背景样式
$preset_backgrounds = [
    'default' => 'linear-gradient(135deg, #fff 10%, #ffecec 60%)',
    'gradient_blue' => 'linear-gradient(135deg, #4a90e2 0%, #7bb3f0 50%, #a8d0ff 100%)',
    'gradient_purple' => 'linear-gradient(135deg, #9b59b6 0%, #8e44ad 50%, #a29bfe 100%)',
    'gradient_green' => 'linear-gradient(135deg, #27ae60 0%, #2ecc71 50%, #55a3ff 100%)',
    'gradient_orange' => 'linear-gradient(135deg, #e67e22 0%, #f39c12 50%, #f1c40f 100%)'
];

// 优先从数据库读取背景设置
try {
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'system_settings'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'login_background' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();
            if ($result) {
                $current_background = $result['setting_value'];
                // 同步到session
                $_SESSION['login_background'] = $current_background;
            }
        }
    }
} catch (Exception $e) {
    // 忽略数据库错误，继续使用session
}

// 如果数据库中没有，则从session读取
if ($current_background === 'default' && isset($_SESSION['login_background'])) {
    $current_background = $_SESSION['login_background'];
}

$error = '';
$account = 'admin';
$selected_role = 'admin';
$password_prefill = 'admin123';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $account = trim($_POST['account'] ?? '');
    $pass = $_POST['password'] ?? '';
    $selected_role = $_POST['role'] ?? '';
    $password_prefill = $pass;

    // 输入验证
    if (empty($account) || empty($pass) || empty($selected_role)) {
        $error = t('login.error_all_fields');
    } elseif (!in_array($selected_role, ['admin', 'teacher'])) {
        $error = t('login.error_invalid_role');
    } else {
        try {
            // 支持邮箱、姓名或邮箱前缀（@ 前）登录
            $stmt = $pdo->prepare("
                SELECT id, name, password, role, avatar
                FROM users
                WHERE role = :role
                  AND (
                    email = :account_email
                    OR name = :account_name
                    OR SUBSTRING_INDEX(email, '@', 1) = :account_prefix
                  )
                LIMIT 1
            ");
            $stmt->execute([
                'role' => $selected_role,
                'account_email' => $account,
                'account_name' => $account,
                'account_prefix' => $account,
            ]);
            $user = $stmt->fetch();

            if ($user && password_verify($pass, $user['password'])) {
                // 刷新会话ID
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'avatar' => $user['avatar'] // 添加头像信息
                ];

                // 记录登录时间
                try {
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                    $stmt->execute(['id' => $user['id']]);
                } catch (PDOException $e) {
                    // 忽略字段不存在的错误
                    if (strpos($e->getMessage(), 'last_login') === false) {
                        throw $e;
                    }
                }

                // 重定向到对应仪表板
                ob_end_clean();
                if ($user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: teacher_dashboard.php');
                }
                exit;
            } else {
                $error = t('login.error_login_failed');
            }
        } catch (PDOException $e) {
            $error = t('login.error_system');
        }
    }
}

// 结束输出缓冲并显示页面
ob_end_flush();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('login.title') ?></title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Microsoft YaHei', Arial, sans-serif;
      <?php 
      if ($current_background !== 'default') {
          // 检查是否是预设背景
          if (isset($preset_backgrounds[$current_background])) {
              echo "background: " . $preset_backgrounds[$current_background] . ";";
          } else {
              echo "background: url('" . htmlspecialchars($current_background) . "') no-repeat center center fixed;";
              echo "background-size: cover;";
          }
      } else {
          echo "background: " . $preset_backgrounds['default'] . ";";
      }
      ?>
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      padding-bottom: 84px;
    }
    
    .login-card {
      background: #fff;
      padding: 40px;
      border-radius: 16px;
      width: 400px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    }
    
    .login-card h2 {
      margin-bottom: 30px;
      color: #4a90e2;
      text-align: center;
      font-size: 24px;
    }
    
    .role-selector {
      margin-bottom: 20px;
      text-align: center;
    }
    
    .role-btn {
      display: inline-block;
      padding: 10px 20px;
      margin: 0 5px;
      border: 2px solid #ddd;
      border-radius: 25px;
      background: #fff;
      color: #666;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .role-btn.active {
      border-color: #4a90e2;
      background: #4a90e2;
      color: #fff;
    }
    
    .role-btn:hover {
      border-color: #4a90e2;
      color: #4a90e2;
    }
    
    .role-btn.active:hover {
      color: #fff;
    }
    
    label {
      display: block;
      margin-bottom: 8px;
      font-size: 14px;
      color: #333;
      font-weight: 500;
    }
    
    label input, label select {
      display: block;
      margin-top: 6px;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #ddd;
      width: 100%;
      font-size: 14px;
    }
    
    .btn {
      background: linear-gradient(45deg, #4a90e2, #7bb3f0);
      color: #fff;
      padding: 14px;
      border-radius: 8px;
      border: none;
      margin-top: 20px;
      cursor: pointer;
      font-weight: 600;
      width: 100%;
      font-size: 16px;
      transition: all 0.3s ease;
    }
    
    .btn:hover {
      background: linear-gradient(45deg, #7bb3f0, #a8d0ff);
      transform: translateY(-2px);
    }
    
    .error {
      background: #f0f8ff;
      border-left: 4px solid #4a90e2;
      padding: 12px;
      margin-bottom: 20px;
      border-radius: 6px;
      color: #4a90e2;
      font-size: 14px;
    }
    
    .login-footer {
      margin-top: 25px;
      text-align: center;
      font-size: 14px;
      color: #666;
    }
    
    .login-footer a {
      color: #4a90e2;
      text-decoration: none;
    }
    
    .login-footer a:hover {
      text-decoration: underline;
    }
    
    /* 语言切换按钮样式优化 */
    .language-switch {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      margin-bottom: 20px;
      justify-content: center;
    }
    
    .language-label {
      color: #666;
      font-size: 13px;
    }
    
    .lang-btn {
      padding: 6px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      color: #666;
      text-decoration: none;
      font-size: 12px;
      transition: all 0.3s ease;
      background: #f8f9fa;
    }
    
    .lang-btn:hover {
      background: #e9ecef;
      color: #4a90e2;
      border-color: #4a90e2;
    }
    
    .lang-btn.active {
      background: #4a90e2;
      color: white;
      border-color: #4a90e2;
      font-weight: 600;
    }

    .erph-global-footer-wrap {
      position: fixed;
      left: 0;
      right: 0;
      bottom: 10px;
      margin-top: 0;
      padding: 0 12px;
      z-index: 10;
      text-align: center;
    }

    .erph-login-footer-text {
      display: inline-block;
      padding: 10px 16px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.92);
      color: #4f5b66;
      font-size: 13px;
      line-height: 1.4;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
    }
  </style>
</head>
<body>
  <div class="login-card">
    <?= renderLanguageSwitch(true) ?>
    
    <!-- debug info -->
    <?php if (isset($_GET['debug'])): ?>
    <div style="background: #f0f8ff; border: 1px solid #4a90e2; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 12px;">
      <strong>Debug:</strong><br>
      DB background setting: <?= htmlspecialchars($current_background) ?><br>
      Session background setting: <?= htmlspecialchars($_SESSION['login_background'] ?? 'Not set') ?><br>
      File exists: <?= file_exists(__DIR__ . '/' . $current_background) ? 'Yes' : 'No' ?><br>
      File path: <?= htmlspecialchars(__DIR__ . '/' . $current_background) ?>
    </div>
    <?php endif; ?>
    
    <h2><?= t('login.title') ?></h2>
    
    <?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="post" id="loginForm">
      <div class="role-selector">
        <label><?= t('login.select_role') ?>：</label>
        <div>
          <input type="radio" name="role" value="admin" id="role_admin" class="role-input" <?= $selected_role === 'admin' ? 'checked' : '' ?>>
          <label for="role_admin" class="role-btn <?= $selected_role === 'admin' ? 'active' : '' ?>"><?= t('login.admin') ?></label>
          
          <input type="radio" name="role" value="teacher" id="role_teacher" class="role-input" <?= $selected_role === 'teacher' ? 'checked' : '' ?>>
          <label for="role_teacher" class="role-btn <?= $selected_role === 'teacher' ? 'active' : '' ?>"><?= t('login.teacher') ?></label>
        </div>
      </div>
      
      <label><?= t('login.account') ?><input type="text" name="account" value="<?= htmlspecialchars($account) ?>" autocomplete="username" required></label>
      <label><?= t('login.password') ?><input type="password" name="password" value="<?= htmlspecialchars($password_prefill) ?>" required></label>
      
      <button type="submit" class="btn"><?= t('login.login_button') ?></button>
    </form>
    
    <div class="login-footer">
      <p><?= t('login.forgot_password') ?></p>
      <p><a href="../index.php"><?= t('login.back_to_home') ?></a></p>
    </div>
  </div>
  <div class="erph-global-footer-wrap">
    <div class="erph-login-footer-text">Copyright &copy; 2026 Desmond Liew. All Rights Reserved.</div>
  </div>

  <script>
    // 角色选择交互
    document.querySelectorAll('.role-input').forEach(input => {
      input.addEventListener('change', function() {
        document.querySelectorAll('.role-btn').forEach(btn => {
          btn.classList.remove('active');
        });
        if (this.checked) {
          this.nextElementSibling.classList.add('active');
        }
      });
    });
    
    // 表单验证
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const role = document.querySelector('input[name="role"]:checked');
      if (!role) {
        e.preventDefault();
        alert('<?= t('login.please_select_role') ?>');
        return false;
      }
    });
  </script>
</body>
</html>
