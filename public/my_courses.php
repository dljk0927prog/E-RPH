<?php
// my_courses.php - 我的课程页面
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

// 检查是否登录且是老师
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header('Location: login_roles.php');
    exit;
}

require_once __DIR__ . '/../db.php';
$user = $_SESSION['user'];
$msg = '';
$error = '';

// 获取当前老师的课程列表
$courses = [];
$course_stats = [];
try {
    // 获取课程基本信息
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.title,
            c.description,
            c.created_at,
            c.is_active,
            COUNT(DISTINCT a.id) as total_reports,
            COUNT(DISTINCT CASE WHEN DATE(a.date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN a.id END) as week_reports,
            COUNT(DISTINCT lp.id) as lesson_plans_count,
            MAX(a.date) as last_report_date
        FROM courses c
        JOIN course_teachers ct ON c.id = ct.course_id
        LEFT JOIN attendance a ON c.id = a.course_id AND a.user_id = ?
        LEFT JOIN lesson_plans lp ON EXISTS (
            SELECT 1 FROM attendance a2 
            WHERE a2.course_id = c.id 
            AND a2.lesson_plan_id = lp.id 
            AND a2.user_id = ?
        )
        WHERE ct.teacher_id = ?
        GROUP BY c.id, c.title, c.description, c.created_at, c.is_active
        ORDER BY c.is_active DESC, c.created_at DESC
    ");
    $stmt->execute([$user['id'], $user['id'], $user['id']]);
    $courses = $stmt->fetchAll();
    
    // 获取总体统计
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT c.id) as total_courses,
            COUNT(DISTINCT CASE WHEN c.is_active = 1 THEN c.id END) as active_courses,
            COUNT(DISTINCT a.id) as total_reports,
            COUNT(DISTINCT CASE WHEN DATE(a.date) = CURDATE() THEN a.id END) as today_reports
        FROM courses c
        JOIN course_teachers ct ON c.id = ct.course_id
        LEFT JOIN attendance a ON c.id = a.course_id AND a.user_id = ?
        WHERE ct.teacher_id = ?
    ");
    $stats_stmt->execute([$user['id'], $user['id']]);
    $course_stats = $stats_stmt->fetch();
    
} catch (Exception $e) {
    $error = "获取课程信息失败: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="<?= t('common.language_code') ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('my_courses.title') ?> - ERPH</title>
    <style>
        :root {
            --bg-primary: #f5f5f5;
            --bg-secondary: #ffffff;
            --bg-tertiary: #f8f9fa;
            --text-primary: #333333;
            --text-secondary: #666666;
            --text-muted: #999999;
            --accent-color: #4a90e2;
            --accent-hover: #7bb3f0;
            --border-color: #e1e5e9;
            --shadow-color: rgba(0, 0, 0, 0.08);
            --shadow-hover: rgba(0, 0, 0, 0.15);
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --header-bg: linear-gradient(90deg, #4a90e2, #7bb3f0);
        }

        [data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #1e2328;
            --bg-tertiary: #2d3748;
            --text-primary: #ffffff;
            --text-secondary: #cccccc;
            --text-muted: #999999;
            --accent-color: #60a5fa;
            --accent-hover: #93c5fd;
            --border-color: #2d3748;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --shadow-hover: rgba(0, 0, 0, 0.5);
            --header-bg: linear-gradient(90deg, #1e3a8a, #3b82f6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .container {
            max-width: 100%;
            margin: 0;
            padding: 0;
        }

        /* Header样式 - 参考teacher_dashboard.php */
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
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.15);
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.2);
            outline: none;
            box-shadow: none;
            backdrop-filter: blur(10px);
        }

        .header a:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }

        .content-wrapper {
            padding: 40px;
            background: var(--bg-primary);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .stat-card {
            background: var(--bg-secondary);
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 8px 32px var(--shadow-color);
            border: 2px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
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
            background: var(--accent-color);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 60px var(--shadow-hover);
            border-color: var(--accent-color);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-number {
            font-size: 56px;
            font-weight: 800;
            color: var(--accent-color);
            margin-bottom: 15px;
            display: block;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .courses-section {
            background: var(--bg-secondary);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 8px 32px var(--shadow-color);
            border: 1px solid var(--border-color);
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--accent-color);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--accent-color);
        }

        .course-item {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 25px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .course-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent 30%, rgba(74, 144, 226, 0.03) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .course-item:hover {
            border-color: var(--accent-color);
            box-shadow: 0 15px 50px var(--shadow-hover);
            transform: translateY(-5px);
        }

        .course-item:hover::before {
            transform: translateX(100%);
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .course-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--accent-color);
        }

        .course-status {
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-active {
            background: var(--success-color);
            color: white;
        }

        .status-inactive {
            background: var(--danger-color);
            color: white;
        }

        .course-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            padding: 20px;
            background: var(--bg-tertiary);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .detail-item:hover {
            background: var(--bg-secondary);
            transform: translateY(-2px);
        }

        .detail-label {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 16px;
            color: var(--text-primary);
            font-weight: 600;
        }

        .no-courses {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-secondary);
        }

        .no-courses-icon {
            font-size: 80px;
            margin-bottom: 30px;
            opacity: 0.6;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .no-courses h3 {
            font-size: 28px;
            margin-bottom: 15px;
            color: var(--text-primary);
        }

        .no-courses p {
            font-size: 18px;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header > div {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .content-wrapper {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .stat-card {
                padding: 30px 20px;
            }
            
            .stat-number {
                font-size: 48px;
            }
            
            .courses-section {
                padding: 25px;
            }
            
            .course-item {
                padding: 20px;
            }
            
            .course-details {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        @media (min-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .course-details {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header - 参考teacher_dashboard.php -->
        <header class="header">
            <h1>ERPH 系统 - <?= t('my_courses.title') ?></h1>
            <div>
                <a href="teacher_dashboard.php"><?= t('common.back') ?><?= t('common.dashboard') ?></a>
                <a href="logout.php"><?= t('common.logout') ?></a>
            </div>
        </header>

        <div class="content-wrapper">
            <!-- 统计卡片 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $course_stats['total_courses'] ?? 0 ?></div>
                    <div class="stat-label"><?= t('my_courses.total_courses') ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $course_stats['active_courses'] ?? 0 ?></div>
                    <div class="stat-label"><?= t('my_courses.active_courses') ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $course_stats['total_reports'] ?? 0 ?></div>
                    <div class="stat-label"><?= t('my_courses.total_reports') ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $course_stats['today_reports'] ?? 0 ?></div>
                    <div class="stat-label"><?= t('my_courses.today_reports') ?></div>
                </div>
            </div>

            <!-- 课程列表 -->
            <div class="courses-section">
                <h2 class="section-title"><?= t('my_courses.title') ?></h2>
                
                <?php if (empty($courses)): ?>
                    <div class="no-courses">
                        <div class="no-courses-icon">📚</div>
                        <h3><?= t('my_courses.no_courses') ?></h3>
                        <p><?= t('my_courses.no_courses_desc') ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="course-item">
                            <div class="course-header">
                                <div class="course-title"><?= htmlspecialchars($course['title']) ?></div>
                                <div class="course-status status-<?= $course['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $course['is_active'] ? t('my_courses.status_active') : t('my_courses.status_inactive') ?>
                                </div>
                            </div>
                            
                            <div class="course-details">
                                <div class="detail-item">
                                    <div class="detail-label"><?= t('common.description') ?></div>
                                    <div class="detail-value"><?= htmlspecialchars($course['description'] ?? t('my_courses.no_description')) ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label"><?= t('my_courses.teaching_reports') ?></div>
                                    <div class="detail-value"><?= $course['total_reports'] ?> <?= t('common.times') ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label"><?= t('my_courses.week_reports') ?></div>
                                    <div class="detail-value"><?= $course['week_reports'] ?> <?= t('common.times') ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label"><?= t('my_courses.lesson_plans_count') ?></div>
                                    <div class="detail-value"><?= $course['lesson_plans_count'] ?> <?= t('common.items') ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label"><?= t('my_courses.last_report') ?></div>
                                    <div class="detail-value"><?= $course['last_report_date'] ? date(t('common.date_format'), strtotime($course['last_report_date'])) : t('common.none') ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label"><?= t('common.created_at') ?></div>
                                    <div class="detail-value"><?= date(t('common.date_format'), strtotime($course['created_at'])) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // 主题切换功能
        function initTheme() {
            const savedTheme = sessionStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        }

        function changeTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            sessionStorage.setItem('theme', theme);
            
            // 发送到服务器保存
            fetch('change_theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'theme=' + theme
            });
        }

        // 页面加载时初始化主题
        document.addEventListener('DOMContentLoaded', function() {
            initTheme();
            
            // 添加滚动动画
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            // 观察所有卡片
            document.querySelectorAll('.stat-card, .course-item').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>
