# 登录页面背景预览弹窗功能

## 功能概述
已将登录页面背景管理中的预览功能从新标签页改为弹窗预览模式，提供更好的用户体验和实时预览效果。

## 主要特性

### 1. 弹窗预览
- ✅ 模态弹窗显示登录页面预览
- ✅ 实时应用当前背景设置
- ✅ 支持深色/浅色主题
- ✅ 响应式设计，移动端友好

### 2. 交互功能
- ✅ 点击按钮打开预览弹窗
- ✅ 点击外部区域关闭弹窗
- ✅ ESC键快速关闭弹窗
- ✅ 阻止背景页面滚动

### 3. 预览内容
- ✅ 完整的登录页面布局
- ✅ 语言切换按钮预览
- ✅ 角色选择按钮预览
- ✅ 表单输入框预览
- ✅ 登录按钮预览

## 技术实现

### 1. HTML结构
```html
<!-- 预览弹窗 -->
<div id="previewModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>登录页面预览</h3>
      <span class="close" onclick="closePreviewModal()">&times;</span>
    </div>
    <div class="modal-body">
      <div class="preview-container">
        <div class="login-preview" id="loginPreview">
          <!-- 登录页面预览内容 -->
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closePreviewModal()">关闭</button>
    </div>
  </div>
</div>
```

### 2. JavaScript功能
```javascript
// 打开预览弹窗
function openPreviewModal() {
  const modal = document.getElementById('previewModal');
  modal.style.display = 'block';
  applyBackgroundToPreview();
  document.body.style.overflow = 'hidden';
}

// 关闭预览弹窗
function closePreviewModal() {
  const modal = document.getElementById('previewModal');
  modal.style.display = 'none';
  document.body.style.overflow = 'auto';
}

// 应用背景到预览区域
function applyBackgroundToPreview() {
  const previewContainer = document.getElementById('loginPreview');
  const currentBackground = '<?= $current_background ?>';
  
  if (currentBackground !== 'default') {
    previewContainer.style.backgroundImage = `url('${currentBackground}')`;
    previewContainer.style.backgroundSize = 'cover';
    previewContainer.style.backgroundPosition = 'center';
    previewContainer.style.backgroundRepeat = 'no-repeat';
  } else {
    previewContainer.style.background = 'linear-gradient(135deg, #fff 10%, #ffecec 60%)';
    previewContainer.style.backgroundImage = 'none';
  }
}
```

### 3. CSS样式
```css
/* 弹窗基础样式 */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(5px);
}

.modal-content {
  background-color: var(--bg-secondary);
  margin: 2% auto;
  padding: 0;
  border-radius: 12px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: modalSlideIn 0.3s ease-out;
}

/* 弹窗动画 */
@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-50px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
```

## 文件修改

### 1. 主要文件
- `public/login_background_manager.php` - 添加弹窗HTML和JavaScript
- `public/assets/css/background-manager.css` - 添加弹窗和预览样式

### 2. 测试文件
- `public/test_background_preview.php` - 弹窗功能测试页面

## 使用方法

### 1. 打开预览
在背景管理页面点击"预览登录页面"按钮

### 2. 查看预览
弹窗中显示完整的登录页面预览，包括：
- 当前设置的背景图片
- 登录表单布局
- 语言切换按钮
- 角色选择按钮

### 3. 关闭预览
- 点击弹窗右上角的 × 按钮
- 点击弹窗外部区域
- 按ESC键

## 样式特性

### 1. 弹窗设计
- 半透明背景遮罩
- 毛玻璃效果（backdrop-filter）
- 圆角边框设计
- 阴影效果增强层次感

### 2. 动画效果
- 弹窗打开时的滑入动画
- 平滑的过渡效果
- 悬停状态的交互反馈

### 3. 响应式布局
- 移动端适配
- 弹窗尺寸自适应
- 内容区域滚动处理

## 深色模式支持

### 1. 主题适配
- 弹窗背景色自动适配
- 边框颜色主题化
- 文字颜色主题化

### 2. CSS变量
```css
[data-theme="dark"] .modal-content {
  background-color: var(--bg-secondary);
  border: 1px solid var(--border-color);
}

[data-theme="dark"] .modal-footer {
  background: var(--bg-secondary);
  border-top-color: var(--border-color);
}
```

## 测试建议

### 1. 功能测试
- 测试弹窗打开/关闭
- 验证背景图片显示
- 检查主题切换效果

### 2. 交互测试
- 测试多种关闭方式
- 验证键盘快捷键
- 检查移动端体验

### 3. 兼容性测试
- 不同浏览器测试
- 不同屏幕尺寸测试
- 触摸设备测试

## 注意事项

### 1. 性能考虑
- 弹窗打开时阻止背景滚动
- 图片加载优化
- 动画性能优化

### 2. 用户体验
- 弹窗大小适中
- 关闭方式多样
- 视觉反馈清晰

### 3. 可访问性
- 键盘导航支持
- 屏幕阅读器友好
- 焦点管理

## 总结

弹窗预览功能成功实现了从新标签页到模态弹窗的升级，提供了更好的用户体验：

- **即时预览** - 无需离开当前页面
- **交互友好** - 多种关闭方式
- **视觉统一** - 与系统主题保持一致
- **响应式设计** - 支持各种设备

该功能让管理员能够更直观地预览背景设置效果，提升了背景管理功能的易用性和专业性。
