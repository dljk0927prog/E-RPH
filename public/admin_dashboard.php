<?php
// admin_dashboard.php - 管理员仪表板
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

// 检查是否登录且是管理员
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login_roles.php');
    exit;
}

$user = $_SESSION['user'];



// 当前页面标识用于侧边栏高亮
$current_page = 'dashboard';

// 获取统计数据
try {
    require_once __DIR__ . '/../db.php';
    
    $stats = [];
    
    // 用户统计
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $role_stats = $stmt->fetchAll();
    foreach ($role_stats as $stat) {
        $stats[$stat['role']] = $stat['count'];
    }
    
    // 课程统计
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
    $stats['courses'] = $stmt->fetch()['count'];
    
    // 出勤统计
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM attendance");
    $stats['attendance'] = $stmt->fetch()['count'];
    
    // 今日教课报告统计
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE DATE(date) = ?");
    $stmt->execute([$today]);
    $stats['today_reports'] = $stmt->fetch()['count'];
    
    // 本周教课报告统计
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE DATE(date) >= ?");
    $stmt->execute([$week_start]);
    $stats['week_reports'] = $stmt->fetch()['count'];
    
    // 教案统计
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM lesson_plans");
    $stats['lesson_plans'] = $stmt->fetch()['count'];
    
    // 获取今天活跃的老师报告状态数据
    $teacher_reports = [];
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.name as teacher_name,
            COUNT(DISTINCT a.id) as total_reports,
            COUNT(DISTINCT CASE WHEN DATE(a.date) = CURDATE() THEN a.id END) as today_reports,
            COUNT(DISTINCT CASE WHEN DATE(a.date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN a.id END) as week_reports,
            MAX(a.created_at) as last_report_time,
            COUNT(DISTINCT c.id) as assigned_courses
        FROM users u
        LEFT JOIN course_teachers ct ON ct.teacher_id = u.id
        LEFT JOIN courses c ON c.id = ct.course_id
        LEFT JOIN attendance a ON a.course_id = c.id
        WHERE u.role = 'teacher'
        GROUP BY u.id, u.name
        HAVING COUNT(DISTINCT CASE WHEN DATE(a.date) = CURDATE() THEN a.id END) > 0
        ORDER BY u.name
    ");
    $teacher_reports = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = t('errors.get_statistics') . ": " . $e->getMessage();
    $teacher_reports = [];
}
?>
<!doctype html>
<html lang="<?= t('common.language_code') ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('dashboard.admin_title') ?></title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <link rel="stylesheet" href="assets/css/mobile-optimization.css">
  <script src="assets/js/theme-sync.js"></script>
  <style>
    /* 内联CSS - 最高优先级 */
    @import url('data:text/css,body#admin-dashboard .header{background:linear-gradient(90deg,#4a90e2,#7bb3f0)!important;color:white!important;padding:14px 20px!important;display:flex!important;justify-content:space-between!important;align-items:center!important;box-shadow:0 2px 10px rgba(0,0,0,0.1)!important;position:relative!important;z-index:100!important;width:100%!important;box-sizing:border-box!important;}body#admin-dashboard .header h1{font-size:20px!important;font-weight:600!important;margin:0!important;text-shadow:0 1px 2px rgba(0,0,0,0.1)!important;color:white!important;}body#admin-dashboard .header>div{display:flex!important;display:flex!important;align-items:center!important;gap:12px!important;}body#admin-dashboard .header a{color:white!important;text-decoration:none!important;background:rgba(255,255,255,0.15)!important;padding:8px 12px!important;border-radius:6px!important;transition:all 0.2s ease!important;font-size:14px!important;font-weight:500!important;border:1px solid rgba(255,255,255,0.2)!important;outline:none!important;box-shadow:none!important;backdrop-filter:blur(10px)!important;}body#admin-dashboard .header a:hover{background:rgba(255,255,255,0.25)!important;border-color:rgba(255,255,255,0.3)!important;transform:translateY(-1px)!important;}body#admin-dashboard .header .profile-trigger{background:rgba(255,255,255,0.15)!important;border:1px solid rgba(255,255,255,0.2)!important;color:white!important;backdrop-filter:blur(10px)!important;}body#admin-dashboard .header .profile-trigger:hover{background:rgba(255,255,255,0.25)!important;border-color:rgba(255,255,255,0.3)!important;}');
    
    /* 深色模式下的老师报告区域样式 - 深色主题 */
    html[data-theme="dark"] #teacherReportsSection {
      background: #2d2d2d !important;
      background-color: #2d2d2d !important;
      color: #ffffff !important;
      border: 1px solid #404040 !important;
      border-radius: 12px !important;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
      padding: 20px !important;
      margin: 20px 0 !important;
    }
    
    /* 深色模式下所有子元素的基础样式 */
    html[data-theme="dark"] #teacherReportsSection * {
      background: #2d2d2d !important;
      background-color: #2d2d2d !important;
      color: #ffffff !important;
      border-color: #404040 !important;
    }
    
    /* 特殊处理section-header */
    html[data-theme="dark"] #teacherReportsSection .section-header {
      background: #1a1a1a !important;
      background-color: #1a1a1a !important;
      border: 1px solid #404040 !important;
      border-left: 4px solid #4a90e2 !important;
      border-radius: 8px !important;
      padding: 20px !important;
      margin-bottom: 20px !important;
      color: #ffffff !important;
    }
    
    /* 特殊处理teacher-report-card */
    html[data-theme="dark"] #teacherReportsSection .teacher-report-card {
      background: #1a1a1a !important;
      background-color: #1a1a1a !important;
      border: 1px solid #404040 !important;
      border-left: 3px solid #4a90e2 !important;
      border-radius: 8px !important;
      padding: 12px !important;
      margin-bottom: 16px !important;
      color: #ffffff !important;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
      min-height: 140px !important;
      max-width: 180px !important;
      flex: 0 0 auto !important;
    }
    
    /* 特殊处理标题和描述 */
    html[data-theme="dark"] #teacherReportsSection .section-title {
      color: #4a90e2 !important;
      font-size: 24px !important;
      font-weight: 600 !important;
      margin-bottom: 10px !important;
      background: transparent !important;
    }
    
    html[data-theme="dark"] #teacherReportsSection .section-description {
      color: #b0b7c3 !important;
      font-size: 14px !important;
      margin-bottom: 20px !important;
      background: transparent !important;
    }
    
    /* 特殊处理教师信息 */
    html[data-theme="dark"] #teacherReportsSection .teacher-info h3 {
      color: #4a90e2 !important;
      font-size: 14px !important;
      font-weight: 600 !important;
      margin-bottom: 6px !important;
      background: transparent !important;
      text-align: center !important;
    }
    
    /* 特殊处理其他文本元素 */
    html[data-theme="dark"] #teacherReportsSection .assigned-courses,
    html[data-theme="dark"] #teacherReportsSection .stat-label,
    html[data-theme="dark"] #teacherReportsSection .last-report-label,
    html[data-theme="dark"] #teacherReportsSection .last-report-time {
      color: #b0b7c3 !important;
      background: transparent !important;
      font-size: 10px !important;
    }
    
    /* 深色模式下的分配课程标签 */
    html[data-theme="dark"] #teacherReportsSection .assigned-courses {
      background: #1e3a8a !important;
      color: #60a5fa !important;
      padding: 2px 4px !important;
      border-radius: 3px !important;
      font-size: 10px !important;
      font-weight: 500 !important;
      text-align: center !important;
      display: block !important;
      margin-bottom: 8px !important;
    }
    
    /* 深色模式下的统计值 */
    html[data-theme="dark"] #teacherReportsSection .stat-value {
      color: #ffffff !important;
      background: #404040 !important;
      padding: 2px 4px !important;
      border-radius: 3px !important;
      font-weight: 600 !important;
      font-size: 10px !important;
    }
    
    /* 深色模式下的最后报告区域 */
    html[data-theme="dark"] #teacherReportsSection .last-report {
      margin: 8px 0 !important;
      padding: 4px !important;
      background: #404040 !important;
      border-radius: 3px !important;
      font-size: 9px !important;
      text-align: center !important;
    }
    
    /* 特殊处理按钮 */
    html[data-theme="dark"] #teacherReportsSection .view-reports-btn {
      background: #4a90e2 !important;
      background-color: #4a90e2 !important;
      color: white !important;
      padding: 4px 8px !important;
      border-radius: 3px !important;
      border: none !important;
      cursor: pointer !important;
      text-decoration: none !important;
      font-size: 10px !important;
      font-weight: 500 !important;
      transition: background-color 0.2s ease !important;
      display: inline-block !important;
      width: 100% !important;
      text-align: center !important;
      box-sizing: border-box !important;
    }
    
    html[data-theme="dark"] #teacherReportsSection .view-reports-btn:hover {
      background: #7bb3f0 !important;
      background-color: #7bb3f0 !important;
    }
    
    /* 强制覆盖所有可能的深色模式变量 */
    html[data-theme="dark"] #teacherReportsSection,
    html[data-theme="dark"] #teacherReportsSection * {
      --bg-primary: #2d2d2d !important;
      --bg-secondary: #1a1a1a !important;
      --text-primary: #ffffff !important;
      --text-secondary: #b0b7c3 !important;
      --text-muted: #7a8288 !important;
      --border-color: #404040 !important;
      --shadow-color: rgba(0, 0, 0, 0.3) !important;
      --accent-color: #4a90e2 !important;
      --accent-hover: #7bb3f0 !important;
    }
    
    /* 深色模式CSS变量 - 使用最高优先级 */
    :root {
      --bg-primary: #f5f5f5 !important;
      --bg-secondary: #ffffff !important;
      --text-primary: #333333 !important;
      --text-secondary: #666666 !important;
      --text-muted: #999999 !important;
      --border-color: #e1e5e9 !important;
      --shadow-color: rgba(0, 0, 0, 0.08) !important;
      --accent-color: #4a90e2 !important;
      --accent-hover: #7bb3f0 !important;
      --header-bg: linear-gradient(90deg, #4a90e2, #7bb3f0) !important;
      --card-border: #4a90e2 !important;
      --success-bg: #d4edda !important;
      --success-text: #155724 !important;
      --success-border: #c3e6cb !important;
      --error-bg: #f8d7da !important;
      --error-text: #721c24 !important;
      --error-border: #f5c6cb !important;
      --warning-bg: #fff3cd !important;
      --warning-text: #856404 !important;
      --warning-border: #ffeaa7 !important;
    }
    
    /* 深色模式样式 */
    [data-theme="dark"] {
      --bg-primary: #1a1a1a !important;
      --bg-secondary: #2d2d2d !important;
      --text-primary: #ffffff !important;
      --text-secondary: #e0e0e0 !important;
      --text-muted: #b0b0b0 !important;
      --border-color: #404040 !important;
      --shadow-color: rgba(0, 0, 0, 0.3) !important;
      --accent-color: #4a90e2 !important;
      --accent-hover: #7bb3f0 !important;
      --header-bg: linear-gradient(90deg, #2d3748, #4a5568) !important;
      --card-border: #4a90e2 !important;
      --success-bg: #22543d !important;
      --success-text: #9ae6b4 !important;
      --success-border: #38a169 !important;
      --error-bg: #742a2a !important;
      --error-text: #feb2b2 !important;
      --error-border: #e53e3e !important;
      --warning-bg: #744210 !important;
      --warning-text: #faf089 !important;
      --warning-border: #d69e2e !important;
    }
    
    /* 基础Header样式 - 最高优先级 */
    .header,
    header.header,
    body .header,
    html .header,
    * .header {
      background: var(--header-bg) !important;
      color: white !important;
      padding: 14px 20px !important;
      display: flex !important;
      justify-content: space-between !important;
      align-items: center !important;
      box-shadow: 0 2px 10px var(--shadow-color) !important;
      transition: background 0.3s ease !important;
      position: relative !important;
      z-index: 100 !important;
      width: 100% !important;
      box-sizing: border-box !important;
    }
    
    /* 个人资料按钮在header中的样式 - 最高优先级 */
    .header .profile-trigger,
    header.header .profile-trigger,
    body .header .profile-trigger,
    html .header .profile-trigger,
    * .header .profile-trigger {
      background: rgba(255, 255, 255, 0.15) !important;
      border: 1px solid rgba(255, 255, 255, 0.2) !important;
      color: white !important;
      backdrop-filter: blur(10px) !important;
    }
    
    .header .profile-trigger:hover,
    header.header .profile-trigger:hover,
    body .header .profile-trigger:hover,
    html .header .profile-trigger:hover,
    * .header .profile-trigger:hover {
      background: rgba(255, 255, 255, 0.25) !important;
      border-color: rgba(255, 255, 255, 0.3) !important;
    }
    
    /* 老师报告状态监控区域样式 - 统一浅色和深色模式 */
    .teacher-reports-section {
      margin-top: 20px;
      background: white;
      border: 1px solid #e1e5e9;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
    }
    
    .teacher-reports-section:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
      border-color: #4a90e2;
    }
    
    .section-title {
      color: #4a90e2;
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 10px;
    }
    
    .section-description {
      color: #666666;
      margin-bottom: 20px;
      font-size: 14px;
    }
    
    .teacher-reports-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    
    .teacher-report-card {
      background: white;
      border: 1px solid #e1e5e9;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      border-left: 3px solid #4a90e2;
    }
    
    .teacher-report-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
      border-color: #4a90e2;
    }
    
    .teacher-info h3 {
      color: #4a90e2;
      font-size: 18px;
      font-weight: 600;
      margin: 0 0 10px 0;
    }
    
    .teacher-meta {
      margin-bottom: 15px;
    }
    
    .assigned-courses {
      color: #666666;
      font-size: 14px;
      background: rgba(74, 144, 226, 0.1);
      padding: 4px 8px;
      border-radius: 4px;
      border: 1px solid rgba(74, 144, 226, 0.2);
    }
    
    .report-stats {
      margin: 15px 0;
    }
    
    .stat-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
      padding: 8px 0;
      border-bottom: 1px solid #e1e5e9;
    }
    
    .stat-label {
      color: #666666;
      font-size: 14px;
    }
    
    .stat-value {
      font-weight: 600;
      font-size: 16px;
      padding: 4px 8px;
      border-radius: 4px;
      min-width: 30px;
      text-align: center;
      background: #f8f9fa;
      color: #333333;
      border: 1px solid #e1e5e9;
    }
    
    .stat-value.active {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .stat-value.inactive {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    .last-report {
      margin: 15px 0;
      padding: 10px;
      background: rgba(74, 144, 226, 0.05);
      border-radius: 6px;
      border: 1px solid rgba(74, 144, 226, 0.1);
    }
    
    .last-report-label {
      color: #666666;
      font-size: 14px;
      margin-right: 10px;
    }
    
    .last-report-time {
      color: #333333;
      font-weight: 500;
    }
    
    .never-submitted {
      color: #721c24;
      font-style: italic;
    }
    
    .view-reports-btn {
      background: #4a90e2;
      color: white;
      padding: 8px 16px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.3s ease;
      display: inline-block;
      border: none;
    }
    
    .view-reports-btn:hover {
      background: #7bb3f0;
      transform: translateY(-1px);
      box-shadow: 0 2px 8px rgba(74, 144, 226, 0.3);
    }
    
    .no-data-card {
      background: white;
      border: 1px solid #e1e5e9;
      border-radius: 8px;
      padding: 40px;
      text-align: center;
      color: #666666;
      border-left: 4px solid #4a90e2;
    }
    
    /* 区域头部样式 */
    .section-header {
      background: white;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      border-left: 4px solid #4a90e2;
      transition: all 0.3s ease;
    }
    
    .section-header:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    }
    
    /* 深色模式下的区域头部样式 - 与浅色模式保持一致 */
    [data-theme="dark"] .section-header {
      background: white !important;
      padding: 20px !important;
      border-radius: 12px !important;
      margin-bottom: 20px !important;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important;
      border-left: 4px solid #4a90e2 !important;
      transition: all 0.3s ease !important;
    }
    
    [data-theme="dark"] .section-header:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12) !important;
    }
    

    

    

    
    [data-theme="dark"] .teacher-reports-section {
      border: 1px solid #e1e5e9 !important;
      border-radius: 12px !important;
      padding: 20px !important;
      transition: all 0.3s ease !important;
    }
    
    [data-theme="dark"] .teacher-reports-section:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12) !important;
      border-color: #4a90e2 !important;
    }
    
    [data-theme="dark"] .section-header {
      background: white !important;
      border: 1px solid #e1e5e9 !important;
      border-left: 4px solid #4a90e2 !important;
    }
    
    [data-theme="dark"] .section-title {
      color: #4a90e2 !important;
      font-size: 24px !important;
      font-weight: 600 !important;
      margin-bottom: 10px !important;
    }
    
    [data-theme="dark"] .section-description {
      color: #666666 !important;
      margin-bottom: 20px !important;
      font-size: 14px !important;
    }
    
    [data-theme="dark"] .teacher-reports-grid {
      display: grid !important;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important;
      gap: 20px !important;
      margin-top: 20px !important;
    }
    
    [data-theme="dark"] .teacher-report-card {
      background: white !important;
      border: 1px solid #e1e5e9 !important;
      border-radius: 8px !important;
      padding: 20px !important;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
      transition: all 0.3s ease !important;
      border-left: 3px solid #4a90e2 !important;
    }
    
    [data-theme="dark"] .teacher-report-card:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12) !important;
      border-color: #4a90e2 !important;
    }
    
    [data-theme="dark"] .teacher-info h3 {
      color: #4a90e2 !important;
      font-size: 18px !important;
      font-weight: 600 !important;
      margin: 0 0 10px 0 !important;
    }
    
    [data-theme="dark"] .teacher-meta {
      margin-bottom: 15px !important;
    }
    
    [data-theme="dark"] .assigned-courses {
      color: #666666 !important;
      font-size: 14px !important;
      background: rgba(74, 144, 226, 0.1) !important;
      padding: 4px 8px !important;
      border-radius: 4px !important;
      border: 1px solid rgba(74, 144, 226, 0.2) !important;
    }
    
    [data-theme="dark"] .report-stats {
      margin: 15px 0 !important;
    }
    
    [data-theme="dark"] .stat-row {
      display: flex !important;
      justify-content: space-between !important;
      align-items: center !important;
      margin-bottom: 8px !important;
      padding: 8px 0 !important;
      border-bottom: 1px solid #e1e5e9 !important;
    }
    
    [data-theme="dark"] .stat-label {
      color: #666666 !important;
      font-size: 14px !important;
    }
    
    [data-theme="dark"] .stat-value {
      font-weight: 600 !important;
      font-size: 16px !important;
      padding: 4px 8px !important;
      border-radius: 4px !important;
      min-width: 30px !important;
      text-align: center !important;
      background: #f8f9fa !important;
      color: #333333 !important;
      border: 1px solid #e1e5e9 !important;
    }
    
    [data-theme="dark"] .stat-value.active {
      background: #d4edda !important;
      color: #155724 !important;
      border: 1px solid #c3e6cb !important;
    }
    
    [data-theme="dark"] .stat-value.inactive {
      background: #f8d7da !important;
      color: #721c24 !important;
      border: 1px solid #f5c6cb !important;
    }
    
    [data-theme="dark"] .last-report {
      margin: 15px 0 !important;
      padding: 10px !important;
      background: rgba(74, 144, 226, 0.05) !important;
      border-radius: 6px !important;
      border: 1px solid rgba(74, 144, 226, 0.1) !important;
    }
    
    [data-theme="dark"] .last-report-label {
      color: #666666 !important;
      margin-right: 10px !important;
    }
    
    [data-theme="dark"] .last-report-time {
      color: #333333 !important;
      font-weight: 500 !important;
    }
    
    [data-theme="dark"] .never-submitted {
      color: #721c24 !important;
      font-style: italic !important;
    }
    
    [data-theme="dark"] .view-reports-btn {
      background: #4a90e2 !important;
      color: white !important;
      padding: 8px 16px !important;
      border-radius: 6px !important;
      text-decoration: none !important;
      font-size: 14px !important;
      font-weight: 500 !important;
      transition: all 0.3s ease !important;
      display: inline-block !important;
      border: none !important;
    }
    
    [data-theme="dark"] .view-reports-btn:hover {
      background: #7bb3f0 !important;
      transform: translateY(-1px) !important;
      box-shadow: 0 2px 8px rgba(74, 144, 226, 0.3) !important;
    }
    
    [data-theme="dark"] .no-data-card {
      background: white !important;
      border: 1px solid #e1e5e9 !important;
      border-radius: 8px !important;
      padding: 40px !important;
      text-align: center !important;
      color: #666666 !important;
      border-left: 4px solid #4a90e2 !important;
    }
    
    /* 强制覆盖深色模式下的所有文字颜色 */
    [data-theme="dark"] .teacher-reports-section h1,
    [data-theme="dark"] .teacher-reports-section h2,
    [data-theme="dark"] .teacher-reports-section h3,
    [data-theme="dark"] .teacher-reports-section p,
    [data-theme="dark"] .teacher-reports-section span,
    [data-theme="dark"] .teacher-reports-section div {
      color: #333333 !important;
    }
    
    [data-theme="dark"] .teacher-reports-section .section-title {
      color: #4a90e2 !important;
    }
    
    [data-theme="dark"] .teacher-reports-section .section-description {
      color: #666666 !important;
    }
    
    [data-theme="dark"] .teacher-reports-section .teacher-info h3 {
      color: #4a90e2 !important;
    }
    
    [data-theme="dark"] .teacher-reports-section .assigned-courses {
      color: #666666 !important;
    }
    
    [data-theme="dark"] .teacher-reports-section .stat-label {
      color: #666666 !important;
    }
    
    [data-theme="dark"] .teacher-reports-section .last-report-label {
      color: #666666 !important;
    }
    
    [data-theme="dark"] .teacher-reports-section .last-report-time {
      color: #333333 !important;
    }
    
    [data-theme="dark"] .teacher-reports-section .never-submitted {
      color: #721c24 !important;
    }
    
    /* 额外的强制覆盖规则 - 确保深色模式下完全一致 */
    [data-theme="dark"] .teacher-reports-section * {
      background: white !important;
    }
    
    [data-theme="dark"] .teacher-reports-section .teacher-report-card {
      background: white !important;
    }
    
    [data-theme="dark"] .teacher-reports-section .section-header {
      background: white !important;
    }
    
    [data-theme="dark"] .teacher-reports-section .no-data-card {
      background: white !important;
    }
    
    /* 确保按钮在深色模式下保持蓝色 */
    [data-theme="dark"] .teacher-reports-section .view-reports-btn {
      background: #4a90e2 !important;
      color: white !important;
    }
    
    [data-theme="dark"] .teacher-reports-section .view-reports-btn:hover {
      background: #7bb3f0 !important;
    }
    
    /* 最强优先级覆盖 - 使用属性选择器 */
    [data-theme="dark"] [class*="teacher-reports"] {
      background: white !important;
    }
    
    [data-theme="dark"] [class*="teacher-reports"] * {
      background: white !important;
      color: #333333 !important;
    }
    
    /* 强制覆盖所有可能的深色模式变量 */
    [data-theme="dark"] .teacher-reports-section,
    [data-theme="dark"] .teacher-reports-section *,
    [data-theme="dark"] .teacher-reports-section > *,
    [data-theme="dark"] .teacher-reports-section > * > *,
    [data-theme="dark"] .teacher-reports-section > * > * > * {
      background: white !important;
      color: #333333 !important;
      border-color: #e1e5e9 !important;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important;
    }
    
    /* 深色模式下的统计卡片样式 */
    [data-theme="dark"] .stats-grid {
      display: grid !important;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
      gap: 20px !important;
      margin: 20px 0 !important;
    }
    
    [data-theme="dark"] .stat-card {
      background: var(--bg-secondary) !important;
      border: 1px solid var(--border-color) !important;
      border-radius: 8px !important;
      padding: 20px !important;
      text-align: center !important;
      box-shadow: 0 2px 4px var(--shadow-color) !important;
      transition: all 0.3s ease !important;
    }
    
    [data-theme="dark"] .stat-card:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 4px 8px var(--shadow-color) !important;
      border-color: var(--accent-color) !important;
    }
    
    [data-theme="dark"] .stat-number {
      font-size: 32px !important;
      font-weight: 700 !important;
      color: var(--accent-color) !important;
      margin-bottom: 8px !important;
    }
    
    [data-theme="dark"] .stat-label {
      color: var(--text-secondary) !important;
      font-size: 14px !important;
      font-weight: 500 !important;
    }
    
    /* 确保统计卡片在深色模式下保持深色主题 */
    [data-theme="dark"] .stats-grid .stat-card {
      background: var(--bg-secondary) !important;
      border: 1px solid var(--border-color) !important;
      color: var(--text-primary) !important;
    }
    
    [data-theme="dark"] .stats-grid .stat-number {
      color: var(--accent-color) !important;
    }
    
    [data-theme="dark"] .stats-grid .stat-label {
      color: var(--text-secondary) !important;
    }
    
    /* 深色模式下的页面整体样式 */
    [data-theme="dark"] body {
      background: var(--bg-primary) !important;
      color: var(--text-primary) !important;
    }
    
    [data-theme="dark"] main {
      background: var(--bg-primary) !important;
      padding: 20px !important;
    }
    
    [data-theme="dark"] .welcome-section {
      background: var(--bg-secondary) !important;
      border: 1px solid var(--border-color) !important;
      border-radius: 8px !important;
      padding: 30px !important;
      margin-bottom: 30px !important;
      box-shadow: 0 2px 4px var(--shadow-color) !important;
    }
    
    [data-theme="dark"] .welcome-section h1 {
      color: var(--text-primary) !important;
      font-size: 28px !important;
      font-weight: 600 !important;
      margin-bottom: 10px !important;
    }
    
    [data-theme="dark"] .welcome-section p {
      color: var(--text-secondary) !important;
      font-size: 16px !important;
      margin: 0 !important;
    }
    
    /* 确保欢迎卡片在深色模式下保持深色主题 */
    [data-theme="dark"] .admin-main-card {
      background: var(--bg-secondary) !important;
      border: 1px solid var(--border-color) !important;
      color: var(--text-primary) !important;
    }
    
    [data-theme="dark"] .admin-main-card .welcome-title {
      color: var(--accent-color) !important;
    }
    
    [data-theme="dark"] .admin-main-card p {
      color: var(--text-secondary) !important;
    }
    
    /* 最高优先级Header样式 - 使用ID选择器 */
    #admin-dashboard .header,
    #admin-dashboard header.header,
    body#admin-dashboard .header,
    html body#admin-dashboard .header {
      background: var(--header-bg) !important;
      color: white !important;
      padding: 14px 20px !important;
      display: flex !important;
      justify-content: space-between !important;
      align-items: center !important;
      box-shadow: 0 2px 10px var(--shadow-color) !important;
      transition: background 0.3s ease !important;
      position: relative !important;
      z-index: 100 !important;
      width: 100% !important;
      box-sizing: border-box !important;
    }
    
    /* 个人资料按钮在header中的样式 - 使用ID选择器最高优先级 */
    #admin-dashboard .header .profile-trigger,
    #admin-dashboard header.header .profile-trigger,
    body#admin-dashboard .header .profile-trigger,
    html body#admin-dashboard .header .profile-trigger {
      background: rgba(255, 255, 255, 0.15) !important;
      border: 1px solid rgba(255, 255, 255, 0.2) !important;
      color: white !important;
      backdrop-filter: blur(10px) !important;
    }
    
    #admin-dashboard .header .profile-trigger:hover,
    #admin-dashboard header.header .profile-trigger:hover,
    body#admin-dashboard .header .profile-trigger:hover,
    html body#admin-dashboard .header .profile-trigger:hover {
      background: rgba(255, 255, 255, 0.25) !important;
      border-color: rgba(255, 255, 255, 0.3) !important;
    }
    
    .header h1,
    header.header h1,
    body .header h1,
    html .header h1,
    * .header h1 {
      font-size: 20px !important;
      font-weight: 600 !important;
      margin: 0 !important;
      text-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
      color: white !important;
    }
    
    .header > div,
    header.header > div,
    body .header > div,
    html .header > div,
    * .header > div {
      display: flex !important;
      align-items: center !important;
      gap: 12px !important;
    }
    
    .header a,
    header.header a,
    body .header a,
    html .header a,
    * .header a {
      color: white !important;
      text-decoration: none !important;
      background: rgba(255,255,255,0.15) !important;
      padding: 8px 12px !important;
      border-radius: 6px !important;
      transition: all 0.2s ease !important;
      font-size: 14px !important;
      font-weight: 500 !important;
      border: 1px solid rgba(255,255,255,0.2) !important;
      outline: none !important;
      box-shadow: none !important;
      backdrop-filter: blur(10px) !important;
    }
    
    .header a:hover,
    header.header a:hover,
    body .header a:hover,
    html .header a:hover,
    * .header a:hover {
      background: rgba(255,255,255,0.25) !important;
      border-color: rgba(255,255,255,0.3) !important;
      transform: translateY(-1px) !important;
    }
    

    
    /* 深色模式样式 - 使用最高优先级 */
    [data-theme="dark"] {
      --bg-primary: #0f1419 !important;
      --bg-secondary: #1e2328 !important;  /* 深色卡片背景 */
      --text-primary: #ffffff !important;  /* 白色文字 */
      --text-secondary: #b0b7c3 !important;  /* 浅灰色文字 */
      --text-muted: #7a8288 !important;  /* 深灰色文字 */
      --border-color: #2d3748 !important;  /* 深色边框 */
      --shadow-color: rgba(0, 0, 0, 0.4) !important;  /* 深色阴影 */
      --accent-color: #60a5fa !important;  /* 蓝色强调色 */
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
      --card-hover-bg: #2a2f35 !important;  /* 深色悬停背景 */
      --card-hover-shadow: rgba(0, 0, 0, 0.5) !important;  /* 深色悬停阴影 */
      --gradient-start: #1e3a8a !important;
      --gradient-end: #3b82f6 !important;
    }
    
    /* 特殊处理透明背景和边框 */
    
    /* 特殊处理透明背景和边框 */
    [data-theme="dark"] .profile-trigger,
    [data-theme="dark"] .profile-trigger:hover {
      background: rgba(255, 255, 255, 0.15);
      border-color: rgba(255, 255, 255, 0.2);
    }
    
    [data-theme="dark"] .modal-backdrop {
      background: rgba(0, 0, 0, 0.5);
    }
    
    /* 基础样式 - 降低优先级 */
    body {
      background: var(--bg-primary);
      color: var(--text-primary);
      transition: background-color 0.3s ease, color 0.3s ease;
    }
    
    /* 深色模式覆盖 - 降低优先级 */
    [data-theme="dark"] body,
    [data-theme="dark"] .admin-layout,
    [data-theme="dark"] .admin-main-card,
    [data-theme="dark"] .page-header {
      background: var(--bg-primary) !important;
      color: var(--text-primary) !important;
    }
    
    /* 深色模式卡片覆盖 - 降低优先级 */
    [data-theme="dark"] .stat-card,
    [data-theme="dark"] .teacher-report-card,
    [data-theme="dark"] .section-header,
    [data-theme="dark"] .no-data-card,
    [data-theme="dark"] .admin-layout .stat-card,
    [data-theme="dark"] .admin-layout .teacher-report-card,
    [data-theme="dark"] .admin-layout .admin-main-card,
    [data-theme="dark"] .admin-layout .page-header,
    [data-theme="dark"] .admin-layout .content-card,
    [data-theme="dark"] .admin-layout .card,
    [data-theme="dark"] .admin-layout .panel,
    [data-theme="dark"] .admin-layout .section {
      background: var(--bg-secondary) !important;
      color: var(--text-primary) !important;
      border-color: var(--border-color) !important;
      box-shadow: 0 4px 15px var(--shadow-color) !important;
      border-radius: 12px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* 深色模式卡片悬停效果美化 - 降低优先级 */
    [data-theme="dark"] .stat-card:hover,
    [data-theme="dark"] .teacher-report-card:hover,
    [data-theme="dark"] .section-header:hover {
      background: var(--card-hover-bg) !important;
      transform: translateY(-2px) !important;
      box-shadow: 0 8px 25px var(--card-hover-shadow) !important;
      border-color: var(--accent-color) !important;
    }
    
    /* 深色模式统计卡片特殊美化 - 降低优先级 */
    [data-theme="dark"] .stat-card {
      background: white !important;
      border: 1px solid #e1e5e9 !important;
      border-left: 4px solid #4a90e2 !important;
      position: relative;
      overflow: hidden;
    }
    
    [data-theme="dark"] .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, #1e3a8a, #3b82f6);
      opacity: 0.8;
    }
    
    /* 深色模式老师报告卡片美化 - 降低优先级 */
    [data-theme="dark"] .teacher-report-card {
      background: white !important;
      border: 1px solid #e1e5e9 !important;
      border-left: 3px solid #4a90e2 !important;
    }
    
    /* 深色模式区域头部美化 - 降低优先级 */
    [data-theme="dark"] .section-header {
      background: white !important;
      border: 1px solid #e1e5e9 !important;
      border-left: 4px solid #4a90e2 !important;
    }
    
    /* 深色模式文字覆盖 - 确保卡片内文字保持白色 */
    [data-theme="dark"] .stat-number,
    [data-theme="dark"] .stat-label,
    [data-theme="dark"] .teacher-info h3,
    [data-theme="dark"] .assigned-courses,
    [data-theme="dark"] .stat-value,
    [data-theme="dark"] .last-report-label,
    [data-theme="dark"] .last-report-time,
    [data-theme="dark"] .welcome-title,
    [data-theme="dark"] .section-title,
    [data-theme="dark"] .section-description,
    [data-theme="dark"] .admin-layout .stat-number,
    [data-theme="dark"] .admin-layout .stat-label,
    [data-theme="dark"] .admin-layout h1,
    [data-theme="dark"] .admin-layout h2,
    [data-theme="dark"] .admin-layout h3,
    [data-theme="dark"] .admin-layout p,
    [data-theme="dark"] .admin-layout span,
    [data-theme="dark"] .admin-layout div {
      color: #ffffff !important;
    }
    
    /* 深色模式统计数字美化 - 使用白色 */
    [data-theme="dark"] .stat-number {
      color: #ffffff !important;
      background: none !important;
      -webkit-background-clip: initial !important;
      -webkit-text-fill-color: initial !important;
      background-clip: initial !important;
      text-shadow: none !important;
      font-weight: 800;
    }
    
    /* 深色模式标题美化 - 使用白色 */
    [data-theme="dark"] .welcome-title,
    [data-theme="dark"] .section-title {
      color: #ffffff !important;
      background: none !important;
      -webkit-background-clip: initial !important;
      -webkit-text-fill-color: initial !important;
      background-clip: initial !important;
      font-weight: 700;
    }
    
    /* 深色模式描述文字美化 - 使用白色 */
    [data-theme="dark"] .section-description {
      color: #ffffff !important;
      background: none !important;
      -webkit-background-clip: initial !important;
      -webkit-text-fill-color: initial !important;
      background-clip: initial !important;
      font-weight: 500;
    }
    
    /* 深色模式次要文字覆盖 - 确保次要文字保持白色 */
    [data-theme="dark"] .text-secondary,
    [data-theme="dark"] .text-muted,
    [data-theme="dark"] .stat-label,
    [data-theme="dark"] .assigned-courses,
    [data-theme="dark"] .last-report-label,
    [data-theme="dark"] .section-description {
      color: #ffffff !important;
    }
    
    /* 深色模式状态值美化 - 降低优先级 */
    [data-theme="dark"] .stat-value.active {
      background: linear-gradient(135deg, var(--success-bg), var(--success-border)) !important;
      color: var(--success-text) !important;
      border: 1px solid var(--success-border) !important;
    }
    
    [data-theme="dark"] .stat-value.inactive {
      background: linear-gradient(135deg, var(--error-bg), var(--error-border)) !important;
      color: var(--error-text) !important;
      border: 1px solid var(--error-border) !important;
    }
    
    /* 深色模式标签美化 - 降低优先级 */
    [data-theme="dark"] .assigned-courses {
      background: linear-gradient(135deg, var(--bg-primary), var(--border-color));
      color: var(--text-secondary);
      border: 1px solid var(--border-color);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }
    
    /* 深色模式按钮美化 - 降低优先级 */
    [data-theme="dark"] .view-reports-btn {
      background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
      color: white;
      border: none;
      box-shadow: 0 2px 8px rgba(96, 165, 250, 0.3);
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    [data-theme="dark"] .view-reports-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(96, 165, 250, 0.4);
    }
    
    /* 深色模式全局重置 - 最高优先级 */
    [data-theme="dark"] {
      color-scheme: dark !important;
    }
    

    
    /* 深色模式header完全保护 - 最高优先级 */
    [data-theme="dark"] header,
    [data-theme="dark"] .header {
      all: unset !important;
      display: flex !important;
      justify-content: space-between !important;
      align-items: center !important;
      padding: 15px 20px !important;
      background: linear-gradient(90deg, #4a90e2, #7bb3f0) !important;
      background-image: linear-gradient(90deg, #4a90e2, #7bb3f0) !important;
      color: white !important;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2) !important;
      position: relative !important;
      z-index: 100 !important;
      width: 100% !important;
      box-sizing: border-box !important;
    }
    
    /* 深色模式header按钮样式 - 最高优先级 */
    [data-theme="dark"] .header > div {
      display: flex !important;
      align-items: center !important;
      gap: 12px !important;
    }
    
    [data-theme="dark"] .header a {
      color: white !important;
      text-decoration: none !important;
      background: rgba(255,255,255,0.2) !important;
      padding: 8px 12px !important;
      border-radius: 6px !important;
      transition: all 0.2s ease !important;
      font-size: 14px !important;
      font-weight: 500 !important;
      border: none !important;
      outline: none !important;
      box-shadow: none !important;
    }
    
    [data-theme="dark"] .header a:hover {
      background: rgba(255,255,255,0.3) !important;
      transform: translateY(-1px) !important;
    }
    

    
    [data-theme="dark"] header h1,
    [data-theme="dark"] .header h1 {
      all: unset !important;
      font-size: 20px !important;
      font-weight: 600 !important;
      margin: 0 !important;
      color: white !important;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
      display: block !important;
    }
    
    [data-theme="dark"] header .user-info,
    [data-theme="dark"] .header .user-info {
      all: unset !important;
      display: flex !important;
      align-items: center !important;
      gap: 15px !important;
      font-size: 14px !important;
      color: white !important;
    }
    
    [data-theme="dark"] header .profile-trigger,
    [data-theme="dark"] .header .profile-trigger {
      all: unset !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      padding: 8px !important;
      background: rgba(255, 255, 255, 0.15) !important;
      color: white !important;
      border: 1px solid rgba(255, 255, 255, 0.2) !important;
      border-radius: 8px !important;
      cursor: pointer !important;
      font-size: 14px !important;
      font-weight: 500 !important;
      transition: all 0.2s ease !important;
      min-width: 40px !important;
      min-height: 40px !important;
    }
    
    [data-theme="dark"] header .profile-trigger:hover,
    [data-theme="dark"] .header .profile-trigger:hover {
      background: rgba(255, 255, 255, 0.25) !important;
      border-color: rgba(255, 255, 255, 0.3) !important;
    }
    
    /* 深色模式页面整体美化 */
    [data-theme="dark"] body {
      background: linear-gradient(135deg, var(--bg-primary), #1a1f24) !important;
      background-attachment: fixed !important;
    }
    
    [data-theme="dark"] .admin-layout {
      background: transparent !important;
    }
    
    /* 确保header在深色模式下保持原有的蓝色 - 最高优先级 */
    [data-theme="dark"] .header {
      background: linear-gradient(90deg, #4a90e2, #7bb3f0) !important;  /* 强制保持原有的蓝色渐变 */
      color: white !important;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2) !important;
    }
    
    [data-theme="dark"] .header h1 {
      color: white !important;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
    }
    
    [data-theme="dark"] .header .user-info {
      color: white !important;
    }
    

    
    /* 确保header中的用户头像按钮保持原有样式 */
    [data-theme="dark"] .header .profile-trigger {
      background: rgba(255, 255, 255, 0.15) !important;
      border: 1px solid rgba(255, 255, 255, 0.2) !important;
      color: white !important;
    }
    
    [data-theme="dark"] .header .profile-trigger:hover {
      background: rgba(255, 255, 255, 0.25) !important;
      border-color: rgba(255, 255, 255, 0.3) !important;
    }
    
    /* 强制覆盖所有可能的深色模式样式 - 最高优先级 */
    [data-theme="dark"] header.header,
    [data-theme="dark"] .header,
    [data-theme="dark"] body .header,
    [data-theme="dark"] html .header,
    [data-theme="dark"] * .header {
      background: linear-gradient(90deg, #4a90e2, #7bb3f0) !important;
      background-image: linear-gradient(90deg, #4a90e2, #7bb3f0) !important;
      background-color: transparent !important;
      color: white !important;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2) !important;
    }
    
    /* 强制覆盖header内所有元素的样式 */
    [data-theme="dark"] .header *,
    [data-theme="dark"] header.header *,
    [data-theme="dark"] body .header *,
    [data-theme="dark"] html .header * {
      color: white !important;
    }
    
    /* 特别保护header标题 */
    [data-theme="dark"] .header h1,
    [data-theme="dark"] header.header h1,
    [data-theme="dark"] body .header h1,
    [data-theme="dark"] html .header h1 {
      color: white !important;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
    }
    
    /* 特别保护用户信息区域 */
    [data-theme="dark"] .header .user-info,
    [data-theme="dark"] header.header .user-info,
    [data-theme="dark"] body .header .user-info,
    [data-theme="dark"] html .header .user-info {
      color: white !important;
    }
    
    /* 特别保护用户头像按钮 */
    [data-theme="dark"] .header .profile-trigger,
    [data-theme="dark"] header.header .profile-trigger,
    [data-theme="dark"] body .header .profile-trigger,
    [data-theme="dark"] html .header .profile-trigger {
      all: unset !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      padding: 8px !important;
      background: rgba(255, 255, 255, 0.15) !important;
      background-color: rgba(255, 255, 255, 0.15) !important;
      color: white !important;
      border: 1px solid rgba(255, 255, 255, 0.2) !important;
      border-color: rgba(255, 255, 255, 0.2) !important;
      border-radius: 8px !important;
      cursor: pointer !important;
      font-size: 14px !important;
      font-weight: 500 !important;
      transition: all 0.2s ease !important;
      min-width: 40px !important;
      min-height: 40px !important;
      box-sizing: border-box !important;
      outline: none !important;
      text-decoration: none !important;
      box-shadow: none !important;
      position: relative !important;
      z-index: 10 !important;
    }
    
    [data-theme="dark"] .header .profile-trigger:hover,
    [data-theme="dark"] header.header .profile-trigger:hover,
    [data-theme="dark"] body .header .profile-trigger:hover,
    [data-theme="dark"] html .header .profile-trigger:hover {
      background: rgba(255, 255, 255, 0.25) !important;
      background-color: rgba(255, 255, 255, 0.25) !important;
      border: 1px solid rgba(255, 255, 255, 0.3) !important;
      border-color: rgba(255, 255, 255, 0.3) !important;
      box-shadow: 0 2px 8px rgba(255, 255, 255, 0.1) !important;
      transform: translateY(-1px) !important;
    }
    
    /* 个人资料下拉菜单完全保护 - 最高优先级 */
    [data-theme="dark"] .profile-dropdown-menu,
    [data-theme="dark"] .header .profile-dropdown-menu,
    [data-theme="dark"] header .profile-dropdown-menu,
    [data-theme="dark"] body .profile-dropdown-menu,
    [data-theme="dark"] html .profile-dropdown-menu {
      background: white !important;
      background-color: white !important;
      border: 1px solid #e1e5e9 !important;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
      color: #000000 !important;
      /* 确保弹窗可见 */
      opacity: 1 !important;
      visibility: visible !important;
      display: block !important;
    }
    
    [data-theme="dark"] .dropdown-header,
    [data-theme="dark"] .header .dropdown-header,
    [data-theme="dark"] header .dropdown-header,
    [data-theme="dark"] body .dropdown-header,
    [data-theme="dark"] html .dropdown-header {
      background: #f8f9fa !important;
      background-color: #f8f9fa !important;
      border-bottom: 1px solid #e1e5e9 !important;
      color: #000000 !important;
    }
    
    [data-theme="dark"] .dropdown-body,
    [data-theme="dark"] .header .dropdown-body,
    [data-theme="dark"] header .dropdown-body,
    [data-theme="dark"] body .dropdown-body,
    [data-theme="dark"] html .dropdown-body {
      background: white !important;
      background-color: white !important;
      color: #000000 !important;
    }
    
    [data-theme="dark"] .dropdown-item,
    [data-theme="dark"] .header .dropdown-item,
    [data-theme="dark"] header .dropdown-item,
    [data-theme="dark"] body .dropdown-item,
    [data-theme="dark"] html .dropdown-item {
      color: #000000 !important;
      background: transparent !important;
      border: none !important;
      /* 确保菜单项可见 */
      display: flex !important;
      opacity: 1 !important;
      visibility: visible !important;
    }
    
    [data-theme="dark"] .dropdown-item:hover,
    [data-theme="dark"] .header .dropdown-item:hover,
    [data-theme="dark"] header .dropdown-item:hover,
    [data-theme="dark"] body .dropdown-item:hover,
    [data-theme="dark"] html .dropdown-item:hover {
      background: #f8f9fa !important;
      background-color: #f8f9fa !important;
    }
    
    [data-theme="dark"] .dropdown-divider,
    [data-theme="dark"] .header .dropdown-divider,
    [data-theme="dark"] header .dropdown-divider,
    [data-theme="dark"] body .dropdown-divider,
    [data-theme="dark"] html .dropdown-divider {
      background: #e1e5e9 !important;
      /* 确保分割线可见 */
      display: block !important;
      opacity: 1 !important;
      visibility: visible !important;
    }
    
    [data-theme="dark"] .user-name,
    [data-theme="dark"] .header .user-name,
    [data-theme="dark"] header .user-name,
    [data-theme="dark"] body .user-name,
    [data-theme="dark"] html .user-name {
      color: #000000 !important;
      /* 确保用户名可见 */
      display: block !important;
      opacity: 1 !important;
      visibility: visible !important;
    }
    
    [data-theme="dark"] .user-role,
    [data-theme="dark"] .header .user-role,
    [data-theme="dark"] header .user-role,
    [data-theme="dark"] body .user-role,
    [data-theme="dark"] html .user-role {
      color: #333333 !important;
      /* 确保用户角色可见 */
      display: block !important;
      opacity: 1 !important;
      visibility: visible !important;
    }
    
    [data-theme="dark"] .dropdown-text,
    [data-theme="dark"] .header .dropdown-text,
    [data-theme="dark"] header .dropdown-text,
    [data-theme="dark"] body .dropdown-text,
    [data-theme="dark"] html .dropdown-text {
      color: #000000 !important;
      /* 确保菜单文字可见 */
      display: block !important;
      opacity: 1 !important;
      visibility: visible !important;
    }
    
    [data-theme="dark"] .logout-item,
    [data-theme="dark"] .header .logout-item,
    [data-theme="dark"] header .logout-item,
    [data-theme="dark"] body .logout-item,
    [data-theme="dark"] html .logout-item {
      color: #dc3545 !important;
      /* 确保登出项可见 */
      display: flex !important;
      opacity: 1 !important;
      visibility: visible !important;
    }
    
    [data-theme="dark"] .logout-item:hover,
    [data-theme="dark"] .header .logout-item:hover,
    [data-theme="dark"] header .logout-item:hover,
    [data-theme="dark"] body .logout-item:hover,
    [data-theme="dark"] html .logout-item:hover {
      background: #fff5f5 !important;
      background-color: #fff5f5 !important;
    }
    
    /* 用户个人资料下拉菜单样式 - 始终白色背景，黑色字体 */
    .profile-dropdown {
      position: relative;
      display: inline-block;
    }
    
    .profile-trigger {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 8px 12px;
      background: rgba(255, 255, 255, 0.15);
      color: white;
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.2s ease;
      width: auto;
      height: auto;
      min-width: 40px;
      min-height: 36px;
      backdrop-filter: blur(10px);
    }
    
    .profile-trigger:hover {
      background: rgba(255, 255, 255, 0.25);
      border-color: rgba(255, 255, 255, 0.3);
    }
    
    .profile-avatar {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 12px;
      font-weight: bold;
      box-shadow: 0 4px 15px rgba(74,144,226,0.3);
      transition: all 0.3s ease;
      flex-shrink: 0;
      /* 确保文字完全居中 */
      text-align: center;
      line-height: 1;
      padding: 0;
      margin: 0;
      /* 强制文字居中 */
      position: relative;
      overflow: hidden;
    }
    
    .profile-avatar span {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      display: block;
      width: auto;
      height: auto;
      margin: 0;
      padding: 0;
    }
    
    .avatar-image {
      width: 24px;
      height: 24px;
      object-fit: cover;
      border-radius: 50%;
      display: block;
    }
    
    .profile-avatar:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 20px rgba(74,144,226,0.4);
    }
    
    .profile-dropdown-menu {
      position: absolute;
      top: 100%;
      right: 0;
      width: 220px;
      background: white !important;
      border: 1px solid #e1e5e9;
      border-radius: 8px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 1001;
      margin-top: 8px;
      overflow: hidden;
      /* 确保初始状态是隐藏的 */
      display: none;
    }
    
    .profile-dropdown-menu.show {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
      /* 显示时改为block */
      display: block;
    }
    
    .dropdown-header {
      padding: 16px;
      border-bottom: 1px solid #e1e5e9;
      background: #f8f9fa !important;
      color: #000000 !important;
    }
    
    .dropdown-body {
      background: white !important;
      color: #000000 !important;
    }
    
    .user-avatar-section {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .user-avatar {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: white;
      font-weight: bold;
      box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
      transition: all 0.3s ease;
      /* 确保文字完全居中 */
      text-align: center;
      line-height: 1;
      padding: 0;
      margin: 0;
      /* 强制文字居中 */
      position: relative;
      overflow: hidden;
    }
    
    .user-avatar span {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      display: block;
      width: auto;
      height: auto;
      margin: 0;
      padding: 0;
    }
    
    .user-avatar .avatar-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
    }
    
    .user-avatar:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 20px rgba(74, 144, 226, 0.4);
    }
    
    .user-info-text {
      flex: 1;
    }
    
    .user-name {
      color: #000000 !important;
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 4px;
    }
    
    .user-role {
      color: #333333 !important;
      font-size: 13px;
      font-weight: 500;
    }
    
    .dropdown-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      color: #000000 !important;
      text-decoration: none;
      transition: background 0.2s ease;
      font-size: 14px;
      border: none;
      background: none;
      width: 100%;
      text-align: left;
      cursor: pointer;
    }
    
    .dropdown-item:hover {
      background: #f8f9fa !important;
    }
    
    .dropdown-icon {
      font-size: 16px;
      width: 20px;
      text-align: center;
    }
    
    .dropdown-text {
      flex: 1;
      font-weight: 500;
    }
    
    .dropdown-divider {
      height: 1px;
      background: #e1e5e9;
      margin: 8px 0;
    }
    
    .logout-item {
      color: #dc3545 !important;
    }
    
    .logout-item:hover {
      background: #fff5f5 !important;
    }
    
    /* 浅色模式下个人资料弹窗强制可见样式 - 最高优先级 */
    html:not([data-theme="dark"]) .profile-dropdown-menu,
    html:not([data-theme="dark"]) .profile-dropdown-menu *,
    html:not([data-theme="dark"]) .header .profile-dropdown-menu,
    html:not([data-theme="dark"]) .header .profile-dropdown-menu *,
    html:not([data-theme="dark"]) body .profile-dropdown-menu,
    html:not([data-theme="dark"]) body .profile-dropdown-menu *,
    html:not([data-theme="dark"]) html .profile-dropdown-menu,
    html:not([data-theme="dark"]) html .profile-dropdown-menu * {
      opacity: 1 !important;
      visibility: visible !important;
      display: block !important;
    }
    
    /* 浅色模式下个人资料弹窗内容强制可见 - 最高优先级 */
    html:not([data-theme="dark"]) .profile-dropdown-menu .dropdown-header,
    html:not([data-theme="dark"]) .profile-dropdown-menu .dropdown-body,
    html:not([data-theme="dark"]) .profile-dropdown-menu .dropdown-item,
    html:not([data-theme="dark"]) .profile-dropdown-menu .user-avatar-section,
    html:not([data-theme="dark"]) .profile-dropdown-menu .user-info-text,
    html:not([data-theme="dark"]) .profile-dropdown-menu .dropdown-icon,
    html:not([data-theme="dark"]) .profile-dropdown-menu .dropdown-text,
    html:not([data-theme="dark"]) .profile-dropdown-menu .dropdown-divider {
      display: block !important;
      opacity: 1 !important;
      visibility: visible !important;
      color: inherit !important;
    }
    
    html:not([data-theme="dark"]) .profile-dropdown-menu .dropdown-item {
      display: flex !important;
    }
    
    html:not([data-theme="dark"]) .profile-dropdown-menu .user-avatar-section {
      display: flex !important;
    }
    
    /* 浅色模式下个人资料弹窗强制显示规则 - 最高优先级 */
    html:not([data-theme="dark"]) .profile-dropdown-menu.show {
      opacity: 1 !important;
      visibility: visible !important;
      transform: translateY(0) !important;
      display: block !important;
      pointer-events: auto !important;
    }
    
    /* 浅色模式下个人资料弹窗初始状态 - 最高优先级 */
    html:not([data-theme="dark"]) .profile-dropdown-menu:not(.show) {
      display: none !important;
      opacity: 0 !important;
      visibility: hidden !important;
      transform: translateY(-10px) !important;
      pointer-events: none !important;
    }
    
    /* 浅色模式下个人资料弹窗文字颜色强制设置 */
    html:not([data-theme="dark"]) .profile-dropdown-menu .user-name {
      color: #000000 !important;
    }
    
    html:not([data-theme="dark"]) .profile-dropdown-menu .user-role {
      color: #333333 !important;
    }
    
    html:not([data-theme="dark"]) .profile-dropdown-menu .dropdown-item {
      color: #000000 !important;
    }
    
    html:not([data-theme="dark"]) .profile-dropdown-menu .dropdown-text {
      color: #000000 !important;
    }
    
    html:not([data-theme="dark"]) .profile-dropdown-menu .logout-item {
      color: #dc3545 !important;
    }
    
    /* 浅色模式下个人资料弹窗背景和边框强制设置 */
    html:not([data-theme="dark"]) .profile-dropdown-menu {
      background: white !important;
      background-color: white !important;
      border: 1px solid #e1e5e9 !important;
      border-color: #e1e5e9 !important;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
    }
    
    html:not([data-theme="dark"]) .profile-dropdown-menu .dropdown-header {
      background: #f8f9fa !important;
      background-color: #f8f9fa !important;
      border-bottom: 1px solid #e1e5e9 !important;
      border-bottom-color: #e1e5e9 !important;
    }
    
    html:not([data-theme="dark"]) .profile-dropdown-menu .dropdown-body {
      background: white !important;
      background-color: white !important;
    }
    
    html:not([data-theme="dark"]) .profile-dropdown-menu .dropdown-item:hover {
      background: #f8f9fa !important;
      background-color: #f8f9fa !important;
    }
    
    html:not([data-theme="dark"]) .profile-dropdown-menu .logout-item:hover {
      background: #fff5f5 !important;
      background-color: #fff5f5 !important;
    }
    
    html:not([data-theme="dark"]) .profile-dropdown-menu .dropdown-divider {
      background: #e1e5e9 !important;
      background-color: #e1e5e9 !important;
    }
    
    /* 设置模态框样式 */
    .settings-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 2000;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }
    
    .settings-modal.show {
      opacity: 1;
      visibility: visible;
    }
    
    .settings-modal-content {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) scale(0.9);
      background: white;
      border-radius: 12px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }
    
    .settings-modal.show .settings-modal-content {
      transform: translate(-50%, -50%) scale(1);
    }
    
    .settings-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 24px;
      border-bottom: 1px solid #e1e5e9;
    }
    
    .settings-modal-header h3 {
      margin: 0;
      color: #333333;
      font-size: 18px;
      font-weight: 600;
    }
    
    .close-btn {
      background: none;
      border: none;
      font-size: 24px;
      color: #999999;
      cursor: pointer;
      padding: 0;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.2s ease;
    }
    
    .close-btn:hover {
      background: #f5f5f5;
      color: #666666;
    }
    
    .settings-modal-body {
      padding: 24px;
    }
    
    .setting-section {
      margin-bottom: 24px;
    }
    
    .setting-section:last-child {
      margin-bottom: 0;
    }
    
    .setting-section h4 {
      margin: 0 0 12px 0;
      color: #333333;
      font-size: 16px;
      font-weight: 600;
    }
    
    .language-options, .theme-options {
      display: flex;
      gap: 12px;
    }
    
    .lang-btn, .theme-btn {
      flex: 1;
      padding: 12px 16px;
      border: 2px solid #e1e5e9;
      background: white;
      color: #666666;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.2s ease;
    }
    
    .lang-btn:hover, .theme-btn:hover {
      border-color: #4a90e2;
      color: #4a90e2;
    }
    
    .lang-btn.active, .theme-btn.active {
      background: #4a90e2;
      border-color: #4a90e2;
      color: white;
    }
    
    /* 模态框背景遮罩 */
    .modal-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1999;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }
    
    .modal-backdrop.show {
      opacity: 1;
      visibility: visible;
    }
    
    /* 深色模式设置模态框样式 */
    [data-theme="dark"] .settings-modal-content {
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      box-shadow: 0 20px 60px var(--shadow-color);
    }
    
    [data-theme="dark"] .settings-modal-header {
      background: var(--bg-primary);
      border-bottom: 1px solid var(--border-color);
    }
    
    [data-theme="dark"] .settings-modal-body {
      background: var(--bg-secondary);
    }
    
    [data-theme="dark"] .settings-modal-header h3 {
      color: var(--text-primary);
    }
    
    [data-theme="dark"] .setting-section h4 {
      color: var(--text-primary);
    }
    
    [data-theme="dark"] .lang-btn,
    [data-theme="dark"] .theme-btn {
      background: var(--bg-secondary);
      color: var(--text-secondary);
      border: 2px solid var(--border-color);
    }
    
    [data-theme="dark"] .lang-btn:hover,
    [data-theme="dark"] .theme-btn:hover {
      border-color: var(--accent-color);
      color: var(--accent-color);
    }
    
    [data-theme="dark"] .lang-btn.active,
    [data-theme="dark"] .theme-btn.active {
      background: var(--accent-color);
      border-color: var(--accent-color);
      color: white;
    }
    
    /* 响应式设计 */
    @media (max-width: 768px) {
      .profile-dropdown-menu {
        width: 200px;
        right: -10px;
      }
      
      .settings-modal-content {
        width: 95%;
        margin: 20px;
      }
      
      .language-options, .theme-options {
        flex-direction: column;
      }
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
    
    /* 确保侧边栏在深色模式下保持深色主题 */
    [data-theme="dark"] .admin-sidebar * {
      background: var(--bg-secondary) !important;
    }
    
    [data-theme="dark"] .admin-sidebar .menu a {
      background: transparent !important;
    }
    
    [data-theme="dark"] .admin-sidebar .menu a:hover {
      background: var(--card-hover-bg) !important;
    }
    
    [data-theme="dark"] .admin-sidebar .menu a.active {
      background: var(--card-hover-bg) !important;
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
    
    /* 深色模式统计值美化 - 使用白色 */
    [data-theme="dark"] .stat-value.active {
      background: linear-gradient(135deg, var(--success-bg), var(--success-border)) !important;
      color: var(--success-text) !important;
      border: 1px solid var(--success-border) !important;
    }
    
    [data-theme="dark"] .stat-value.inactive {
      background: linear-gradient(135deg, var(--error-bg), var(--error-border)) !important;
      color: var(--error-text) !important;
      border: 1px solid var(--error-border) !important;
    }
    
    /* 深色模式默认状态美化 - 使用白色 */
    [data-theme="dark"] .stat-value {
      background: linear-gradient(135deg, var(--bg-primary), var(--border-color)) !important;
      color: #ffffff !important;
      border: 1px solid var(--border-color) !important;
    }
    
    /* 深色模式标签美化 - 使用白色 */
    [data-theme="dark"] .assigned-courses {
      background: linear-gradient(135deg, var(--bg-primary), var(--border-color));
      color: #ffffff !important;
      border: 1px solid var(--border-color);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }
    
    /* 深色模式按钮美化 - 使用白色 */
    [data-theme="dark"] .view-reports-btn {
      background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
      color: white;
      border: none;
      box-shadow: 0 2px 8px rgba(96, 165, 250, 0.3);
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    [data-theme="dark"] .view-reports-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(96, 165, 250, 0.4);
    }
    
    /* 深色模式无数据卡片美化 - 使用白色 */
    [data-theme="dark"] .no-data-card {
      background: var(--bg-secondary) !important;
      border: 1px solid var(--border-color) !important;
      border-left: 4px solid var(--accent-color) !important;
      box-shadow: 0 4px 20px var(--shadow-color) !important;
      color: #ffffff !important;
    }
    
    /* 深色模式无数据卡片文字 */
    [data-theme="dark"] .no-data-card p {
      color: var(--text-primary) !important;
    }
    /* 确保深色模式下个人资料弹窗的所有子元素都可见 */
    [data-theme="dark"] .profile-dropdown-menu *,
    [data-theme="dark"] .header .profile-dropdown-menu *,
    [data-theme="dark"] header .profile-dropdown-menu *,
    [data-theme="dark"] body .profile-dropdown-menu *,
    [data-theme="dark"] html .profile-dropdown-menu * {
      opacity: 1 !important;
      visibility: visible !important;
    }
    
    /* 深色模式下个人资料弹窗强制显示规则 */
    [data-theme="dark"] .profile-dropdown-menu.show {
      opacity: 1 !important;
      visibility: visible !important;
      transform: translateY(0) !important;
      display: block !important;
    }
    
    /* 深色模式下个人资料弹窗初始状态 */
    [data-theme="dark"] .profile-dropdown-menu {
      display: none !important;
    }
    
    /* 深色模式下个人资料弹窗内容强制可见 */
    [data-theme="dark"] .profile-dropdown-menu .dropdown-header,
    [data-theme="dark"] .profile-dropdown-menu .dropdown-body,
    [data-theme="dark"] .profile-dropdown-menu .dropdown-item,
    [data-theme="dark"] .profile-dropdown-menu .user-avatar-section,
    [data-theme="dark"] .profile-dropdown-menu .user-info-text,
    [data-theme="dark"] .profile-dropdown-menu .dropdown-icon,
    [data-theme="dark"] .profile-dropdown-menu .dropdown-text {
      display: block !important;
      opacity: 1 !important;
      visibility: visible !important;
      color: inherit !important;
    }
    
    [data-theme="dark"] .profile-dropdown-menu .dropdown-item {
      display: flex !important;
    }
    
    [data-theme="dark"] .profile-dropdown-menu .user-avatar-section {
      display: flex !important;
    }
    
    /* 内容区域样式恢复 */
    .stats-grid { 
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
      gap: 16px; 
      margin-bottom: 20px; 
    }
    
    /* 统计卡片样式 */
    .admin-layout .stat-card,
    .admin-dashboard .stat-card,
    .stat-card { 
      background: white !important; 
      padding: 22px; 
      border-radius: 12px; 
      text-align: center; 
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important; 
      border-left: 4px solid #4a90e2 !important; 
      transition: all 0.3s ease; 
    }
    
    .admin-layout .stat-card .stat-number,
    .admin-dashboard .stat-card .stat-number,
    .stat-card .stat-number { 
      font-size: 32px; 
      font-weight: 700; 
      color: #4a90e2 !important; 
      margin-bottom: 8px; 
    }
    
    .admin-layout .stat-card .stat-label,
    .admin-dashboard .stat-card .stat-label,
    .stat-card .stat-label { 
      color: #666666 !important; 
      font-size: 13px; 
    }

    /* 老师报告状态区域样式 */
    .teacher-reports-section { 
      margin-top: 20px; 
    }
    
    .section-header { 
      background: white !important; 
      padding: 20px; 
      border-radius: 12px; 
      margin-bottom: 20px; 
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important; 
      transition: all 0.3s ease; 
    }
    
    .teacher-reports-grid { 
      display: grid; 
      grid-template-columns: repeat(5, 1fr); 
      gap: 16px; 
    }
    
    /* 深色模式下的老师报告网格 - 6列布局 */
    html[data-theme="dark"] #teacherReportsSection .teacher-reports-grid {
      display: grid !important;
      grid-template-columns: repeat(6, 1fr) !important;
      gap: 12px !important;
      margin-top: 20px !important;
    }
    
    /* 老师报告卡片样式 */
    .admin-layout .teacher-report-card,
    .admin-dashboard .teacher-report-card,
    .teacher-report-card { 
      background: white !important; 
      border-radius: 8px; 
      padding: 14px; 
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important; 
      border-left: 3px solid #4a90e2 !important;
      transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.3s ease;
      min-height: 160px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    
    .teacher-report-card:hover { 
      transform: translateY(-2px); 
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08) !important; 
    }
    
    .teacher-info h3 { 
      color: #4a90e2 !important; 
      font-size: 14px; 
      font-weight: 600; 
      margin-bottom: 6px; 
      text-align: center;
    }
    
    .teacher-meta { 
      margin-bottom: 10px; 
      text-align: center;
    }
    
    .assigned-courses { 
      color: #666666 !important; 
      font-size: 11px; 
      background: #f8f9fa !important; 
      padding: 2px 6px; 
      border-radius: 3px; 
      display: inline-block;
    }
    
    .report-stats { 
      margin-bottom: 10px; 
      border-top: 1px solid #e1e5e9 !important; 
      padding-top: 10px; 
    }
    
    .stat-row { 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      margin-bottom: 4px; 
    }
    
    .stat-label { 
      color: #666666 !important; 
      font-size: 11px; 
    }
    
    .stat-value { 
      font-weight: 600; 
      color: #333333 !important; 
      padding: 2px 6px; 
      border-radius: 3px; 
      background: #f8f9fa !important; 
      font-size: 11px;
    }
    
    .stat-value.active { 
      background: #d4edda !important; 
      color: #155724 !important; 
    }
    
    .stat-value.inactive { 
      background: #f8d7da !important; 
      color: #721c24 !important; 
    }
    
    .last-report { 
      margin-bottom: 8px; 
      padding: 6px; 
      background: #f8f9fa !important; 
      border-radius: 4px; 
      font-size: 10px; 
      text-align: center;
    }
    
    .last-report-label { 
      color: #666666 !important; 
      font-weight: 500; 
      display: block;
      margin-bottom: 2px;
    }
    
    .last-report-time { 
      color: #333333 !important; 
      font-weight: 600; 
    }
    
    .teacher-actions { 
      text-align: center; 
    }
    
    .view-reports-btn { 
      display: inline-block; 
      background: #4a90e2 !important; 
      color: white; 
      padding: 6px 12px; 
      text-decoration: none; 
      border-radius: 4px; 
      font-size: 11px; 
      font-weight: 500; 
      transition: background 0.2s ease; 
    }
    
    .view-reports-btn:hover { 
      background: #7bb3f0 !important; 
    }
    
    /* 无数据卡片样式 */
    .no-data-card { 
      background: white !important; 
      padding: 40px; 
      border-radius: 12px; 
      text-align: center; 
      color: #666666 !important; 
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important; 
      transition: all 0.3s ease;
    }
    
    /* 欢迎标题和区域标题样式 */
    .welcome-title {
      color: #4a90e2 !important;
      margin-bottom: 6px;
    }
    
    .section-title {
      color: #4a90e2 !important;
      margin-bottom: 10px;
    }
    
    .section-description {
      color: #666666 !important;
      margin-bottom: 20px;
    }
    
    .never-submitted {
      color: #721c24 !important;
    }
    
    /* 响应式设计 */
    @media (max-width: 1200px) {
      .teacher-reports-grid { 
        grid-template-columns: repeat(4, 1fr); 
      }
      /* 深色模式响应式 */
      html[data-theme="dark"] #teacherReportsSection .teacher-reports-grid {
        grid-template-columns: repeat(5, 1fr) !important;
      }
    }
    
    @media (max-width: 992px) {
      .teacher-reports-grid { 
        grid-template-columns: repeat(3, 1fr); 
      }
      /* 深色模式响应式 */
      html[data-theme="dark"] #teacherReportsSection .teacher-reports-grid {
        grid-template-columns: repeat(4, 1fr) !important;
      }
    }
    
    @media (max-width: 768px) {
      .teacher-reports-grid { 
        grid-template-columns: repeat(2, 1fr); 
      }
      /* 深色模式响应式 */
      html[data-theme="dark"] #teacherReportsSection .teacher-reports-grid {
        grid-template-columns: repeat(3, 1fr) !important;
      }
    }
    
    @media (max-width: 480px) {
      .teacher-reports-grid { 
        grid-template-columns: 1fr; 
      }
      /* 深色模式响应式 */
      html[data-theme="dark"] #teacherReportsSection .teacher-reports-grid {
        grid-template-columns: repeat(2, 1fr) !important;
      }
    }
    
    /* 欢迎卡片样式 */
    .admin-main-card {
      background: white !important;
      padding: 24px;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important;
      border-left: 4px solid #4a90e2 !important;
      transition: all 0.3s ease;
      margin-bottom: 16px;
    }
    
    .admin-main-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12) !important;
    }
    
    /* 深色模式下的卡片样式 */
    [data-theme="dark"] .admin-main-card {
      background: var(--bg-secondary) !important;
      border: 1px solid var(--border-color) !important;
      border-left: 4px solid var(--accent-color) !important;
      box-shadow: 0 4px 20px var(--shadow-color) !important;
      color: var(--text-primary) !important;
    }
    
    [data-theme="dark"] .admin-main-card .welcome-title {
      color: var(--accent-color) !important;
    }
    
    [data-theme="dark"] .admin-main-card p {
      color: var(--text-secondary) !important;
    }
    
    /* 个人资料弹窗样式恢复 */
    [data-theme="dark"] .profile-dropdown-menu .user-avatar-section {
      display: flex !important;
    }
    
    /* 确保个人资料弹窗在所有主题下都能正确工作 */
    .profile-dropdown-menu:not(.show) {
      display: none !important;
      opacity: 0 !important;
      visibility: hidden !important;
      transform: translateY(-10px) !important;
    }
    
    .profile-dropdown-menu.show {
      display: block !important;
      opacity: 1 !important;
      visibility: visible !important;
      transform: translateY(0) !important;
    }
    
    /* 强制确保弹窗在显示状态下可见 */
    .profile-dropdown-menu.show,
    [data-theme="dark"] .profile-dropdown-menu.show {
      display: block !important;
      opacity: 1 !important;
      visibility: visible !important;
      transform: translateY(0) !important;
      pointer-events: auto !important;
    }
    
    /* 强制确保弹窗在隐藏状态下不可见 */
    .profile-dropdown-menu:not(.show),
    [data-theme="dark"] .profile-dropdown-menu:not(.show) {
      display: none !important;
      opacity: 0 !important;
      visibility: hidden !important;
      transform: translateY(-10px) !important;
      pointer-events: none !important;
    }
    
    /* 额外强制覆盖 - 确保所有元素都使用深色模式样式 */
    html[data-theme="dark"] #teacherReportsSection,
    html[data-theme="dark"] #teacherReportsSection *,
    html[data-theme="dark"] #teacherReportsSection > *,
    html[data-theme="dark"] #teacherReportsSection > * > *,
    html[data-theme="dark"] #teacherReportsSection > * > * > *,
    html[data-theme="dark"] #teacherReportsSection > * > * > * > * {
      background: #2d2d2d !important;
      background-color: #2d2d2d !important;
      color: #ffffff !important;
      border: 1px solid #404040 !important;
      border-color: #404040 !important;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
    }
    
    /* 强制覆盖所有可能的深色模式变量 */
    html[data-theme="dark"] #teacherReportsSection,
    html[data-theme="dark"] #teacherReportsSection * {
      --bg-primary: white !important;
      --bg-secondary: white !important;
      --text-primary: #333333 !important;
      --text-secondary: #666666 !important;
      --text-muted: #999999 !important;
      --border-color: #e1e5e9 !important;
      --shadow-color: rgba(0, 0, 0, 0.08) !important;
      --accent-color: #4a90e2 !important;
      --accent-hover: #7bb3f0 !important;
    }
    
    /* 特殊处理统计数字 */
    html[data-theme="dark"] #teacherReportsSection .stat-value {
      color: #333333 !important;
      background: #f8f9fa !important;
      padding: 4px 8px !important;
      border-radius: 4px !important;
      font-weight: 600 !important;
      font-size: 16px !important;
    }
    
    /* 特殊处理分配课程标签 */
    html[data-theme="dark"] #teacherReportsSection .assigned-courses {
      background: #e3f2fd !important;
      color: #1976d2 !important;
      padding: 4px 8px !important;
      border-radius: 4px !important;
      font-size: 12px !important;
      font-weight: 500 !important;
    }
    
    /* 强制覆盖所有可能的深色模式变量 */
  </style>
</head>
<body id="admin-dashboard">
  <header class="header">
    <h1><?= t('dashboard.admin_title') ?></h1>
    <div>

      <div class="profile-dropdown">
        <button class="profile-trigger" onclick="toggleProfileDropdown()" title="个人资料">
          <span class="profile-avatar">
            <?php if (!empty($user['avatar'])): ?>
              <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="头像" class="avatar-image">
            <?php else: ?>
              <?= mb_substr($user['name'], 0, 1) ?>
            <?php endif; ?>
          </span>
        </button>
        <div class="profile-dropdown-menu" id="profileDropdown">
          <div class="dropdown-header">
            <div class="user-avatar-section">
              <div class="user-avatar">
                <?php if (!empty($user['avatar'])): ?>
                  <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="头像" class="avatar-image">
                <?php else: ?>
                  <?= mb_substr($user['name'], 0, 1) ?>
                <?php endif; ?>
              </div>
              <div class="user-info-text">
                <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                <div class="user-role"><?= t('roles.admin') ?></div>
              </div>
            </div>
          </div>
          <div class="dropdown-body">
            <a href="profile.php" class="dropdown-item">
              <span class="dropdown-icon">👤</span>
              <span class="dropdown-text"><?= t('common.profile') ?></span>
            </a>
            <a href="user_manual.php" class="dropdown-item">
              <span class="dropdown-icon">📖</span>
              <span class="dropdown-text"><?= t('common.manual', '系统说明书') ?></span>
            </a>
            <button class="dropdown-item settings-trigger" onclick="toggleSettingsModal()">
              <span class="dropdown-icon">⚙</span>
              <span class="dropdown-text"><?= t('common.settings') ?></span>
            </button>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="dropdown-item logout-item">
              <span class="dropdown-icon">🚪</span>
              <span class="dropdown-text"><?= t('common.logout') ?></span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="admin-layout">
    <!-- 使用统一侧边栏 -->
    <?php include 'inc/admin_sidebar.php'; ?>

    <!-- 主体 -->
    <main>
      <div class="admin-main-card" style="margin-bottom:16px;">
        <h2 class="welcome-title"><?= t('dashboard.welcome_back') ?>，<?= htmlspecialchars($user['name']) ?>！</h2>
        <p><?= t('dashboard.admin_description') ?></p>
      </div>

      <div class="stats-grid">
        <div class="stat-card"><div class="stat-number"><?= $stats['admin'] ?? 0 ?></div><div class="stat-label"><?= t('stats.admins') ?></div></div>
        <div class="stat-card"><div class="stat-number"><?= $stats['teacher'] ?? 0 ?></div><div class="stat-label"><?= t('stats.teachers') ?></div></div>
        <div class="stat-card"><div class="stat-number"><?= $stats['courses'] ?? 0 ?></div><div class="stat-label"><?= t('stats.courses') ?></div></div>
        <div class="stat-card"><div class="stat-number"><?= $stats['attendance'] ?? 0 ?></div><div class="stat-label"><?= t('stats.total_reports') ?></div></div>
        <div class="stat-card"><div class="stat-number"><?= $stats['today_reports'] ?? 0 ?></div><div class="stat-label"><?= t('stats.today_reports') ?></div></div>
        <div class="stat-card"><div class="stat-number"><?= $stats['week_reports'] ?? 0 ?></div><div class="stat-label"><?= t('stats.week_reports') ?></div></div>
        <div class="stat-card"><div class="stat-number"><?= $stats['lesson_plans'] ?? 0 ?></div><div class="stat-label"><?= t('stats.lesson_plans') ?></div></div>
      </div>

      <!-- 老师报告状态区域 -->
      <div class="teacher-reports-section" id="teacherReportsSection">
        <div class="section-header">
          <h2 class="section-title"><?= t('teacher_reports.title') ?> - <?= t('teacher_reports.today_active') ?></h2>
          <p class="section-description"><?= t('teacher_reports.description') ?> (<?= t('teacher_reports.today_only_hint') ?>)</p>
        </div>

        <?php if (empty($teacher_reports)): ?>
          <div class="no-data-card">
            <p><?= t('teacher_reports.no_teachers') ?></p>
          </div>
        <?php else: ?>
          <div class="teacher-reports-grid">
            <?php foreach ($teacher_reports as $teacher): ?>
              <div class="teacher-report-card">
                <div class="teacher-info">
                  <h3><?= htmlspecialchars($teacher['teacher_name']) ?></h3>
                  <div class="teacher-meta">
                    <span class="assigned-courses"><?= t('teacher_reports.assigned_courses') ?>: <?= $teacher['assigned_courses'] ?><?= t('teacher_reports.courses_count') ?></span>
                  </div>
                </div>
                
                <div class="report-stats">
                  <div class="stat-row">
                    <span class="stat-label"><?= t('teacher_reports.total_reports') ?></span>
                    <span class="stat-value"><?= $teacher['total_reports'] ?></span>
                  </div>
                  <div class="stat-row">
                    <span class="stat-label"><?= t('teacher_reports.today_reports') ?></span>
                    <span class="stat-value <?= $teacher['today_reports'] > 0 ? 'active' : 'inactive' ?>">
                      <?= $teacher['today_reports'] ?>
                    </span>
                  </div>
                  <div class="stat-row">
                    <span class="stat-label"><?= t('teacher_reports.week_reports') ?></span>
                    <span class="stat-value"><?= $teacher['week_reports'] ?></span>
                  </div>
                </div>
                
                <div class="last-report">
                  <span class="last-report-label"><?= t('teacher_reports.last_submit') ?>:</span>
                  <span class="last-report-time">
                    <?php if ($teacher['last_report_time']): ?>
                      <?= date('Y-m-d H:i', strtotime($teacher['last_report_time'])) ?>
                    <?php else: ?>
                      <span class="never-submitted"><?= t('teacher_reports.never_submitted') ?></span>
                    <?php endif; ?>
                  </span>
                </div>
                
                <div class="teacher-actions">
                  <a href="admin_teaching_reports.php?teacher=<?= $teacher['id'] ?>" class="view-reports-btn">
                    <?= t('teacher_reports.view_reports') ?>
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
  
  <script>
    // 个人资料下拉菜单切换
    function toggleProfileDropdown() {
      const dropdown = document.getElementById('profileDropdown');
      if (dropdown) {
      dropdown.classList.toggle('show');
        console.log('弹窗状态:', dropdown.classList.contains('show') ? '显示' : '隐藏');
      }
    }
    
    // 关闭个人资料下拉菜单
    function closeProfileDropdown() {
      const dropdown = document.getElementById('profileDropdown');
      if (dropdown) {
        dropdown.classList.remove('show');
        console.log('弹窗已关闭');
      }
    }
    
    // 设置模态框切换
    function toggleSettingsModal() {
      const modal = document.getElementById('settingsModal');
      const backdrop = document.getElementById('modalBackdrop');
      if (modal && backdrop) {
      modal.classList.toggle('show');
      backdrop.classList.toggle('show');
      }
    }
    
    // 语言切换
    function changeLanguage(lang) {
      console.log('切换语言到:', lang);
      
      // 显示加载状态
      const currentBtn = document.querySelector(`.lang-btn[onclick*="${lang}"]`);
      if (currentBtn) {
        currentBtn.textContent = '切换中...';
        currentBtn.disabled = true;
      }
      
      fetch('change_language.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'lang=' + lang
      })
      .then(response => {
        console.log('响应状态:', response.status);
        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }
        return response.json();
      })
      .then(data => {
        console.log('语言切换响应:', data);
        if (data.success) {
          console.log('语言切换成功，即将刷新页面');
          // 立即刷新页面
          location.reload();
        } else {
          console.error('语言切换失败:', data.error);
          alert('语言切换失败: ' + data.error);
          // 恢复按钮状态
          if (currentBtn) {
            currentBtn.textContent = lang === 'zh' ? '🇨🇳 中文' : '🇺🇸 English';
            currentBtn.disabled = false;
          }
        }
      })
      .catch(error => {
        console.error('语言切换请求失败:', error);
        alert('语言切换请求失败: ' + error.message);
        // 恢复按钮状态
        if (currentBtn) {
          currentBtn.textContent = lang === 'zh' ? '🇨🇳 中文' : '🇺🇸 English';
          currentBtn.disabled = false;
        }
      });
    }
    
    // 主题切换
    function changeTheme(theme) {
      // 更新按钮状态
      document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.classList.remove('active');
      });
      
      // 找到当前点击的按钮并添加active类
      const currentBtn = document.querySelector(`.theme-btn[onclick*="${theme}"]`);
      if (currentBtn) {
        currentBtn.classList.add('active');
      }
      
      // 设置主题
      document.documentElement.setAttribute('data-theme', theme);
      
      // 保存到sessionStorage
      sessionStorage.setItem('theme', theme);
      
      // 发送到服务器保存
      fetch('change_theme.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'theme=' + theme
      }).then(response => response.json())
      .then(data => {
        if (data.success) {
          console.log('主题切换成功:', theme);
        } else {
          console.error('主题切换失败:', data.error);
        }
      }).catch(error => {
        console.error('主题切换请求失败:', error);
      });
    }
    
    // 点击外部关闭下拉菜单
    document.addEventListener('click', function(event) {
      const profileTrigger = document.querySelector('.profile-trigger');
      const profileDropdown = document.getElementById('profileDropdown');
      
      // 如果点击的不是触发按钮也不是弹窗内容，则关闭弹窗
      if (profileTrigger && profileDropdown && 
          !profileTrigger.contains(event.target) && 
          !profileDropdown.contains(event.target)) {
        closeProfileDropdown();
        console.log('点击外部，弹窗已关闭');
      }
    });
    
    // 添加键盘事件支持 - ESC键关闭弹窗
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeProfileDropdown();
        console.log('按ESC键，弹窗已关闭');
      }
    });
    
    // 页面加载时恢复主题
    function initTheme() {
      const savedTheme = sessionStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', savedTheme);
      
      // 更新设置模态框中的按钮状态
      updateThemeButtonStates(savedTheme);
    }
    
    // 更新主题按钮状态
    function updateThemeButtonStates(theme) {
      const lightBtn = document.querySelector('.theme-btn[onclick*="light"]');
      const darkBtn = document.querySelector('.theme-btn[onclick*="dark"]');
      
      if (lightBtn && darkBtn) {
        lightBtn.classList.remove('active');
        darkBtn.classList.remove('active');
        
        if (theme === 'light' && lightBtn) {
          lightBtn.classList.add('active');
        } else if (theme === 'dark' && darkBtn) {
          darkBtn.classList.add('active');
        }
      }
    }
    
    // 更新语言按钮状态
    function updateLanguageButtonStates() {
      const currentLang = '<?= $_SESSION['lang'] ?? 'en' ?>';
      const zhBtn = document.querySelector('.lang-btn[onclick*="zh"]');
      const enBtn = document.querySelector('.lang-btn[onclick*="en"]');
      
      if (zhBtn && enBtn) {
        zhBtn.classList.remove('active');
        enBtn.classList.remove('active');
        
        if (currentLang === 'zh' && zhBtn) {
          zhBtn.classList.add('active');
        } else if (currentLang === 'en' && enBtn) {
          enBtn.classList.add('active');
        }
      }
    }
    
    // 页面加载完成后初始化主题和语言按钮状态
    document.addEventListener('DOMContentLoaded', function() {
      initTheme();
      updateLanguageButtonStates();
    });

  </script>
  
  <!-- 设置模态框 -->
  <div class="settings-modal" id="settingsModal">
    <div class="settings-modal-content">
      <div class="settings-modal-header">
        <h3><?= t('common.settings') ?></h3>
        <button class="close-btn" onclick="toggleSettingsModal()">&times;</button>
      </div>
      
      <div class="settings-modal-body">
        <div class="setting-section">
          <h4><?= t('common.language') ?></h4>
          <div class="language-options">
            <button class="lang-btn <?= ($_SESSION['lang'] ?? 'en') === 'zh' ? 'active' : '' ?>" onclick="changeLanguage('zh')">
              🇨🇳 中文
            </button>
            <button class="lang-btn <?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'active' : '' ?>" onclick="changeLanguage('en')">
              🇺🇸 English
            </button>
          </div>
        </div>
        
        <div class="setting-section">
          <h4><?= t('common.theme') ?></h4>
          <div class="theme-options">
            <button class="theme-btn" onclick="changeTheme('light')">
              ☀️ <?= t('common.light_mode') ?>
            </button>
            <button class="theme-btn" onclick="changeTheme('dark')">
              🌙 <?= t('common.dark_mode') ?>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- 模态框背景遮罩 -->
  <div class="modal-backdrop" id="modalBackdrop" onclick="toggleSettingsModal()"></div>
</body>
</html>
