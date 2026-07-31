<?php
// 通用管理员侧边栏。使用前设定 $current_page 标识：
// 'dashboard' | 'users' | 'courses' | 'admin_reports' | 'textbooks_homework' | 'classes' | 'logs' | 'monitor'
if (!isset($current_page)) { $current_page = ''; }

// 确保语言配置已加载
if (!function_exists('t')) {
    require_once __DIR__ . '/language_config.php';
}
?>
<aside class="admin-sidebar">
  <div class="brand"><?= t('navigation.admin_menu', '管理菜单') ?></div>
  <div class="menu-title"><?= t('navigation.quick_nav', '快捷导航') ?></div>
  <ul class="menu">
    <li><a class="<?= $current_page==='dashboard'?'active':'' ?>" href="admin_dashboard.php"><?= t('navigation.dashboard') ?></a></li>
    <li><a class="<?= $current_page==='users'?'active':'' ?>" href="user_management.php"><?= t('navigation.user_management') ?></a></li>
    <li><a class="<?= $current_page==='courses'?'active':'' ?>" href="course_management.php"><?= t('navigation.course_management') ?></a></li>
    <li><a class="<?= $current_page==='admin_reports'?'active':'' ?>" href="admin_teaching_reports.php"><?= t('navigation.teaching_reports') ?></a></li>
    <li><a class="<?= $current_page==='textbooks_homework'?'active':'' ?>" href="textbooks_homework.php"><?= t('navigation.textbooks_homework') ?></a></li>
    <li><a class="<?= $current_page==='classes'?'active':'' ?>" href="classes.php"><?= t('navigation.classes') ?></a></li>
  </ul>
  <div class="menu-title" style="margin-top:12px;"><?= t('common.system') ?></div>
  <ul class="menu">
    <li><a class="<?= $current_page==='background_manager'?'active':'' ?>" href="login_background_manager.php"><?= t('navigation.login_background_manager') ?></a></li>
    <li><a class="<?= $current_page==='monitor'?'active':'' ?>" href="activity_monitor.php"><?= t('navigation.activity_monitor') ?></a></li>
  </ul>
</aside>
