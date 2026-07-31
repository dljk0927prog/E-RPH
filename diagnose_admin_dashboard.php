<?php
// diagnose_admin_dashboard.php - 诊断管理员仪表板数据问题
require_once __DIR__ . '/inc/session_config.php';

// 检查是否登录且是管理员
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login_roles.php');
    exit;
}

require_once __DIR__ . '/../db.php';

echo "<h1>管理员仪表板数据诊断</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .error { background: #ffe6e6; border-color: #ff9999; }
    .success { background: #e6ffe6; border-color: #99ff99; }
    .warning { background: #fff3cd; border-color: #ffeaa7; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f2f2f2; }
</style>";

try {
    echo "<div class='section'>";
    echo "<h2>1. 检查用户表</h2>";
    
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY role, name");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p class='error'>❌ 用户表为空！</p>";
    } else {
        echo "<p class='success'>✅ 用户表有 " . count($users) . " 条记录</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>姓名</th><th>邮箱</th><th>角色</th><th>创建时间</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    echo "<div class='section'>";
    echo "<h2>2. 检查课程表</h2>";
    
    $stmt = $pdo->query("SELECT id, title, description, old_teacher_id, created_at FROM courses ORDER BY id");
    $courses = $stmt->fetchAll();
    
    if (empty($courses)) {
        echo "<p class='error'>❌ 课程表为空！</p>";
    } else {
        echo "<p class='success'>✅ 课程表有 " . count($courses) . " 条记录</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>标题</th><th>描述</th><th>原老师ID</th><th>创建时间</th></tr>";
        foreach ($courses as $course) {
            echo "<tr>";
            echo "<td>{$course['id']}</td>";
            echo "<td>{$course['title']}</td>";
            echo "<td>{$course['description']}</td>";
            echo "<td>{$course['old_teacher_id']}</td>";
            echo "<td>{$course['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    echo "<div class='section'>";
    echo "<h2>3. 检查课程-老师关系表</h2>";
    
    $stmt = $pdo->query("SELECT ct.id, ct.course_id, ct.teacher_id, c.title as course_title, u.name as teacher_name 
                         FROM course_teachers ct 
                         LEFT JOIN courses c ON c.id = ct.course_id 
                         LEFT JOIN users u ON u.id = ct.teacher_id 
                         ORDER BY ct.id");
    $course_teachers = $stmt->fetchAll();
    
    if (empty($course_teachers)) {
        echo "<p class='warning'>⚠️ 课程-老师关系表为空！这可能是问题所在。</p>";
    } else {
        echo "<p class='success'>✅ 课程-老师关系表有 " . count($course_teachers) . " 条记录</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>课程ID</th><th>老师ID</th><th>课程标题</th><th>老师姓名</th></tr>";
        foreach ($course_teachers as $ct) {
            echo "<tr>";
            echo "<td>{$ct['id']}</td>";
            echo "<td>{$ct['course_id']}</td>";
            echo "<td>{$ct['teacher_id']}</td>";
            echo "<td>{$ct['course_title']}</td>";
            echo "<td>{$ct['teacher_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    echo "<div class='section'>";
    echo "<h2>4. 检查教课报告表</h2>";
    
    $stmt = $pdo->query("SELECT id, user_id, course_id, date, status, check_in, check_out, created_at 
                         FROM attendance ORDER BY created_at DESC LIMIT 10");
    $attendance = $stmt->fetchAll();
    
    if (empty($attendance)) {
        echo "<p class='warning'>⚠️ 教课报告表为空！没有教课报告数据。</p>";
    } else {
        echo "<p class='success'>✅ 教课报告表有数据，显示最近10条记录</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>用户ID</th><th>课程ID</th><th>日期</th><th>状态</th><th>签到时间</th><th>签退时间</th><th>创建时间</th></tr>";
        foreach ($attendance as $a) {
            echo "<tr>";
            echo "<td>{$a['id']}</td>";
            echo "<td>{$a['user_id']}</td>";
            echo "<td>{$a['course_id']}</td>";
            echo "<td>{$a['date']}</td>";
            echo "<td>{$a['status']}</td>";
            echo "<td>{$a['check_in']}</td>";
            echo "<td>{$a['check_out']}</td>";
            echo "<td>{$a['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    echo "<div class='section'>";
    echo "<h2>5. 测试修复后的查询</h2>";
    
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
        ORDER BY u.name
    ");
    $teacher_reports = $stmt->fetchAll();
    
    if (empty($teacher_reports)) {
        echo "<p class='error'>❌ 修复后的查询仍然没有返回数据！</p>";
        
        // 进一步诊断
        echo "<h3>进一步诊断：</h3>";
        
        // 检查是否有老师用户
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
        $teacher_count = $stmt->fetch()['count'];
        echo "<p>老师用户数量: {$teacher_count}</p>";
        
        // 检查是否有课程-老师关系
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM course_teachers");
        $ct_count = $stmt->fetch()['count'];
        echo "<p>课程-老师关系数量: {$ct_count}</p>";
        
        // 检查是否有教课报告
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM attendance");
        $attendance_count = $stmt->fetch()['count'];
        echo "<p>教课报告数量: {$attendance_count}</p>";
        
    } else {
        echo "<p class='success'>✅ 修复后的查询成功返回数据！</p>";
        echo "<table>";
        echo "<tr><th>老师ID</th><th>老师姓名</th><th>总报告数</th><th>今日报告</th><th>本周报告</th><th>最后报告时间</th><th>分配课程数</th></tr>";
        foreach ($teacher_reports as $tr) {
            echo "<tr>";
            echo "<td>{$tr['id']}</td>";
            echo "<td>{$tr['teacher_name']}</td>";
            echo "<td>{$tr['total_reports']}</td>";
            echo "<td>{$tr['today_reports']}</td>";
            echo "<td>{$tr['week_reports']}</td>";
            echo "<td>{$tr['last_report_time']}</td>";
            echo "<td>{$tr['assigned_courses']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    echo "<div class='section'>";
    echo "<h2>6. 建议的解决方案</h2>";
    
    if (empty($teacher_reports)) {
        echo "<p class='warning'>⚠️ 需要执行以下步骤来解决问题：</p>";
        echo "<ol>";
        echo "<li>确保数据库中有老师用户（role = 'teacher'）</li>";
        echo "<li>确保课程已分配给老师（course_teachers表有数据）</li>";
        echo "<li>确保老师已提交教课报告（attendance表有数据）</li>";
        echo "<li>检查数据库表结构是否正确</li>";
        echo "</ol>";
        
        echo "<p><strong>立即执行的操作：</strong></p>";
        echo "<p>1. 运行 <code>add_multiple_teachers_support.sql</code> 脚本</p>";
        echo "<p>2. 创建一些测试数据</p>";
        echo "<p>3. 重新测试管理员仪表板</p>";
    } else {
        echo "<p class='success'>✅ 数据正常，管理员仪表板应该能正常显示老师报告！</p>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='section error'>";
    echo "<h2>❌ 诊断过程中发生错误</h2>";
    echo "<p>错误信息: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div class='section'>";
echo "<h2>7. 返回管理员仪表板</h2>";
echo "<p><a href='admin_dashboard.php' style='background: #4a90e2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>返回管理员仪表板</a></p>";
echo "</div>";
?>
