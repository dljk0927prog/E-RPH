<?php
// profile.php - 个人资料页面
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

// 检查是否登录
if (!isset($_SESSION['user'])) {
    header('Location: login_roles.php');
    exit;
}

require_once __DIR__ . '/../db.php';
$user = $_SESSION['user'];
$msg = '';
$error = '';

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
        
        // 检查avatar字段是否存在
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'");
        $avatar_exists = $stmt->fetch();
        
        if ($phone_exists && $avatar_exists) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $user['id']]);
        } elseif ($phone_exists) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $user['id']]);
        } elseif ($avatar_exists) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $user['id']]);
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

// 处理头像上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar'])) {
    try {
        if (!isset($_POST['avatar_data']) || empty($_POST['avatar_data'])) {
            throw new Exception('头像数据不能为空');
        }
        
        // 检查avatar字段是否存在
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'");
        $avatar_exists = $stmt->fetch();
        
        if (!$avatar_exists) {
            // 如果avatar字段不存在，先创建
            $stmt = $pdo->query("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
        }
        
        // 处理base64图片数据
        $avatar_data = $_POST['avatar_data'];
        $avatar_data = str_replace('data:image/png;base64,', '', $avatar_data);
        $avatar_data = str_replace(' ', '+', $avatar_data);
        
        // 生成唯一文件名
        $avatar_filename = 'avatar_' . $user['id'] . '_' . time() . '.png';
        $avatar_path = 'uploads/avatars/' . $avatar_filename;
        
        // 确保上传目录存在
        if (!is_dir('uploads/avatars/')) {
            mkdir('uploads/avatars/', 0777, true);
        }
        
        // 保存图片
        if (file_put_contents($avatar_path, base64_decode($avatar_data))) {
            // 更新数据库
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$avatar_path, $user['id']]);
            
            // 更新session
            $_SESSION['user']['avatar'] = $avatar_path;
            $user = $_SESSION['user'];
            
            $msg = '头像更新成功！';
        } else {
            throw new Exception('头像保存失败');
        }
        
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
    
    // 如果没有avatar字段，尝试从session获取
    if (empty($current_user_info['avatar'])) {
        if (isset($user['avatar'])) {
            $current_user_info['avatar'] = $user['avatar'];
        } else {
            $current_user_info['avatar'] = '';
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
        'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s')
    ];
}

// 获取用户统计数据
$user_stats = [];
try {
    if ($user['role'] === 'teacher') {
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
        
    } else {
        // 管理员统计
        $stmt = $pdo->query("
            SELECT 
                COUNT(DISTINCT u.id) as total_users,
                COUNT(DISTINCT c.id) as total_courses,
                COUNT(DISTINCT a.id) as total_reports,
                COUNT(DISTINCT lp.id) as total_lesson_plans
            FROM users u, courses c, attendance a, lesson_plans lp
        ");
        $user_stats = $stmt->fetch();
        
        // 获取最近系统活动
        $stmt = $pdo->prepare("
            SELECT 'user' as type, u.created_at as activity_date, u.name as course_title, ? as activity_desc
            FROM users u 
            ORDER BY u.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([t('profile.new_user_registration')]);
        $recent_activities = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error = t('profile.get_statistics_failed') . ": " . $e->getMessage();
    // 如果查询失败，设置默认值
    if ($user['role'] === 'teacher') {
        $user_stats = [
            'total_courses' => 0,
            'total_reports' => 0,
            'total_lesson_plans' => 0,
            'month_reports' => 0
        ];
        $recent_activities = [];
    } else {
        $user_stats = [
            'total_users' => 0,
            'total_courses' => 0,
            'total_reports' => 0,
            'total_lesson_plans' => 0
        ];
        $recent_activities = [];
    }
}
?>
<!doctype html>
<html lang="<?= t('common.language_code') ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('profile.page_title') ?></title>
  <link rel="stylesheet" href="assets/css/mobile-optimization.css">
  <style>
    /* 深色模式CSS变量 - 统一风格 */
    :root {
      --bg-primary: #f5f5f5;
      --bg-secondary: #ffffff;
      --text-primary: #333333;
      --text-secondary: #666666;
      --text-muted: #888888;
      --border-color: #e1e5e9;
      --shadow-color: rgba(0, 0, 0, 0.1);
      --accent-color: #4a90e2;
      --accent-hover: #7bb3f0;
      --header-bg: linear-gradient(90deg, #4a90e2, #7bb3f0);
      --card-border: #4a90e2;
      --success-bg: #d4edda;
      --success-text: #155724;
      --error-bg: #f8d7da;
      --error-text: #721c24;
      --warning-bg: #fff3cd;
      --warning-text: #856404;
      --danger-bg: #dc3545;
      --danger-hover: #c82333;
    }
    
    /* 深色模式样式 - 统一风格 */
    [data-theme="dark"] {
      --bg-primary: #1a1a1a;
      --bg-secondary: #1e2328;
      --text-primary: #ffffff;
      --text-secondary: #cccccc;
      --text-muted: #999999;
      --border-color: #2d3748;
      --shadow-color: rgba(0, 0, 0, 0.4);
      --accent-color: #60a5fa;
      --accent-hover: #93c5fd;
      --header-bg: linear-gradient(90deg, #1e3a8a, #3b82f6);
      --card-border: #60a5fa;
      --success-bg: #065f46;
      --success-text: #d1fae5;
      --error-bg: #7f1d1d;
      --error-text: #fecaca;
      --warning-bg: #92400e;
      --warning-text: #fef3c7;
      --danger-bg: #dc2626;
      --danger-hover: #b91c1c;
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
      transition: background-color 0.3s ease, color 0.3s ease;
      line-height: 1.6;
    }
    
    .header { 
      background: var(--header-bg); 
      color: white; 
      padding: 15px 20px; 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      box-shadow: 0 2px 10px var(--shadow-color);
      transition: background 0.3s ease;
    }
    
    /* 确保header中的按钮容器始终水平排列 */
    .header > div {
      display: flex !important;
      align-items: center !important;
      gap: 12px !important;
      flex-direction: row !important;
      flex-wrap: nowrap !important;
    }
    
    .header h1 { 
      font-size: 20px; 
      font-weight: 600; 
    }
    
    .nav-links { 
      display: flex; 
      gap: 15px; 
      align-items: center;
    }
    
    .nav-links a { 
      color: white; 
      text-decoration: none; 
      padding: 8px 16px; 
      border-radius: 6px; 
      background: rgba(255,255,255,0.2); 
      transition: all 0.2s ease;
      font-size: 14px;
    }
    
    .nav-links a:hover { 
      background: rgba(255,255,255,0.3); 
      transform: translateY(-1px);
    }
    
    /* 导航按钮样式 - 参考admin_dashboard.php */
    .nav-btn {
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
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      white-space: nowrap !important;
    }
    
    .nav-btn:hover {
      background: rgba(255,255,255,0.25) !important;
      border-color: rgba(255,255,255,0.3) !important;
      transform: translateY(-1px) !important;
    }
    
    .dashboard-btn {
      background: rgba(255,255,255,0.15) !important;
    }
    
    .dashboard-btn:hover {
      background: rgba(255,255,255,0.25) !important;
    }
    
    .logout-btn {
      background: rgba(255,255,255,0.15) !important;
      border-color: rgba(255,255,255,0.2) !important;
    }
    
    .logout-btn:hover {
      background: rgba(255,255,255,0.25) !important;
      border-color: rgba(255,255,255,0.3) !important;
    }
    
    .nav-btn .btn-icon {
      font-size: 14px;
      opacity: 0.9;
    }
    
    .nav-btn .btn-text {
      font-weight: 500;
      letter-spacing: 0.3px;
    }
    
    .theme-toggle-btn {
      background: rgba(255,255,255,0.15);
      color: white;
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 6px;
      padding: 8px 12px;
      cursor: pointer;
      font-size: 16px;
      transition: all 0.2s ease;
      backdrop-filter: blur(10px);
      display: flex;
      align-items: center;
      justify-content: center;
      min-width: 40px;
      min-height: 40px;
    }
    
    .theme-toggle-btn:hover {
      background: rgba(255,255,255,0.25);
      border-color: rgba(255,255,255,0.3);
      transform: translateY(-1px);
      box-shadow: 0 2px 8px rgba(255,255,255,0.1);
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
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      border-left: 4px solid var(--accent-color);
    }
    
    .page-header h2 { 
      color: var(--accent-color); 
      font-size: 24px;
      font-weight: 600;
    }
    
    .user-badge { 
      background: var(--accent-color); 
      color: white; 
      padding: 8px 16px; 
      border-radius: 20px; 
      font-size: 12px; 
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .content-grid { 
      display: grid; 
      grid-template-columns: 1fr 1fr; 
      gap: 20px; 
      margin-bottom: 20px; 
    }
    
    .profile-card { 
      background: var(--bg-secondary); 
      padding: 25px; 
      border-radius: 12px; 
      box-shadow: 0 4px 15px var(--shadow-color); 
      transition: all 0.3s ease; 
      border-left: 4px solid var(--accent-color);
      position: relative;
      overflow: hidden;
    }
    
    .profile-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--accent-color), var(--accent-hover));
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .profile-card:hover::before {
      opacity: 1;
    }
    
    .profile-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px var(--shadow-color);
    }
    
    .profile-card h3 { 
      color: var(--accent-color); 
      margin-bottom: 20px; 
      font-size: 18px; 
      display: flex; 
      align-items: center; 
      gap: 10px; 
      font-weight: 600;
    }
    
    .form-group { 
      margin-bottom: 20px; 
    }
    
    .form-group label { 
      display: block; 
      margin-bottom: 8px; 
      color: var(--text-primary); 
      font-weight: 500; 
      font-size: 14px;
    }
    
    .form-group input, 
    .form-group select, 
    .form-group textarea { 
      width: 100%; 
      padding: 12px; 
      border: 2px solid var(--border-color); 
      border-radius: 8px; 
      font-size: 14px; 
      background: var(--bg-secondary);
      color: var(--text-primary);
      transition: all 0.3s ease;
    }
    
    .form-group input:focus, 
    .form-group select:focus, 
    .form-group textarea:focus { 
      border-color: var(--accent-color); 
      outline: none; 
      box-shadow: 0 0 0 3px rgba(74,144,226,0.1); 
      transform: translateY(-1px);
    }
    
    .btn { 
      background: var(--accent-color); 
      color: white; 
      padding: 12px 24px; 
      border: none; 
      border-radius: 8px; 
      cursor: pointer; 
      font-size: 14px; 
      font-weight: 500;
      transition: all 0.3s ease; 
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .btn:hover { 
      background: var(--accent-hover); 
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(74,144,226,0.3);
    }
    
    .btn.danger { 
      background: var(--danger-bg); 
    }
    
    .btn.danger:hover { 
      background: var(--danger-hover); 
      box-shadow: 0 4px 15px rgba(220,53,69,0.3);
    }
    
    .stats-grid { 
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
      gap: 20px; 
      margin-bottom: 30px; 
    }
    
    .stat-card { 
      background: var(--bg-secondary); 
      padding: 24px; 
      border-radius: 12px; 
      text-align: center; 
      box-shadow: 0 4px 15px var(--shadow-color); 
      transition: all 0.3s ease; 
      border-left: 4px solid var(--accent-color);
      position: relative;
      overflow: hidden;
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--accent-color), var(--accent-hover));
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .stat-card:hover::before {
      opacity: 1;
    }
    
    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px var(--shadow-color);
    }
    
    .stat-icon {
      font-size: 32px;
      margin-bottom: 12px;
      display: block;
      opacity: 0.8;
    }
    
    .stat-number { 
      font-size: 32px; 
      font-weight: bold; 
      color: var(--accent-color); 
      margin-bottom: 12px; 
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .stat-label { 
      color: var(--text-secondary); 
      font-size: 14px; 
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* 卡片头部样式 */
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--border-color);
    }
    
    .card-header h3 {
      margin: 0;
      color: var(--accent-color);
      font-size: 20px;
      font-weight: 600;
    }
    
    .card-badge {
      background: var(--accent-color);
      color: white;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .card-badge.security {
      background: var(--success-bg);
      color: var(--success-text);
    }
    
    /* 个人资料头像区域 */
    .profile-avatar-section {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 25px;
      padding: 20px;
      background: rgba(74,144,226,0.05);
      border-radius: 12px;
      border: 1px solid var(--border-color);
    }
    
    .profile-avatar-container {
      position: relative;
      display: inline-block;
    }
    
    .profile-avatar { 
      width: 80px; 
      height: 80px; 
      border-radius: 50%; 
      background: linear-gradient(135deg, var(--accent-color), var(--accent-hover)); 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      color: white; 
      font-size: 24px; 
      font-weight: bold; 
      box-shadow: 0 4px 15px rgba(74,144,226,0.3);
      transition: all 0.3s ease;
      flex-shrink: 0;
      overflow: hidden;
    }
    
    .profile-avatar:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 20px rgba(74,144,226,0.4);
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
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: var(--accent-color);
      color: white;
      border: 2px solid white;
      cursor: pointer;
      font-size: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    
    .avatar-edit-btn:hover {
      background: var(--accent-hover);
      transform: scale(1.1);
    }
    
    .profile-info {
      flex: 1;
    }
    
    .profile-name {
      font-size: 24px;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 5px;
    }
    
    .profile-role {
      color: var(--text-secondary);
      font-size: 14px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* 表单样式增强 */
    .profile-form,
    .password-form {
      margin-top: 20px;
    }
    
    .form-label {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px; 
      color: var(--text-primary); 
      font-weight: 500; 
      font-size: 14px;
    }
    
    .label-icon {
      font-size: 16px;
      opacity: 0.8;
    }
    
    .form-input {
      width: 100%; 
      padding: 12px; 
      border: 2px solid var(--border-color); 
      border-radius: 8px; 
      font-size: 14px; 
      background: var(--bg-secondary);
      color: var(--text-primary);
      transition: all 0.3s ease;
    }
    
    .form-input:focus { 
      border-color: var(--accent-color); 
      outline: none; 
      box-shadow: 0 0 0 3px rgba(74,144,226,0.1); 
      transform: translateY(-1px);
    }
    
    .password-input {
      font-family: 'Courier New', monospace;
      letter-spacing: 2px;
    }
    
    /* 信息区域样式 */
    .info-section {
      margin: 25px 0;
      padding: 20px;
      background: rgba(74,144,226,0.03);
      border-radius: 12px;
      border: 1px solid var(--border-color);
    }
    
    .section-title {
      color: var(--accent-color);
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .info-row { 
      display: flex; 
      justify-content: space-between; 
      align-items: center;
      padding: 12px 0; 
      border-bottom: 1px solid var(--border-color); 
      transition: all 0.3s ease;
    }
    
    .info-row:hover {
      background: rgba(74,144,226,0.05);
      padding-left: 10px;
      padding-right: 10px;
      margin: 0 -10px;
      border-radius: 6px;
    }
    
    .info-row:last-child { 
      border-bottom: none; 
    }
    
    .info-label { 
      color: var(--text-secondary); 
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .info-value { 
      color: var(--text-primary); 
      font-weight: 600; 
      font-family: 'Courier New', monospace;
    }
    
    .role-badge {
      background: var(--accent-color);
      color: white;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* 按钮样式增强 */
    .btn-primary {
      background: var(--accent-color);
      width: 100%;
      margin-top: 20px;
    }
    
    .btn-danger {
      background: var(--danger-bg);
      width: 100%;
    }
    
    .btn-icon {
      margin-right: 8px;
      font-size: 16px;
    }
    
    /* 安全警告样式 */
    .security-warning {
      background: var(--warning-bg);
      color: var(--warning-text);
      padding: 20px;
      border-radius: 12px;
      margin-top: 25px;
      border-left: 4px solid #ffc107;
      line-height: 1.6;
    }
    
    .warning-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 10px;
    }
    
    .warning-icon {
      font-size: 18px;
    }
    
    .security-warning strong {
      font-size: 14px;
      font-weight: 600;
    }
    
    .security-warning p {
      margin: 0;
      font-size: 13px;
      line-height: 1.5;
    }
    
    /* 密码提示样式 */
    .password-tips {
      margin-top: 25px;
      padding: 20px;
      background: rgba(74,144,226,0.05);
      border-radius: 12px;
      border: 1px solid var(--border-color);
    }
    
    .tips-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 10px;
    }
    
    .tips-icon {
      font-size: 18px;
      color: var(--accent-color);
    }
    
    .password-tips strong {
      color: var(--text-primary);
      font-size: 14px;
      font-weight: 600;
    }
    
    .password-tips p {
      margin: 0;
      color: var(--text-secondary);
      font-size: 13px;
      line-height: 1.5;
    }
    
    /* 活动区域样式 */
    .activity-section {
      margin-top: 30px;
    }
    
    .section-header {
      background: var(--bg-secondary);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      box-shadow: 0 4px 15px var(--shadow-color);
      border-left: 4px solid var(--accent-color);
      transition: all 0.3s ease;
    }
    
    .section-header:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px var(--shadow-color);
    }
    
    .section-title {
      color: var(--accent-color);
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 8px;
    }
    
    .section-description {
      color: var(--text-secondary);
      font-size: 14px;
      margin: 0;
    }
    
    .activity-list { 
      background: var(--bg-secondary); 
      padding: 25px; 
      border-radius: 12px; 
      box-shadow: 0 4px 15px var(--shadow-color); 
      transition: all 0.3s ease; 
      border-left: 4px solid var(--accent-color);
    }
    
    .activity-list:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px var(--shadow-color);
    }
    
    .activity-item { 
      padding: 18px 0; 
      border-bottom: 1px solid var(--border-color); 
      display: flex; 
      align-items: center;
      gap: 15px;
      transition: all 0.3s ease;
    }
    
    .activity-item:hover {
      background: rgba(74,144,226,0.05);
      padding-left: 15px;
      padding-right: 15px;
      margin: 0 -15px;
      border-radius: 8px;
    }
    
    .activity-item:last-child { 
      border-bottom: none; 
    }
    
    .activity-icon {
      font-size: 24px;
      opacity: 0.8;
      flex-shrink: 0;
    }
    
    .activity-info {
      flex: 1;
    }
    
    .activity-title { 
      color: var(--text-primary); 
      margin-bottom: 6px; 
      font-size: 14px; 
      font-weight: 600;
    }
    
    .activity-detail { 
      color: var(--text-secondary); 
      font-size: 12px; 
      margin: 0;
    }
    
    .activity-date { 
      color: var(--text-muted); 
      font-size: 12px; 
      background: rgba(74,144,226,0.1);
      padding: 6px 10px;
      border-radius: 12px;
      font-weight: 500;
      flex-shrink: 0;
    }
    
    /* 无活动状态 */
    .no-activity {
      text-align: center;
      padding: 40px 20px;
      color: var(--text-secondary);
    }
    
    .no-activity-icon {
      font-size: 48px;
      margin-bottom: 15px;
      opacity: 0.5;
    }
    
    .no-activity p {
      font-size: 16px;
      font-weight: 500;
      margin-bottom: 8px;
      color: var(--text-primary);
    }
    
    .no-activity small {
      font-size: 12px;
      opacity: 0.7;
    }
    
    /* 消息样式 */
    .message { 
      padding: 16px; 
      border-radius: 8px; 
      margin-bottom: 20px; 
      font-weight: 500; 
      border-left: 4px solid;
      animation: slideInDown 0.5s ease;
    }
    
    @keyframes slideInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .message.success { 
      background: var(--success-bg); 
      color: var(--success-text); 
      border-left-color: #28a745;
    }
    
    .message.error { 
      background: var(--error-bg); 
      color: var(--error-text); 
      border-left-color: #dc3545;
    }
    
    .message.warning {
      background: var(--warning-bg);
      color: var(--warning-text);
      border-left-color: #ffc107;
    }
    
    /* 遮罩信息和切换按钮样式 */
    .masked-info {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .toggle-visibility {
      background: none;
      border: 1px solid var(--accent-color);
      color: var(--accent-color);
      cursor: pointer;
      font-size: 12px;
      padding: 4px 8px;
      border-radius: 6px;
      transition: all 0.3s ease;
      font-weight: 500;
    }
    
    .toggle-visibility:hover {
      background: var(--accent-color);
      color: white;
      transform: translateY(-1px);
    }
    
    /* 深色模式特殊样式 */
    [data-theme="dark"] .profile-card,
    [data-theme="dark"] .stat-card,
    [data-theme="dark"] .activity-list,
    [data-theme="dark"] .page-header {
      background: var(--bg-secondary) !important;
      border: 1px solid var(--border-color) !important;
      color: var(--text-primary) !important;
    }
    
    [data-theme="dark"] .form-group input,
    [data-theme="dark"] .form-group select,
    [data-theme="dark"] .form-group textarea {
      background: var(--bg-secondary) !important;
      color: var(--text-primary) !important;
      border-color: var(--border-color) !important;
    }
    
    [data-theme="dark"] .info-value {
      color: var(--text-primary) !important;
    }
    
    [data-theme="dark"] .activity-info h4 {
      color: var(--text-primary) !important;
    }
    
    [data-theme="dark"] .activity-info p {
      color: var(--text-secondary) !important;
    }
    
    [data-theme="dark"] .profile-name {
      color: var(--text-primary) !important;
    }
    
    [data-theme="dark"] .profile-role {
      color: var(--text-secondary) !important;
    }
    
    [data-theme="dark"] .section-title {
      color: var(--accent-color) !important;
    }
    
    [data-theme="dark"] .section-description {
      color: var(--text-secondary) !important;
    }
    
    [data-theme="dark"] .password-tips strong {
      color: var(--text-primary) !important;
    }
    
    [data-theme="dark"] .password-tips p {
      color: var(--text-secondary) !important;
    }
    
    /* 深色模式下header按钮样式保护 */
    [data-theme="dark"] .header {
      background: linear-gradient(90deg, #4a90e2, #7bb3f0) !important;
      color: white !important;
    }
    
    [data-theme="dark"] .header h1 {
      color: white !important;
    }
    
    /* 深色模式下确保按钮容器水平排列 */
    [data-theme="dark"] .header > div {
      display: flex !important;
      align-items: center !important;
      gap: 12px !important;
      flex-direction: row !important;
      flex-wrap: nowrap !important;
    }
    
    [data-theme="dark"] .nav-btn {
      color: white !important;
      background: rgba(255,255,255,0.15) !important;
      border-color: rgba(255,255,255,0.2) !important;
    }
    
    [data-theme="dark"] .nav-btn:hover {
      background: rgba(255,255,255,0.25) !important;
      border-color: rgba(255,255,255,0.3) !important;
    }
    
    [data-theme="dark"] .dashboard-btn {
      background: rgba(255,255,255,0.2) !important;
    }
    
    [data-theme="dark"] .dashboard-btn:hover {
      background: rgba(255,255,255,0.3) !important;
    }
    
    [data-theme="dark"] .logout-btn {
      background: rgba(255,255,255,0.15) !important;
      border-color: rgba(255,255,255,0.2) !important;
    }
    
    [data-theme="dark"] .logout-btn:hover {
      background: rgba(255,255,255,0.25) !important;
      border-color: rgba(255,255,255,0.3) !important;
    }
    
    [data-theme="dark"] .theme-toggle-btn {
      background: rgba(255,255,255,0.15) !important;
      border-color: rgba(255,255,255,0.2) !important;
      color: white !important;
    }
    
    [data-theme="dark"] .theme-toggle-btn:hover {
      background: rgba(255,255,255,0.25) !important;
      border-color: rgba(255,255,255,0.3) !important;
    }
    
    /* 头像编辑模态框样式 */
    .avatar-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 2000;
      display: none;
      align-items: center;
      justify-content: center;
    }
    
    .avatar-modal.show {
      display: flex;
    }
    
    .avatar-modal-content {
      background: var(--bg-secondary);
      border-radius: 12px;
      width: 90%;
      max-width: 500px;
      max-height: 80vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .avatar-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 24px;
      border-bottom: 1px solid var(--border-color);
    }
    
    .avatar-modal-header h3 {
      margin: 0;
      color: var(--text-primary);
      font-size: 18px;
      font-weight: 600;
    }
    
    .avatar-modal-body {
      padding: 24px;
    }
    
    .avatar-upload-section {
      text-align: center;
      margin-bottom: 20px;
    }
    
    .upload-tip {
      color: var(--text-secondary);
      font-size: 12px;
      margin-top: 10px;
      margin-bottom: 0;
    }
    
    .avatar-crop-section {
      text-align: center;
    }
    
    .crop-container {
      margin: 20px 0;
      display: flex;
      justify-content: center;
    }
    
    #cropCanvas {
      border: 2px solid var(--border-color);
      border-radius: 8px;
      max-width: 100%;
      height: auto;
    }
    
    .crop-controls {
      display: flex;
      gap: 10px;
      justify-content: center;
      flex-wrap: wrap;
    }
    
    .crop-controls .btn {
      min-width: 120px;
    }
    
    /* 响应式设计 */
    @media (max-width: 768px) {
      .content-grid { 
        grid-template-columns: 1fr; 
      }
      
      .stats-grid { 
        grid-template-columns: repeat(2, 1fr); 
      }
      
      .header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      
      .header > div {
        flex-wrap: nowrap !important;
        justify-content: center;
        gap: 10px;
        display: flex !important;
        flex-direction: row !important;
      }
      
      .nav-btn {
        padding: 6px 10px !important;
        font-size: 13px !important;
        flex-shrink: 0 !important;
      }
      
      .theme-toggle-btn {
        padding: 6px 8px;
        font-size: 14px;
        min-width: 36px;
        min-height: 36px;
        flex-shrink: 0 !important;
      }
      
      .profile-avatar-section {
        flex-direction: column;
        text-align: center;
        gap: 15px;
      }
      
      .card-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
      }
    }
    
    @media (max-width: 480px) {
      .stats-grid { 
        grid-template-columns: 1fr; 
      }
      
      .container {
        padding: 0 15px;
      }
      
      .profile-card,
      .stat-card,
      .activity-list,
      .section-header {
        padding: 20px;
      }
      
      .profile-avatar-section {
        padding: 15px;
      }
      
      .info-section {
        padding: 15px;
      }
      
      .security-warning,
      .password-tips {
        padding: 15px;
      }
      
      .header > div {
        gap: 8px;
        flex-wrap: nowrap !important;
        flex-direction: row !important;
      }
      
      .nav-btn {
        padding: 5px 8px !important;
        font-size: 12px !important;
        flex-shrink: 0 !important;
      }
      
      .theme-toggle-btn {
        padding: 5px 6px;
        font-size: 12px;
        min-width: 32px;
        min-height: 32px;
        flex-shrink: 0 !important;
      }
    }
  </style>
</head>
<body>
  <header class="header">
    <h1>ERPH 系统 - <?= t('profile.title') ?></h1>
    <div>
      <?php if ($user['role'] === 'teacher'): ?>
        <a href="teacher_dashboard.php" class="nav-btn dashboard-btn">
          <?= t('common.back') ?><?= t('common.dashboard') ?>
        </a>
      <?php else: ?>
        <a href="admin_dashboard.php" class="nav-btn dashboard-btn">
          <?= t('common.back') ?><?= t('common.dashboard') ?>
        </a>
      <?php endif; ?>
      <a href="logout.php" class="nav-btn logout-btn">
        <?= t('common.logout') ?>
      </a>
      <button class="theme-toggle-btn" onclick="toggleTheme()" title="切换主题">
        🌙
      </button>
    </div>
  </header>

  <div class="container">
    <div class="page-header">
      <h2><?= t('profile.title') ?></h2>
      <span class="user-badge"><?= $user['role'] === 'teacher' ? t('common.teacher') : t('common.admin') ?></span>
    </div>

    <?php if(!empty($msg)): ?>
      <div class="message success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    
    <?php if(!empty($error)): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- 调试信息 - 开发时使用，生产环境请删除 -->
    <?php if (isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
      <div class="message warning" style="background: var(--warning-bg); color: var(--warning-text);">
        <strong>调试信息：</strong><br>
        Session用户信息: <?= htmlspecialchars(json_encode($user, JSON_UNESCAPED_UNICODE)) ?><br>
        数据库用户信息: <?= htmlspecialchars(json_encode($current_user_info, JSON_UNESCAPED_UNICODE)) ?><br>
        Created_at字段值: <?= htmlspecialchars($current_user_info['created_at'] ?? 'NULL') ?>
      </div>
    <?php endif; ?>

    <!-- 统计信息 -->
    <?php if ($user['role'] === 'teacher'): ?>
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📚</div>
        <div class="stat-number"><?= $user_stats['total_courses'] ?? 0 ?></div>
        <div class="stat-label"><?= t('profile.responsible_courses') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📝</div>
        <div class="stat-number"><?= $user_stats['total_reports'] ?? 0 ?></div>
        <div class="stat-label"><?= t('profile.teaching_reports') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-number"><?= $user_stats['total_lesson_plans'] ?? 0 ?></div>
        <div class="stat-label"><?= t('profile.created_lesson_plans') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-number"><?= $user_stats['month_reports'] ?? 0 ?></div>
        <div class="stat-label"><?= t('profile.monthly_reports') ?></div>
      </div>
    </div>
    <?php else: ?>
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-number"><?= $user_stats['total_users'] ?? 0 ?></div>
        <div class="stat-label"><?= t('profile.system_users') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🎓</div>
        <div class="stat-number"><?= $user_stats['total_courses'] ?? 0 ?></div>
        <div class="stat-label"><?= t('profile.system_courses') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-number"><?= $user_stats['total_reports'] ?? 0 ?></div>
        <div class="stat-label"><?= t('profile.total_reports') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📖</div>
        <div class="stat-number"><?= $user_stats['total_lesson_plans'] ?? 0 ?></div>
        <div class="stat-label"><?= t('profile.total_lesson_plans') ?></div>
      </div>
    </div>
    <?php endif; ?>

    <div class="content-grid">
      <!-- 个人信息 -->
      <div class="profile-card">
        <div class="card-header">
          <h3>👤 <?= t('profile.personal_info') ?></h3>
          <div class="card-badge"><?= $user['role'] === 'teacher' ? '老师' : '管理员' ?></div>
        </div>
        
        <div class="profile-avatar-section">
          <div class="profile-avatar-container">
            <div class="profile-avatar" id="profileAvatar">
              <?php if (!empty($user['avatar'])): ?>
                <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="头像" class="avatar-image">
              <?php else: ?>
                <?= mb_substr($user['name'], 0, 1) ?>
              <?php endif; ?>
            </div>
            <button type="button" class="avatar-edit-btn" onclick="openAvatarModal()" title="编辑头像">
              ✏️
            </button>
          </div>
          <div class="profile-info">
            <h4 class="profile-name"><?= htmlspecialchars($user['name']) ?></h4>
            <p class="profile-role"><?= $user['role'] === 'teacher' ? t('common.teacher') : t('common.admin') ?></p>
          </div>
        </div>
        
        <form method="post" action="" class="profile-form">
          <div class="form-group">
            <label class="form-label">
              <span class="label-icon">📝</span>
              <?= t('common.name') ?>
            </label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="form-input">
          </div>
          
          <div class="form-group">
            <label class="form-label">
              <span class="label-icon">📧</span>
              <?= t('common.email') ?>
            </label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="form-input">
          </div>
          
          <div class="form-group">
            <label class="form-label">
              <span class="label-icon">📱</span>
              <?= t('common.phone') ?>
            </label>
            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="form-input">
          </div>
          
          <div class="info-section">
            <h4 class="section-title">🔍 <?= t('profile.current_info') ?></h4>
            
            <div class="info-row">
              <span class="info-label">
                <span class="label-icon">📧</span>
                <?= t('profile.current_email') ?>
              </span>
              <div class="masked-info">
                <span class="info-value" style="font-family: monospace;" id="email-display">
                  <?= !empty($current_user_info['email']) ? maskEmail($current_user_info['email']) : t('profile.not_set') ?>
                </span>
                <?php if (!empty($current_user_info['email'])): ?>
                  <button type="button" class="toggle-visibility" onclick="toggleEmailVisibility()">
                    <span id="email-toggle-text">👁️ <?= t('profile.show') ?></span>
                  </button>
                <?php endif; ?>
              </div>
            </div>
            
            <div class="info-row">
              <span class="info-label">
                <span class="label-icon">🔒</span>
                <?= t('profile.current_password_label') ?>
              </span>
              <div class="masked-info">
                <span class="info-value" style="font-family: monospace; color: var(--danger-bg);" id="password-display">
                  <?= maskString('passwordlength', 2) ?>
                </span>
                <button type="button" class="toggle-visibility" onclick="togglePasswordVisibility()">
                  <span id="password-toggle-text">👁️ <?= t('profile.show') ?></span>
                </button>
              </div>
            </div>
            
            <div class="info-row">
              <span class="info-label">
                <span class="label-icon">👤</span>
                <?= t('profile.role_label') ?>
              </span>
              <span class="info-value role-badge"><?= $user['role'] === 'teacher' ? t('common.teacher') : t('common.admin') ?></span>
            </div>
            
            <div class="info-row">
              <span class="info-label">
                <span class="label-icon">📅</span>
                <?= t('profile.registration_time') ?>
              </span>
              <span class="info-value">
                <?php 
                if (!empty($current_user_info['created_at']) && $current_user_info['created_at'] !== '1970-01-01 00:00:00'): 
                  try {
                    $date = new DateTime($current_user_info['created_at']);
                    echo $date->format('Y-m-d');
                  } catch (Exception $e) {
                    echo t('profile.not_available');
                  }
                else: 
                  echo t('profile.not_available');
                endif; 
                ?>
              </span>
            </div>
          </div>
          
          <div class="security-warning">
            <div class="warning-header">
              <span class="warning-icon">🔒</span>
              <strong><?= t('profile.security_tip') ?></strong>
            </div>
            <p><?= t('profile.security_tip_content') ?></p>
          </div>
          
          <button type="submit" name="update_profile" class="btn btn-primary">
            <span class="btn-icon">💾</span>
            <?= t('profile.update_personal_info') ?>
          </button>
        </form>
      </div>

      <!-- 密码更改 -->
      <div class="profile-card">
        <div class="card-header">
          <h3>🔒 <?= t('profile.password_settings') ?></h3>
          <div class="card-badge security">安全</div>
        </div>
        
        <form method="post" action="" class="password-form">
          <div class="form-group">
            <label class="form-label">
              <span class="label-icon">🔑</span>
              <?= t('profile.current_password') ?>
            </label>
            <input type="password" name="current_password" required class="form-input password-input">
          </div>
          
          <div class="form-group">
            <label class="form-label">
              <span class="label-icon">🆕</span>
              <?= t('profile.new_password') ?>
            </label>
            <input type="password" name="new_password" minlength="6" required class="form-input password-input">
          </div>
          
          <div class="form-group">
            <label class="form-label">
              <span class="label-icon">✅</span>
              <?= t('profile.confirm_password') ?>
            </label>
            <input type="password" name="confirm_password" minlength="6" required class="form-input password-input">
          </div>
          
          <button type="submit" name="change_password" class="btn btn-danger">
            <span class="btn-icon">🔐</span>
            <?= t('profile.change_password_button') ?>
          </button>
        </form>
        
        <div class="password-tips">
          <div class="tips-header">
            <span class="tips-icon">💡</span>
            <strong><?= t('profile.password_security_tip') ?></strong>
          </div>
          <p><?= t('profile.password_security_content') ?></p>
        </div>
      </div>
    </div>

    <!-- 最近活动 -->
    <div class="activity-section">
      <div class="section-header">
        <h3 class="section-title">📊 <?= t('profile.recent_activities') ?></h3>
        <p class="section-description"><?= t('profile.activity_description') ?></p>
      </div>
      
      <div class="activity-list">
        <?php if (!empty($recent_activities)): ?>
          <?php foreach ($recent_activities as $activity): ?>
            <div class="activity-item">
              <div class="activity-icon">
                <?php if ($activity['type'] === 'report'): ?>
                  📝
                <?php elseif ($activity['type'] === 'lesson_plan'): ?>
                  📋
                <?php else: ?>
                  👤
                <?php endif; ?>
              </div>
              <div class="activity-info">
                <h4 class="activity-title"><?= htmlspecialchars($activity['activity_desc']) ?></h4>
                <p class="activity-detail"><?= htmlspecialchars($activity['course_title']) ?></p>
              </div>
              <div class="activity-date">
                <?= date('m-d H:i', strtotime($activity['activity_date'])) ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-activity">
            <div class="no-activity-icon">😴</div>
            <p><?= t('profile.no_recent_activities') ?></p>
            <small><?= t('profile.no_activity_description') ?></small>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <!-- 头像编辑模态框 -->
  <div id="avatarModal" class="avatar-modal">
    <div class="avatar-modal-content">
      <div class="avatar-modal-header">
        <h3>编辑头像</h3>
        <button class="close-btn" onclick="closeAvatarModal()">&times;</button>
      </div>
      <div class="avatar-modal-body">
        <div class="avatar-upload-section">
          <input type="file" id="avatarFile" accept="image/*" style="display: none;">
          <button type="button" class="btn btn-primary" onclick="document.getElementById('avatarFile').click()">
            📁 选择图片
          </button>
          <p class="upload-tip">支持 JPG、PNG 格式，建议尺寸 200x200 像素以上</p>
        </div>
        
        <div class="avatar-crop-section" id="avatarCropSection" style="display: none;">
          <div class="crop-container">
            <canvas id="cropCanvas" width="400" height="400"></canvas>
          </div>
          <div class="crop-controls">
            <button type="button" class="btn btn-primary" onclick="cropAvatar()">✂️ 裁剪并保存</button>
            <button type="button" class="btn" onclick="resetCrop()">🔄 重新选择</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // 存储原始数据
    const originalEmail = '<?= htmlspecialchars($current_user_info['email'] ?? '') ?>';
    const maskedEmail = '<?= !empty($current_user_info['email']) ? maskEmail($current_user_info['email']) : t('profile.not_set') ?>';
    
    // 显示/隐藏邮箱
    let emailVisible = false;
    function toggleEmailVisibility() {
      const emailDisplay = document.getElementById('email-display');
      const toggleText = document.getElementById('email-toggle-text');
      
      if (emailVisible) {
        emailDisplay.textContent = maskedEmail;
        toggleText.innerHTML = '👁️ <?= t('profile.show') ?>';
        emailVisible = false;
      } else {
        if (confirm('<?= t('profile.confirm_show_email') ?>')) {
          emailDisplay.textContent = originalEmail;
          toggleText.innerHTML = '🙈 <?= t('profile.hide') ?>';
          emailVisible = true;
          
          // 5秒后自动隐藏
          setTimeout(() => {
            if (emailVisible) {
              emailDisplay.textContent = maskedEmail;
              toggleText.innerHTML = '👁️ <?= t('profile.show') ?>';
              emailVisible = false;
            }
          }, 5000);
        }
      }
    }
    
    // 显示/隐藏密码
    let passwordVisible = false;
    function togglePasswordVisibility() {
      const passwordDisplay = document.getElementById('password-display');
      const toggleText = document.getElementById('password-toggle-text');
      
      if (passwordVisible) {
        passwordDisplay.textContent = '<?= maskString('passwordlength', 2) ?>';
        passwordDisplay.style.color = 'var(--danger-bg)';
        toggleText.innerHTML = '👁️ <?= t('profile.show') ?>';
        passwordVisible = false;
      } else {
        if (confirm('<?= t('profile.confirm_show_password') ?>')) {
          passwordDisplay.textContent = '<?= t('profile.password_length_info', ['length' => isset($current_user_info['password']) ? strlen($current_user_info['password']) : 0]) ?>';
          passwordDisplay.style.color = 'var(--success-text)';
          toggleText.innerHTML = '🙈 <?= t('profile.hide') ?>';
          passwordVisible = true;
          
          // 3秒后自动隐藏
          setTimeout(() => {
            if (passwordVisible) {
              passwordDisplay.textContent = '<?= maskString('passwordlength', 2) ?>';
              passwordDisplay.style.color = 'var(--danger-bg)';
              toggleText.innerHTML = '👁️ <?= t('profile.show') ?>';
              passwordVisible = false;
            }
          }, 3000);
        }
      }
    }

    // 表单验证
    document.addEventListener('DOMContentLoaded', function() {
      const passwordForm = document.querySelector('form[method="post"]:has(input[name="change_password"])');
      if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
          const newPassword = document.querySelector('input[name="new_password"]').value;
          const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
          
          if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('<?= t('profile.password_mismatch') ?>');
            return false;
          }
          
          if (newPassword.length < 6) {
            e.preventDefault();
            alert('<?= t('profile.password_too_short') ?>');
            return false;
          }
        });
      }
      
      // 初始化主题
      initTheme();
      
      // 添加页面加载动画
      addPageLoadAnimation();
    });
    
    // 页面加载动画
    function addPageLoadAnimation() {
      const cards = document.querySelectorAll('.profile-card, .stat-card, .activity-list');
      cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
          card.style.transition = 'all 0.6s ease';
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, index * 100);
      });
    }
    
    // 主题切换功能
    function toggleTheme() {
      const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      
      // 设置主题
      document.documentElement.setAttribute('data-theme', newTheme);
      
      // 更新按钮图标
      const themeBtn = document.querySelector('.theme-toggle-btn');
      themeBtn.innerHTML = newTheme === 'light' ? '🌙' : '☀️';
      themeBtn.title = newTheme === 'light' ? '切换到深色模式' : '切换到浅色模式';
      
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
          // 添加主题切换动画
          addThemeTransitionAnimation();
        } else {
          console.error('主题切换失败:', data.error);
        }
      }).catch(error => {
        console.error('主题切换请求失败:', error);
        // 即使请求失败，本地主题切换仍然有效
        addThemeTransitionAnimation();
      });
    }
    
    // 主题切换动画
    function addThemeTransitionAnimation() {
      const cards = document.querySelectorAll('.profile-card, .stat-card, .activity-list, .page-header');
      cards.forEach(card => {
        card.style.transition = 'all 0.3s ease';
        card.style.transform = 'scale(0.98)';
        
        setTimeout(() => {
          card.style.transform = 'scale(1)';
        }, 150);
      });
    }
    
    // 页面加载时恢复主题
    function initTheme() {
      const savedTheme = sessionStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', savedTheme);
      
      // 更新按钮图标
      const themeBtn = document.querySelector('.theme-toggle-btn');
      if (themeBtn) {
        themeBtn.innerHTML = savedTheme === 'light' ? '🌙' : '☀️';
        themeBtn.title = savedTheme === 'light' ? '切换到深色模式' : '切换到浅色模式';
      }
    }
    
    // 头像编辑功能
    let selectedImage = null;
    let cropContext = null;
    
    function openAvatarModal() {
      document.getElementById('avatarModal').classList.add('show');
    }
    
    function closeAvatarModal() {
      document.getElementById('avatarModal').classList.remove('show');
      resetCrop();
    }
    
    function resetCrop() {
      selectedImage = null;
      document.getElementById('avatarCropSection').style.display = 'none';
      document.getElementById('avatarFile').value = '';
    }
    
    // 文件选择处理
    document.getElementById('avatarFile').addEventListener('change', function(e) {
      const file = e.target.files[0];
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
    });
    
    function showCropSection() {
      document.getElementById('avatarCropSection').style.display = 'block';
    }
    
    function drawCropCanvas() {
      const canvas = document.getElementById('cropCanvas');
      const ctx = canvas.getContext('2d');
      cropContext = ctx;
      
      // 清空画布
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      
      // 计算图片缩放和位置（居中显示）
      const canvasSize = 400;
      const imgAspect = selectedImage.width / selectedImage.height;
      const canvasAspect = 1;
      
      let drawWidth, drawHeight, offsetX, offsetY;
      
      if (imgAspect > canvasAspect) {
        // 图片更宽，以高度为准
        drawHeight = canvasSize;
        drawWidth = drawHeight * imgAspect;
        offsetX = (canvasSize - drawWidth) / 2;
        offsetY = 0;
      } else {
        // 图片更高，以宽度为准
        drawWidth = canvasSize;
        drawHeight = drawWidth / imgAspect;
        offsetX = 0;
        offsetY = (canvasSize - drawHeight) / 2;
      }
      
      // 绘制图片
      ctx.drawImage(selectedImage, offsetX, offsetY, drawWidth, drawHeight);
      
      // 绘制圆形裁剪遮罩
      ctx.globalCompositeOperation = 'destination-in';
      ctx.beginPath();
      ctx.arc(canvasSize/2, canvasSize/2, canvasSize/2, 0, 2 * Math.PI);
      ctx.fill();
      ctx.globalCompositeOperation = 'source-over';
    }
    
    function cropAvatar() {
      if (!selectedImage) return;
      
      const canvas = document.getElementById('cropCanvas');
      const croppedCanvas = document.createElement('canvas');
      const croppedCtx = croppedCanvas.getContext('2d');
      
      // 设置裁剪后的画布尺寸
      croppedCanvas.width = 200;
      croppedCanvas.height = 200;
      
      // 绘制圆形裁剪后的图片
      croppedCtx.beginPath();
      croppedCtx.arc(100, 100, 100, 0, 2 * Math.PI);
      croppedCtx.clip();
      
      croppedCtx.drawImage(canvas, 0, 0, 200, 200);
      
      // 转换为base64并保存
      const avatarData = croppedCanvas.toDataURL('image/png');
      
      // 发送到服务器
      const formData = new FormData();
      formData.append('update_avatar', '1');
      formData.append('avatar_data', avatarData);
      
      fetch('profile.php', {
        method: 'POST',
        body: formData
      }).then(response => response.text())
      .then(html => {
        // 刷新页面显示新头像
        location.reload();
      }).catch(error => {
        console.error('头像上传失败:', error);
        alert('头像上传失败，请重试');
      });
    }
    
    // 添加输入框焦点效果
    document.addEventListener('DOMContentLoaded', function() {
      const inputs = document.querySelectorAll('input, textarea, select');
      inputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.parentElement.style.transform = 'translateY(-2px)';
        });
        
        input.addEventListener('blur', function() {
          this.parentElement.style.transform = 'translateY(0)';
        });
      });
    });
  </script>
</body>
</html>
