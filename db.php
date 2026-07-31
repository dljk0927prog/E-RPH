<?php
// db.php - 数据库连接文件
try {
    // 检查配置文件是否存在
    if (!file_exists(__DIR__ . '/config.php')) {
        throw new Exception('配置文件 config.php 不存在');
    }
    
    $config = require __DIR__ . '/config.php';
    
    // 检查配置是否完整
    if (!isset($config['db']) || !is_array($config['db'])) {
        throw new Exception('配置文件格式错误：缺少数据库配置');
    }
    
    $db = $config['db'];
    $required_keys = ['host', 'dbname', 'user', 'pass', 'charset'];
    
    foreach ($required_keys as $key) {
        if (!isset($db[$key])) {
            throw new Exception("配置文件缺少必要的数据库配置项：{$key}");
        }
    }

    $dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}";
    
    // 尝试连接数据库
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // 测试连接
    $pdo->query('SELECT 1');
    
} catch (PDOException $e) {
    // 数据库连接错误
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        die("数据库 '{$db['dbname']}' 不存在。请先运行 setup_database.sql 创建数据库。");
    } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
        die("数据库访问被拒绝。请检查用户名和密码是否正确。");
    } else {
        die("数据库连接失败: " . $e->getMessage());
    }
} catch (Exception $e) {
    die("配置文件错误: " . $e->getMessage());
}
