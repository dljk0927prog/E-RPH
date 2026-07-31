<?php
// activity_monitor_data.php - 活动监控AJAX数据接口
require_once __DIR__ . '/../inc/session_config.php';
require_once __DIR__ . '/../inc/activity_monitor_db.php';

// 检查是否登录且是管理员
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => '权限不足']);
    exit;
}

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '方法不允许']);
    exit;
}

try {
    // 获取数据库连接
    $config = require __DIR__ . '/../../config.php';
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    $monitorDB = new ActivityMonitorDB($pdo);
    
    // 获取请求类型和用户筛选
    $action = $_POST['action'] ?? 'get_all';
    $user_filter = isset($_POST['user_filter']) ? (int)$_POST['user_filter'] : null;
    
    $response = [];
    
    switch ($action) {
        case 'get_stats':
            $response['stats'] = $monitorDB->getActivityStats();
            break;
            
        case 'get_reports':
            $response['report_stats'] = $monitorDB->getTeachingReportStats();
            break;
            
        case 'get_activities':
            $limit = (int)($_POST['limit'] ?? 10);
            $response['activities'] = $monitorDB->getRecentActivities($limit, $user_filter);
            break;
            
        case 'get_hourly':
            $response['hourly'] = $monitorDB->getHourlyActivity();
            break;
            
        case 'get_all':
        default:
            $response['stats'] = $monitorDB->getActivityStats();
            $response['report_stats'] = $monitorDB->getTeachingReportStats();
            $response['activities'] = $monitorDB->getRecentActivities(10, $user_filter);
            $response['hourly'] = $monitorDB->getHourlyActivity();
            $response['timestamp'] = date('Y-m-d H:i:s');
            break;
    }
    
    // 记录查看活动监控的活动
    $monitorDB->logActivity(
        $_SESSION['user']['id'],
        'view_monitor',
        '查看活动监控数据',
        'activity_monitor',
        null,
        'success'
    );
    
    echo json_encode([
        'success' => true,
        'data' => $response
    ]);
    
} catch (PDOException $e) {
    error_log("活动监控数据查询失败: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => '数据库查询失败',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("活动监控数据获取失败: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => '系统错误',
        'message' => $e->getMessage()
    ]);
}
?>
