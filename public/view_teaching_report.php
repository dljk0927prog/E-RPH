<?php
// view_teaching_report.php - 详细浏览教课报告
require_once __DIR__ . '/inc/session_config.php';
require_once __DIR__ . '/inc/language_config.php';

// 检查是否登录且是管理员
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login_roles.php');
    exit;
}

require_once __DIR__ . '/../db.php';
$user = $_SESSION['user'];
$error = '';
$report = null;

// 获取报告ID
$report_id = $_GET['id'] ?? 0;
if (!$report_id) {
    header('Location: admin_teaching_reports.php');
    exit;
}

try {
    // 获取教课报告详细信息
    $sql = "
        SELECT 
            a.id,
            a.date,
            a.status,
            a.check_in,
            a.check_out,
            a.created_at,
            u.name as teacher_name,
            u.email as teacher_email,
            u.id as teacher_id,
            c.title as course_title,
            c.description as course_description,
            c.id as course_id
        FROM attendance a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN courses c ON a.course_id = c.id
        WHERE a.id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();
    
    if (!$report) {
        $error = 'Teaching report not found';
    }
    
} catch (Exception $e) {
    $error = 'Failed to retrieve teaching report: ' . $e->getMessage();
}

// 状态翻译
function getStatusText($status) {
    switch ($status) {
        case 'present': return 'Present';
        case 'absent': return 'Absent';
        case 'leave': return 'Leave';
        default: return 'Unknown';
    }
}

// 状态样式
function getStatusClass($status) {
    switch ($status) {
        case 'present': return 'status-present';
        case 'absent': return 'status-absent';
        case 'leave': return 'status-leave';
        default: return '';
    }
}
?>
<!doctype html>
<html lang="<?= t('common.language_code') ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
    <meta charset="utf-8">
    <title>View Teaching Report - ERPH</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        /* 页面布局样式 */
        .admin-layout {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        main {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        /* 页面头部样式 */
        .page-header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e9ecef;
        }
        
        .page-header h2 {
            color: #4a90e2;
            margin: 0;
            font-size: 24px;
        }
        
        /* 报告详情样式 */
        .report-details {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }
        
        .report-section {
            padding: 25px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .report-section:last-child {
            border-bottom: none;
        }
        
        .report-section h3 {
            color: #4a90e2;
            margin: 0 0 20px 0;
            font-size: 18px;
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 10px;
        }
        
        .report-section p {
            margin: 12px 0;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .report-section strong {
            color: #495057;
            font-weight: 600;
            min-width: 120px;
            display: inline-block;
        }
        
        /* 状态样式 */
        .status-present {
            background: #d4edda;
            color: #155724;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-absent {
            background: #f8d7da;
            color: #721c24;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-leave {
            background: #fff3cd;
            color: #856404;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        
        /* 按钮样式 */
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        
        /* 错误消息样式 */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .admin-layout {
                padding: 10px;
            }
            
            main {
                padding: 20px;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .report-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>ERPH System - View Teaching Report</h1>
        <div>
            <a href="admin_teaching_reports.php">Back to Teaching Reports</a>
            <a href="admin_dashboard.php">Back to Dashboard</a>
            <a href="logout.php">Log Out</a>
        </div>
    </header>

    <div class="admin-layout">
        <main style="margin-left: 0; width: 100%;">
            <div class="page-header">
                <h2>Teaching Report Details</h2>
                <div class="action-buttons">
                    <a href="admin_teaching_reports.php" class="btn btn-secondary">Back to List</a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php elseif ($report): ?>
                <div class="report-details">
                    <!-- 基本信息 -->
                    <div class="report-section">
                        <h3>Basic Information</h3>
                        <p><strong>Report ID:</strong> #<?= htmlspecialchars($report['id']) ?></p>
                        <p><strong>Date:</strong> <?= htmlspecialchars($report['date']) ?></p>
                        <p><strong>Status:</strong> 
                            <span class="<?= getStatusClass($report['status']) ?>">
                                <?= getStatusText($report['status']) ?>
                            </span>
                        </p>
                        <p><strong>Created At:</strong> <?= htmlspecialchars($report['created_at']) ?></p>
                    </div>

                    <!-- 老师信息 -->
                    <div class="report-section">
                        <h3>Teacher Information</h3>
                        <p><strong>Teacher Name:</strong> <?= htmlspecialchars($report['teacher_name']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($report['teacher_email']) ?></p>
                        <p><strong>Teacher ID:</strong> #<?= htmlspecialchars($report['teacher_id']) ?></p>
                    </div>

                    <!-- 课程信息 -->
                    <div class="report-section">
                        <h3>Course Information</h3>
                        <p><strong>Course Title:</strong> <?= htmlspecialchars($report['course_title'] ?? 'No course assigned') ?></p>
                        <p><strong>Course ID:</strong> <?= $report['course_id'] ? '#' . htmlspecialchars($report['course_id']) : 'N/A' ?></p>
                        <?php if ($report['course_description']): ?>
                        <p><strong>Course Description:</strong> <?= htmlspecialchars($report['course_description']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- 时间信息 -->
                    <div class="report-section">
                        <h3>Time Information</h3>
                        <p><strong>Check-in Time:</strong> <?= $report['check_in'] ? htmlspecialchars($report['check_in']) : 'Not checked in' ?></p>
                        <p><strong>Check-out Time:</strong> <?= $report['check_out'] ? htmlspecialchars($report['check_out']) : 'Not checked out' ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
