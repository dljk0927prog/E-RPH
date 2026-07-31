# ERPH 系统快速设置指南

## 问题诊断

如果您看到"Internal Server Error"，通常是因为数据库连接问题。

## 解决步骤

### 步骤 1: 启动MySQL服务
1. 打开XAMPP控制面板
2. 启动MySQL服务
3. 确保MySQL状态显示为"Running"

### 步骤 2: 创建数据库和用户
1. 打开phpMyAdmin: http://localhost/phpmyadmin
2. 以root用户身份登录（通常没有密码）
3. 点击"SQL"标签
4. 复制并粘贴 `setup_database.sql` 文件的内容
5. 点击"执行"

### 步骤 3: 测试连接
1. 在浏览器中访问: http://localhost/E-RPH/test_connection.php
2. 查看测试结果
3. 如果显示"系统准备就绪"，说明设置成功

### 步骤 4: 登录系统
1. 访问: http://localhost/E-RPH/public/login_roles.php
2. 使用默认账户登录:
   - 邮箱: admin@erph.com
   - 密码: admin123

## 常见问题

### 问题1: "数据库不存在"
**解决方案**: 运行 `setup_database.sql` 脚本

### 问题2: "访问被拒绝"
**解决方案**: 检查用户名和密码是否正确

### 问题3: "MySQL服务未启动"
**解决方案**: 在XAMPP控制面板中启动MySQL服务

### 问题4: 权限问题
**解决方案**: 确保 `uploads/` 目录可写

## 手动创建数据库（如果SQL脚本不工作）

```sql
-- 在phpMyAdmin中执行以下命令

-- 1. 创建数据库
CREATE DATABASE erph CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. 创建用户
CREATE USER 'erph_user'@'localhost' IDENTIFIED BY '123456';

-- 3. 授权
GRANT ALL PRIVILEGES ON erph.* TO 'erph_user'@'localhost';
FLUSH PRIVILEGES;

-- 4. 使用数据库
USE erph;

-- 5. 创建表（复制database.sql中的表结构）
```

## 联系支持

如果问题仍然存在，请检查：
1. XAMPP版本是否兼容
2. PHP版本是否为7.4+
3. MySQL版本是否为5.7+
4. 错误日志中的具体错误信息
