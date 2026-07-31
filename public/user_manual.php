<?php
// user_manual.php - 系统说明书
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

// 检查是否登录
if (!isset($_SESSION['user'])) {
    header('Location: login_roles.php');
    exit;
}

$user = $_SESSION['user'];
$user_role = $user['role'];
?>
<!doctype html>
<html lang="<?= t('common.language_code') ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('manual.title', '系统说明书') ?></title>
  <style>
    /* 重置样式 */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    /* 说明书页面样式 */
    body {
      background: linear-gradient(135deg, #4a90e2 0%, #87ceeb 50%, #e6f3ff 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #333;
    }
    
    .manual-container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 40px;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(74, 144, 226, 0.2);
      position: relative;
      overflow: hidden;
      backdrop-filter: blur(10px);
    }
    
    .manual-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #4a90e2, #87ceeb, #4a90e2);
      border-radius: 20px 20px 0 0;
    }
    
    .manual-header {
      text-align: center;
      margin-bottom: 40px;
      padding-bottom: 30px;
      border-bottom: 2px solid #4a90e2;
      position: relative;
    }
    
    .manual-title {
      color: #4a90e2;
      font-size: 36px;
      font-weight: 800;
      margin-bottom: 15px;
      text-shadow: 0 2px 4px rgba(74, 144, 226, 0.3);
      background: linear-gradient(135deg, #4a90e2, #87ceeb);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .manual-subtitle {
      color: #666;
      font-size: 18px;
      margin-bottom: 20px;
      font-weight: 300;
    }
    
    .role-badge {
      display: inline-block;
      background: linear-gradient(135deg, #4a90e2, #87ceeb);
      color: white;
      padding: 10px 25px;
      border-radius: 25px;
      font-size: 14px;
      font-weight: 600;
      margin-top: 15px;
      box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
    
    .section {
      margin-bottom: 40px;
      animation: fadeInUp 0.6s ease-out;
    }
    
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .section-title {
      color: #4a90e2;
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 20px;
      padding-left: 15px;
      border-left: 5px solid #4a90e2;
      position: relative;
    }
    
    .section-title::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 15px;
      width: 50px;
      height: 3px;
      background: linear-gradient(90deg, #4a90e2, transparent);
      border-radius: 2px;
    }
    
    .feature-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 25px;
      margin-bottom: 25px;
    }
    
    .feature-card {
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(74, 144, 226, 0.2);
      border-radius: 15px;
      padding: 25px;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      border-left: 5px solid #4a90e2;
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(74, 144, 226, 0.1);
    }
    
    .feature-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(74, 144, 226, 0.05), rgba(135, 206, 235, 0.05));
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .feature-card:hover::before {
      opacity: 1;
    }
    
    .feature-card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 15px 40px rgba(74, 144, 226, 0.2);
      border-color: #4a90e2;
    }
    
    .feature-icon {
      font-size: 32px;
      margin-bottom: 15px;
      color: #4a90e2;
      display: block;
      animation: bounce 2s infinite;
    }
    
    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
      40% { transform: translateY(-10px); }
      60% { transform: translateY(-5px); }
    }
    
    .feature-title {
      color: #333;
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 12px;
      position: relative;
      z-index: 1;
    }
    
    .feature-desc {
      color: #666;
      font-size: 15px;
      line-height: 1.6;
      position: relative;
      z-index: 1;
    }
    
    .quick-start {
      background: linear-gradient(135deg, #4a90e2, #87ceeb);
      color: white;
      padding: 30px;
      border-radius: 15px;
      margin-bottom: 30px;
      position: relative;
      overflow: hidden;
    }
    
    .quick-start::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
      animation: rotate 20s linear infinite;
    }
    
    @keyframes rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    
    .quick-start h3 {
      margin: 0 0 20px 0;
      font-size: 22px;
      font-weight: 700;
      position: relative;
      z-index: 1;
    }
    
    .quick-start ol {
      margin: 0;
      padding-left: 25px;
      position: relative;
      z-index: 1;
    }
    
    .quick-start li {
      margin-bottom: 12px;
      line-height: 1.6;
      font-size: 16px;
      position: relative;
    }
    
    .quick-start li::marker {
      color: rgba(255, 255, 255, 0.8);
      font-weight: bold;
    }
    
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, #4a90e2, #87ceeb);
      color: white;
      padding: 12px 24px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      margin-bottom: 25px;
      box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
      position: relative;
      overflow: hidden;
    }
    
    .back-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s;
    }
    
    .back-btn:hover::before {
      left: 100%;
    }
    
    .back-btn:hover {
      background: linear-gradient(135deg, #87ceeb, #4a90e2);
      transform: translateY(-2px) scale(1.05);
      box-shadow: 0 8px 25px rgba(74, 144, 226, 0.4);
    }
    
    .back-btn:active {
      transform: translateY(0) scale(0.98);
    }
    
    .back-icon {
      font-size: 16px;
      transition: transform 0.3s ease;
    }
    
    .back-btn:hover .back-icon {
      transform: translateX(-3px);
    }
    
    /* 深色模式样式 */
    [data-theme="dark"] body {
      background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%);
    }
    
    [data-theme="dark"] .manual-container {
      background: rgba(30, 58, 138, 0.95);
      border: 1px solid rgba(59, 130, 246, 0.3);
    }
    
    [data-theme="dark"] .feature-card {
      background: rgba(30, 58, 138, 0.8);
      border: 1px solid rgba(59, 130, 246, 0.3);
    }
    
    [data-theme="dark"] .feature-card:hover {
      background: rgba(30, 58, 138, 0.9);
    }
    
    [data-theme="dark"] .manual-title {
      color: #93c5fd;
    }
    
    [data-theme="dark"] .manual-subtitle {
      color: #cbd5e1;
    }
    
    [data-theme="dark"] .feature-title {
      color: #f1f5f9;
    }
    
    [data-theme="dark"] .feature-desc {
      color: #cbd5e1;
    }
    
    /* 响应式设计 */
    @media (max-width: 768px) {
      .manual-container {
        margin: 10px;
        padding: 20px;
        border-radius: 15px;
      }
      
      .manual-title {
        font-size: 24px;
      }
      
      .manual-subtitle {
        font-size: 16px;
      }
      
      .feature-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }
      
      .feature-card {
        padding: 20px;
      }
      
      .quick-start {
        padding: 20px;
      }
      
      .section-title {
        font-size: 20px;
      }
    }
    
    @media (max-width: 480px) {
      .manual-container {
        margin: 5px;
        padding: 15px;
      }
      
      .manual-title {
        font-size: 20px;
      }
      
      .feature-card {
        padding: 15px;
      }
      
      .quick-start {
        padding: 15px;
      }
    }
    
    /* 滚动条美化 */
    ::-webkit-scrollbar {
      width: 8px;
    }
    
    ::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.1);
    }
    
    ::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #4a90e2, #87ceeb);
      border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #87ceeb, #4a90e2);
    }
  </style>
</head>
<body>
  <div class="manual-container">
    <a href="javascript:history.back()" class="back-btn">
      <span class="back-icon">←</span>
      <span><?= t('common.back', '返回') ?></span>
    </a>
    
    <div class="manual-header">
      <h1 class="manual-title"><?= t('manual.title', 'ERPH 系统说明书') ?></h1>
      <p class="manual-subtitle"><?= t('manual.subtitle', '电子资源规划系统使用指南') ?></p>
      <div class="role-badge">
        <?php if ($user_role === 'admin'): ?>
          <?= t('roles.admin', '管理员') ?>
        <?php elseif ($user_role === 'teacher'): ?>
          <?= t('roles.teacher', '教师') ?>
        <?php else: ?>
          <?= t('roles.student', '学生') ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="quick-start">
      <h3><?= t('manual.quick_start', '快速开始') ?></h3>
      <ol>
        <li><?= t('manual.step1', '登录系统后，您将看到个人仪表板') ?></li>
        <li><?= t('manual.step2', '根据您的角色，使用相应的功能模块') ?></li>
        <li><?= t('manual.step3', '点击右上角头像可访问个人资料和设置') ?></li>
        <li><?= t('manual.step4', '使用主题切换按钮调整界面外观') ?></li>
      </ol>
    </div>

    <?php if ($user_role === 'admin'): ?>
      <!-- 管理员功能说明 -->
      <div class="section">
        <h2 class="section-title"><?= t('manual.admin_features', '管理员功能') ?></h2>
        <div class="feature-grid">
          <div class="feature-card">
            <div class="feature-icon">📊</div>
            <div class="feature-title"><?= t('manual.dashboard', '仪表板') ?></div>
            <div class="feature-desc"><?= t('manual.dashboard_desc', '查看系统整体统计信息，包括用户数量、课程数量、教课报告等关键数据') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">👥</div>
            <div class="feature-title"><?= t('manual.user_management', '用户管理') ?></div>
            <div class="feature-desc"><?= t('manual.user_management_desc', '添加、编辑、删除用户账户，管理用户角色和权限') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">📚</div>
            <div class="feature-title"><?= t('manual.course_management', '课程管理') ?></div>
            <div class="feature-desc"><?= t('manual.course_management_desc', '创建和管理课程，分配教师，设置课程信息') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">📝</div>
            <div class="feature-title"><?= t('manual.teaching_reports', '教课报告') ?></div>
            <div class="feature-desc"><?= t('manual.teaching_reports_desc', '查看所有教师的教课报告，监控教学进度和出勤情况') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">📖</div>
            <div class="feature-title"><?= t('manual.textbooks_homework', '教材作业') ?></div>
            <div class="feature-desc"><?= t('manual.textbooks_homework_desc', '管理教材资源和作业布置，跟踪学习进度') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">🏫</div>
            <div class="feature-title"><?= t('manual.classes', '班级管理') ?></div>
            <div class="feature-desc"><?= t('manual.classes_desc', '管理班级信息，分配学生到相应班级') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">🎨</div>
            <div class="feature-title"><?= t('manual.background_manager', '登录背景管理') ?></div>
            <div class="feature-desc"><?= t('manual.background_manager_desc', '自定义登录页面背景，提升用户体验') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">📈</div>
            <div class="feature-title"><?= t('manual.activity_monitor', '活动监控') ?></div>
            <div class="feature-desc"><?= t('manual.activity_monitor_desc', '实时监控系统活动，查看用户操作日志和统计数据') ?></div>
          </div>
        </div>
      </div>

    <?php elseif ($user_role === 'teacher'): ?>
      <!-- 教师功能说明 -->
      <div class="section">
        <h2 class="section-title"><?= t('manual.teacher_features', '教师功能') ?></h2>
        <div class="feature-grid">
          <div class="feature-card">
            <div class="feature-icon">📊</div>
            <div class="feature-title"><?= t('manual.teacher_dashboard', '教师仪表板') ?></div>
            <div class="feature-desc"><?= t('manual.teacher_dashboard_desc', '查看个人教学统计，包括课程数量、教课报告、教案等') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">📝</div>
            <div class="feature-title"><?= t('manual.submit_reports', '提交教课报告') ?></div>
            <div class="feature-desc"><?= t('manual.submit_reports_desc', '记录每日教学情况，包括出勤、教学内容、学生表现等') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">📚</div>
            <div class="feature-title"><?= t('manual.my_courses', '我的课程') ?></div>
            <div class="feature-desc"><?= t('manual.my_courses_desc', '查看和管理分配给您的课程，了解课程详情和进度') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">📖</div>
            <div class="feature-title"><?= t('manual.lesson_plans', '教案管理') ?></div>
            <div class="feature-desc"><?= t('manual.lesson_plans_desc', '上传和管理教学计划，分享教学资源') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">👤</div>
            <div class="feature-title"><?= t('manual.profile', '个人资料') ?></div>
            <div class="feature-desc"><?= t('manual.profile_desc', '管理个人信息，包括头像、联系方式等') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">⚙️</div>
            <div class="feature-title"><?= t('manual.settings', '系统设置') ?></div>
            <div class="feature-desc"><?= t('manual.settings_desc', '调整语言、主题等个人偏好设置') ?></div>
          </div>
        </div>
      </div>

    <?php else: ?>
      <!-- 学生功能说明 -->
      <div class="section">
        <h2 class="section-title"><?= t('manual.student_features', '学生功能') ?></h2>
        <div class="feature-grid">
          <div class="feature-card">
            <div class="feature-icon">📊</div>
            <div class="feature-title"><?= t('manual.student_dashboard', '学生仪表板') ?></div>
            <div class="feature-desc"><?= t('manual.student_dashboard_desc', '查看个人学习统计，包括课程进度、出勤记录等') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">📚</div>
            <div class="feature-title"><?= t('manual.my_courses', '我的课程') ?></div>
            <div class="feature-desc"><?= t('manual.my_courses_desc', '查看已注册的课程，了解课程安排和要求') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">📖</div>
            <div class="feature-title"><?= t('manual.course_materials', '课程资料') ?></div>
            <div class="feature-desc"><?= t('manual.course_materials_desc', '下载和查看课程相关材料，包括教案、作业等') ?></div>
          </div>
          
          <div class="feature-card">
            <div class="feature-icon">👤</div>
            <div class="feature-title"><?= t('manual.profile', '个人资料') ?></div>
            <div class="feature-desc"><?= t('manual.profile_desc', '管理个人信息，包括头像、联系方式等') ?></div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="section">
      <h2 class="section-title"><?= t('manual.common_features', '通用功能') ?></h2>
      <div class="feature-grid">
        <div class="feature-card">
          <div class="feature-icon">🌙</div>
          <div class="feature-title"><?= t('manual.theme_switch', '主题切换') ?></div>
          <div class="feature-desc"><?= t('manual.theme_switch_desc', '在浅色和深色主题之间切换，适应不同使用环境') ?></div>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">🌍</div>
          <div class="feature-title"><?= t('manual.language_switch', '语言切换') ?></div>
          <div class="feature-desc"><?= t('manual.language_switch_desc', '支持中文和英文界面，方便不同语言用户使用') ?></div>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">🔒</div>
          <div class="feature-title"><?= t('manual.security', '安全特性') ?></div>
          <div class="feature-desc"><?= t('manual.security_desc', '采用安全的用户认证机制，保护用户数据和隐私') ?></div>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">📱</div>
          <div class="feature-title"><?= t('manual.responsive', '响应式设计') ?></div>
          <div class="feature-desc"><?= t('manual.responsive_desc', '支持各种设备访问，包括电脑、平板和手机') ?></div>
        </div>
      </div>
    </div>

    <div class="section">
      <h2 class="section-title"><?= t('manual.help_support', '帮助与支持') ?></h2>
      <div class="feature-card">
        <div class="feature-icon">❓</div>
        <div class="feature-title"><?= t('manual.need_help', '需要帮助？') ?></div>
        <div class="feature-desc">
          <?= t('manual.help_text', '如果您在使用过程中遇到任何问题，请联系系统管理员。您也可以查看本说明书了解各功能的使用方法。') ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    // 页面加载时恢复主题
    function initTheme() {
      const savedTheme = sessionStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', savedTheme);
    }
    
    // 平滑滚动到顶部
    function scrollToTop() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    }
    
    // 添加滚动到顶部按钮
    function addScrollToTopButton() {
      const scrollBtn = document.createElement('button');
      scrollBtn.innerHTML = '↑';
      scrollBtn.className = 'scroll-to-top';
      scrollBtn.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
        color: white;
        border: none;
        font-size: 20px;
        font-weight: bold;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
        opacity: 0;
        visibility: hidden;
        z-index: 1000;
      `;
      
      scrollBtn.addEventListener('click', scrollToTop);
      document.body.appendChild(scrollBtn);
      
      // 监听滚动事件
      window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
          scrollBtn.style.opacity = '1';
          scrollBtn.style.visibility = 'visible';
        } else {
          scrollBtn.style.opacity = '0';
          scrollBtn.style.visibility = 'hidden';
        }
      });
      
      // 悬停效果
      scrollBtn.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1)';
        this.style.boxShadow = '0 6px 20px rgba(102, 126, 234, 0.4)';
      });
      
      scrollBtn.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
        this.style.boxShadow = '0 4px 15px rgba(102, 126, 234, 0.3)';
      });
    }
    
    // 添加卡片悬停效果
    function addCardHoverEffects() {
      const cards = document.querySelectorAll('.feature-card');
      cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0) scale(1)';
        });
      });
    }
    
    // 添加页面加载动画
    function addPageLoadAnimation() {
      const sections = document.querySelectorAll('.section');
      sections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
          section.style.transition = 'all 0.6s ease-out';
          section.style.opacity = '1';
          section.style.transform = 'translateY(0)';
        }, index * 100);
      });
    }
    
    // 添加返回按钮增强功能
    function enhanceBackButton() {
      const backBtn = document.querySelector('.back-btn');
      if (backBtn) {
        backBtn.addEventListener('click', function(e) {
          e.preventDefault();
          
          // 添加点击动画
          this.style.transform = 'scale(0.95)';
          setTimeout(() => {
            this.style.transform = 'scale(1)';
          }, 150);
          
          // 检查是否有历史记录
          if (window.history.length > 1) {
            window.history.back();
          } else {
            // 如果没有历史记录，跳转到仪表板
            const userRole = '<?= $user_role ?>';
            if (userRole === 'admin') {
              window.location.href = 'admin_dashboard.php';
            } else if (userRole === 'teacher') {
              window.location.href = 'teacher_dashboard.php';
            } else {
              window.location.href = 'index.php';
            }
          }
        });
      }
    }
    
    // 页面加载完成后初始化所有功能
    document.addEventListener('DOMContentLoaded', function() {
      initTheme();
      addScrollToTopButton();
      addCardHoverEffects();
      addPageLoadAnimation();
      enhanceBackButton();
      
      // 添加页面加载完成后的淡入效果
      document.body.style.opacity = '0';
      document.body.style.transition = 'opacity 0.5s ease-in';
      setTimeout(() => {
        document.body.style.opacity = '1';
      }, 100);
    });
    
    // 添加键盘快捷键支持
    document.addEventListener('keydown', function(e) {
      // ESC键返回
      if (e.key === 'Escape') {
        const backBtn = document.querySelector('.back-btn');
        if (backBtn) {
          backBtn.click();
        }
      }
      
      // Ctrl + Home 回到顶部
      if (e.ctrlKey && e.key === 'Home') {
        e.preventDefault();
        scrollToTop();
      }
    });
  </script>
</body>
</html>
