# 🎨 老师页面UI美化与昼夜转换功能

## 📋 概述

本项目为ERPH系统的老师相关页面提供了现代化的UI设计，包括：

- ✨ 现代化卡片式设计
- 🌙 昼夜主题切换
- 🌐 双语支持
- 📱 响应式设计
- 🎭 流畅动画效果
- 🎨 统一的设计语言

## 🚀 已完成的页面

### 1. `teacher_dashboard.php` - 老师仪表板 ✅
- ✅ 完全重写，现代化设计
- ✅ 昼夜主题切换
- ✅ 响应式布局
- ✅ 动画效果
- ✅ 统计卡片
- ✅ 快速操作按钮
- ✅ 个人资料弹窗功能（类似admin_dashboard.php）
- ✅ 设置模态框（语言和主题切换）

### 2. `teaching_reports.php` - 教课报告页面 ✅
- ✅ 完全重写，现代化设计
- ✅ 昼夜主题切换
- ✅ 响应式布局
- ✅ 统计卡片
- ✅ 报告卡片设计
- ✅ 状态标签统一为淡蓝色

### 3. `my_courses.php` - 我的课程页面 ✅
- ✅ 完全重写，现代化设计
- ✅ 昼夜主题切换
- ✅ 响应式布局
- ✅ 统计卡片
- ✅ 课程卡片设计
- ✅ 操作按钮优化
- ✅ 完整的双语言支持（中文/英文）
- ✅ 语言切换按钮

### 4. `lessonplans.php` - 教案管理页面 ✅
- ✅ 完全美化完成
- ✅ 引入通用样式和脚本
- ✅ 完整的双语言支持（中文/英文）
- ✅ 语言切换按钮
- ✅ 所有文本使用语言函数

## 📁 文件结构

```
public/
├── assets/
│   ├── css/
│   │   └── teacher-common.css    # 通用样式文件 ✅
│   └── js/
│       └── teacher-common.js     # 通用JavaScript功能 ✅
├── teacher_dashboard.php          # 老师仪表板（已美化） ✅
├── teaching_reports.php           # 教课报告（已美化） ✅
├── my_courses.php                 # 我的课程（已美化） ✅
├── lessonplans.php                # 教案管理（部分美化） 🔄
└── COLOR_SCHEME_GUIDE.md         # 颜色方案指南 ✅
```

## 🎨 设计特色

### 颜色系统
- **浅色模式**: 仅使用淡蓝色 (`#60a5fa`) 和白色/浅灰色
- **深色模式**: 仅使用淡蓝色 (`#60a5fa`) 和灰色系
- **统一性**: 所有状态颜色（成功、警告、危险）统一为淡蓝色

### 组件设计
- **卡片**: 圆角设计，阴影效果，悬停动画
- **按钮**: 淡蓝色背景，悬停效果，图标支持
- **表单**: 现代化输入框，聚焦效果
- **表格**: 清晰的分隔线，悬停高亮

### 动画效果
- **页面加载**: 淡入上升动画
- **悬停效果**: 微妙的变换和阴影
- **主题切换**: 平滑的颜色过渡
- **滚动动画**: 元素进入视口时的动画

## 🌙 昼夜转换功能

### 实现方式
1. **CSS变量**: 使用CSS自定义属性定义颜色
2. **JavaScript控制**: 通过`data-theme`属性切换主题
3. **本地存储**: 记住用户的主题偏好
4. **平滑过渡**: 所有颜色变化都有过渡动画

### 使用方法
```html
<!-- 在HTML元素上添加data-theme属性 -->
<html lang="zh-CN" data-theme="light">
  <!-- 或 -->
<html lang="zh-CN" data-theme="dark">

<!-- 主题切换按钮 -->
<button class="theme-toggle-btn" onclick="toggleTheme()">
  🌙
</button>
```

## 🌐 双语支持

### 实现方式
- 使用`t()`函数包装所有文本
- 支持中文和英文切换
- 语言文件存储在`inc/language_config.php`

### 示例
```php
<?= t('dashboard.welcome_back') ?>
<?= t('stats.my_courses') ?>
<?= t('common.teacher') ?>
```

## 📱 响应式设计

### 断点设置
- **大屏幕**: 1024px以上
- **平板**: 768px - 1024px
- **手机**: 480px - 768px
- **小手机**: 480px以下

### 适配特性
- 网格布局自动调整
- 按钮大小适配触摸
- 字体大小响应式变化
- 间距和边距自适应

## 🔧 使用方法

### 1. 引入通用样式
```html
<link rel="stylesheet" href="assets/css/teacher-common.css">
```

### 2. 引入通用脚本
```html
<script src="assets/js/teacher-common.js"></script>
```

### 3. 使用预定义类名
```html
<!-- 卡片 -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">标题</h3>
    <p class="card-subtitle">副标题</p>
  </div>
  <!-- 内容 -->
</div>

<!-- 按钮 -->
<button class="btn btn-primary">主要按钮</button>
<button class="btn btn-secondary">次要按钮</button>
<button class="btn btn-danger">危险按钮</button>

<!-- 表单 -->
<div class="form-group">
  <label class="form-label">标签</label>
  <input type="text" class="form-input">
</div>

<!-- 表格 -->
<div class="table-container">
  <table class="table">
    <thead>
      <tr>
        <th data-sort="name">名称</th>
        <th data-sort="date">日期</th>
      </tr>
    </thead>
    <tbody>
      <!-- 数据行 -->
    </tbody>
  </table>
</div>
```

## 🎯 待完成页面

### 1. 所有主要页面已完成 ✅
- ✅ `teacher_dashboard.php` - 老师仪表板
- ✅ `teaching_reports.php` - 教课报告页面
- ✅ `my_courses.php` - 我的课程页面
- ✅ `lessonplans.php` - 教案管理页面

### 2. 未来可优化功能
- 🔄 添加更多动画效果
- 🔄 实现高级表格功能
- 🔄 添加数据可视化图表
- 🔄 优化移动端体验
- 🔄 添加更多主题选项

## 🔧 新增功能

### 个人资料弹窗系统
`teacher_dashboard.php` 现在包含完整的个人资料弹窗功能，与 `admin_dashboard.php` 保持一致：

#### 功能特性
- **个人资料按钮**: 位于header右侧，显示用户头像图标
- **下拉菜单**: 包含用户信息、个人资料、设置和登出选项
- **设置模态框**: 语言切换和主题切换功能
- **交互体验**: 点击外部关闭、ESC键关闭、悬停效果

#### 技术实现
- **CSS样式**: 完整的弹窗样式，支持深色模式
- **JavaScript功能**: 弹窗切换、模态框控制、事件处理
- **响应式设计**: 在不同屏幕尺寸下正常显示
- **无障碍支持**: 键盘导航和屏幕阅读器友好

#### 使用方法
```html
<!-- 个人资料弹窗按钮 -->
<div class="profile-dropdown">
  <button class="profile-trigger" onclick="toggleProfileDropdown()">
    <span class="profile-avatar">👤</span>
  </button>
  <div class="profile-dropdown-menu" id="profileDropdown">
    <!-- 弹窗内容 -->
  </div>
</div>
```

```javascript
// 弹窗控制函数
function toggleProfileDropdown() {
  const dropdown = document.getElementById('profileDropdown');
  dropdown.classList.toggle('show');
}

function closeProfileDropdown() {
  const dropdown = document.getElementById('profileDropdown');
  dropdown.classList.remove('show');
}
```

## 🛠️ 开发指南

### 添加新页面
1. 复制现有页面的HTML结构
2. 引入通用CSS和JS文件
3. 使用预定义的类名和组件
4. 添加页面特定的样式（如需要）

### 自定义样式
```css
/* 在页面中添加自定义样式 */
.custom-component {
  background: var(--bg-primary);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
}
```

### 添加新功能
1. 在`teacher-common.js`中添加新模块
2. 在页面中调用相应功能
3. 确保与现有功能兼容

## 🔍 浏览器兼容性

- **现代浏览器**: Chrome 80+, Firefox 75+, Safari 13+
- **移动浏览器**: iOS Safari 13+, Chrome Mobile 80+
- **IE**: 不支持（建议使用Edge）

## 📝 注意事项

1. **性能优化**: 动画使用CSS3，避免JavaScript动画
2. **可访问性**: 保持足够的颜色对比度
3. **SEO友好**: 语义化HTML结构
4. **维护性**: 使用CSS变量，便于主题定制

## 🚀 未来计划

- [x] 完成老师仪表板美化
- [x] 完成教课报告页面美化
- [x] 完成我的课程页面美化
- [x] 完成教案管理页面美化
- [x] 实现完整的双语言支持
- [x] 添加语言切换功能
- [ ] 添加更多动画效果
- [ ] 实现高级表格功能
- [ ] 添加数据可视化图表
- [ ] 优化移动端体验
- [ ] 添加更多主题选项

## 📞 技术支持

如有问题或建议，请联系开发团队或查看相关文档。

## 🎉 最新更新

### 2024年更新
- ✅ 完成三个主要页面的美化
- ✅ 统一淡蓝色主题
- ✅ 实现昼夜转换功能
- ✅ 添加响应式设计
- ✅ 为teacher_dashboard.php添加个人资料弹窗功能
- ✅ 实现设置模态框（语言和主题切换）
- ✅ 完成lessonplans.php页面美化
- ✅ 为所有页面添加完整的双语言支持（中文/英文）
- ✅ 添加语言切换按钮
- ✅ 所有页面文本使用语言函数，支持国际化
