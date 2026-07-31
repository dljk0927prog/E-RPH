# 翻译键修复总结

## 问题描述
在个人资料下拉菜单中，"系统说明书"显示的是翻译键 `common.manual` 而不是实际的中文文本。

## 问题原因
翻译键 `common.manual` 没有在语言翻译文件中定义，导致系统显示原始的翻译键而不是翻译后的文本。

## 解决方案

### 1. 添加基础翻译键
在 `public/inc/translations/zh.php` 和 `public/inc/translations/en.php` 中添加了基础翻译键：

**中文翻译 (zh.php):**
```php
'common' => [
    // ... 其他翻译
    'manual' => '系统说明书',
    // ... 其他翻译
]
```

**英文翻译 (en.php):**
```php
'common' => [
    // ... 其他翻译
    'manual' => 'System Manual',
    // ... 其他翻译
]
```

### 2. 添加完整的系统说明书翻译
为系统说明书页面添加了完整的翻译键集合，包括：

**中文翻译键 (manual 部分):**
- `manual.title` => '系统说明书'
- `manual.subtitle` => '电子资源规划系统使用指南'
- `manual.quick_start` => '快速开始'
- `manual.admin_features` => '管理员功能'
- `manual.teacher_features` => '教师功能'
- `manual.student_features` => '学生功能'
- `manual.common_features` => '通用功能'
- `manual.help_support` => '帮助与支持'
- 以及所有功能模块的详细描述翻译

**英文翻译键 (manual 部分):**
- `manual.title` => 'System Manual'
- `manual.subtitle` => 'Electronic Resource Planning System User Guide'
- `manual.quick_start` => 'Quick Start'
- `manual.admin_features` => 'Administrator Features'
- `manual.teacher_features` => 'Teacher Features'
- `manual.student_features` => 'Student Features'
- `manual.common_features` => 'Common Features'
- `manual.help_support` => 'Help & Support'
- 以及所有功能模块的详细描述翻译

## 修复效果

### 1. 菜单显示正常
- 个人资料下拉菜单中的"系统说明书"现在正确显示中文文本
- 支持中英文语言切换

### 2. 系统说明书页面完整翻译
- 所有文本都支持多语言显示
- 中文界面显示完整的中文翻译
- 英文界面显示完整的英文翻译

### 3. 翻译键结构
```
common.manual -> 系统说明书 / System Manual
manual.title -> 系统说明书 / System Manual
manual.subtitle -> 电子资源规划系统使用指南 / Electronic Resource Planning System User Guide
manual.admin_features -> 管理员功能 / Administrator Features
manual.teacher_features -> 教师功能 / Teacher Features
manual.student_features -> 学生功能 / Student Features
manual.common_features -> 通用功能 / Common Features
manual.help_support -> 帮助与支持 / Help & Support
```

## 测试验证

### 1. 翻译功能测试
```bash
C:\xampp\php\php.exe -r "require_once 'public/inc/language_config.php'; echo t('common.manual') . PHP_EOL;"
# 输出: 系统说明书
```

### 2. 界面显示测试
- 管理员仪表板个人资料下拉菜单显示"系统说明书"
- 教师仪表板个人资料下拉菜单显示"系统说明书"
- 系统说明书页面所有文本正确显示

## 技术细节

### 1. 翻译键命名规范
- 使用点号分隔的层级结构：`category.key`
- 基础翻译键放在 `common` 分类下
- 功能特定翻译键放在对应的功能分类下

### 2. 翻译文件结构
- `public/inc/translations/zh.php` - 中文翻译
- `public/inc/translations/en.php` - 英文翻译
- 使用 PHP 数组结构存储翻译键值对

### 3. 翻译函数
- 使用 `t()` 函数进行翻译
- 支持嵌套键访问：`t('manual.admin_features')`
- 支持默认值：`t('common.manual', '系统说明书')`

## 总结

通过添加完整的翻译键定义，解决了"系统说明书"菜单项显示翻译键而不是实际文本的问题。现在系统支持完整的中英文界面，所有文本都能正确显示翻译后的内容。
