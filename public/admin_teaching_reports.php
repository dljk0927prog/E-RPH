<?php
// admin_teaching_reports.php - 管理员教课报告浏览页面
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

// 检查是否登录且是管理员
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login_roles.php');
    exit;
}

require_once __DIR__ . '/../db.php';
$user = $_SESSION['user'];
$current_page = 'admin_reports';
$error = ''; // 初始化错误变量
$msg = '';   // 初始化成功消息变量
$teachers = [];
$courses = [];
$reports = [];
$stats = [];
$total_records = 0;
$total_pages = 0;

// 获取筛选参数
$date_filter = $_GET['date'] ?? '';
$teacher_filter = $_GET['teacher'] ?? '';
$course_filter = $_GET['course'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20; // 每页显示20条记录
$offset = ($page - 1) * $limit;

// 处理删除操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    try {
        $pdo->beginTransaction();
        
        // 检查报告是否存在
        $check_stmt = $pdo->prepare("SELECT a.id, a.date, u.name as teacher_name, c.title as course_title FROM attendance a LEFT JOIN users u ON a.user_id = u.id LEFT JOIN courses c ON a.course_id = c.id WHERE a.id = ?");
        $check_stmt->execute([$delete_id]);
        $report = $check_stmt->fetch();
        
        if ($report) {
            // 删除教课报告
            $delete_stmt = $pdo->prepare("DELETE FROM attendance WHERE id = ?");
            $delete_stmt->execute([$delete_id]);
            
            if ($delete_stmt->rowCount() > 0) {
                $pdo->commit();
                $msg = t('teaching_reports.delete_success') . ' ' . str_replace(
                    ['{teacher}', '{course}', '{date}'],
                    [$report['teacher_name'], $report['course_title'], $report['date']],
                    t('teaching_reports.delete_success_format')
                );
            } else {
                $pdo->rollBack();
                $error = t('teaching_reports.delete_failed');
            }
        } else {
            $error = t('teaching_reports.not_found');
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = t('errors.delete_failed') . '：' . $e->getMessage();
    }
}

// 处理编辑操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $status = $_POST['status'] ?? '';
    $check_in = trim($_POST['check_in'] ?? '');
    $check_out = trim($_POST['check_out'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($status)) {
        $error = t('teaching_reports.select_status');
    } else {
        try {
            $pdo->beginTransaction();
            
            // 更新教课报告
            $update_stmt = $pdo->prepare("UPDATE attendance SET status = ?, check_in = ?, check_out = ?, notes = ? WHERE id = ?");
            $update_stmt->execute([$status, $check_in ?: null, $check_out ?: null, $notes, $edit_id]);
            
            if ($update_stmt->rowCount() > 0) {
                $pdo->commit();
                $msg = t('teaching_reports.update_success');
            } else {
                $pdo->rollBack();
                $error = t('teaching_reports.update_failed');
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = t('errors.update_failed') . '：' . $e->getMessage();
        }
    }
}

try {
    // 获取所有老师列表（用于筛选）
    $teachers_stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'teacher' ORDER BY name");
    $teachers = $teachers_stmt->fetchAll();
    
    // 获取所有课程列表（用于筛选）
    $courses_stmt = $pdo->query("SELECT id, title FROM courses ORDER BY title");
    $courses = $courses_stmt->fetchAll();
    
    // 构建查询条件
    $where_conditions = [];
    $params = [];
    
    if ($date_filter) {
        $where_conditions[] = "DATE(a.date) = ?";
        $params[] = $date_filter;
    }
    
    if ($teacher_filter) {
        $where_conditions[] = "u.id = ?";
        $params[] = $teacher_filter;
    }
    
    if ($course_filter) {
        $where_conditions[] = "c.id = ?";
        $params[] = $course_filter;
    }
    
    if ($status_filter) {
        $where_conditions[] = "a.status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // 获取总记录数
    $count_sql = "
        SELECT COUNT(*) as total
        FROM attendance a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN courses c ON a.course_id = c.id
        $where_clause
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_records / $limit);
    
    // 获取教课报告数据
    $sql = "
        SELECT 
            a.id,
            a.date,
            a.status,
            a.check_in,
            a.check_out,
            a.notes,
            a.created_at,
            u.name as teacher_name,
            u.id as teacher_id,
            c.title as course_title,
            c.id as course_id
        FROM attendance a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN courses c ON a.course_id = c.id
        $where_clause
        ORDER BY a.date DESC, a.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();
    
    // 获取统计数据
    $stats_sql = "
        SELECT 
            COUNT(*) as total_reports,
            COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
            COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count,
            COUNT(CASE WHEN a.status = 'leave' THEN 1 END) as leave_count,
            COUNT(CASE WHEN DATE(a.date) = CURDATE() THEN 1 END) as today_reports
        FROM attendance a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN courses c ON a.course_id = c.id
        $where_clause
    ";
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute(array_slice($params, 0, -2)); // 移除 limit 和 offset 参数
    $stats = $stats_stmt->fetch();
    
} catch (Exception $e) {
    $error = t('teaching_reports.fetch_failed') . ": " . $e->getMessage();
}

// 状态翻译
function getStatusText($status) {
    switch ($status) {
        case 'present': return t('teaching_reports.status_present');
        case 'absent': return t('teaching_reports.status_absent');
        case 'leave': return t('teaching_reports.status_leave');
        default: return t('common.unknown');
    }
}

// 状态样式
function getStatusClass($status) {
    switch ($status) {
        case 'present': return 'status-present';
        case 'absent': return 'status-absent';
        case 'leave': return 'status-leave';
        default: return '';
    }
}
?>
<!doctype html>
<html lang="<?= t('common.language_code') ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
  <meta charset="utf-8">
  <title><?= t('teaching_reports.admin_title') ?> - ERPH</title>
  <link rel="stylesheet" href="assets/css/admin.css">
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
    [data-theme="dark"] .search-filters,
    [data-theme="dark"] .reports-table {
      background: linear-gradient(135deg, var(--bg-secondary), var(--card-hover-bg)) !important;
      border: 1px solid var(--border-color) !important;
      box-shadow: 0 8px 25px var(--shadow-color) !important;
      backdrop-filter: blur(10px) !important;
    }
    
    [data-theme="dark"] .page-header:hover,
    [data-theme="dark"] .search-filters:hover,
    [data-theme="dark"] .reports-table:hover {
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
    
    /* 搜索筛选样式 */
    .search-filters {
      background: var(--bg-secondary);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      box-shadow: 0 4px 15px var(--shadow-color);
    }
    
    .search-form {
      display: flex;
      gap: 15px;
      align-items: end;
      flex-wrap: wrap;
    }
    
    .search-input,
    .teacher-select,
    .course-select,
    .status-select {
      min-width: 150px;
      padding: 10px 12px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 14px;
      background: var(--bg-secondary);
      color: var(--text-primary);
    }
    
    .search-btn {
      background: var(--accent-color);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s ease;
    }
    
    .search-btn:hover {
      background: var(--accent-hover);
      transform: translateY(-1px);
    }
    
    /* 教课报告表格样式 */
    .reports-table {
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
    
    .status-present {
      background: var(--success-bg);
      color: var(--success-text);
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
    }
    
    .status-absent {
      background: var(--error-bg);
      color: var(--error-text);
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
    }
    
    .status-leave {
      background: var(--warning-bg);
      color: var(--warning-text);
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
    }
    
    .action-buttons {
      display: flex;
      gap: 8px;
    }
    
    .view-btn, .delete-btn {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      font-size: 12px;
      text-decoration: none;
      display: inline-block;
      cursor: pointer;
    }
    
    .view-btn {
      background: var(--accent-color);
      color: white;
    }
    
    .view-btn:hover {
      background: var(--accent-hover);
      transform: translateY(-1px);
    }
    
    .delete-btn {
      background: #f44336;
      color: white;
    }
    
    .delete-btn:hover {
      background: #d32f2f;
      transform: translateY(-1px);
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
    
    /* 分页样式 */
    .pagination {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 20px;
    }
    
    .pagination a {
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 4px;
      text-decoration: none;
      color: var(--text-primary);
      background: var(--bg-secondary);
    }
    
    .pagination a:hover {
      background: var(--accent-color);
      color: white;
      border-color: var(--accent-color);
    }
    
    .pagination .current {
      background: var(--accent-color);
      color: white;
      border-color: var(--accent-color);
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
      max-width: 800px;
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
      max-height: 70vh;
      overflow-y: auto;
    }
    
    .report-detail-section {
      margin-bottom: 25px;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--border-color);
    }
    
    .report-detail-section:last-child {
      border-bottom: none;
      margin-bottom: 0;
    }
    
    .report-detail-section h4 {
      color: var(--accent-color);
      margin: 0 0 15px 0;
      font-size: 16px;
      font-weight: 600;
      border-bottom: 2px solid var(--accent-color);
      padding-bottom: 8px;
    }
    
    .report-detail-row {
      display: flex;
      margin: 10px 0;
      align-items: center;
    }
    
    .report-detail-label {
      font-weight: 600;
      color: var(--text-secondary);
      min-width: 120px;
      margin-right: 15px;
    }
    
    .report-detail-value {
      color: var(--text-primary);
      flex: 1;
    }
    
    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 600;
      text-align: center;
    }
    
    .status-present {
      background: var(--success-bg);
      color: var(--success-text);
    }
    
    .status-absent {
      background: var(--error-bg);
      color: var(--error-text);
    }
    
    .status-leave {
      background: var(--warning-bg);
      color: var(--warning-text);
    }
    
    /* 深色模式弹窗样式 */
    [data-theme="dark"] .modal-content {
      background: var(--bg-secondary) !important;
      border: 1px solid var(--border-color) !important;
    }
    
    [data-theme="dark"] .report-detail-section {
      border-bottom-color: var(--border-color) !important;
    }
    
    [data-theme="dark"] .report-detail-label {
      color: var(--text-secondary) !important;
    }
    
    [data-theme="dark"] .report-detail-value {
      color: var(--text-primary) !important;
    }
  </style>
</head>
<body>
  <header class="header">
    <h1>ERPH 系统 - <?= t('teaching_reports.admin_title') ?></h1>
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
        <h2><?= t('teaching_reports.admin_title') ?></h2>
      </div>

      <?php if ($msg): ?>
        <div class="message success"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="search-filters">
        <form class="search-form" method="GET">
          <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>" class="search-input" placeholder="<?= t('common.date') ?>">
          <select name="teacher" class="teacher-select">
            <option value=""><?= t('teaching_reports.all_teachers') ?></option>
            <?php foreach ($teachers as $teacher): ?>
              <option value="<?= $teacher['id'] ?>" <?= $teacher_filter == $teacher['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($teacher['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <select name="course" class="course-select">
            <option value=""><?= t('teaching_reports.all_courses') ?></option>
            <?php foreach ($courses as $course): ?>
              <option value="<?= $course['id'] ?>" <?= $course_filter == $course['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($course['title']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <select name="status" class="status-select">
            <option value=""><?= t('teaching_reports.all_status') ?></option>
            <option value="present" <?= $status_filter === 'present' ? 'selected' : '' ?>><?= t('teaching_reports.status_present') ?></option>
            <option value="absent" <?= $status_filter === 'absent' ? 'selected' : '' ?>><?= t('teaching_reports.status_absent') ?></option>
            <option value="leave" <?= $status_filter === 'leave' ? 'selected' : '' ?>><?= t('teaching_reports.status_leave') ?></option>
          </select>
          <button type="submit" class="search-btn"><?= t('common.search') ?></button>
        </form>
      </div>

      <div class="reports-table">
        <?php if (empty($reports)): ?>
          <div style="text-align:center;padding:40px;color:#666;"><?= t('teaching_reports.no_data') ?></div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th><?= t('common.date') ?></th>
                <th><?= t('common.teacher') ?></th>
                <th><?= t('common.course') ?></th>
                <th><?= t('common.status') ?></th>
                <th><?= t('teaching_reports.check_in_time') ?></th>
                <th><?= t('teaching_reports.check_out_time') ?></th>
                <th><?= t('common.notes') ?></th>
                <th><?= t('common.action') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reports as $report): ?>
              <tr>
                <td><?= htmlspecialchars($report['id']) ?></td>
                <td><?= htmlspecialchars($report['date']) ?></td>
                <td><?= htmlspecialchars($report['teacher_name']) ?></td>
                <td><?= htmlspecialchars($report['course_title']) ?></td>
                <td>
                  <span class="<?= getStatusClass($report['status']) ?>">
                    <?= getStatusText($report['status']) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($report['check_in'] ?? '-') ?></td>
                <td><?= htmlspecialchars($report['check_out'] ?? '-') ?></td>
                <td><?= htmlspecialchars($report['notes'] ?? '-') ?></td>
                <td>
                  <div class="action-buttons">
                    <button class="view-btn" onclick="viewReport(<?= $report['id'] ?>)"><?= t('common.view') ?></button>
                    <button class="delete-btn" onclick="deleteReport(<?= $report['id'] ?>)"><?= t('common.delete') ?></button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          
          <?php if ($total_pages > 1): ?>
            <div class="pagination">
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&date=<?= urlencode($date_filter) ?>&teacher=<?= urlencode($teacher_filter) ?>&course=<?= urlencode($course_filter) ?>&status=<?= urlencode($status_filter) ?>" 
                   class="<?= $i == $page ? 'current' : '' ?>">
                  <?= $i ?>
                </a>
              <?php endfor; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- 教课报告详情弹窗 -->
  <div id="reportModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3><?= t('teaching_reports.report_info') ?></h3>
        <span class="close" onclick="closeModal()">&times;</span>
      </div>
      <div class="modal-body" id="modalBody">
        <!-- 弹窗内容将通过JavaScript动态填充 -->
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
    
    // 弹窗相关函数
    function viewReport(reportId) {
              console.log('<?= t('teaching_reports.start_fetch') ?> ID:', reportId);
      
      // 显示弹窗
      document.getElementById('reportModal').style.display = 'block';
      
      // 显示加载状态
      document.getElementById('modalBody').innerHTML = 
        '<div style="text-align:center;padding:40px;color:#666;"><?= t('common.loading') ?> <?= t('teaching_reports.report_info') ?>...</div>';
      
      // 获取教课报告详情
      const url = 'get_teaching_report.php?id=' + reportId;
              console.log('<?= t('common.request_url') ?>:', url);
      
      fetch(url)
        .then(response => {
          console.log('<?= t('common.response_received') ?>:', response);
          if (!response.ok) {
            throw new Error('<?= t('common.http_error') ?>: ' + response.status);
          }
          return response.json();
        })
        .then(data => {
          console.log('<?= t('common.parsed_json_data') ?>:', data);
          if (data.success) {
            displayReportDetails(data.report);
          } else {
            document.getElementById('modalBody').innerHTML = 
              '<div style="text-align:center;color:red;padding:20px;"><?= t('teaching_reports.fetch_failed') ?>: ' + (data.message || '<?= t('common.unknown_error') ?>') + '</div>';
          }
        })
        .catch(error => {
          console.error('<?= t('teaching_reports.fetch_failed') ?>:', error);
          document.getElementById('modalBody').innerHTML = 
            '<div style="text-align:center;color:red;padding:20px;"><?= t('teaching_reports.fetch_error') ?>: ' + error.message + '</div>';
        });
    }
    
    function closeModal() {
      document.getElementById('reportModal').style.display = 'none';
    }
    
    function displayReportDetails(report) {
      const modalBody = document.getElementById('modalBody');
      
      // JavaScript版本的状态处理函数
      function getStatusText(status) {
        switch (status) {
          case 'present': return '<?= t('teaching_reports.status_present') ?>';
          case 'absent': return '<?= t('teaching_reports.status_absent') ?>';
          case 'leave': return '<?= t('teaching_reports.status_leave') ?>';
          default: return '<?= t('common.unknown') ?>';
        }
      }
      
      function getStatusClass(status) {
        switch (status) {
          case 'present': return 'status-present';
          case 'absent': return 'status-absent';
          case 'leave': return 'status-leave';
          default: return '';
        }
      }
      
      modalBody.innerHTML = `
        <div class="report-detail-section">
          <h4>基本信息</h4>
          <div class="report-detail-row">
            <span class="report-detail-label">报告ID:</span>
            <span class="report-detail-value">#${report.id}</span>
          </div>
          <div class="report-detail-row">
            <span class="report-detail-label">日期:</span>
            <span class="report-detail-value">${report.date}</span>
          </div>
          <div class="report-detail-row">
            <span class="report-detail-label">状态:</span>
            <span class="report-detail-value">
              <span class="status-badge ${getStatusClass(report.status)}">
                ${getStatusText(report.status)}
              </span>
            </span>
          </div>
          <div class="report-detail-row">
            <span class="report-detail-label">创建时间:</span>
            <span class="report-detail-value">${report.created_at}</span>
          </div>
        </div>
        
        <div class="report-detail-section">
          <h4>老师信息</h4>
          <div class="report-detail-row">
            <span class="report-detail-label">老师姓名:</span>
            <span class="report-detail-value">${report.teacher_name}</span>
          </div>
          <div class="report-detail-row">
            <span class="report-detail-label">邮箱:</span>
            <span class="report-detail-value">${report.teacher_email}</span>
          </div>
          <div class="report-detail-row">
            <span class="report-detail-label">老师ID:</span>
            <span class="report-detail-value">#${report.teacher_id}</span>
          </div>
        </div>
        
        <div class="report-detail-section">
          <h4>课程信息</h4>
          <div class="report-detail-row">
            <span class="report-detail-label">课程名称:</span>
            <span class="report-detail-value">${report.course_title || '<?= t('teaching_reports.unassigned_course') ?>'}</span>
          </div>
          <div class="report-detail-row">
            <span class="report-detail-label">课程ID:</span>
            <span class="report-detail-value">${report.course_id ? '#' + report.course_id : '<?= t('common.none') ?>'}</span>
          </div>
          ${report.course_description ? `
          <div class="report-detail-row">
            <span class="report-detail-label">课程描述:</span>
            <span class="report-detail-value">${report.course_description}</span>
          </div>
          ` : ''}
        </div>
        
        <div class="report-detail-section">
          <h4>时间信息</h4>
          <div class="report-detail-row">
            <span class="report-detail-label">签到时间:</span>
            <span class="report-detail-value">${report.check_in || '<?= t('teaching_reports.not_checked_in') ?>'}</span>
          </div>
          <div class="report-detail-row">
            <span class="report-detail-label">签退时间:</span>
            <span class="report-detail-value">${report.check_out || '<?= t('teaching_reports.not_checked_out') ?>'}</span>
          </div>
        </div>
        
        <div class="report-detail-section">
          <h4>备注信息</h4>
          <div class="report-detail-row">
            <span class="report-detail-label">备注:</span>
            <span class="report-detail-value">${report.notes || '<?= t('common.no_notes') ?>'}</span>
          </div>
        </div>
      `;
    }
    
    // 点击弹窗外部关闭弹窗
    window.onclick = function(event) {
      const modal = document.getElementById('reportModal');
      if (event.target === modal) {
        closeModal();
      }
    }
    
    function deleteReport(reportId) {
      if (confirm('<?= t('teaching_reports.confirm_delete') ?>')) {
        // 创建表单提交删除请求
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="delete_id" value="' + reportId + '">';
        document.body.appendChild(form);
        form.submit();
      }
    }
  </script>
</body>
</html>
