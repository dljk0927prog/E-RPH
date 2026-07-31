# 🎨 颜色方案指南 - 淡蓝色主题

## 📋 概述

本系统采用统一的淡蓝色主题，确保视觉一致性和用户体验的连贯性。

## 🌈 颜色定义

### 主要颜色
- **淡蓝色 (Primary Blue)**: `#60a5fa` - 主要强调色
- **深蓝色 (Dark Blue)**: `#3b82f6` - 悬停和激活状态
- **浅蓝色 (Light Blue)**: `#93c5fd` - 次要元素和渐变

### 中性色
- **纯白色**: `#ffffff` - 主要背景
- **浅灰色**: `#f8fafc` - 次要背景
- **中灰色**: `#f1f5f9` - 三级背景
- **深灰色**: `#e2e8f0` - 边框和分割线

## 🌞 浅色模式配色

### 背景色
```css
--bg-primary: #ffffff;      /* 卡片和主要内容背景 */
--bg-secondary: #f8fafc;    /* 页面背景 */
--bg-tertiary: #f1f5f9;    /* 悬停和选中状态 */
```

### 文字色
```css
--text-primary: #1e293b;    /* 主要文字 */
--text-secondary: #64748b;  /* 次要文字 */
--text-muted: #94a3b8;     /* 提示和说明文字 */
```

### 强调色
```css
--accent-color: #60a5fa;    /* 主要按钮、链接、标题 */
--accent-hover: #3b82f6;   /* 悬停状态 */
--success-color: #60a5fa;   /* 成功状态（统一为淡蓝色） */
--warning-color: #60a5fa;   /* 警告状态（统一为淡蓝色） */
--danger-color: #60a5fa;    /* 危险状态（统一为淡蓝色） */
```

### 按钮色
```css
--button-primary: #60a5fa;      /* 主要按钮 */
--button-primary-hover: #3b82f6; /* 主要按钮悬停 */
--button-secondary: #93c5fd;     /* 次要按钮 */
--button-secondary-hover: #60a5fa; /* 次要按钮悬停 */
--button-danger: #60a5fa;        /* 危险按钮（统一为淡蓝色） */
--button-danger-hover: #3b82f6;  /* 危险按钮悬停 */
```

## 🌙 深色模式配色

### 背景色
```css
--bg-primary: #1e293b;      /* 卡片和主要内容背景 */
--bg-secondary: #334155;     /* 页面背景 */
--bg-tertiary: #475569;     /* 悬停和选中状态 */
```

### 文字色
```css
--text-primary: #f8fafc;    /* 主要文字（白色） */
--text-secondary: #cbd5e1;  /* 次要文字（浅灰色） */
--text-muted: #94a3b8;     /* 提示和说明文字（中灰色） */
```

### 强调色
```css
--accent-color: #60a5fa;    /* 主要按钮、链接、标题 */
--accent-hover: #93c5fd;   /* 悬停状态（更亮的淡蓝色） */
```

### 按钮色
```css
--button-primary: #60a5fa;      /* 主要按钮 */
--button-primary-hover: #93c5fd; /* 主要按钮悬停 */
--button-secondary: #475569;     /* 次要按钮（深灰色） */
--button-secondary-hover: #334155; /* 次要按钮悬停（更深的灰色） */
--button-danger: #60a5fa;        /* 危险按钮（统一为淡蓝色） */
--button-danger-hover: #93c5fd;  /* 危险按钮悬停 */
```

## 🎯 使用原则

### 1. 颜色统一性
- 所有状态颜色（成功、警告、危险）统一使用淡蓝色
- 避免使用其他颜色，保持视觉一致性

### 2. 对比度
- 浅色模式：淡蓝色在白色背景上提供良好的对比度
- 深色模式：淡蓝色在灰色背景上提供清晰的视觉层次

### 3. 可访问性
- 确保文字和背景之间有足够的对比度
- 支持色盲用户的视觉需求

## 🔧 实现方式

### CSS变量
```css
/* 在:root中定义浅色模式变量 */
:root {
  --accent-color: #60a5fa;
  --bg-primary: #ffffff;
  /* ... 其他变量 */
}

/* 在[data-theme="dark"]中定义深色模式变量 */
[data-theme="dark"] {
  --accent-color: #60a5fa;
  --bg-primary: #1e293b;
  /* ... 其他变量 */
}
```

### 使用示例
```css
.button {
  background: var(--accent-color);
  color: white;
}

.button:hover {
  background: var(--accent-hover);
}

.card {
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
}
```

## 📱 响应式考虑

### 移动端
- 保持颜色方案一致
- 确保触摸目标有足够的对比度

### 高分辨率屏幕
- 使用CSS变量确保颜色在不同设备上保持一致
- 支持系统级深色模式切换

## 🎨 设计建议

### 1. 层次结构
- 使用不同的灰色层次创建视觉层次
- 淡蓝色用于重要的交互元素

### 2. 状态反馈
- 悬停状态使用稍深的淡蓝色
- 激活状态使用最深的蓝色

### 3. 一致性
- 所有页面使用相同的颜色变量
- 避免硬编码颜色值

## 🔍 测试建议

### 1. 对比度测试
- 使用在线工具测试颜色对比度
- 确保符合WCAG 2.1 AA标准

### 2. 深色模式测试
- 在不同设备上测试深色模式
- 确保所有元素在深色模式下都清晰可见

### 3. 可访问性测试
- 使用屏幕阅读器测试
- 检查色盲用户的视觉体验

## 📝 维护说明

### 1. 颜色更新
- 修改CSS变量即可全局更新颜色
- 避免在组件中硬编码颜色值

### 2. 新组件开发
- 使用预定义的颜色变量
- 遵循现有的颜色使用模式

### 3. 主题扩展
- 可以轻松添加新的主题变体
- 保持颜色方案的一致性
