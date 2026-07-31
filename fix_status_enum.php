<?php
// 修复 status 字段的枚举值
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "开始修复 status 字段...\n";

try {
    require_once 'db.php';
    echo "数据库连接成功\n";
    
    // 修改 status 字段的枚举值
    echo "正在修改 status 字段...\n";
    $pdo->exec("ALTER TABLE attendance MODIFY COLUMN status ENUM('present', 'absent', 'late') DEFAULT 'present'");
    echo "✓ status 字段修改成功\n";
    
    // 显示修改后的结构
    echo "\n修改后的表结构:\n";
    $columns = $pdo->query("DESCRIBE attendance")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            echo "   - {$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']} {$column['Default']}\n";
            break;
        }
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "错误位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>
