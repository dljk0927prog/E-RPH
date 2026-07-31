# 主题同步功能使用说明

## 概述
主题同步功能允许在不同页面间保持深色/浅色模式的一致性，无需刷新页面即可同步主题状态。

## 文件结构
- `theme-sync.js` - 主要的主题同步脚本
- 在需要主题同步的页面中引入此脚本

## 使用方法

### 1. 引入脚本
在HTML页面的`<head>`部分添加：
```html
<script src="assets/js/theme-sync.js"></script>
```

### 2. 添加主题切换按钮
在页面中添加主题切换按钮：
```html
<button class="theme-toggle-btn" onclick="toggleTheme()" title="切换主题">
  🌙
</button>
```

### 3. 设置HTML根元素
确保HTML根元素有`data-theme`属性：
```html
<html lang="zh" data-theme="light">
```

## 功能特性

### 自动主题初始化
- 页面加载时自动检测并应用保存的主题
- 优先级：localStorage > session > 默认值(light)

### 跨页面同步
- 使用localStorage存储主题状态
- 支持CustomEvent进行页面内通信
- 支持BroadcastChannel进行跨标签页通信

### 服务器同步
- 自动将主题变化发送到`change_theme.php`
- 支持错误处理和日志记录

## API接口

### 全局函数
- `toggleTheme()` - 切换主题（深色↔浅色）
- `ThemeManager.initializeTheme()` - 手动初始化主题
- `ThemeManager.syncThemeToOtherPages(theme)` - 同步主题到其他页面

### 事件监听
页面可以监听主题变化事件：
```javascript
window.addEventListener('themeChanged', function(event) {
  const newTheme = event.detail.theme;
  console.log('主题已更改为:', newTheme);
  // 执行相应的主题切换逻辑
});
```

## 兼容性
- 现代浏览器：支持所有功能
- 旧版浏览器：降级到localStorage存储
- 不支持JavaScript：回退到服务器端主题设置

## 故障排除

### 主题不同步
1. 检查是否正确引入了`theme-sync.js`
2. 确认localStorage可用
3. 检查控制台是否有错误信息

### 样式不生效
1. 确认CSS中有对应的深色模式样式
2. 检查`data-theme`属性是否正确设置
3. 验证CSS选择器优先级

## 示例页面
参考`admin_dashboard.php`的实现方式，该页面完整展示了如何使用主题同步功能。

