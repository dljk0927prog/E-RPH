<?php
// update_access_statistics.php - 更新访问统计数据的AJAX接口
require_once __DIR__ . '/../inc/session_config.php';

// 检查是否登录
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['error' => '未登录']);
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
    
    $action = $_POST['action'] ?? 'record_visit';
    $response = [];
    
    switch ($action) {
        case 'record_visit':
            // 记录一次访问
            $current_hour = (int)date('H');
            $today = date('Y-m-d');
            
            // 检查是否已有今日该小时的记录
            $stmt = $pdo->prepare("
                SELECT id, total_visits, unique_users 
                FROM access_statistics 
                WHERE date = ? AND hour = ?
            ");
            $stmt->execute([$today, $current_hour]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // 更新现有记录
                $new_visits = $existing['total_visits'] + 1;
                $new_unique_users = $existing['unique_users'];
                
                // 检查是否是新用户（今日首次访问）
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM access_statistics 
                    WHERE date = ? AND hour = ? AND unique_users > 0
                ");
                $stmt->execute([$today, $current_hour]);
                $has_visits = $stmt->fetch()['count'] > 0;
                
                if (!$has_visits) {
                    $new_unique_users = 1;
                }
                
                $stmt = $pdo->prepare("
                    UPDATE access_statistics 
                    SET total_visits = ?, unique_users = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$new_visits, $new_unique_users, $existing['id']]);
            } else {
                // 创建新记录
                $stmt = $pdo->prepare("
                    INSERT INTO access_statistics (date, hour, total_visits, unique_users, new_users)
                    VALUES (?, ?, 1, 1, 0)
                ");
                $stmt->execute([$today, $current_hour]);
            }
            
            $response['success'] = true;
            $response['message'] = '访问记录已更新';
            break;
            
        case 'get_current_hour_data':
            // 获取当前小时的数据
            $current_hour = (int)date('H');
            $today = date('Y-m-d');
            
            $stmt = $pdo->prepare("
                SELECT total_visits, unique_users 
                FROM access_statistics 
                WHERE date = ? AND hour = ?
            ");
            $stmt->execute([$today, $current_hour]);
            $data = $stmt->fetch();
            
            if ($data) {
                $response['success'] = true;
                $response['data'] = $data;
            } else {
                $response['success'] = true;
                $response['data'] = ['total_visits' => 0, 'unique_users' => 0];
            }
            break;
            
        case 'simulate_activity':
            // 模拟一些活动数据（用于测试）
            $today = date('Y-m-d');
            
            // 生成今日24小时的模拟数据
            for ($hour = 0; $hour < 24; $hour++) {
                // 工作时间（9-17点）有更多活动
                if ($hour >= 9 && $hour <= 17) {
                    $visits = rand(15, 35);
                    $unique_users = rand(8, 20);
                } else {
                    $visits = rand(0, 10);
                    $unique_users = rand(0, 5);
                }
                
                $new_users = rand(0, 2);
                
                // 检查是否已有该小时的记录
                $stmt = $pdo->prepare("
                    SELECT id FROM access_statistics 
                    WHERE date = ? AND hour = ?
                ");
                $stmt->execute([$today, $hour]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // 更新现有记录
                    $stmt = $pdo->prepare("
                        UPDATE access_statistics 
                        SET total_visits = ?, unique_users = ?, new_users = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $stmt->execute([$visits, $unique_users, $new_users, $existing['id']]);
                } else {
                    // 创建新记录
                    $stmt = $pdo->prepare("
                        INSERT INTO access_statistics (date, hour, total_visits, unique_users, new_users)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$today, $hour, $visits, $unique_users, $new_users]);
                }
            }
            
            $response['success'] = true;
            $response['message'] = '模拟活动数据已更新，生成了24小时的测试数据';
            break;
            
        case 'generate_today_data':
            // 生成今日的活动监控数据 - 基于真实的教课报告数据
            $today = date('Y-m-d');
            
            // 首先清空今日的访问统计数据，重新生成
            $stmt = $pdo->prepare("DELETE FROM access_statistics WHERE date = ?");
            $stmt->execute([$today]);
            
            // 尝试从教课报告数据获取真实的活动数据 - 考虑完整的活动时长
            $stmt = $pdo->prepare("
                SELECT 
                    HOUR(a.check_in) as check_in_hour,
                    HOUR(a.check_out) as check_out_hour,
                    COUNT(*) as count
                FROM attendance a
                WHERE DATE(a.date) = ? 
                AND a.check_in IS NOT NULL 
                AND a.check_out IS NOT NULL
                ORDER BY a.check_in
            ");
            $stmt->execute([$today]);
            $attendance_data = $stmt->fetchAll();
            
            if (!empty($attendance_data)) {
                // 使用真实的教课数据 - 考虑从签到到签退的完整时长
                $hourly_activity = array_fill(0, 24, 0);
                
                foreach ($attendance_data as $row) {
                    $check_in_hour = (int)$row['check_in_hour'];
                    $check_out_hour = (int)$row['check_out_hour'];
                    $count = (int)$row['count'];
                    
                    // 将签到到签退之间的所有小时都标记为有活动
                    for ($hour = $check_in_hour; $hour <= $check_out_hour; $hour++) {
                        if ($hour >= 0 && $hour < 24) {
                            $hourly_activity[$hour] += $count;
                        }
                    }
                }
                
                // 插入所有24小时的数据
                for ($hour = 0; $hour < 24; $hour++) {
                    $activity_count = $hourly_activity[$hour];
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO access_statistics (date, hour, total_visits, unique_users, new_users)
                        VALUES (?, ?, ?, ?, 0)
                    ");
                    $stmt->execute([$today, $hour, $activity_count, min($activity_count, 5), 0]);
                }
                
                $response['success'] = true;
                $response['message'] = '今日数据生成成功，基于真实教课报告数据';
            } else {
                // 如果没有教课数据，生成模拟数据
                for ($hour = 0; $hour < 24; $hour++) {
                    // 模拟工作时间的高峰（9-17点）
                    if ($hour >= 9 && $hour <= 17) {
                        $total_visits = rand(8, 25); // 工作时间随机活动
                    } else {
                        $total_visits = rand(0, 8);  // 非工作时间较少活动
                    }
                    
                    $unique_users = min($total_visits, 5); // 假设最多5个独立用户
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO access_statistics (date, hour, total_visits, unique_users, new_users)
                        VALUES (?, ?, ?, ?, 0)
                    ");
                    $stmt->execute([$today, $hour, $total_visits, $unique_users]);
                }
                
                $response['success'] = true;
                $response['message'] = '今日数据生成成功，已生成24小时的模拟数据（无真实教课数据）';
            }
            break;
            
        case 'sync_attendance':
            // 同步教课报告数据到访问统计
            $today = date('Y-m-d');
            
            // 首先清空今日的访问统计数据，重新生成
            $stmt = $pdo->prepare("DELETE FROM access_statistics WHERE date = ?");
            $stmt->execute([$today]);
            
            // 获取今日的教课报告数据 - 考虑完整的活动时长
            $stmt = $pdo->prepare("
                SELECT 
                    HOUR(a.check_in) as check_in_hour,
                    HOUR(a.check_out) as check_out_hour,
                    COUNT(*) as count
                FROM attendance a
                WHERE DATE(a.date) = ? 
                AND a.check_in IS NOT NULL 
                AND a.check_out IS NOT NULL
                ORDER BY a.check_in
            ");
            $stmt->execute([$today]);
            $attendance_data = $stmt->fetchAll();
            
            // 记录调试信息
            error_log("同步教课数据: 找到 " . count($attendance_data) . " 条记录");
            
            if (empty($attendance_data)) {
                $response['success'] = false;
                $response['error'] = '今日没有找到教课报告数据';
                break;
            }
            
            // 使用真实的教课数据 - 考虑从签到到签退的完整时长
            $hourly_activity = array_fill(0, 24, 0);
            
            foreach ($attendance_data as $row) {
                $check_in_hour = (int)$row['check_in_hour'];
                $check_out_hour = (int)$row['check_out_hour'];
                $count = (int)$row['count'];
                
                // 记录调试信息
                error_log("同步记录: 签到 {$check_in_hour}:00, 签退 {$check_out_hour}:00, 数量 {$count}");
                
                // 将签到到签退之间的所有小时都标记为有活动
                for ($hour = $check_in_hour; $hour <= $check_out_hour; $hour++) {
                    if ($hour >= 0 && $hour < 24) {
                        $hourly_activity[$hour] += $count;
                    }
                }
            }
            
            // 插入所有24小时的数据
            for ($hour = 0; $hour < 24; $hour++) {
                $activity_count = $hourly_activity[$hour];
                
                $stmt = $pdo->prepare("
                    INSERT INTO access_statistics (date, hour, total_visits, unique_users, new_users)
                    VALUES (?, ?, ?, ?, 0)
                ");
                $stmt->execute([$today, $hour, $activity_count, min($activity_count, 5), 0]);
            }
            
            $response['success'] = true;
            $response['message'] = '教课数据同步成功，已更新 ' . count($attendance_data) . ' 个小时的数据';
            break;
            
        default:
            $response['success'] = false;
            $response['error'] = '未知操作';
            break;
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("更新访问统计数据失败: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => '数据库错误',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("更新访问统计数据失败: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => '系统错误',
        'message' => $e->getMessage()
    ]);
}
?>
