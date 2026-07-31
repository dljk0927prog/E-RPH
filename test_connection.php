<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== 测试数据库连接 ===\n";

// 方法1: 直接连接
echo "方法1: 直接连接...\n";
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=erph", "erph_user", "123456");
    echo "✓ 方法1成功\n";
    
    $result = $pdo->query("SELECT COUNT(*) as count FROM attendance")->fetch();
    echo "  attendance表记录数: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "❌ 方法1失败: " . $e->getMessage() . "\n";
}

// 方法2: 使用localhost
echo "\n方法2: 使用localhost...\n";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=erph", "erph_user", "123456");
    echo "✓ 方法2成功\n";
    
    $result = $pdo->query("SELECT COUNT(*) as count FROM lesson_plans")->fetch();
    echo "  lesson_plans表记录数: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "❌ 方法2失败: " . $e->getMessage() . "\n";
}

// 方法3: 使用root用户
echo "\n方法3: 使用root用户...\n";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=erph", "root", "");
    echo "✓ 方法3成功\n";
    
    $result = $pdo->query("SELECT COUNT(*) as count FROM attendance")->fetch();
    echo "  attendance表记录数: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "❌ 方法3失败: " . $e->getMessage() . "\n";
}

echo "\n连接测试完成\n";
?>
