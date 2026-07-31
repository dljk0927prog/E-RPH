<?php
// activity_monitor.php - 活动监控页面
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';
require_once __DIR__ . '/inc/activity_monitor_db.php';

// 检查是否登录且是管理员
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login_roles.php');
    exit;
}

$user = $_SESSION['user'];
$current_page = 'monitor';

// 获取筛选参数
$user_filter = isset($_GET['user_filter']) ? (int)$_GET['user_filter'] : null;

try {
    // 获取数据库连接
    $config = require __DIR__ . '/../config.php';
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    $monitorDB = new ActivityMonitorDB($pdo);
    
    // 获取活动统计数据
    $activity_stats = $monitorDB->getActivityStats();
    
    // 获取教课报告统计数据
    $report_stats = $monitorDB->getTeachingReportStats();
    
    // 获取最近活动列表
    $recent_activities = $monitorDB->getRecentActivities(10, $user_filter);
    
    // 获取教课活动时间段数据
    $timeline_data = $monitorDB->getHourlyActivity();
    
    // 调试信息
    error_log("教课活动时间段数据: " . print_r($timeline_data, true));
    
    // 将时间段数据添加到页面数据中
    $page_data = [
        'timeline' => $timeline_data
    ];
    
    // 获取用户列表（用于筛选）
    $stmt = $pdo->query("SELECT id, name, role FROM users ORDER BY name");
    $users = $stmt->fetchAll();
    
    // 记录查看活动监控的活动
    $monitorDB->logActivity(
        $_SESSION['user']['id'],
        'view_monitor',
        '访问活动监控页面',
        'activity_monitor',
        null,
        'success'
    );
    
} catch (PDOException $e) {
    error_log("活动监控数据库连接失败: " . $e->getMessage());
    // 使用默认数据
    $activity_stats = [
        'total_users' => 0,
        'active_users' => 0,
        'total_sessions' => 0,
        'avg_session_time' => '0 min',
        'peak_hours' => 'No data'
    ];
    
    $report_stats = [
        'total_reports' => 0,
        'today_reports' => 0,
        'week_reports' => 0,
        'month_reports' => 0
    ];
    
         $recent_activities = [
         [
             'user' => 'System',
             'role' => 'admin',
             'action' => 'Database connection failed',
             'time' => 'Just now',
             'status' => 'failed',
             'is_inactive' => false,
             'inactive_hours' => 0,
             'course' => null
         ]
     ];
    
    $hourly_activity = [];
    for ($i = 0; $i < 24; $i++) {
        $hourly_activity[sprintf('%02d:00', $i)] = 0;
    }
    
    $users = [];
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('activity_monitor.page_title') ?></title>
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
      --inactive-bg: #f8d7da;
      --inactive-text: #721c24;
      --inactive-border: #dc3545;
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
      --header-bg: linear-gradient(90deg, #4a90e2, #7bb3f0) !important;  /* 保持原有的蓝色渐变 */
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
      --gradient-start: #1e3a8a !important;
      --gradient-end: #3b82f6 !important;
      --inactive-bg: #7f1d1d !important;
      --inactive-text: #fecaca !important;
      --inactive-border: #dc2626 !important;
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
    
    /* 深色模式强制覆盖所有可能的深色元素 - 但排除header */
    [data-theme="dark"] *:not(.header):not(header):not(.header *):not(header *) {
      background-color: var(--bg-secondary) !important;
      color: var(--text-primary) !important;
    }
    
    /* 深色模式强制覆盖所有可能的深色文字 - 但排除header */
    [data-theme="dark"] *:not(.header):not(header):not(.header *):not(header *) {
      color: var(--text-primary) !important;
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
    [data-theme="dark"] .admin-main-card,
    [data-theme="dark"] .stat-card {
      background: linear-gradient(135deg, var(--bg-secondary), var(--card-hover-bg)) !important;
      border: 1px solid var(--border-color) !important;
      box-shadow: 0 8px 25px var(--shadow-color) !important;
      backdrop-filter: blur(10px) !important;
    }
    
    [data-theme="dark"] .admin-main-card:hover,
    [data-theme="dark"] .stat-card:hover {
      background: var(--card-hover-bg) !important;
      transform: translateY(-2px) !important;
      box-shadow: 0 8px 25px var(--card-hover-shadow) !important;
      border-color: var(--accent-color) !important;
    }
    
    /* 深色模式统计卡片美化 */
    [data-theme="dark"] .stat-card {
      background: linear-gradient(135deg, var(--bg-secondary), var(--bg-secondary)) !important;
      border: 1px solid var(--border-color) !important;
      box-shadow: 0 8px 25px var(--shadow-color) !important;
      backdrop-filter: blur(10px) !important;
    }
    
    [data-theme="dark"] .stat-card:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 12px 35px var(--shadow-color) !important;
    }
    
    /* 深色模式按钮美化 */
    [data-theme="dark"] .btn {
      background: linear-gradient(135deg, var(--accent-color), var(--accent-hover)) !important;
      color: white !important;
      border: none !important;
      box-shadow: 0 4px 15px var(--accent-color) !important;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    
    [data-theme="dark"] .btn:hover {
      background: linear-gradient(135deg, var(--accent-hover), var(--accent-color)) !important;
      transform: translateY(-2px) !important;
      box-shadow: 0 8px 25px var(--accent-color) !important;
    }
    
    /* 深色模式输入框美化 */
    [data-theme="dark"] input[type="text"],
    [data-theme="dark"] select {
      background: var(--bg-primary) !important;
      border: 2px solid var(--border-color) !important;
      color: var(--text-primary) !important;
      transition: all 0.3s ease !important;
    }
    
    [data-theme="dark"] input[type="text"]:focus,
    [data-theme="dark"] select:focus {
      border-color: var(--accent-color) !important;
      box-shadow: 0 0 0 3px rgba(100, 181, 246, 0.1) !important;
    }
    
    /* 深色模式表格美化 */
    [data-theme="dark"] table th {
      background: linear-gradient(135deg, var(--bg-primary), var(--bg-primary)) !important;
      color: var(--accent-color) !important;
      border-bottom: 2px solid var(--accent-color) !important;
    }
    
    [data-theme="dark"] table td {
      border-bottom: 1px solid var(--border-color) !important;
      color: var(--text-primary) !important;
    }
    
    [data-theme="dark"] table tr:hover {
      background: var(--bg-primary) !important;
      transform: scale(1.01) !important;
    }
    
    /* 深色模式徽章美化 */
    [data-theme="dark"] .badge.on {
      background: linear-gradient(135deg, var(--success-bg), var(--success-border)) !important;
      color: var(--success-text) !important;
      box-shadow: 0 2px 8px var(--success-bg) !important;
    }
    
    [data-theme="dark"] .badge.off {
      background: linear-gradient(135deg, var(--error-bg), var(--error-border)) !important;
      color: var(--error-text) !important;
      box-shadow: 0 2px 8px var(--error-bg) !important;
    }
    
    [data-theme="dark"] .badge.inactive {
      background: linear-gradient(135deg, var(--inactive-bg), var(--inactive-border)) !important;
      color: var(--inactive-text) !important;
      box-shadow: 0 2px 8px var(--inactive-bg) !important;
    }
    
    /* 深色模式动画 */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    [data-theme="dark"] .admin-main-card,
    [data-theme="dark"] .stat-card {
      animation: fadeInUp 0.6s ease-out !important;
    }
    
    /* 深色模式侧边栏美化 - 统一颜色并移除蓝色条 */
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
    
    /* 浅色模式侧边栏美化 - 统一颜色并移除蓝色条 */
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
    
    /* 原有样式保持不变 */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    .container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 0 20px;
    }
    
    .page-header {
      background: var(--bg-secondary);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      box-shadow: 0 4px 15px var(--shadow-color);
      transition: all 0.3s ease;
    }
    
    .page-header h2 {
      color: var(--accent-color);
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background: var(--bg-secondary);
      padding: 25px;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 4px 15px var(--shadow-color);
      border-left: 4px solid var(--card-border);
      transition: all 0.3s ease;
    }
    
    .stat-number {
      font-size: 36px;
      font-weight: bold;
      color: var(--accent-color);
      margin-bottom: 10px;
    }
    
    .stat-label {
      color: var(--text-secondary);
      font-size: 14px;
    }
    
    .chart-section {
      background: var(--bg-secondary);
      padding: 25px;
      border-radius: 12px;
      margin-bottom: 30px;
      box-shadow: 0 4px 15px var(--shadow-color);
      transition: all 0.3s ease;
    }
    
    .chart-section h3 {
      color: var(--accent-color);
      margin-bottom: 20px;
      font-size: 18px;
    }
    
    .activity-timeline {
      height: 300px;
      background: var(--bg-primary);
      border-radius: 8px;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }
    
    .timeline-header {
      margin-bottom: 20px;
    }
    
    .time-scale {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid var(--border-color);
      padding-bottom: 10px;
    }
    
    .time-marker {
      font-size: 11px;
      color: var(--text-secondary);
      text-align: center;
      min-width: 30px;
    }
    
    .timeline-content {
      position: relative;
      height: 200px;
      background: linear-gradient(to right, 
        rgba(74, 144, 226, 0.1) 0%, 
        rgba(74, 144, 226, 0.05) 50%, 
        rgba(74, 144, 226, 0.1) 100%);
      border-radius: 6px;
    }
    
    .activity-block {
      position: absolute;
      height: 40px;
      background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 12px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(74, 144, 226, 0.3);
      border: 2px solid rgba(255, 255, 255, 0.2);
    }
    
    .activity-block:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(74, 144, 226, 0.4);
      z-index: 10;
    }
    
    .activity-block .time-info {
      text-align: center;
      line-height: 1.2;
    }
    
    .activity-block .course-name {
      font-size: 10px;
      opacity: 0.9;
    }
    
    .activity-block .time-range {
      font-size: 9px;
      opacity: 0.8;
    }
    
    .no-activity {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      color: var(--text-muted);
      font-size: 16px;
      text-align: center;
    }
    
    .activities-section {
      background: var(--bg-secondary);
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 4px 15px var(--shadow-color);
      transition: all 0.3s ease;
    }
    
    .activities-section h3 {
      color: var(--accent-color);
      margin-bottom: 20px;
      font-size: 18px;
    }
    
    .filter-controls {
      display: flex;
      gap: 15px;
      align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    
    .filter-controls select {
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 14px;
      background: var(--bg-secondary);
      color: var(--text-primary);
    }
    
    .filter-controls button {
      background: var(--accent-color);
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
    }
    
    .filter-controls button:hover {
      background: var(--accent-hover);
    }
    
    .activity-item {
      display: flex;
      align-items: center;
      padding: 15px;
      border-bottom: 1px solid var(--border-color);
      border-radius: 8px;
      margin-bottom: 10px;
      transition: all 0.3s ease;
    }
    
    .activity-item.inactive {
      border: 2px solid var(--inactive-border);
      background-color: var(--inactive-bg);
      box-shadow: 0 2px 8px rgba(220, 53, 69, 0.1);
    }
    
    .activity-item.inactive .activity-user {
      color: var(--inactive-text);
      font-weight: 600;
    }
    
    .inactive-badge {
      background: var(--inactive-border);
      color: white;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 500;
      margin-left: 10px;
    }
    
    .inactive-time {
      color: var(--inactive-text);
      font-size: 12px;
      font-weight: 500;
      margin-top: 4px;
    }
    
    .activity-item:last-child {
      border-bottom: none;
    }
    
    .activity-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      margin-right: 15px;
    }
    
    .activity-content {
      flex: 1;
    }
    
    .activity-user {
      font-weight: 500;
      margin-bottom: 4px;
    }
    
    .activity-action {
      color: var(--text-secondary);
      font-size: 14px;
      margin-bottom: 4px;
    }
    
    .activity-course {
      color: var(--accent-color);
      font-size: 12px;
      font-weight: 500;
    }
    
    .activity-time {
      color: var(--text-muted);
      font-size: 12px;
    }
    
    .activity-status {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      margin-left: 10px;
    }
    
    .status-success {
      background: var(--success-bg);
    }
    
    .status-pending {
      background: var(--warning-bg);
    }
    
    .status-failed {
      background: var(--error-bg);
    }
    
    .status-present {
      background: var(--success-bg);
    }
    
    .status-absent {
      background: var(--error-bg);
    }
    
    .status-leave {
      background: var(--warning-bg);
    }
    
    .refresh-btn {
      background: var(--accent-color);
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      margin-left: 15px;
    }
    
    .refresh-btn:hover {
      background: var(--accent-hover);
    }
    
    .loading {
      opacity: 0.6;
      pointer-events: none;
    }
    
    .error-message {
      background: var(--error-bg);
      color: var(--error-text);
      padding: 10px;
      border-radius: 6px;
      margin: 10px 0;
      border: 1px solid var(--error-border);
    }
    
    .success-message {
      background: var(--success-bg);
      color: var(--success-text);
      padding: 10px;
      border-radius: 6px;
      margin: 10px 0;
      border: 1px solid var(--success-border);
    }
    
    .last-update-text {
      margin-right: 15px;
      color: var(--text-secondary);
    }
    
    .peak-hours-info {
      text-align: center;
      margin-top: 30px;
      color: var(--text-secondary);
    }
    
    @media (max-width: 768px) {
      .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      }
      
      .filter-controls {
        flex-direction: column;
        align-items: stretch;
      }
      
      .activity-timeline {
        padding: 15px;
        height: 250px;
      }
      
      .timeline-content {
        height: 150px;
      }
      
      .time-marker {
        font-size: 10px;
        min-width: 25px;
      }
      
      .activity-block {
        height: 35px;
        font-size: 11px;
      }
      
      .activity-block .course-name {
        font-size: 9px;
      }
      
      .activity-block .time-range {
        font-size: 8px;
      }
    }
    
    /* 图表控件样式 */
    .chart-controls {
      display: flex;
      gap: 10px;
      align-items: center;
      margin-bottom: 20px;
      padding: 15px;
      background: var(--bg-secondary);
      border-radius: 8px;
      border: 1px solid var(--border-color);
      box-shadow: 0 2px 4px var(--shadow-color);
    }
    

    

    

    

    

    
    .status-indicator {
      margin-left: auto;
      padding: 6px 12px;
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 4px;
      font-size: 12px;
      color: var(--text-secondary);
      transition: all 0.3s ease;
    }
    
    .status-indicator.active {
      background: var(--success-bg);
      color: var(--success-text);
      border-color: var(--success-border);
    }
    
    /* 图表柱高亮效果 */
    .chart-bar.highlight {
      background: var(--warning-bg) !important;
      box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
      transform: scale(1.05);
      transition: all 0.3s ease;
    }
    
    /* 签到签退时间样式 */
    .activity-check-in,
    .activity-check-out {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 2px;
      padding: 2px 6px;
      background: var(--light-color);
      border-radius: 4px;
      display: inline-block;
      margin-right: 8px;
    }
    
    .activity-check-in {
      border-left: 3px solid var(--success-color);
    }
    
    .activity-check-out {
      border-left: 3px solid var(--info-color);
    }
    
    /* 深色模式下的签到签退时间样式 */
    [data-theme="dark"] .activity-check-in,
    [data-theme="dark"] .activity-check-out {
      background: var(--bg-primary);
      color: var(--text-secondary);
    }
  </style>
</head>
<body>
  <header class="header">
    <h1><?= t('home.main_title') ?> - <?= t('activity_monitor.title') ?></h1>
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
      <h2><?= t('activity_monitor.title') ?></h2>
      <div style="display: flex; align-items: center;">
        <span class="last-update-text"><?= t('activity_monitor.last_update') ?>: <span id="last-update"><?= date('H:i:s') ?></span></span>
        <button class="refresh-btn" onclick="refreshData()" id="refresh-btn"><?= t('activity_monitor.refresh_data') ?></button>
        <button class="refresh-btn" onclick="generateTodayData()" id="generate-btn" style="margin-left: 10px; background: #28a745;"><?= t('activity_monitor.sync_teaching_data') ?></button>
      </div>
    </div>

    <div id="error-container"></div>
    <div id="success-container"></div>

    <!-- 系统统计 -->
    <div class="stats-grid" id="stats-grid">
      <div class="stat-card">
        <div class="stat-number" id="total-users"><?= $activity_stats['total_users'] ?></div>
        <div class="stat-label"><?= t('activity_monitor.total_users') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-number" id="active-users"><?= $activity_stats['active_users'] ?></div>
        <div class="stat-label"><?= t('activity_monitor.active_users') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-number" id="total-sessions"><?= $activity_stats['total_sessions'] ?></div>
        <div class="stat-label"><?= t('activity_monitor.today_sessions') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-number" id="avg-session-time"><?= $activity_stats['avg_session_time'] ?></div>
        <div class="stat-label"><?= t('activity_monitor.avg_session_time') ?></div>
      </div>
    </div>

    <!-- 教课报告统计 -->
    <div class="stats-grid" id="report-stats-grid">
      <div class="stat-card">
        <div class="stat-number" id="total-reports"><?= $report_stats['total_reports'] ?></div>
        <div class="stat-label"><?= t('activity_monitor.total_teaching_reports') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-number" id="today-reports"><?= $report_stats['today_reports'] ?></div>
        <div class="stat-label"><?= t('activity_monitor.today_teaching_reports') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-number" id="week-reports"><?= $report_stats['week_reports'] ?></div>
        <div class="stat-label"><?= t('activity_monitor.week_teaching_reports') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-number" id="month-reports"><?= $report_stats['month_reports'] ?></div>
        <div class="stat-label"><?= t('activity_monitor.month_teaching_reports') ?></div>
      </div>
    </div>

    <!-- 教课活动时间段图 -->
    <div class="chart-section">
      <h3><?= t('activity_monitor.teaching_activity_timeline') ?></h3>
      <div class="chart-controls">
        <span id="auto-sync-status" class="status-indicator"><?= t('activity_monitor.auto_sync_enabled') ?></span>
        <span id="real-time-status" class="status-indicator"><?= t('activity_monitor.real_time_updates_disabled') ?></span>
      </div>
      <div class="activity-timeline" id="activity-timeline">
        <div class="timeline-header">
          <div class="time-scale">
            <?php for ($i = 0; $i < 24; $i++): ?>
              <div class="time-marker"><?= sprintf('%02d:00', $i) ?></div>
            <?php endfor; ?>
          </div>
        </div>
        <div class="timeline-content" id="timeline-content">
          <!-- 动态生成时间段条 -->
        </div>
      </div>
      <div class="peak-hours-info">
        <p><?= t('activity_monitor.peak_hours') ?>: <span id="peak-hours"><?= $activity_stats['peak_hours'] ?></span></p>
      </div>
    </div>

    <!-- 今日活动列表 -->
    <div class="activities-section">
      <h3><?= t('activity_monitor.today_activities') ?></h3>
      
      <!-- 筛选控件 -->
      <div class="filter-controls">
        <select id="user-filter" onchange="filterActivities()">
          <option value=""><?= t('activity_monitor.all_users') ?></option>
          <?php foreach ($users as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $user_filter == $u['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($u['name']) ?> (<?= $u['role'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <button onclick="clearFilter()"><?= t('activity_monitor.clear_filter') ?></button>
      </div>
      
      <div id="activities-list">
        <?php foreach ($recent_activities as $activity): ?>
          <div class="activity-item <?= $activity['is_inactive'] ? 'inactive' : '' ?>">
            <div class="activity-avatar">
              <?= mb_substr($activity['user'], 0, 1) ?>
            </div>
            <div class="activity-content">
              <div class="activity-user">
                <?= htmlspecialchars($activity['user']) ?>
                <?php if ($activity['is_inactive']): ?>
                  <span class="inactive-badge"><?= t('activity_monitor.inactive') ?></span>
                <?php endif; ?>
              </div>
              <div class="activity-action"><?= htmlspecialchars($activity['action']) ?></div>
              <?php if (isset($activity['course'])): ?>
                <div class="activity-course"><?= htmlspecialchars($activity['course']) ?></div>
              <?php endif; ?>
              <div class="activity-time"><?= htmlspecialchars($activity['time']) ?></div>
              <?php if (isset($activity['check_in']) && $activity['check_in']): ?>
                <div class="activity-check-in"><?= t('activity_monitor.check_in_time') ?>: <?= htmlspecialchars($activity['check_in']) ?></div>
              <?php endif; ?>
              <?php if (isset($activity['check_out']) && $activity['check_out']): ?>
                <div class="activity-check-out"><?= t('activity_monitor.check_out_time') ?>: <?= htmlspecialchars($activity['check_out']) ?></div>
              <?php endif; ?>
              <?php if ($activity['is_inactive'] && $activity['inactive_hours'] > 0): ?>
                <div class="inactive-time"><?= t('activity_monitor.inactive_time') ?>: <?= $activity['inactive_hours'] ?> <?= t('activity_monitor.hours') ?></div>
              <?php endif; ?>
            </div>
            <div class="activity-status status-<?= $activity['status'] ?>"></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <script>
    let refreshInterval;
    let realTimeInterval;
    let autoSyncInterval;
    let isRealTimeEnabled = true; // 默认开启实时更新
    let currentUserFilter = '<?= $user_filter ?? "" ?>';
    
    function showMessage(message, type = 'success') {
      const container = document.getElementById(type === 'success' ? 'success-container' : 'error-container');
      container.innerHTML = `<div class="${type === 'success' ? 'success-message' : 'error-message'}">${message}</div>`;
      
      setTimeout(() => {
        container.innerHTML = '';
      }, 5000);
    }
    
    function setLoading(loading) {
      const refreshBtn = document.getElementById('refresh-btn');
      const statsGrid = document.getElementById('stats-grid');
      const reportStatsGrid = document.getElementById('report-stats-grid');
      const chartSection = document.querySelector('.chart-section');
      const activitiesSection = document.querySelector('.activities-section');
      
      if (loading) {
        refreshBtn.disabled = true;
        refreshBtn.textContent = '<?= t('activity_monitor.refreshing') ?>';
        statsGrid.classList.add('loading');
        reportStatsGrid.classList.add('loading');
        chartSection.classList.add('loading');
        activitiesSection.classList.add('loading');
      } else {
        refreshBtn.disabled = false;
        refreshBtn.textContent = '<?= t('activity_monitor.refresh_data') ?>';
        statsGrid.classList.remove('loading');
        reportStatsGrid.classList.remove('loading');
        chartSection.classList.remove('loading');
        activitiesSection.classList.remove('loading');
      }
    }
    
    function updateStats(data) {
      document.getElementById('total-users').textContent = data.stats.total_users;
      document.getElementById('active-users').textContent = data.stats.active_users;
      document.getElementById('total-sessions').textContent = data.stats.total_sessions;
      document.getElementById('avg-session-time').textContent = data.stats.avg_session_time;
      document.getElementById('peak-hours').textContent = data.stats.peak_hours;
      
      // 更新教课报告统计
      if (data.report_stats) {
        document.getElementById('total-reports').textContent = data.report_stats.total_reports;
        document.getElementById('today-reports').textContent = data.report_stats.today_reports;
        document.getElementById('week-reports').textContent = data.report_stats.week_reports;
        document.getElementById('month-reports').textContent = data.report_stats.month_reports;
      }
      
      // 更新高峰时段显示
      if (data.peak_hours) {
        document.getElementById('peak-hours').textContent = data.peak_hours;
      }
    }
    
    function updateChart(data) {
      const timeline = document.getElementById('timeline-content');
      timeline.innerHTML = '';
      
      console.log('updateChart called with data:', data); // 调试信息
      
      // 检查是否有时间段数据
      if (data.timeline && data.timeline.length > 0) {
        console.log('Using timeline data:', data.timeline);
        renderTimelineFromData(data.timeline);
      } else if (data.activities && data.activities.length > 0) {
        console.log('Using activities data:', data.activities);
        // 从活动数据中提取时间段信息
        const timelineData = data.activities
          .filter(activity => activity.check_in && activity.check_out)
          .map(activity => ({
            check_in: activity.check_in,
            check_out: activity.check_out,
            course: activity.course,
            status: activity.status
          }));
        
        if (timelineData.length > 0) {
          renderTimelineFromData(timelineData);
        } else {
          timeline.innerHTML = '<div class="no-activity"><?= t('activity_monitor.no_teaching_activities_today') ?></div>';
        }
      } else if (data.hourly && Object.keys(data.hourly).length > 0) {
        console.log('Using hourly data:', data.hourly);
        // 兼容旧的小时数据格式
        renderHourlyData(data.hourly);
      } else {
        console.log('No data available');
        timeline.innerHTML = '<div class="no-activity"><?= t('activity_monitor.no_teaching_activities_today') ?></div>';
      }
    }
    
    function renderTimelineFromData(timelineData) {
      const timeline = document.getElementById('timeline-content');
      timeline.innerHTML = '';
      
      // 计算时间轴的总宽度（24小时）
      const timelineWidth = timeline.offsetWidth - 40; // 减去左右边距
      const hourWidth = timelineWidth / 24;
      
      timelineData.forEach((activity, index) => {
        if (activity.check_in && activity.check_out) {
          const checkInTime = new Date(`2000-01-01 ${activity.check_in}`);
          const checkOutTime = new Date(`2000-01-01 ${activity.check_out}`);
          
          const startHour = checkInTime.getHours() + (checkInTime.getMinutes() / 60);
          const endHour = checkOutTime.getHours() + (checkOutTime.getMinutes() / 60);
          
          const left = startHour * hourWidth;
          const width = (endHour - startHour) * hourWidth;
          
          // 确保最小宽度
          const minWidth = Math.max(width, 60);
          
          const block = document.createElement('div');
          block.className = 'activity-block';
          block.style.left = `${left}px`;
          block.style.width = `${minWidth}px`;
          block.style.top = `${20 + (index * 50)}px`;
          
          // 显示课程信息和时间
          const courseName = activity.course || '<?= t('activity_monitor.unassigned_course') ?>';
          const timeRange = `${activity.check_in} - ${activity.check_out}`;
          
          block.innerHTML = `
            <div class="time-info">
              <div class="course-name">${courseName}</div>
              <div class="time-range">${timeRange}</div>
            </div>
          `;
          
          block.title = `${courseName}\n${timeRange}`;
          
          timeline.appendChild(block);
        }
      });
    }
    
    function renderHourlyData(hourlyData) {
      const timeline = document.getElementById('timeline-content');
      timeline.innerHTML = '';
      
      // 计算时间轴的总宽度（24小时）
      const timelineWidth = timeline.offsetWidth - 40;
      const hourWidth = timelineWidth / 24;
      
      // 找到最大活动数用于计算高度
      const maxCount = Math.max(...Object.values(hourlyData));
      
      Object.entries(hourlyData).forEach(([hour, count], index) => {
        if (count > 0) {
          const hourNum = parseInt(hour.split(':')[0]);
          const left = hourNum * hourWidth;
          const width = hourWidth * 0.8; // 留一些间距
          
          const block = document.createElement('div');
          block.className = 'activity-block';
          block.style.left = `${left}px`;
          block.style.width = `${width}px`;
          block.style.top = `${20 + (index * 50)}px`;
          
          block.innerHTML = `
            <div class="time-info">
              <div class="course-name"><?= t('activity_monitor.activity') ?></div>
              <div class="time-range">${hour}: ${count}<?= t('activity_monitor.times_activity') ?></div>
            </div>
          `;
          
          block.title = `${hour}: ${count} <?= t('activity_monitor.times_activity') ?>`;
          
          timeline.appendChild(block);
        }
      });
    }
    
    function initTimelineChart() {
      // 获取页面中的时间段数据
      const timelineData = <?= json_encode($page_data['timeline'] ?? []) ?>;
      console.log('Initial timeline data:', timelineData);
      
      if (timelineData && timelineData.length > 0) {
        renderTimelineFromData(timelineData);
      } else {
        // 如果没有数据，显示提示信息
        const timeline = document.getElementById('timeline-content');
        timeline.innerHTML = '<div class="no-activity"><?= t('activity_monitor.no_teaching_activities_today_click_sync') ?></div>';
      }
    }
    
    function updateActivities(data) {
      const container = document.getElementById('activities-list');
      
      container.innerHTML = '';
      
      data.activities.forEach(activity => {
        const item = document.createElement('div');
        item.className = `activity-item ${activity.is_inactive ? 'inactive' : ''}`;
        
        let courseHtml = '';
        if (activity.course) {
          courseHtml = `<div class="activity-course">${activity.course}</div>`;
        }
        
        let inactiveBadge = '';
        if (activity.is_inactive) {
          inactiveBadge = '<span class="inactive-badge"><?= t('activity_monitor.inactive') ?></span>';
        }
        
        let inactiveTime = '';
        if (activity.is_inactive && activity.inactive_hours > 0) {
          inactiveTime = `<div class="inactive-time"><?= t('activity_monitor.inactive_time') ?>: ${activity.inactive_hours} <?= t('activity_monitor.hours') ?></div>`;
        }
        
        let checkInHtml = '';
        if (activity.check_in) {
          checkInHtml = `<div class="activity-check-in"><?= t('activity_monitor.check_in_time') ?>: ${activity.check_in}</div>`;
        }
        
        let checkOutHtml = '';
        if (activity.check_out) {
          checkOutHtml = `<div class="activity-check-out"><?= t('activity_monitor.check_out_time') ?>: ${activity.check_out}</div>`;
        }
        
        item.innerHTML = `
          <div class="activity-avatar">
            ${activity.user.charAt(0)}
          </div>
          <div class="activity-content">
            <div class="activity-user">
              ${activity.user}
              ${inactiveBadge}
            </div>
            <div class="activity-action">${activity.action}</div>
            ${courseHtml}
            <div class="activity-time">${activity.time}</div>
            ${checkInHtml}
            ${checkOutHtml}
            ${inactiveTime}
          </div>
          <div class="activity-status status-${activity.status}"></div>
        `;
        
        container.appendChild(item);
      });
      
      // 同时更新时间段图
      updateChart(data);
    }
    
    function filterActivities() {
      const userFilter = document.getElementById('user-filter').value;
      currentUserFilter = userFilter;
      
      if (userFilter) {
        // 重新加载数据
        refreshData();
      } else {
        // 清除筛选，重新加载数据
        refreshData();
      }
    }
    
    function clearFilter() {
      document.getElementById('user-filter').value = '';
      currentUserFilter = '';
      refreshData();
    }
    
    function refreshData() {
      setLoading(true);
      
      const formData = new FormData();
      formData.append('action', 'get_all');
      if (currentUserFilter) {
        formData.append('user_filter', currentUserFilter);
      }
      
      fetch('ajax/activity_monitor_data.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          updateStats(data.data);
          updateActivities(data.data);
          document.getElementById('last-update').textContent = new Date().toLocaleTimeString('zh-CN', {hour12: false});
          showMessage('<?= t('activity_monitor.data_refresh_success') ?>', 'success');
        } else {
          showMessage(`<?= t('activity_monitor.refresh_failed') ?>: ${data.error}`, 'error');
        }
      })
      .catch(error => {
        console.error('<?= t('activity_monitor.refresh_failed') ?>:', error);
        showMessage('<?= t('activity_monitor.network_error_retry') ?>', 'error');
      })
      .finally(() => {
        setLoading(false);
      });
    }
    
    function generateTodayData() {
      setLoading(true);
      
      const formData = new FormData();
      formData.append('action', 'generate_today_data');
      
      fetch('ajax/update_access_statistics.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showMessage('<?= t('activity_monitor.teaching_data_sync_success') ?>', 'success');
          // 同步成功后刷新数据
          setTimeout(() => {
            refreshData();
          }, 1000);
        } else {
          showMessage(`<?= t('activity_monitor.sync_failed') ?>: ${data.error}`, 'error');
        }
      })
      .catch(error => {
        console.error('<?= t('activity_monitor.sync_teaching_data_failed') ?>:', error);
        showMessage('<?= t('activity_monitor.network_error_sync_failed') ?>', 'error');
      })
      .finally(() => {
        setLoading(false);
      });
    }
    
    // 自动刷新数据（每30秒）
    function startAutoRefresh() {
      refreshInterval = setInterval(() => {
        refreshData();
      }, 30000);
    }
    
    function stopAutoRefresh() {
      if (refreshInterval) {
        clearInterval(refreshInterval);
      }
    }
    
    // 实时更新趋势图
    function startRealTimeUpdates() {
      if (realTimeInterval) {
        clearInterval(realTimeInterval);
      }
      
      realTimeInterval = setInterval(() => {
        updateCurrentHourData();
      }, 5000); // 每5秒更新一次
      
      isRealTimeEnabled = true;
      document.getElementById('real-time-status').textContent = '<?= t('activity_monitor.real_time_updates_enabled_text') ?>';
      document.getElementById('real-time-status').className = 'status-indicator active';
    }
    
    function stopRealTimeUpdates() {
      if (realTimeInterval) {
        clearInterval(realTimeInterval);
        realTimeInterval = null;
      }
      
      isRealTimeEnabled = false;
      document.getElementById('real-time-status').textContent = '<?= t('activity_monitor.real_time_updates_disabled_text') ?>';
      document.getElementById('real-time-status').className = 'status-indicator';
    }
    
    // 自动同步教课数据
    function startAutoSync() {
      if (autoSyncInterval) {
        clearInterval(autoSyncInterval);
      }
      
      // 立即执行一次同步
      syncAttendanceData();
      
      // 每5分钟自动同步一次
      autoSyncInterval = setInterval(() => {
        syncAttendanceData();
      }, 300000); // 5分钟 = 300000毫秒
      
      document.getElementById('auto-sync-status').textContent = '<?= t('activity_monitor.auto_sync_enabled_text') ?>';
      document.getElementById('auto-sync-status').className = 'status-indicator active';
    }
    
    function stopAutoSync() {
      if (autoSyncInterval) {
        clearInterval(autoSyncInterval);
        autoSyncInterval = null;
      }
      
      document.getElementById('auto-sync-status').textContent = '<?= t('activity_monitor.auto_sync_disabled_text') ?>';
      document.getElementById('auto-sync-status').className = 'status-indicator';
    }
    
    function updateCurrentHourData() {
      const formData = new FormData();
      formData.append('action', 'get_current_hour_data');
      
      fetch('ajax/update_access_statistics.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // 刷新整个时间段图
          refreshData();
        }
      })
      .catch(error => {
        console.error('<?= t('activity_monitor.update_current_hour_data_failed') ?>:', error);
      });
    }
    

    
    function syncAttendanceData() {
      const formData = new FormData();
      formData.append('action', 'sync_attendance');
      
      fetch('ajax/update_access_statistics.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          console.log('<?= t('activity_monitor.teaching_data_auto_sync_success_log') ?>');
          // 不刷新整个图表，保持当前显示的数据
          // refreshData();
        } else {
          console.error('<?= t('activity_monitor.auto_sync_failed_log') ?>:', data.error);
        }
      })
      .catch(error => {
        console.error('<?= t('activity_monitor.auto_sync_failed_log') ?>:', error);
      });
    }
    
    // 页面加载完成后启动实时更新和自动同步，但不立即刷新数据
    document.addEventListener('DOMContentLoaded', function() {
      // 不启动自动刷新，避免覆盖初始数据
      // startAutoRefresh();
      // 暂时禁用实时更新，避免数据干扰
      // startRealTimeUpdates();
      startAutoSync();
      
      // 初始化时间段图
      initTimelineChart();
    });
    
         // 页面失去焦点时停止自动刷新，获得焦点时重新启动
     document.addEventListener('visibilitychange', function() {
       if (document.hidden) {
         stopAutoRefresh();
         stopRealTimeUpdates();
         stopAutoSync();
       } else {
         // 不重新启动自动刷新，避免数据覆盖
         // startAutoRefresh();
         // 暂时禁用实时更新，避免数据干扰
         // startRealTimeUpdates();
         startAutoSync();
       }
     });
     
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
   </script>
    </main>
  </div>
</body>
</html>
