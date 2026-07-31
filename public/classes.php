<?php
// classes.php - 班级管理（管理员）
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login_roles.php');
    exit;
}

require_once __DIR__ . '/../db.php';
$msg = '';
$error = '';
$current_page = 'classes';

// 处理删除班级
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    
    // 添加调试信息
    error_log("尝试删除班级ID: " . $delete_id);
    
    try {
        // 检查班级是否被使用
        $check_stmt = $pdo->prepare('SELECT COUNT(*) FROM lesson_plans WHERE class_id = ?');
        $check_stmt->execute([$delete_id]);
        $usage_count = $check_stmt->fetchColumn();
        
        error_log("班级使用次数: " . $usage_count);
        
        if ($usage_count > 0) {
            $error = str_replace('{count}', $usage_count, t('classes.cannot_delete_used'));
            error_log("删除失败: " . $error);
        } else {
            $stmt = $pdo->prepare('DELETE FROM classes WHERE id = ?');
            $stmt->execute([$delete_id]);
            
            if ($stmt->rowCount() > 0) {
                $msg = t('classes.delete_success');
                error_log("班级删除成功: " . $delete_id);
            } else {
                $error = t('classes.delete_not_found');
                error_log("删除失败: 班级不存在");
            }
        }
    } catch (Throwable $e) {
        $error = t('classes.delete_failed') . '：' . $e->getMessage();
        error_log("删除异常: " . $e->getMessage());
    }
}

// 处理新增班级
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $name = trim($_POST['name'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '') {
        $error = t('classes.enter_name');
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO classes (name, is_active) VALUES (?, ?)');
            $stmt->execute([$name, $is_active]);
            $msg = t('classes.create_success');
        } catch (Throwable $e) {
            $error = t('classes.create_failed') . '：' . $e->getMessage();
        }
    }
}

// 读取班级列表
try {
    $classes = $pdo->query('SELECT id, name, is_active, created_at FROM classes ORDER BY created_at DESC')->fetchAll();
} catch (Throwable $e) {
    $classes = [];
    $error = $error ?: (t('classes.query_failed') . '：' . $e->getMessage());
}
?>
<!doctype html>
<html lang="<?= t('common.language_code') ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('classes.title') ?> - ERPH</title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <link rel="stylesheet" href="assets/css/mobile-optimization.css">
  <style>
    /* 深色模式CSS变量 */
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
    
    /* 深色模式样式 */
    [data-theme="dark"] {
      --bg-primary: #0f1419 !important;
      --bg-secondary: #1e2328 !important;
      --text-primary: #ffffff !important;
      --text-secondary: #b0b7c3 !important;
      --text-muted: #7a8288 !important;
      --border-color: #2d3748 !important;
      --shadow-color: rgba(0, 0, 0, 0.4) !important;
      --accent-color: #60a5fa !important;
      --accent-hover: #93c5fd !important;
      --header-bg: linear-gradient(90deg, #4a90e2, #7bb3f0) !important;
      --card-border: #60a5fa !important;
      --success-bg: #065f46 !important;
      --success-text: #d1fae5 !important;
      --success-border: #047857 !important;
      --error-bg: #7f1d1d !important;
      --error-text: #fecaca !important;
      --error-border: #dc2626 !important;
      --warning-bg: #92400e !important;
      --warning-text: #fed7aa !important;
      --warning-border: #ea580c !important;
      --card-hover-bg: #2a2f35 !important;
      --card-hover-shadow: rgba(0, 0, 0, 0.5) !important;
    }
    
    body {
      font-family: 'Microsoft YaHei', Arial, sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      transition: background-color 0.3s ease, color 0.3s ease;
      margin: 0;
      padding: 0;
    }
    
    /* Header完全保护 - 最高优先级，不受深色模式影响 */
    .header {
      background: var(--header-bg);
      color: white;
      padding: 14px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 10px var(--shadow-color);
      transition: background 0.3s ease;
    }
    
    .header h1 {
      font-size: 20px;
      font-weight: 600;
      margin: 0;
      text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    
    .header a {
      color: white;
      text-decoration: none;
      background: rgba(255,255,255,0.2);
      padding: 8px 12px;
      border-radius: 6px;
      margin-left: 8px;
      transition: all 0.2s ease;
    }
    
    .header a:hover {
      background: rgba(255,255,255,0.3);
      transform: translateY(-1px);
    }
    
    /* 主题切换按钮 */
    .theme-toggle-btn {
      background: rgba(255,255,255,0.2);
      color: white;
      border: 1px solid rgba(255,255,255,0.3);
      border-radius: 6px;
      padding: 8px 12px;
      cursor: pointer;
      font-size: 16px;
      transition: all 0.2s ease;
      margin-left: 8px;
    }
    
    .theme-toggle-btn:hover {
      background: rgba(255,255,255,0.3);
      border-color: rgba(255,255,255,0.5);
      transform: translateY(-1px);
    }
    
    /* 深色模式页面整体美化 */
    [data-theme="dark"] body {
      background: linear-gradient(135deg, var(--bg-primary), #1a1f24) !important;
      background-attachment: fixed !important;
    }
    
    [data-theme="dark"] .admin-layout {
      background: transparent !important;
    }
    
    [data-theme="dark"] main {
      background: transparent !important;
    }
    
    /* 深色模式卡片美化 */
    [data-theme="dark"] .page-header,
    [data-theme="dark"] .classes-form,
    [data-theme="dark"] .classes-list {
      background: linear-gradient(135deg, var(--bg-secondary), var(--card-hover-bg)) !important;
      border: 1px solid var(--border-color) !important;
      box-shadow: 0 8px 25px var(--shadow-color) !important;
      backdrop-filter: blur(10px) !important;
    }
    
    [data-theme="dark"] .page-header:hover,
    [data-theme="dark"] .classes-form:hover,
    [data-theme="dark"] .classes-list:hover {
      background: var(--card-hover-bg) !important;
      transform: translateY(-2px) !important;
      box-shadow: 0 8px 25px var(--card-hover-shadow) !important;
      border-color: var(--accent-color) !important;
    }
    
    /* 深色模式侧边栏美化 */
    [data-theme="dark"] .admin-sidebar {
      background: var(--bg-secondary) !important;
      border: 1px solid var(--border-color) !important;
      box-shadow: 0 6px 18px var(--shadow-color) !important;
    }
    
    [data-theme="dark"] .admin-sidebar .brand {
      color: var(--text-primary) !important;
    }
    
    [data-theme="dark"] .admin-sidebar .menu-title {
      color: var(--text-secondary) !important;
    }
    
    [data-theme="dark"] .admin-sidebar .menu a {
      color: var(--text-secondary) !important;
      background: transparent !important;
      border: none !important;
      box-shadow: none !important;
    }
    
    [data-theme="dark"] .admin-sidebar .menu a:hover {
      background: var(--card-hover-bg) !important;
      color: var(--text-primary) !important;
    }
    
    [data-theme="dark"] .admin-sidebar .menu a.active {
      background: var(--card-hover-bg) !important;
      color: var(--accent-color) !important;
      box-shadow: none !important;
      border-left: 3px solid var(--accent-color) !important;
    }
    
    [data-theme="dark"] .admin-sidebar .menu a .icon {
      color: var(--text-secondary) !important;
    }
    
    [data-theme="dark"] .admin-sidebar .menu a.active .icon {
      color: var(--accent-color) !important;
    }
    
    /* 浅色模式侧边栏美化 */
    .admin-sidebar {
      background: white !important;
      border: 1px solid #e1e5e9 !important;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08) !important;
    }
    
    .admin-sidebar .brand {
      color: #333333 !important;
    }
    
    .admin-sidebar .menu-title {
      color: #666666 !important;
    }
    
    .admin-sidebar .menu a {
      color: #666666 !important;
      background: transparent !important;
      border: none !important;
      box-shadow: none !important;
    }
    
    .admin-sidebar .menu a:hover {
      background: #f8f9fa !important;
      color: #333333 !important;
    }
    
    .admin-sidebar .menu a.active {
      background: #f8f9fa !important;
      color: #4a90e2 !important;
      box-shadow: none !important;
      border-left: 3px solid #4a90e2 !important;
    }
    
    .admin-sidebar .menu a .icon {
      color: #666666 !important;
    }
    
    .admin-sidebar .menu a.active .icon {
      color: #4a90e2 !important;
    }
    
    /* 页面头部样式 */
    .page-header {
      background: var(--bg-secondary);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      box-shadow: 0 4px 15px var(--shadow-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .page-header h2 {
      color: var(--accent-color);
      margin: 0;
    }
    
    /* 表单样式 */
    .classes-form {
      background: var(--bg-secondary);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      box-shadow: 0 4px 15px var(--shadow-color);
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      color: var(--text-primary);
      font-weight: 500;
    }
    
    .form-group input[type="text"],
    .form-group input[type="checkbox"] {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 14px;
      background: var(--bg-secondary);
      color: var(--text-primary);
    }
    
    .form-group input[type="checkbox"] {
      width: auto;
    }
    
    .submit-btn {
      background: var(--accent-color);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s ease;
    }
    
    .submit-btn:hover {
      background: var(--accent-hover);
      transform: translateY(-1px);
    }
    
    /* 班级列表样式 */
    .classes-list {
      background: var(--bg-secondary);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px var(--shadow-color);
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    
    th, td {
      padding: 14px;
      border-bottom: 1px solid var(--border-color);
      text-align: left;
    }
    
    th {
      background: #f8f9fa;
      color: var(--accent-color);
      font-weight: 600;
    }
    
    [data-theme="dark"] th {
      background: var(--bg-primary) !important;
      color: var(--accent-color) !important;
    }
    
    .status-active {
      background: var(--success-bg);
      color: var(--success-text);
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
    }
    
    .status-inactive {
      background: var(--error-bg);
      color: var(--error-text);
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
    }
    
    .action-buttons {
      display: flex;
      gap: 8px;
    }
    
    .edit-btn, .delete-btn {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      font-size: 12px;
      text-decoration: none;
      display: inline-block;
      cursor: pointer;
    }
    
    .edit-btn {
      background: var(--accent-color);
      color: white;
    }
    
    .delete-btn {
      background: #f44336;
      color: white;
    }
    
    .delete-btn:hover {
      background: #d32f2f;
      transform: translateY(-1px);
      box-shadow: 0 2px 8px rgba(244, 67, 54, 0.3);
    }
    
    /* 消息样式 */
    .message {
      padding: 12px 16px;
      border-radius: 6px;
      margin-bottom: 20px;
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
    
    /* 弹窗样式 */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(5px);
    }
    
    .modal-content {
      background: var(--bg-secondary);
      margin: 5% auto;
      padding: 0;
      border-radius: 12px;
      width: 90%;
      max-width: 600px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
      from {
        opacity: 0;
        transform: translateY(-50px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .modal-header {
      background: var(--accent-color);
      color: white;
      padding: 20px;
      border-radius: 12px 12px 0 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .modal-header h3 {
      margin: 0;
      font-size: 20px;
      font-weight: 600;
    }
    
    .close {
      color: white;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      line-height: 1;
      transition: all 0.2s ease;
    }
    
    .close:hover {
      opacity: 0.7;
      transform: scale(1.1);
    }
    
    .modal-body {
      padding: 30px;
    }
    
    .form-actions {
      display: flex;
      gap: 15px;
      margin-top: 25px;
      justify-content: flex-end;
    }
    
    .cancel-btn {
      background: var(--text-secondary);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s ease;
    }
    
    .cancel-btn:hover {
      background: var(--text-muted);
      transform: translateY(-1px);
    }
    
    /* 深色模式弹窗样式 */
    [data-theme="dark"] .modal-content {
      background: var(--bg-secondary) !important;
      border: 1px solid var(--border-color) !important;
    }
  </style>
</head>
<body>
  <header class="header">
    <h1>ERPH 系统 - <?= t('classes.title') ?></h1>
    <div>
      <a href="admin_dashboard.php"><?= t('common.back') ?><?= t('common.dashboard') ?></a>
      <a href="logout.php"><?= t('common.logout') ?></a>
      <button class="theme-toggle-btn" onclick="toggleTheme()" title="<?= t('common.toggle_theme') ?>">
        🌙
      </button>
    </div>
  </header>

  <div class="admin-layout">
    <?php include 'inc/admin_sidebar.php'; ?>

    <main>
      <div class="page-header">
        <h2><?= t('classes.title') ?></h2>
      </div>

      <?php if ($msg || isset($_SESSION['msg'])): ?>
        <div class="message success"><?= htmlspecialchars($msg ?: $_SESSION['msg']) ?></div>
        <?php unset($_SESSION['msg']); ?>
      <?php endif; ?>

      <?php if ($error || isset($_SESSION['error'])): ?>
        <div class="message error"><?= htmlspecialchars($error ?: $_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <div class="classes-form">
        <form method="POST">
          <div class="form-group">
            <label for="name"><?= t('classes.class_name') ?>:</label>
            <input type="text" id="name" name="name" required>
          </div>
          <div class="form-group">
            <label>
              <input type="checkbox" name="is_active" checked> <?= t('common.enable') ?>
            </label>
          </div>
          <button type="submit" name="create" class="submit-btn"><?= t('classes.create_class') ?></button>
        </form>
      </div>

      <div class="classes-list">
        <?php if (empty($classes)): ?>
          <div style="text-align:center;padding:40px;color:#666;"><?= t('classes.no_data') ?></div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th><?= t('classes.class_name') ?></th>
                <th><?= t('common.status') ?></th>
                <th><?= t('common.created_at') ?></th>
                <th><?= t('common.action') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($classes as $class): ?>
              <tr>
                <td><?= htmlspecialchars($class['id']) ?></td>
                <td><?= htmlspecialchars($class['name']) ?></td>
                <td>
                  <span class="status-<?= $class['is_active'] ? 'active' : 'inactive' ?>">
                    <?= $class['is_active'] ? t('common.enabled') : t('common.disabled') ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($class['created_at']) ?></td>
                <td>
                  <div class="action-buttons">
                    <button class="edit-btn" onclick="editClass(<?= $class['id'] ?>, '<?= htmlspecialchars($class['name']) ?>', <?= $class['is_active'] ?>)"><?= t('common.edit') ?></button>
                    <button class="delete-btn" onclick="deleteClass(<?= $class['id'] ?>)"><?= t('common.delete') ?></button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- 编辑弹窗 -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3><?= t('common.edit') ?> <?= t('classes.title') ?></h3>
        <span class="close" onclick="closeEditModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form method="POST" id="editForm" action="">
          <input type="hidden" id="edit_id" name="edit_id">
          <div class="form-group">
            <label for="edit_name"><?= t('classes.class_name') ?>:</label>
            <input type="text" id="edit_name" name="name" required>
          </div>
          <div class="form-group">
            <label>
              <input type="checkbox" id="edit_is_active" name="is_active"> <?= t('common.enable') ?>
            </label>
          </div>
          <div class="form-actions">
            <button type="submit" class="submit-btn"><?= t('common.save') ?></button>
            <button type="button" class="cancel-btn" onclick="closeEditModal()"><?= t('common.cancel') ?></button>
          </div>
        </form>
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
      themeBtn.title = newTheme === 'light' ? '<?= t('common.switch_to_dark') ?>' : '<?= t('common.switch_to_light') ?>';
      
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
    
    // 页面加载时恢复主题
    function initTheme() {
      const savedTheme = sessionStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', savedTheme);
      
      // 更新按钮图标
      const themeBtn = document.querySelector('.theme-toggle-btn');
      themeBtn.innerHTML = savedTheme === 'light' ? '🌙' : '☀️';
      themeBtn.title = savedTheme === 'light' ? '<?= t('common.switch_to_dark') ?>' : '<?= t('common.switch_to_light') ?>';
    }
    
    // 页面加载完成后初始化主题
    document.addEventListener('DOMContentLoaded', function() {
      initTheme();
    });
    
    // 点击弹窗外部关闭弹窗
    window.onclick = function(event) {
      const modal = document.getElementById('editModal');
      if (event.target === modal) {
        closeEditModal();
      }
    }
    
    function editClass(classId, name, isActive) {
      console.log('编辑班级:', { classId, name, isActive });
      
      document.getElementById('edit_id').value = classId;
      document.getElementById('edit_name').value = name;
      document.getElementById('edit_is_active').checked = isActive === 1;
      
      document.getElementById('editModal').style.display = 'block';
    }
    
    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
    }

    function deleteClass(classId) {
      if (confirm('<?= t('classes.delete_confirm') ?>')) {
        // 创建表单提交删除请求
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="delete_id" value="' + classId + '">';
        document.body.appendChild(form);
        form.submit();
      }
    }
  </script>
</body>
</html>
