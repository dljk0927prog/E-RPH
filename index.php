<?php
require_once __DIR__ . '/public/inc/session_config.php';
require_once __DIR__ . '/public/inc/language_config.php';

// 尝试连接数据库读取背景设置
$current_background = 'default';

// 预设背景样式
$preset_backgrounds = [
    'default' => 'linear-gradient(135deg, #4a90e2 0%, #7bb3f0 50%, #a8d0ff 100%)',
    'gradient_blue' => 'linear-gradient(135deg, #4a90e2 0%, #7bb3f0 50%, #a8d0ff 100%)',
    'gradient_purple' => 'linear-gradient(135deg, #9b59b6 0%, #8e44ad 50%, #a29bfe 100%)',
    'gradient_green' => 'linear-gradient(135deg, #27ae60 0%, #2ecc71 50%, #55a3ff 100%)',
    'gradient_orange' => 'linear-gradient(135deg, #e67e22 0%, #f39c12 50%, #f1c40f 100%)'
];

try {
    require_once __DIR__ . '/db.php';
    
    // 检查是否存在背景设置表
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'system_settings'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'login_background' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();
            if ($result && $result['setting_value'] !== 'default') {
                $current_background = $result['setting_value'];
            }
        }
    }
} catch (Exception $e) {
    // 忽略数据库错误，继续使用默认背景
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= t('home.title') ?></title>
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
              echo "background: url('public/" . htmlspecialchars($current_background) . "') no-repeat center center fixed;";
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

    /* index 页面版权固定在底部，不参与中间区域布局 */
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

    .erph-index-footer-text {
      display: inline-block;
      padding: 10px 16px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.2);
      color: #ffffff;
      font-size: 13px;
      line-height: 1.4;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }
    
    .enter-btn {
      display: inline-block;
      background: rgba(74, 144, 226, 0.8);
      color: white;
      padding: 25px 60px;
      font-size: 22px;
      text-decoration: none;
      border-radius: 60px;
      font-weight: bold;
      transition: all 0.3s ease;
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.25);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      min-width: 200px;
      text-align: center;
    }
    
    .enter-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 16px 45px rgba(0, 0, 0, 0.35);
      background: rgba(74, 144, 226, 0.9);
    }
    
    .enter-btn:active {
      transform: translateY(-1px);
    }
    
    @media (max-width: 480px) {
      .enter-btn {
        padding: 20px 50px;
        font-size: 20px;
        min-width: 180px;
      }
    }
  </style>
</head>
<body>
  <a href="public/login_roles.php" class="enter-btn">
    <?= t('home.enter_system') ?>
  </a>
  <div class="erph-global-footer-wrap">
    <div class="erph-index-footer-text">Copyright &copy; 2026 Desmond Liew. All Rights Reserved.</div>
  </div>
</body>
</html>
