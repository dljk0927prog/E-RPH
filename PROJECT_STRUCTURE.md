# ERPH 系统项目结构

## 📁 项目根目录

### 核心配置文件
- `config.php` - 数据库和系统配置
- `db.php` - 数据库连接管理
- `index.php` - 系统首页（进入按钮）

### 数据库脚本
- `setup_database.sql` - 完整的数据库初始化脚本（推荐使用）
- `add_teacher_user.sql` - 添加教师用户的补充脚本

### 文档
- `README.md` - 项目说明文档
- `SETUP.md` - 快速安装指南

### 目录
- `public/` - 公共访问文件
- `uploads/` - 文件上传目录

## 📁 public/ 目录

### 认证系统
- `login_roles.php` - 主要登录页面（支持角色选择）
- `login_roles.php` - 支持角色的登录系统
- `logout.php` - 登出处理
- `reset_password.php` - 密码重置页面

### 仪表板
- `admin_dashboard.php` - 管理员仪表板
- `teacher_dashboard.php` - 教师仪表板

### 功能页面
- `teaching_reports.php` - 教课报告浏览（原 `attendance.php`）
- `lessonplans.php` - 教案管理

### 错误页面
- `403.php` - 访问被拒绝页面
- `404.php` - 页面未找到页面

### 配置和样式
- `.htaccess` - Apache服务器配置
- `assets/css/style.css` - 主样式文件
- `inc/` - 公共包含文件
  - `header.php` - 页面头部
  - `footer.php` - 页面底部

## 🗑️ 已清理的文件

以下文件已被删除（不再需要）：
- `test_db.php` - 旧的测试文件
- `debug_login.php` - 临时调试文件
- `debug.php` - 系统诊断文件
- `test_connection.php` - 连接测试文件
- `database.sql` - 重复的数据库脚本
- `public/login_simple.php` - 简化登录测试文件
- `public/header.php` - 重复的头部文件

## 🚀 使用说明

### 1. 系统访问
- 首页：`index.php` - 点击"进入系统"按钮
- 登录：`public/login_roles.php` - 选择角色后登录

### 2. 默认账户
- 管理员：admin@erph.com / admin123
- 测试账户：test@erph.com / admin123

### 3. 密码重置
如果登录失败，访问：`public/reset_password.php`

### 4. 数据库设置
运行：`setup_database.sql` 脚本初始化数据库

## 📊 文件统计

- 总文件数：约 20 个核心文件
- 总代码行数：约 2000+ 行
- 主要功能：用户认证、角色管理、教课报告、教案管理

## 🔒 安全特性

- 密码哈希加密
- SQL注入防护
- XSS防护
- 角色权限控制
- 会话管理
