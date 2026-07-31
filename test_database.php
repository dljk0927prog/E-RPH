<?php
// 测试数据库连接和表结构
require_once __DIR__ . '/db.php';

echo "<h2>数据库连接测试</h2>";

try {
    // 测试数据库连接
    echo "<p>✅ 数据库连接成功</p>";
    
    // 检查users表结构
    echo "<h3>Users表结构:</h3>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>字段名</th><th>类型</th><th>是否为空</th><th>键</th><th>默认值</th></tr>";
    
    $phone_exists = false;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'phone') {
            $phone_exists = true;
        }
    }
    echo "</table>";
    
    if ($phone_exists) {
        echo "<p>✅ phone字段存在 - profile.php应该可以正常工作</p>";
    } else {
        echo "<p>❌ phone字段不存在 - 需要运行fix_users_table.sql脚本</p>";
        echo "<p><strong>解决方案：</strong></p>";
        echo "<ol>";
        echo "<li>打开phpMyAdmin (通常在 http://localhost/phpmyadmin)</li>";
        echo "<li>选择 'erph' 数据库</li>";
        echo "<li>点击 'SQL' 标签</li>";
        echo "<li>复制并粘贴 fix_users_table.sql 文件的内容</li>";
        echo "<li>点击 '执行' 按钮</li>";
        echo "</ol>";
    }
    
    // 测试用户数据
    echo "<h3>用户数据测试:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "<p>用户总数: {$userCount}</p>";
    
    if ($userCount > 0) {
        echo "<p>✅ 有用户数据，系统应该可以正常登录</p>";
    } else {
        echo "<p>❌ 没有用户数据，需要创建管理员账户</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ 数据库错误: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>可能的解决方案：</strong></p>";
    echo "<ul>";
    echo "<li>确保XAMPP的MySQL服务正在运行</li>";
    echo "<li>检查数据库配置文件 db.php</li>";
    echo "<li>运行 setup_database.sql 创建数据库和表</li>";
    echo "</ul>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
.success { color: green; }
.error { color: red; }
</style>
