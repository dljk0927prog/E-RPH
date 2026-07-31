<?php
// teacher_profile.php - 老师个人资料页面
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

// 检查是否登录且是老师
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header('Location: login_roles.php');
    exit;
}

// 语言切换后去掉 URL 中的 lang 参数
if (isset($_GET['lang']) && in_array($_GET['lang'], ['zh', 'en'], true)) {
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirect_url);
    exit;
}

require_once __DIR__ . '/../db.php';
$user = $_SESSION['user'];
$msg = '';
$error = '';

// 处理头像上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar'])) {
    try {
        $avatar_data = $_POST['avatar_data'];
        
        // 检查avatar字段是否存在，如果不存在则添加
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'");
        $avatar_exists = $stmt->fetch();
        
        if (!$avatar_exists) {
            $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL COMMENT '用户头像路径'");
        }
        
        // 解码Base64图片数据
        $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $avatar_data));
        
        // 创建上传目录
        $upload_dir = 'uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // 生成唯一文件名
        $filename = 'avatar_' . $user['id'] . '_' . time() . '.png';
        $filepath = $upload_dir . $filename;
        
        // 保存图片
        if (file_put_contents($filepath, $image_data)) {
            // 更新数据库
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$filepath, $user['id']]);
            
            // 更新session
            $_SESSION['user']['avatar'] = $filepath;
            $user = $_SESSION['user'];
            
            // 强制刷新session
            session_write_close();
            session_start();
            $_SESSION['user']['avatar'] = $filepath;
            $user = $_SESSION['user'];
            
            $msg = '头像更新成功！';
        } else {
            throw new Exception('保存头像文件失败');
        }
        
    } catch (Exception $e) {
        $error = '头像更新失败: ' . $e->getMessage();
    }
}

// 处理个人信息更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        if (empty($name)) {
            throw new Exception(t('profile.name_required'));
        }
        
        // 检查邮箱是否已被其他用户使用
        if (!empty($email)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                throw new Exception(t('profile.email_in_use'));
            }
        }
        
        // 检查phone字段是否存在，然后更新用户信息
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'");
        $phone_exists = $stmt->fetch();
        
        if ($phone_exists) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $user['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $user['id']]);
        }
        
        // 更新session中的用户信息
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['phone'] = $phone;
        $user = $_SESSION['user'];
        
        $msg = t('profile.personal_info_update_success');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 处理密码更改
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    try {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception(t('profile.all_password_fields_required'));
        }
        
        if ($new_password !== $confirm_password) {
            throw new Exception(t('profile.password_mismatch_error'));
        }
        
        if (strlen($new_password) < 6) {
            throw new Exception(t('profile.password_too_short_error'));
        }
        
        // 验证当前密码
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $user_data = $stmt->fetch();
        
        if (!password_verify($current_password, $user_data['password'])) {
            throw new Exception(t('profile.current_password_incorrect'));
        }
        
        // 更新密码
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user['id']]);
        
        $msg = t('profile.password_change_success');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 辅助函数：部分加密显示
function maskString($str, $visible_chars = 2) {
    if (empty($str)) return '';
    $length = mb_strlen($str);
    if ($length <= $visible_chars) {
        return str_repeat('*', $length);
    }
    $visible_part = mb_substr($str, 0, $visible_chars);
    $masked_part = str_repeat('*', $length - $visible_chars);
    return $visible_part . $masked_part;
}

function maskEmail($email) {
    if (empty($email)) return '';
    $parts = explode('@', $email);
    if (count($parts) !== 2) return maskString($email, 2);
    
    $username = $parts[0];
    $domain = $parts[1];
    
    // 用户名部分：显示前2个字符，其余加密
    $masked_username = maskString($username, 2);
    
    // 域名部分：显示前2个字符，其余加密
    $masked_domain = maskString($domain, 2);
    
    return $masked_username . '@' . $masked_domain;
}

// 获取当前用户完整信息（包括密码用于显示）
$current_user_info = [];
try {
    // 先检查phone字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'");
    $phone_exists = $stmt->fetch();
    
    // 检查created_at字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_at'");
    $created_at_exists = $stmt->fetch();
    
    // 检查avatar字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'");
    $avatar_exists = $stmt->fetch();
    
    if ($phone_exists && $created_at_exists && $avatar_exists) {
        $stmt = $pdo->prepare("SELECT name, email, phone, password, role, created_at, avatar FROM users WHERE id = ?");
    } elseif ($phone_exists && $created_at_exists) {
        $stmt = $pdo->prepare("SELECT name, email, phone, password, role, created_at, '' as avatar FROM users WHERE id = ?");
    } elseif ($phone_exists && $avatar_exists) {
        $stmt = $pdo->prepare("SELECT name, email, phone, password, role, '' as created_at, avatar FROM users WHERE id = ?");
    } elseif ($created_at_exists && $avatar_exists) {
        $stmt = $pdo->prepare("SELECT name, email, '' as phone, password, role, created_at, avatar FROM users WHERE id = ?");
    } elseif ($phone_exists) {
        $stmt = $pdo->prepare("SELECT name, email, phone, password, role, '' as created_at, '' as avatar FROM users WHERE id = ?");
    } elseif ($created_at_exists) {
        $stmt = $pdo->prepare("SELECT name, email, '' as phone, password, role, created_at, '' as avatar FROM users WHERE id = ?");
    } elseif ($avatar_exists) {
        $stmt = $pdo->prepare("SELECT name, email, '' as phone, password, role, '' as created_at, avatar FROM users WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT name, email, '' as phone, password, role, '' as created_at, '' as avatar FROM users WHERE id = ?");
    }
    $stmt->execute([$user['id']]);
    $current_user_info = $stmt->fetch();
    
    // 如果没有created_at字段，尝试从session获取或使用默认值
    if (empty($current_user_info['created_at'])) {
        if (isset($user['created_at'])) {
            $current_user_info['created_at'] = $user['created_at'];
        } else {
            $current_user_info['created_at'] = date('Y-m-d H:i:s'); // 使用当前时间作为默认值
        }
    }
} catch (Exception $e) {
    $error = t('profile.get_user_info_failed') . ": " . $e->getMessage();
    // 如果查询失败，使用session中的基本信息
    $current_user_info = [
        'name' => $user['name'] ?? '',
        'email' => $user['email'] ?? '',
        'phone' => $user['phone'] ?? '',
        'password' => '',
        'role' => $user['role'] ?? '',
        'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
        'avatar' => $user['avatar'] ?? ''
    ];
}

// 获取老师统计数据
$user_stats = [];
try {
    // 检查数据库表结构
    $stmt = $pdo->query("SHOW TABLES LIKE 'course_teachers'");
    $course_teachers_exists = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM courses");
    $course_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 确定如何查询老师的课程
    if ($course_teachers_exists) {
        // 使用新的多老师结构（course_teachers表）
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(COUNT(DISTINCT c.id), 0) as total_courses,
                COALESCE(COUNT(DISTINCT a.id), 0) as total_reports,
                COALESCE(COUNT(DISTINCT lp.id), 0) as total_lesson_plans,
                COALESCE(COUNT(DISTINCT CASE WHEN DATE(a.date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN a.id END), 0) as month_reports
            FROM users u
            LEFT JOIN course_teachers ct ON ct.teacher_id = u.id
            LEFT JOIN courses c ON ct.course_id = c.id
            LEFT JOIN attendance a ON a.user_id = u.id
            LEFT JOIN lesson_plans lp ON lp.created_by = u.id
            WHERE u.id = ?
        ");
    } else {
        // 使用旧的单老师结构（courses表中的teacher_id字段）
        // 确定teacher字段名
        $teacher_field = 'teacher_id';
        if (in_array('user_id', $course_columns)) {
            $teacher_field = 'user_id';
        } elseif (in_array('teacher_id', $course_columns)) {
            $teacher_field = 'teacher_id';
        } elseif (in_array('created_by', $course_columns)) {
            $teacher_field = 'created_by';
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(COUNT(DISTINCT c.id), 0) as total_courses,
                COALESCE(COUNT(DISTINCT a.id), 0) as total_reports,
                COALESCE(COUNT(DISTINCT lp.id), 0) as total_lesson_plans,
                COALESCE(COUNT(DISTINCT CASE WHEN DATE(a.date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN a.id END), 0) as month_reports
            FROM users u
            LEFT JOIN courses c ON c.{$teacher_field} = u.id
            LEFT JOIN attendance a ON a.user_id = u.id
            LEFT JOIN lesson_plans lp ON lp.created_by = u.id
            WHERE u.id = ?
        ");
    }
    
    $stmt->execute([$user['id']]);
    $user_stats = $stmt->fetch();
    
    // 获取最近活动
    if ($course_teachers_exists) {
        // 使用新的多老师结构
        $stmt = $pdo->prepare("
            SELECT 'report' as type, a.date as activity_date, COALESCE(c.title, '未知课程') as course_title, ? as activity_desc
            FROM attendance a 
            LEFT JOIN courses c ON a.course_id = c.id 
            WHERE a.user_id = ?
            UNION ALL
            SELECT 'lesson_plan' as type, lp.created_at as activity_date, COALESCE(lp.title, '未知教案') as course_title, ? as activity_desc
            FROM lesson_plans lp 
            WHERE lp.created_by = ?
            ORDER BY activity_date DESC 
            LIMIT 10
        ");
    } else {
        // 使用旧的单老师结构
        $stmt = $pdo->prepare("
            SELECT 'report' as type, a.date as activity_date, COALESCE(c.title, '未知课程') as course_title, ? as activity_desc
            FROM attendance a 
            LEFT JOIN courses c ON a.course_id = c.id 
            WHERE a.user_id = ?
            UNION ALL
            SELECT 'lesson_plan' as type, lp.created_at as activity_date, COALESCE(lp.title, '未知教案') as course_title, ? as activity_desc
            FROM lesson_plans lp 
            WHERE lp.created_by = ?
            ORDER BY activity_date DESC 
            LIMIT 10
        ");
    }
    
    $stmt->execute([t('profile.submit_teaching_report'), $user['id'], t('profile.create_lesson_plan'), $user['id']]);
    $recent_activities = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = t('profile.get_statistics_failed') . ": " . $e->getMessage();
    // 如果查询失败，设置默认值
    $user_stats = [
        'total_courses' => 0,
        'total_reports' => 0,
        'total_lesson_plans' => 0,
        'month_reports' => 0
    ];
    $recent_activities = [];
}
?>
<!DOCTYPE html>
<html lang="<?= t('common.language_code') ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('teacher_profile.title') ?> - ERPH</title>
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --accent-color: #007bff;
            --border-color: #dee2e6;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        [data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --accent-color: #4dabf7;
            --border-color: #404040;
            --shadow-color: rgba(0, 0, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: var(--bg-secondary);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px var(--shadow-color);
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 16px;
        }

        .back-btn {
            display: inline-block;
            background: var(--accent-color);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px var(--accent-color);
        }

        .profile-section {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 15px var(--shadow-color);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-primary);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px var(--shadow-color);
            border: 2px solid var(--border-color);
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
        }

        .profile-avatar-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-avatar-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            font-weight: 700;
            margin: 0 auto;
            overflow: hidden;
            position: relative;
        }

        .avatar-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .avatar-edit-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .avatar-edit-btn:hover {
            background: var(--accent-color);
            transform: scale(1.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px var(--accent-color);
        }

        .btn-secondary {
            background: var(--text-secondary);
        }

        .btn-secondary:hover {
            background: var(--text-secondary);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: var(--success-color);
            color: white;
        }

        .alert-error {
            background: var(--danger-color);
            color: white;
        }

        .activity-item {
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            border-color: var(--accent-color);
            box-shadow: 0 6px 20px var(--shadow-color);
            transform: translateY(-2px);
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .activity-type {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .type-report {
            background: var(--success-color);
            color: white;
        }

        .type-lesson_plan {
            background: var(--accent-color);
            color: white;
        }

        .activity-date {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .activity-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .activity-desc {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .no-activities {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .no-activities-icon {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* 头像模态框样式 */
        .avatar-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .avatar-modal-content {
            background-color: var(--bg-primary);
            margin: 5% auto;
            padding: 20px;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px var(--shadow-color);
        }

        .avatar-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .avatar-modal-header h3 {
            color: var(--text-primary);
            font-size: 20px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .avatar-upload-section {
            margin-bottom: 20px;
        }

        .upload-tip {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 15px;
        }

        .avatar-crop-section {
            display: none;
            margin-top: 20px;
        }

        .crop-container {
            text-align: center;
            margin: 20px 0;
        }

        #cropCanvas {
            max-width: 100%;
            border: 2px solid var(--border-color);
            border-radius: 8px;
        }

        .crop-buttons {
            margin-top: 15px;
        }

        .crop-buttons button {
            margin: 0 10px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .page-header {
                padding: 20px;
            }
            
            .page-title {
                font-size: 24px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="teacher_dashboard.php" class="back-btn">← <?= t('common.back') ?><?= t('common.dashboard') ?></a>
        
        <div class="page-header">
            <h1 class="page-title"><?= t('teacher_profile.title') ?></h1>
            <p class="page-subtitle"><?= t('teacher_profile.subtitle') ?></p>
        </div>

        <!-- 统计卡片 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $user_stats['total_courses'] ?? 0 ?></div>
                <div class="stat-label"><?= t('teacher_profile.total_courses') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $user_stats['total_reports'] ?? 0 ?></div>
                <div class="stat-label"><?= t('teacher_profile.total_reports') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $user_stats['total_lesson_plans'] ?? 0 ?></div>
                <div class="stat-label"><?= t('teacher_profile.total_lesson_plans') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $user_stats['month_reports'] ?? 0 ?></div>
                <div class="stat-label"><?= t('teacher_profile.month_reports') ?></div>
            </div>
        </div>

        <!-- 个人信息编辑 -->
        <div class="profile-section">
            <h2 class="section-title"><?= t('teacher_profile.personal_info') ?></h2>
            
            <?php if ($msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- 头像部分 -->
            <div class="profile-avatar-section">
                <div class="profile-avatar-container">
                    <div class="profile-avatar">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="头像" class="avatar-image">
                        <?php else: ?>
                            <?= mb_substr($user['name'], 0, 1) ?>
                        <?php endif; ?>
                    </div>
                    <button class="avatar-edit-btn" onclick="openAvatarModal()" title="<?= t('teacher_profile.edit_avatar') ?>">✏️</button>
                </div>
            </div>

            <!-- 个人信息表单 -->
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= t('common.name') ?></label>
                        <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($current_user_info['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('common.email') ?></label>
                        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($current_user_info['email']) ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?= t('common.phone') ?></label>
                    <input type="text" name="phone" class="form-input" value="<?= htmlspecialchars($current_user_info['phone']) ?>">
                </div>
                
                <button type="submit" name="update_profile" class="btn"><?= t('teacher_profile.update_personal_info') ?></button>
            </form>
        </div>

        <!-- 密码修改 -->
        <div class="profile-section">
            <h2 class="section-title"><?= t('teacher_profile.change_password') ?></h2>
            
            <form method="POST" action="">
                <div class="form-group">
                                            <label class="form-label"><?= t('teacher_profile.current_password') ?></label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= t('teacher_profile.new_password') ?></label>
                        <input type="password" name="new_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('teacher_profile.confirm_new_password') ?></label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>
                </div>
                
                <button type="submit" name="change_password" class="btn"><?= t('teacher_profile.change_password') ?></button>
            </form>
        </div>

        <!-- 最近活动 -->
        <div class="profile-section">
            <h2 class="section-title"><?= t('teacher_profile.recent_activities') ?></h2>
            
            <?php if (empty($recent_activities)): ?>
                <div class="no-activities">
                    <div class="no-activities-icon">📊</div>
                                            <h3><?= t('teacher_profile.no_activities') ?></h3>
                        <p><?= t('teacher_profile.no_activities_desc') ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-header">
                            <div class="activity-type type-<?= $activity['type'] ?>">
                                <?= $activity['type'] === 'report' ? t('teacher_profile.teaching_report') : t('teacher_profile.lesson_plan') ?>
                            </div>
                            <div class="activity-date"><?= date('Y年m月d日', strtotime($activity['activity_date'])) ?></div>
                        </div>
                        
                        <div class="activity-title"><?= htmlspecialchars($activity['course_title']) ?></div>
                        <div class="activity-desc"><?= htmlspecialchars($activity['activity_desc']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 头像编辑模态框 -->
    <div id="avatarModal" class="avatar-modal">
        <div class="avatar-modal-content">
            <div class="avatar-modal-header">
                <h3><?= t('teacher_profile.edit_avatar') ?></h3>
                <button class="close-btn" onclick="closeAvatarModal()">&times;</button>
            </div>
            
            <div class="avatar-upload-section">
                <div class="upload-tip"><?= t('teacher_profile.avatar_upload_tip') ?></div>
                <input type="file" id="avatarFile" accept="image/*" onchange="handleFileSelect(event)">
            </div>
            
            <div class="avatar-crop-section">
                <div class="crop-container">
                    <canvas id="cropCanvas" width="400" height="400"></canvas>
                </div>
                <div class="crop-buttons">
                    <button class="btn btn-secondary" onclick="resetCrop()"><?= t('teacher_profile.reselect') ?></button>
                    <button class="btn" onclick="cropAvatar()"><?= t('teacher_profile.confirm_crop') ?></button>
                </div>
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

        // 头像编辑功能
        let selectedImage = null;
        let cropCanvas = null;
        let cropCtx = null;

        function openAvatarModal() {
            document.getElementById('avatarModal').style.display = 'block';
        }

        function closeAvatarModal() {
            document.getElementById('avatarModal').style.display = 'none';
            resetCrop();
        }

        function resetCrop() {
            selectedImage = null;
            document.querySelector('.avatar-crop-section').style.display = 'none';
            document.querySelector('.avatar-upload-section').style.display = 'block';
        }

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    selectedImage = new Image();
                    selectedImage.onload = function() {
                        showCropSection();
                        drawCropCanvas();
                    };
                    selectedImage.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        function showCropSection() {
            document.querySelector('.avatar-upload-section').style.display = 'none';
            document.querySelector('.avatar-crop-section').style.display = 'block';
        }

        function drawCropCanvas() {
            cropCanvas = document.getElementById('cropCanvas');
            cropCtx = cropCanvas.getContext('2d');
            
            // 清空画布
            cropCtx.clearRect(0, 0, cropCanvas.width, cropCanvas.height);
            
            // 创建圆形裁剪区域
            cropCtx.save();
            cropCtx.beginPath();
            cropCtx.arc(cropCanvas.width/2, cropCanvas.height/2, cropCanvas.width/2, 0, 2 * Math.PI);
            cropCtx.clip();
            
            // 计算图片尺寸和位置
            const size = Math.min(cropCanvas.width, cropCanvas.height);
            const scale = size / Math.min(selectedImage.width, selectedImage.height);
            const scaledWidth = selectedImage.width * scale;
            const scaledHeight = selectedImage.height * scale;
            const x = (cropCanvas.width - scaledWidth) / 2;
            const y = (cropCanvas.height - scaledHeight) / 2;
            
            // 绘制图片
            cropCtx.drawImage(selectedImage, x, y, scaledWidth, scaledHeight);
            cropCtx.restore();
        }

        function cropAvatar() {
            if (!cropCanvas) return;
            
            // 获取裁剪后的图片数据
            const imageData = cropCanvas.toDataURL('image/png');
            
            // 发送到服务器
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'update_avatar=1&avatar_data=' + encodeURIComponent(imageData)
            }).then(response => response.text())
            .then(data => {
                console.log('头像上传成功，准备刷新页面');
                // 关闭模态框
                closeAvatarModal();
                // 延迟刷新页面，确保服务器处理完成
                setTimeout(() => {
                    // 强制刷新页面，不使用缓存
                    window.location.reload(true);
                }, 1000);
            }).catch(error => {
                console.error('Error:', error);
                alert('头像上传失败，请重试');
            });
        }

        // 点击模态框外部关闭
        window.onclick = function(event) {
            const modal = document.getElementById('avatarModal');
            if (event.target === modal) {
                closeAvatarModal();
            }
        }

        // 页面加载时初始化主题
        document.addEventListener('DOMContentLoaded', function() {
            initTheme();
        });
    </script>
</body>
</html>
