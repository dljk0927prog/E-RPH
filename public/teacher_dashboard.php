<?php
// teacher_dashboard.php - 老师仪表板
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

// 检查是否登录且是老师
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header('Location: login_roles.php');
    exit;
}

$user = $_SESSION['user'];

// 获取老师相关的统计数据
try {
    require_once __DIR__ . '/../db.php';
    
    $stats = [];
    
    // 获取老师教授的课程数量
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT c.id) as count 
                          FROM courses c 
                          JOIN course_teachers ct ON c.id = ct.course_id 
                          WHERE ct.teacher_id = ?");
    $stmt->execute([$user['id']]);
    $stats['my_courses'] = $stmt->fetch()['count'];
    
    // 获取老师的出勤记录数量
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance a 
                          JOIN courses c ON a.course_id = c.id 
                          JOIN course_teachers ct ON c.id = ct.course_id
                          WHERE ct.teacher_id = ?");
    $stmt->execute([$user['id']]);
    $stats['my_attendance'] = $stmt->fetch()['count'];
    
    // 获取老师上传的教案数量
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM lesson_plans WHERE created_by = ?");
    $stmt->execute([$user['id']]);
    $stats['my_lesson_plans'] = $stmt->fetch()['count'];
    
    // 获取最近的课程
    $stmt = $pdo->prepare("SELECT DISTINCT c.id, c.title, c.description, c.created_at 
                          FROM courses c 
                          JOIN course_teachers ct ON c.id = ct.course_id 
                          WHERE ct.teacher_id = ? 
                          ORDER BY c.created_at DESC LIMIT 5");
    $stmt->execute([$user['id']]);
    $recent_courses = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = t('errors.get_statistics') . ": " . $e->getMessage();
}
?>

<!doctype html
<html lang="<?= t('common.language_code') ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('teacher_dashboard.title') ?> - ERPH</title>
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
      --bg-primary: #1a1a1a;
      --bg-secondary: #1e2328;
      --text-primary: #ffffff;
      --text-secondary: #cccccc;
      --text-muted: #999999;
      --border-color: #2d3748;
      --shadow-color: rgba(0, 0, 0, 0.3);
      --accent-color: #60a5fa;
      --accent-hover: #93c5fd;
      --header-bg: linear-gradient(90deg, #1e3a8a, #3b82f6);
      --card-border: #60a5fa;
      --success-bg: #065f46;
      --success-text: #9ae6b4;
      --success-border: #38a169;
      --error-bg: #742a2a;
      --error-text: #feb2b2;
      --error-border: #e53e3e;
      --warning-bg: #744210;
      --warning-text: #fef3c7;
      --warning-border: #d69e2e;
    }
    
    body {
      font-family: 'Microsoft YaHei', Arial, sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      transition: background-color 0.3s ease, color 0.3s ease;
      margin: 0;
      padding: 0;
    }
    
    /* Header样式 */
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
    
    .header > div {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .header a {
      background: rgba(255,255,255,0.15);
      color: white;
      text-decoration: none;
      padding: 8px 16px;
      border-radius: 6px;
      transition: all 0.2s ease;
      font-size: 14px;
      font-weight: 500;
      border: 1px solid rgba(255,255,255,0.2);
      outline: none;
      box-shadow: none;
      backdrop-filter: none; /* 移除模糊效果 */
    }
    
    .header a:hover {
      background: rgba(255,255,255,0.25);
      border-color: rgba(255,255,255,0.3);
      transform: translateY(-1px);
    }
    
    /* 主题切换按钮 */
    .theme-toggle-btn {
      background: rgba(255,255,255,0.15);
      color: white;
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 6px;
      padding: 8px 12px;
      cursor: pointer;
      font-size: 16px;
      transition: all 0.2s ease;
      margin-left: 8px;
    }
    
    .theme-toggle-btn:hover {
      background: rgba(255,255,255,0.25);
      border-color: rgba(255,255,255,0.3);
    }
    
    /* 深色模式页面整体美化 */
    [data-theme="dark"] body {
      background: linear-gradient(135deg, var(--bg-primary), #1a1f24);
      background-attachment: fixed;
    }
    
    [data-theme="dark"] .teacher-layout {
      background: transparent;
    }
    
    [data-theme="dark"] main {
      background: transparent;
    }
    
    /* 深色模式卡片美化 */
    [data-theme="dark"] .page-header,
    [data-theme="dark"] .stats-grid,
    [data-theme="dark"] .functions-grid,
    [data-theme="dark"] .recent-courses {
      background: linear-gradient(135deg, var(--bg-secondary), #2a2f35);
      border: 1px solid var(--border-color);
      box-shadow: 0 8px 25px var(--shadow-color);
      backdrop-filter: none; /* 移除模糊效果 */
    }
    
    [data-theme="dark"] .page-header:hover,
    [data-theme="dark"] .stats-grid:hover,
    [data-theme="dark"] .functions-grid:hover,
    [data-theme="dark"] .recent-courses:hover {
      background: #2a2f35;
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
      border-color: var(--accent-color);
    }
    
    /* 深色模式功能按钮美化 */
    [data-theme="dark"] .function-btn {
      background: linear-gradient(135deg, var(--bg-secondary), #2a2f35);
      border-color: var(--border-color);
      color: var(--text-primary);
    }
    
    [data-theme="dark"] .function-btn:hover {
      background: linear-gradient(135deg, #2a2f35, rgba(96, 165, 250, 0.1));
      border-color: var(--accent-color);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
    }
    
    [data-theme="dark"] .function-title {
      color: var(--accent-color);
    }
    
    [data-theme="dark"] .function-desc {
      color: var(--text-secondary);
    }
    
    /* 页面头部样式 */
    .page-header {
      background: var(--bg-secondary);
      padding: 25px;
      border-radius: 16px;
      margin-bottom: 25px;
      box-shadow: 0 8px 25px var(--shadow-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }
    
    .header-left h2 {
      color: var(--accent-color);
      margin: 0 0 8px 0;
      font-size: 24px;
    }
    
    .user-badge {
      background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
      color: white;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* 页面头部右侧样式 */
    .header-right {
      display: flex;
      align-items: center;
      gap: 20px;
      flex-wrap: wrap;
    }
    
    /* 个人资料下拉菜单样式 */
    .profile-dropdown {
      position: relative;
      display: inline-block;
    }
    
    .profile-trigger {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 8px;
      background: rgba(74, 144, 226, 0.1);
      color: var(--accent-color);
      border: 2px solid var(--accent-color);
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.2s ease;
      width: 40px;
      height: 40px;
    }
    
    .profile-trigger:hover {
      background: rgba(74, 144, 226, 0.2);
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
    }
    
    .profile-avatar {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 16px;
      font-weight: bold;
      box-shadow: 0 4px 15px rgba(74,144,226,0.3);
      transition: all 0.3s ease;
      flex-shrink: 0;
      text-align: center;
      line-height: 1;
      padding: 0;
      margin: 0;
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
      width: 100%;
      height: 100%;
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
      background: white;
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
      display: none;
    }
    
    .profile-dropdown-menu.show {
      opacity: 1 !important;
      visibility: visible !important;
      transform: translateY(0) !important;
      display: block !important;
    }
    
    .dropdown-header {
      padding: 16px !important;
      border-bottom: 1px solid #e1e5e9 !important;
      background: #f8f9fa !important;
      color: #000000 !important;
    }
    
    .user-avatar-section {
      display: flex !important;
      align-items: center !important;
      gap: 12px !important;
    }
    
    .user-avatar {
      width: 48px !important;
      height: 48px !important;
      background: linear-gradient(135deg, var(--accent-color), var(--accent-hover)) !important;
      border-radius: 50% !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      font-size: 20px !important;
      color: white !important;
      font-weight: bold !important;
      box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3) !important;
      transition: all 0.3s ease !important;
      text-align: center !important;
      line-height: 1 !important;
      padding: 0 !important;
      margin: 0 !important;
      position: relative !important;
      overflow: hidden !important;
    }
    
    .user-avatar .avatar-image {
      width: 100% !important;
      height: 100% !important;
      object-fit: cover !important;
      border-radius: 50% !important;
    }
    
    .user-info-text {
      flex: 1 !important;
    }
    
    .user-name {
      color: #000000 !important;
      font-size: 16px !important;
      font-weight: 600 !important;
      margin-bottom: 4px !important;
    }
    
    .user-role {
      color: #333333 !important;
      font-size: 13px !important;
      font-weight: 500 !important;
    }
    
    /* 临时测试样式 - 强制显示下拉菜单 */
    .profile-dropdown-menu {
      position: absolute !important;
      top: 100% !important;
      right: 0 !important;
      width: 220px !important;
      background: white !important;
      border: 1px solid #e1e5e9 !important;
      border-radius: 8px !important;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
      z-index: 9999 !important; /* 最高层级 */
      margin-top: 8px !important;
      overflow: hidden !important; /* 改为hidden防止内容溢出 */
      min-height: auto !important;
      max-height: none !important;
      backdrop-filter: none !important; /* 移除模糊效果 */
      -webkit-backdrop-filter: none !important; /* Safari兼容 */
      /* 确保清晰的边界 */
      transform: none !important;
      filter: none !important;
      -webkit-filter: none !important;
      /* 锐利的边缘 */
      clip-path: none !important;
      -webkit-clip-path: none !important;
    }
    
    .dropdown-body {
      background: white !important;
      color: #000000 !important;
      padding: 0 !important;
      overflow: hidden !important; /* 改为hidden */
    }
    
    .dropdown-item {
      display: flex !important;
      align-items: center !important;
      gap: 12px !important;
      padding: 12px 16px !important;
      color: #000000 !important;
      text-decoration: none !important;
      transition: background 0.2s ease !important;
      font-size: 14px !important;
      border: none !important;
      background: none !important;
      width: 100% !important;
      text-align: left !important;
      cursor: pointer !important;
      min-height: 44px !important;
    }
    
    .dropdown-item:hover {
      background: #f8f9fa;
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
      font-weight: 600;
    }
    
    .logout-item:hover {
      background: #fff5f5;
      color: #dc3545 !important;
    }
    
    /* 个人资料字段样式 - 黑色字体 */
    .profile-field {
      color: #000000 !important;
    }
    
    .profile-field:hover {
      background: #f8f9fa;
    }
    
    /* 深色模式下保持字体颜色不变 */
    [data-theme="dark"] .profile-field {
      color: #000000 !important;
    }
    
    [data-theme="dark"] .profile-field:hover {
      background: #f8f9fa;
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
    
    .theme-options {
      display: flex;
      gap: 12px;
    }
    
    .theme-btn {
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
    
    .theme-btn:hover {
      border-color: #4a90e2;
      color: #4a90e2;
    }
    
    .theme-btn.active {
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
    
    [data-theme="dark"] .theme-btn {
      background: var(--bg-secondary);
      color: var(--text-secondary);
      border: 2px solid var(--border-color);
    }
    
    [data-theme="dark"] .theme-btn:hover {
      border-color: var(--accent-color);
      color: var(--accent-color);
    }
    
    [data-theme="dark"] .theme-btn.active {
      background: var(--accent-color);
      border-color: var(--accent-color);
      color: white;
    }
    
    /* 统计网格样式 - 现代卡片式 */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 25px;
      margin: 30px 0;
    }
    
    .stat-card {
      background: linear-gradient(135deg, var(--bg-secondary), rgba(74, 144, 226, 0.02));
      border: 2px solid var(--border-color);
      border-radius: 16px;
      padding: 30px 20px;
      text-align: center;
      box-shadow: 0 8px 25px var(--shadow-color);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--accent-color), var(--accent-hover));
      transform: scaleX(0);
      transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 20px 40px var(--shadow-color);
      border-color: var(--accent-color);
    }
    
    .stat-card:hover::before {
      transform: scaleX(1);
    }
    
    .stat-number {
      font-size: 48px;
      font-weight: 800;
      background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 12px;
      display: block;
    }
    
    .stat-label {
      color: var(--text-secondary);
      font-size: 16px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* 功能按钮网格样式 */
    .functions-grid {
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 20px;
      margin: 20px 0;
      box-shadow: 0 4px 15px var(--shadow-color);
    }
    
    .functions-grid h3 {
      color: var(--accent-color);
      margin-bottom: 20px;
      text-align: center;
    }
    
    .function-buttons {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }
    
    .function-btn {
      display: block;
      background: var(--bg-secondary);
      border: 2px solid var(--border-color);
      border-radius: 12px;
      padding: 20px;
      text-decoration: none;
      color: var(--text-primary);
      transition: all 0.3s ease;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    
    .function-btn:hover {
      transform: translateY(-5px);
      border-color: var(--accent-color);
      box-shadow: 0 8px 25px var(--shadow-color);
      background: linear-gradient(135deg, var(--bg-secondary), rgba(74, 144, 226, 0.05));
    }
    
    .function-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(74, 144, 226, 0.1), transparent);
      transition: left 0.5s ease;
    }
    
    .function-btn:hover::before {
      left: 100%;
    }
    
    .function-icon {
      font-size: 32px;
      margin-bottom: 12px;
      display: block;
    }
    
    .function-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--accent-color);
      margin-bottom: 8px;
    }
    
    .function-desc {
      font-size: 14px;
      color: var(--text-secondary);
      line-height: 1.4;
    }
    
    /* 最近课程样式 */
    .recent-courses {
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 20px;
      margin: 20px 0;
      box-shadow: 0 4px 15px var(--shadow-color);
    }
    
    .recent-courses h3 {
      color: var(--accent-color);
      margin-bottom: 15px;
    }
    
    .course-item {
      padding: 15px;
      border-bottom: 1px solid var(--border-color);
      transition: all 0.3s ease;
    }
    
    .course-item:last-child {
      border-bottom: none;
    }
    
    .course-item:hover {
      background: rgba(74, 144, 226, 0.05);
      transform: translateX(5px);
    }
    
    .course-title {
      color: var(--text-primary);
      font-weight: 600;
      margin-bottom: 5px;
    }
    
    .course-description {
      color: var(--text-secondary);
      font-size: 14px;
      margin-bottom: 5px;
    }
    
    .course-date {
      color: var(--text-muted);
      font-size: 12px;
    }
    
    /* 响应式设计 */
    @media (max-width: 768px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .function-buttons {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
      }
      
      .header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      
      .header > div {
        flex-wrap: wrap;
        justify-content: center;
      }
    }
    
    @media (max-width: 480px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .function-buttons {
        grid-template-columns: 1fr;
        gap: 15px;
      }
      
      .page-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      
      .function-btn {
        padding: 15px;
      }
      
      .function-icon {
        font-size: 28px;
      }
      
      .function-title {
        font-size: 16px;
      }
      
      .function-desc {
        font-size: 13px;
      }
    }

    /* 头部个人资料按钮样式 */
    .header .profile-dropdown {
      margin-left: 8px;
    }
    
    .header .profile-trigger {
      width: 36px;
      height: 36px;
      border-radius: 6px;
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.2);
      color: white;
      backdrop-filter: none; /* 移除模糊效果 */
    }
    
    .header .profile-trigger:hover {
      background: rgba(255,255,255,0.25);
      border-color: rgba(255,255,255,0.3);
      transform: translateY(-1px);
    }
    
    .header .profile-avatar {
      width: 100%;
      height: 100%;
      border-radius: 4px;
      background: rgba(255,255,255,0.2);
      font-size: 14px;
      font-weight: 600;
    }
    
    .header .profile-avatar .avatar-image {
      border-radius: 4px;
    }
    
    /* 个人资料信息样式 */
    .profile-info {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 16px;
      margin-top: 12px;
    }
    
    .profile-info-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid #e9ecef;
    }
    
    .profile-info-item:last-child {
      border-bottom: none;
    }
    
    .profile-info-label {
      color: #000000 !important;
      font-weight: 600;
      font-size: 14px;
    }
    
    .profile-info-value {
      color: #000000 !important;
      font-size: 14px;
    }
    
    /* 语言选项样式 */
    .language-options {
      display: flex;
      gap: 12px;
      margin-top: 12px;
    }
    
    .language-btn {
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
    
    .language-btn:hover {
      border-color: #4a90e2;
      color: #4a90e2;
    }
    
    .language-btn.active {
      background: #4a90e2;
      border-color: #4a90e2;
      color: white;
    }
    
    /* 深色模式下的个人资料样式保持不变 */
    [data-theme="dark"] .profile-info {
      background: #f8f9fa;
    }
    
    [data-theme="dark"] .profile-info-label,
    [data-theme="dark"] .profile-info-value {
      color: #000000 !important;
    }
    
    /* 深色模式语言按钮样式 */
    [data-theme="dark"] .language-btn {
      background: var(--bg-secondary);
      color: var(--text-secondary);
      border: 2px solid var(--border-color);
    }
    
    [data-theme="dark"] .language-btn:hover {
      border-color: var(--accent-color);
      color: var(--accent-color);
    }
    
    [data-theme="dark"] .language-btn.active {
      background: var(--accent-color);
      border-color: var(--accent-color);
      color: white;
    }
  </style>
</head>
<body>
  <header class="header">
    <h1>ERPH 系统 - <?= t('teacher_dashboard.title') ?></h1>
    <div>
      <a href="teacher_dashboard.php"><?= t('common.back') ?><?= t('common.dashboard') ?></a>
      <a href="logout.php"><?= t('common.logout') ?></a>
      <!-- 个人资料下拉菜单 -->
      <div class="profile-dropdown">
        <button class="profile-trigger" onclick="toggleProfileDropdown()" title="<?= t('common.profile') ?>">
          <span class="profile-avatar">
            <?php if (!empty($user['avatar'])): ?>
              <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="<?= t('common.avatar') ?>" class="avatar-image">
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
                  <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="<?= t('common.avatar') ?>" class="avatar-image">
                <?php else: ?>
                  <?= mb_substr($user['name'], 0, 1) ?>
                <?php endif; ?>
              </div>
              <div class="user-info-text">
                <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                <div class="user-role"><?= t('common.teacher') ?></div>
              </div>
            </div>
          </div>
          <div class="dropdown-body">
            <a href="teacher_profile.php" class="dropdown-item profile-field">
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

  <div class="teacher-layout">
    <main>
      <div class="page-header">
        <div class="header-left">
          <h2><?= t('teacher_dashboard.welcome_back') ?>，<?= htmlspecialchars($user['name']) ?>！</h2>
          <span class="user-badge"><?= t('common.teacher') ?></span>
        </div>
        <div class="header-right">
          <!-- 个人资料按钮已移动到头部 -->
        </div>
      </div>

      <?php if (isset($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- 统计卡片 -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-number"><?= $stats['my_courses'] ?? 0 ?></div>
          <div class="stat-label"><?= t('teacher_dashboard.my_courses') ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $stats['my_attendance'] ?? 0 ?></div>
          <div class="stat-label"><?= t('teacher_dashboard.attendance_records') ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $stats['my_lesson_plans'] ?? 0 ?></div>
          <div class="stat-label"><?= t('teacher_dashboard.my_lesson_plans') ?></div>
        </div>
      </div>

      <!-- 功能按钮网格 -->
      <div class="functions-grid">
        <h3><?= t('teacher_dashboard.quick_functions') ?></h3>
        <div class="function-buttons">
          <a href="teaching_reports.php" class="function-btn">
            <div class="function-icon">📊</div>
            <div class="function-title"><?= t('teacher_dashboard.teaching_reports') ?></div>
            <div class="function-desc"><?= t('teacher_dashboard.teaching_reports_desc') ?></div>
          </a>
          
          <a href="lessonplans.php" class="function-btn">
            <div class="function-icon">📝</div>
            <div class="function-title"><?= t('teacher_dashboard.lesson_plans') ?></div>
            <div class="function-desc"><?= t('teacher_dashboard.lesson_plans_desc') ?></div>
          </a>
          
          <a href="my_courses.php" class="function-btn">
            <div class="function-icon">📚</div>
            <div class="function-title"><?= t('teacher_dashboard.my_courses') ?></div>
            <div class="function-desc"><?= t('teacher_dashboard.my_courses_desc') ?></div>
          </a>
          
          <a href="teacher_profile.php" class="function-btn">
            <div class="function-icon">👤</div>
            <div class="function-title"><?= t('common.profile') ?></div>
            <div class="function-desc"><?= t('teacher_dashboard.profile_desc') ?></div>
            </a>
          </div>
        </div>

      <!-- 最近课程 -->
      <div class="recent-courses">
        <h3><?= t('teacher_dashboard.recent_courses') ?></h3>
        <?php if (!empty($recent_courses)): ?>
          <?php foreach ($recent_courses as $course): ?>
            <div class="course-item">
              <div class="course-title"><?= htmlspecialchars($course['title']) ?></div>
              <div class="course-description"><?= htmlspecialchars($course['description'] ?? '') ?></div>
              <div class="course-date"><?= t('common.created_at') ?>: <?= date('Y-m-d', strtotime($course['created_at'])) ?></div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="text-align: center; color: var(--text-muted);"><?= t('teacher_dashboard.no_courses') ?></p>
        <?php endif; ?>
      </div>
    </main>
  </div>
  
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
            <button class="language-btn" data-lang="zh" onclick="changeLanguage('zh')">
              🇨🇳 中文
            </button>
            <button class="language-btn" data-lang="en" onclick="changeLanguage('en')">
              🇺🇸 English
            </button>
          </div>
        </div>
        
        <div class="setting-section">
          <h4><?= t('common.theme') ?></h4>
          <div class="theme-options">
            <button class="theme-btn" data-theme="light" onclick="changeTheme('light')">
              ☀️ <?= t('common.light_mode') ?>
            </button>
            <button class="theme-btn" data-theme="dark" onclick="changeTheme('dark')">
              🌙 <?= t('common.dark_mode') ?>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- 模态框背景遮罩 -->
  <div class="modal-backdrop" id="modalBackdrop" onclick="toggleSettingsModal()"></div>

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
      // 从服务器获取当前主题设置，而不是从sessionStorage
      const currentTheme = '<?= $_SESSION['theme'] ?? 'light' ?>';
      document.documentElement.setAttribute('data-theme', currentTheme);
      
      // 同步sessionStorage
      sessionStorage.setItem('theme', currentTheme);
      
      // 更新按钮图标
      const themeBtn = document.querySelector('.theme-toggle-btn');
      if (themeBtn) {
        themeBtn.innerHTML = currentTheme === 'light' ? '🌙' : '☀️';
        themeBtn.title = currentTheme === 'light' ? '<?= t('common.switch_to_dark') ?>' : '<?= t('common.switch_to_light') ?>';
      }
      
      console.log('主题初始化完成:', currentTheme);
    }
    

    
    // 个人资料下拉菜单切换
    function toggleProfileDropdown() {
      const dropdown = document.getElementById('profileDropdown');
      if (dropdown) {
        dropdown.classList.toggle('show');
      }
    }
    
    // 关闭个人资料下拉菜单
    function closeProfileDropdown() {
      const dropdown = document.getElementById('profileDropdown');
      if (dropdown) {
        dropdown.classList.remove('show');
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
    
    // 主题切换
    function changeTheme(theme) {
      console.log('正在切换主题到:', theme);
      
      // 更新按钮状态
      document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.classList.remove('active');
      });
      
      // 找到当前点击的按钮并添加active类
      const currentBtn = document.querySelector(`.theme-btn[data-theme="${theme}"]`);
      if (currentBtn) {
        currentBtn.classList.add('active');
        console.log('已激活主题按钮:', theme);
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
    
    // 语言切换功能
    function changeLanguage(language) {
      console.log('正在切换语言到:', language);
      
      // 更新按钮状态
      document.querySelectorAll('.language-btn').forEach(btn => {
        btn.classList.remove('active');
      });
      
      // 找到当前点击的按钮并添加active类
      const currentBtn = document.querySelector(`.language-btn[data-lang="${language}"]`);
      if (currentBtn) {
        currentBtn.classList.add('active');
        console.log('已激活语言按钮:', language);
      }
      
      // 保存到sessionStorage
      sessionStorage.setItem('language', language);
      
      // 发送到服务器保存
      fetch('change_language.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'lang=' + language
      }).then(response => response.json())
      .then(data => {
        if (data.success) {
          console.log('语言切换成功:', language);
          // 刷新页面以应用新语言
          location.reload();
        } else {
          console.error('语言切换失败:', data.error);
        }
      }).catch(error => {
        console.error('语言切换请求失败:', error);
      });
    }
    
    // 更新语言按钮状态
    function updateLanguageButtonStates() {
      // 从服务器获取当前语言设置，而不是从sessionStorage
      const currentLanguage = '<?= $_SESSION['lang'] ?? 'en' ?>';
      
      document.querySelectorAll('.language-btn').forEach(btn => {
        btn.classList.remove('active');
        // 使用data-lang属性来准确识别按钮
        if (btn.getAttribute('data-lang') === currentLanguage) {
          btn.classList.add('active');
        }
      });
      
      console.log('当前语言:', currentLanguage, '语言按钮状态已更新');
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
      }
    });
    
    // 添加键盘事件支持 - ESC键关闭弹窗
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeProfileDropdown();
      }
    });
    
    // 页面加载完成后初始化主题和布局
    document.addEventListener('DOMContentLoaded', function() {
      initTheme();
      
      // 初始化主题按钮状态
      updateThemeButtonStates();

      // 初始化语言按钮状态
      updateLanguageButtonStates();
    });
    
    // 更新主题按钮状态
    function updateThemeButtonStates() {
      // 从服务器获取当前主题设置
      const currentTheme = '<?= $_SESSION['theme'] ?? 'light' ?>';
      
      document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.classList.remove('active');
        // 使用data-theme属性来准确识别按钮
        if (btn.getAttribute('data-theme') === currentTheme) {
          btn.classList.add('active');
        }
      });
      
      console.log('当前主题:', currentTheme, '主题按钮状态已更新');
    }
  </script>
</body>
</html>
