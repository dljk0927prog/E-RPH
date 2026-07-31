





<?php
// login_background_manager.php - 登录页面背景图片管理页面
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

// 尝试连接数据库
try {
    require_once __DIR__ . '/../db.php';
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

// 检查是否登录且是管理员
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login_roles.php');
    exit;
}

$user = $_SESSION['user'];
$current_page = 'background_manager';

// 处理背景图片上传
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload':
                if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['background_image'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    
                    if (in_array($file['type'], $allowed_types)) {
                        $upload_dir = __DIR__ . '/uploads/backgrounds/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        $filename = 'login_bg_' . time() . '_' . basename($file['name']);
                        $filepath = $upload_dir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            // 保存到数据库或配置文件
                            $background_path = 'uploads/backgrounds/' . $filename;
                            $_SESSION['login_background'] = $background_path;
                            
                            // 尝试保存到数据库（如果存在）
                            try {
                                if (isset($pdo)) {
                                    // 检查是否存在背景设置表
                                    $stmt = $pdo->prepare("SHOW TABLES LIKE 'system_settings'");
                                    $stmt->execute();
                                    if ($stmt->rowCount() > 0) {
                                        // 先尝试更新，如果失败则插入
                                        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = :value, updated_at = NOW() WHERE setting_key = 'login_background'");
                                        $stmt->execute(['value' => $background_path]);
                                        
                                        if ($stmt->rowCount() === 0) {
                                            // 如果没有更新任何行，插入新记录
                                            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES ('login_background', :value, NOW())");
                                            $stmt->execute(['value' => $background_path]);
                                        }
                                    }
                                }
                            } catch (Exception $e) {
                                // 忽略数据库错误，继续使用session
                            }
                            
                            $message = t('background_manager.background_upload_success');
                            $message_type = 'success';
                        } else {
                            $message = t('background_manager.file_upload_failed');
                            $message_type = 'error';
                        }
                    } else {
                        $message = t('background_manager.unsupported_file_type');
                        $message_type = 'error';
                    }
                } else {
                    $message = t('background_manager.please_select_image_file');
                    $message_type = 'error';
                }
                break;
                
            case 'set_default':
                $_SESSION['login_background'] = 'default';
                
                // 尝试保存到数据库（如果存在）
                try {
                    if (isset($pdo)) {
                        $stmt = $pdo->prepare("SHOW TABLES LIKE 'system_settings'");
                        $stmt->execute();
                        if ($stmt->rowCount() > 0) {
                            // 先尝试更新，如果失败则插入
                            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = 'default', updated_at = NOW() WHERE setting_key = 'login_background'");
                            $stmt->execute();
                            
                            if ($stmt->rowCount() === 0) {
                                // 如果没有更新任何行，插入新记录
                                $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES ('login_background', 'default', NOW())");
                                $stmt->execute();
                            }
                        }
                    }
                } catch (Exception $e) {
                    // 忽略数据库错误，继续使用session
                }
                
                $message = t('background_manager.set_as_default_background');
                $message_type = 'success';
                break;
                
            case 'set_background':
                if (isset($_POST['background'])) {
                    $background = $_POST['background'];
                    $_SESSION['login_background'] = $background;
                    
                    // 尝试保存到数据库（如果存在）
                    try {
                        if (isset($pdo)) {
                            $stmt = $pdo->prepare("SHOW TABLES LIKE 'system_settings'");
                            $stmt->execute();
                            if ($stmt->rowCount() > 0) {
                                // 先尝试更新，如果失败则插入
                                $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = :value, updated_at = NOW() WHERE setting_key = 'login_background'");
                                $stmt->execute(['value' => $background]);
                                
                                if ($stmt->rowCount() === 0) {
                                    // 如果没有更新任何行，插入新记录
                                    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES ('login_background', :value, NOW())");
                                    $stmt->execute(['value' => $background]);
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $message = t('background_manager.database_update_failed') . $e->getMessage();
                        $message_type = 'error';
                        break;
                    }
                    
                    $message = t('background_manager.background_set_success');
                    $message_type = 'success';
                }
                break;
                
            case 'remove':
                if (isset($_POST['filename']) && $_POST['filename'] !== 'default') {
                    $filepath = __DIR__ . '/uploads/backgrounds/' . $_POST['filename'];
                    if (file_exists($filepath)) {
                        unlink($filepath);
                        if ($_SESSION['login_background'] === 'uploads/backgrounds/' . $_POST['filename']) {
                            $_SESSION['login_background'] = 'default';
                        }
                        $message = t('background_manager.background_image_deleted');
                        $message_type = 'success';
                    }
                }
                break;
        }
    }
}

// 获取当前背景设置
$current_background = 'default';

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

// 获取可用的背景图片
$backgrounds_dir = __DIR__ . '/uploads/backgrounds/';
$available_backgrounds = [];
if (is_dir($backgrounds_dir)) {
    $files = scandir($backgrounds_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $available_backgrounds[] = $file;
        }
    }
}

// 预设背景选项
$preset_backgrounds = [
    'default' => [
        'name' => t('background_manager.default_gradient'),
        'style' => 'linear-gradient(135deg, #fff 10%, #ffecec 60%)'
    ],
    'gradient_blue' => [
        'name' => t('background_manager.blue_gradient'),
        'style' => 'linear-gradient(135deg, #4a90e2 0%, #7bb3f0 50%, #a8d0ff 100%)'
    ],
    'gradient_purple' => [
        'name' => t('background_manager.purple_gradient'),
        'style' => 'linear-gradient(135deg, #9b59b6 0%, #8e44ad 50%, #a29bfe 100%)'
    ],
    'gradient_green' => [
        'name' => t('background_manager.green_gradient'),
        'style' => 'linear-gradient(135deg, #27ae60 0%, #2ecc71 50%, #55a3ff 100%)'
    ],
    'gradient_orange' => [
        'name' => t('background_manager.orange_gradient'),
        'style' => 'linear-gradient(135deg, #e67e22 0%, #f39c12 50%, #f1c40f 100%)'
    ]
];
?>
<!doctype html>
<html lang="<?= t('common.language_code') ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('background_manager.page_title') ?></title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <link rel="stylesheet" href="assets/css/background-manager.css">
  <link rel="stylesheet" href="assets/css/mobile-optimization.css">
</head>
<body>
  <header class="header">
    <h1><?= t('common.main_title') ?> - <?= t('background_manager.title') ?></h1>
    <div>
      <a href="admin_dashboard.php"><?= t('background_manager.back_to_dashboard') ?></a>
      <a href="logout.php"><?= t('background_manager.logout') ?></a>
      <button class="theme-toggle-btn" onclick="toggleTheme()" title="<?= t('background_manager.toggle_theme') ?>">
        🌙
      </button>
    </div>
  </header>

  <div class="admin-layout">
    <?php include 'inc/admin_sidebar.php'; ?>

    <main>
      <div class="page-header">
        <h2><?= t('background_manager.title') ?></h2>
        <div>
          <button class="btn btn-primary" onclick="openPreviewModal()"><?= t('background_manager.preview_login_page') ?></button>
        </div>
      </div>

      <?php if (!empty($message)): ?>
      <div class="message <?= $message_type ?>">
        <?= htmlspecialchars($message) ?>
        <?php if ($message_type === 'success' && $current_background !== 'default'): ?>
          <br><small><?= t('background_manager.current_background') ?><?= htmlspecialchars($current_background) ?></small>
          <br><small><?= t('background_manager.file_path') ?><?= __DIR__ . '/' . $current_background ?></small>
          <br><small><?= t('background_manager.file_exists') ?><?= file_exists(__DIR__ . '/' . $current_background) ? t('background_manager.yes') : t('background_manager.no') ?></small>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      
      <?php if (isset($db_error)): ?>
      <div class="message error">
        <?= t('background_manager.database_connection_error') ?><?= htmlspecialchars($db_error) ?>
      </div>
      <?php endif; ?>
      
      <?php if (isset($pdo)): ?>
      <div class="message success" style="margin-bottom: 20px;">
        ✅ <?= t('background_manager.database_connection_normal') ?>
        <br><small><?= t('background_manager.current_background') ?><?= htmlspecialchars($current_background) ?></small>
      </div>
      <?php endif; ?>

      <div class="upload-section">
        <h3><?= t('background_manager.upload_new_background') ?></h3>
        <form class="upload-form" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload">
          <div class="file-input">
            <input type="file" name="background_image" accept="image/*" required>
          </div>
          <button type="submit" class="upload-btn"><?= t('background_manager.upload_background_image') ?></button>
        </form>
        <p style="margin-top: 10px; color: var(--text-muted); font-size: 14px;">
          <?= t('background_manager.supported_formats') ?>
        </p>
      </div>

      <div class="preset-backgrounds">
        <h3><?= t('background_manager.preset_backgrounds') ?></h3>
        <div class="preset-grid">
          <?php foreach ($preset_backgrounds as $key => $preset): ?>
          <div class="preset-item <?= $current_background === $key ? 'current' : '' ?>">
            <div class="preset-preview" data-preset="<?= $key ?>" style="background: <?= $preset['style'] ?>"></div>
            <div class="preset-info">
              <div class="preset-name"><?= htmlspecialchars($preset['name']) ?></div>
              <div class="preset-actions">
                <button class="btn btn-primary" onclick="setBackground('<?= $key ?>')">
                  <?= $current_background === $key ? t('background_manager.current_background') : t('common.apply') ?>
                </button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="backgrounds-grid">
        <h3><?= t('background_manager.uploaded_backgrounds') ?></h3>
        <?php if (empty($available_backgrounds)): ?>
          <div style="text-align:center;padding:40px;color:var(--text-muted);">
            <?= t('background_manager.no_uploaded_backgrounds') ?>
          </div>
        <?php else: ?>
          <div class="backgrounds-list">
            <?php foreach ($available_backgrounds as $filename): ?>
            <div class="background-item <?= $current_background === 'uploads/backgrounds/' . $filename ? 'current' : '' ?>">
              <div class="background-preview" style="background-image: url('uploads/backgrounds/<?= htmlspecialchars($filename) ?>')"></div>
              <div class="background-info">
                <div class="background-name"><?= htmlspecialchars($filename) ?></div>
                <div class="background-actions">
                  <button class="btn btn-primary" onclick="setBackground('uploads/backgrounds/<?= htmlspecialchars($filename) ?>')">
                    <?= t('background_manager.set_as_current_background') ?>
                  </button>
                  <button class="btn btn-danger" onclick="removeBackground('<?= htmlspecialchars($filename) ?>')">
                    <?= t('background_manager.delete') ?>
                  </button>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- 预览弹窗 -->
  <div id="previewModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3><?= t('background_manager.login_page_preview') ?></h3>
        <span class="close" onclick="closePreviewModal()">&times;</span>
      </div>
      <div class="modal-body">
        <div class="preview-container">
          <div class="login-preview" id="loginPreview">
            <!-- 这里将显示登录页面的预览 -->
            <div class="preview-login-card">
              <div class="preview-language-switch">
                <span class="preview-lang-label"><?= t('background_manager.language') ?></span>
                <span class="preview-lang-btn active"><?= t('background_manager.chinese') ?></span>
                <span class="preview-lang-btn"><?= t('background_manager.english') ?></span>
              </div>
              <h2 class="preview-title"><?= t('background_manager.erph_system_login') ?></h2>
              <div class="preview-form">
                <div class="preview-role-selector">
                  <label><?= t('background_manager.select_role') ?></label>
                  <div class="preview-role-btns">
                    <span class="preview-role-btn active"><?= t('background_manager.administrator') ?></span>
                    <span class="preview-role-btn"><?= t('background_manager.teacher') ?></span>
                  </div>
                </div>
                <div class="preview-input-group">
                  <label><?= t('background_manager.email') ?></label>
                  <input type="email" placeholder="<?= t('background_manager.enter_email_address') ?>" disabled>
                </div>
                <div class="preview-input-group">
                  <label><?= t('background_manager.password') ?></label>
                  <input type="password" placeholder="<?= t('background_manager.enter_password') ?>" disabled>
                </div>
                <button class="preview-login-btn" disabled><?= t('background_manager.login') ?></button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="closePreviewModal()"><?= t('background_manager.close') ?></button>
      </div>
    </div>
  </div>

  <script>
    // 主题切换功能
    function toggleTheme() {
      const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      
      // 设置主题
      document.documentElement.setAttribute('data-theme', newTheme);
      
      // 更新按钮图标
      const themeBtn = document.querySelector('.theme-toggle-btn');
      themeBtn.innerHTML = newTheme === 'light' ? '🌙' : '☀️';
      themeBtn.title = newTheme === 'light' ? '<?= t('background_manager.switch_to_dark_mode') ?>' : '<?= t('background_manager.switch_to_light_mode') ?>';
      
      // 保存到sessionStorage
      sessionStorage.setItem('theme', newTheme);
      
      // 发送到服务器保存
      fetch('change_theme.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'theme=' + newTheme
      }).then(response => response.json())
      .then(data => {
        if (data.success) {
          console.log('主题切换成功:', newTheme);
        } else {
          console.error('主题切换失败:', data.error);
        }
      }).catch(error => {
        console.error('主题切换请求失败:', error);
      });
    }
    
    // 设置背景
    function setBackground(background) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.innerHTML = `
        <input type="hidden" name="action" value="set_background">
        <input type="hidden" name="background" value="${background}">
      `;
      document.body.appendChild(form);
      form.submit();
    }
    
    // 删除背景
    function removeBackground(filename) {
      if (confirm('<?= t('background_manager.confirm_delete_background') ?>')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
          <input type="hidden" name="action" value="remove">
          <input type="hidden" name="filename" value="${filename}">
        `;
        document.body.appendChild(form);
        form.submit();
      }
    }
    
    // 页面加载时恢复主题
    function initTheme() {
      const savedTheme = sessionStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', savedTheme);
      
      // 更新按钮图标
      const themeBtn = document.querySelector('.theme-toggle-btn');
      themeBtn.innerHTML = savedTheme === 'light' ? '🌙' : '☀️';
      themeBtn.title = savedTheme === 'light' ? '<?= t('background_manager.switch_to_dark_mode') ?>' : '<?= t('background_manager.switch_to_light_mode') ?>';
    }
    
    // 页面加载完成后初始化主题
    document.addEventListener('DOMContentLoaded', function() {
      initTheme();
    });
    
    // 弹窗预览功能
    function openPreviewModal() {
      const modal = document.getElementById('previewModal');
      modal.style.display = 'block';
      
      // 应用当前背景到预览区域
      applyBackgroundToPreview();
      
      // 阻止背景滚动
      document.body.style.overflow = 'hidden';
    }
    
    function closePreviewModal() {
      const modal = document.getElementById('previewModal');
      modal.style.display = 'none';
      
      // 恢复背景滚动
      document.body.style.overflow = 'auto';
    }
    
    function applyBackgroundToPreview() {
      const previewContainer = document.getElementById('loginPreview');
      const currentBackground = '<?= $current_background ?>';
      
      if (currentBackground !== 'default') {
        // 检查是否是预设背景
        const presetBackgrounds = <?= json_encode($preset_backgrounds) ?>;
        if (presetBackgrounds[currentBackground]) {
          // 预设背景
          previewContainer.style.background = presetBackgrounds[currentBackground].style;
          previewContainer.style.backgroundImage = 'none';
        } else {
          // 自定义图片背景
          previewContainer.style.backgroundImage = `url('${currentBackground}')`;
          previewContainer.style.backgroundSize = 'cover';
          previewContainer.style.backgroundPosition = 'center';
          previewContainer.style.backgroundRepeat = 'no-repeat';
        }
      } else {
        // 默认背景
        previewContainer.style.background = 'linear-gradient(135deg, #fff 10%, #ffecec 60%)';
        previewContainer.style.backgroundImage = 'none';
      }
    }
    
    // 点击弹窗外部关闭弹窗
    window.onclick = function(event) {
      const modal = document.getElementById('previewModal');
      if (event.target === modal) {
        closePreviewModal();
      }
    }
    
    // ESC键关闭弹窗
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closePreviewModal();
      }
    });
  </script>
</body>
</html>
