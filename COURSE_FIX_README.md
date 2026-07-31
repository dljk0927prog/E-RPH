# 课程创建问题修复说明

## 问题描述
在尝试创建课程时出现错误：
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'name' in 'field list'
```

## 问题原因
1. **字段名不匹配**：代码中使用的是 `name` 字段，但数据库表中实际字段名是 `title`
2. **缺少字段**：数据库表中缺少 `is_active` 字段

## 已修复的问题

### 1. 字段名修复
- 将 `name` 改为 `title`
- 更新了表单字段名和数据库查询

### 2. 数据库结构更新
创建了 `update_courses_table.sql` 脚本来添加缺失的字段

## 解决步骤

### 步骤 1：运行数据库更新脚本
在 MySQL 中执行以下命令：

```sql
-- 方法1：使用命令行
C:\xampp\mysql\bin\mysql.exe -u root < update_courses_table.sql

-- 方法2：在 phpMyAdmin 中执行 update_courses_table.sql 文件内容
```

### 步骤 2：验证修复
1. 访问 `public/add_course.php` 页面
2. 填写课程信息并提交
3. 应该能成功创建课程

## 修复后的功能

### 添加课程页面
- ✅ 课程名称字段（使用 `title` 字段）
- ✅ 课程描述字段
- ✅ 授课老师选择
- ✅ 启用/禁用课程选项

### 课程管理页面
- ✅ 课程列表显示
- ✅ 按老师筛选
- ✅ 课程状态显示
- ✅ 编辑和删除链接

## 数据库表结构

更新后的 `courses` 表结构：
```sql
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,           -- 课程名称
    description TEXT,                      -- 课程描述
    teacher_id INT,                        -- 授课老师ID
    is_active BOOLEAN DEFAULT TRUE,        -- 是否启用
    course_code VARCHAR(50) UNIQUE,        -- 课程代码
    credits INT DEFAULT 0,                 -- 学分
    max_students INT DEFAULT 30,           -- 最大学生数
    semester VARCHAR(20),                  -- 学期
    academic_year VARCHAR(10),             -- 学年
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
);
```

## 注意事项
1. 确保在运行更新脚本前备份数据库
2. 如果仍有问题，检查数据库连接和权限
3. 所有相关页面都已更新以匹配新的数据库结构

## 测试建议
1. 先创建一个测试课程
2. 验证课程管理页面能正确显示
3. 测试编辑和删除功能
4. 检查其他相关功能是否正常
