# 多老师支持功能实现指南

## 概述
本功能允许一个课程分配给多个老师，替代了原来的单老师限制。通过创建课程-老师关系表来实现多对多的关系。

## 数据库更改

### 1. 新建关系表
创建了 `course_teachers` 表来存储课程和老师的多对多关系：

```sql
CREATE TABLE course_teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_course_teacher (course_id, teacher_id)
);
```

### 2. 数据迁移
- 将现有 `courses.teacher_id` 数据迁移到 `course_teachers` 表
- 保留原 `teacher_id` 字段为 `old_teacher_id` 以便回滚

### 3. 执行迁移
运行以下脚本完成数据库迁移：
```bash
mysql -u erph_user -p erph < add_multiple_teachers_support.sql
```

## 功能更改

### 1. 课程管理页面 (`course_management.php`)
**更改内容：**
- 课程列表查询改为使用 `course_teachers` 关系表
- 支持按老师筛选（多老师课程也会显示在筛选结果中）
- 课程卡片显示所有分配的老师名称（逗号分隔）

**主要SQL更改：**
```sql
-- 旧查询
SELECT c.*, u.name as teacher_name FROM courses c LEFT JOIN users u ON c.teacher_id = u.id

-- 新查询  
SELECT c.*, 
       GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') as teacher_names,
       GROUP_CONCAT(u.id ORDER BY u.name) as teacher_ids
FROM courses c 
LEFT JOIN course_teachers ct ON c.id = ct.course_id
LEFT JOIN users u ON ct.teacher_id = u.id 
GROUP BY c.id
```

### 2. 添加课程页面 (`add_course.php`)
**更改内容：**
- 将单选老师改为多选复选框
- 表单验证要求至少选择一个老师
- 使用事务确保数据一致性

**界面更改：**
- 老师选择区域改为复选框列表
- 添加滚动条支持大量老师选择
- 美化的选择界面

### 3. 编辑课程页面 (`edit_course.php`)
**更改内容：**
- 支持编辑课程的老师分配
- 显示当前已分配的老师
- 支持添加/移除老师

**功能特点：**
- 预选当前分配的老师
- 支持完全重新分配老师
- 事务保证更新安全

### 4. 老师仪表板 (`teacher_dashboard.php`)
**更改内容：**
- 统计查询改为使用关系表
- 正确显示老师参与的所有课程数量

### 5. 我的课程页面 (`my_courses.php`)
**更改内容：**
- 查询改为使用关系表
- 支持老师查看自己参与的所有课程

## 界面改进

### 1. 老师选择界面
- **样式**：美观的复选框列表
- **功能**：支持滚动，最大高度200px
- **交互**：悬停效果，清晰的标签关联

### 2. 课程卡片
- **显示**：所有分配老师的名称
- **格式**：逗号分隔的老师列表
- **适应**：长名单自动换行

## 兼容性说明

### 1. 向后兼容
- 保留了 `courses.old_teacher_id` 字段用于紧急回滚
- 新旧系统可以并存运行

### 2. 数据安全
- 使用事务确保数据一致性
- 外键约束防止数据不一致
- 唯一约束防止重复分配

## 使用指南

### 1. 管理员操作
1. **创建课程**：选择一个或多个授课老师
2. **编辑课程**：可以随时调整老师分配
3. **查看课程**：可以看到所有分配的老师

### 2. 老师操作
1. **查看课程**：可以看到自己参与的所有课程
2. **统计信息**：正确显示参与的课程数量
3. **功能不变**：其他功能保持原有操作方式

## 测试建议

### 1. 基本功能测试
- 创建单老师课程
- 创建多老师课程
- 编辑课程老师分配
- 删除课程（验证关系表数据也被删除）

### 2. 数据一致性测试
- 验证统计数据正确性
- 验证筛选功能正确性
- 验证老师视图正确性

### 3. 界面测试
- 测试大量老师的选择界面
- 测试长老师名单的显示
- 测试响应式布局

## 故障排除

### 1. 迁移失败
如果迁移脚本执行失败，检查：
- 数据库连接权限
- 现有数据完整性
- 外键约束冲突

### 2. 显示异常
如果页面显示异常，检查：
- 新表是否创建成功
- 数据是否正确迁移
- PHP错误日志

### 3. 回滚方案
如需回滚到单老师模式：
1. 恢复 `courses.teacher_id` 字段
2. 从 `old_teacher_id` 复制数据
3. 删除 `course_teachers` 表

## 未来扩展

### 1. 角色细分
- 主讲老师 vs 助教
- 不同老师的权限级别

### 2. 时间段分配
- 不同时间段的老师分配
- 学期制的老师轮换

### 3. 批量操作
- 批量分配老师到多个课程
- 老师工作量统计和平衡

## 总结

多老师支持功能成功实现了：
- ✅ 数据库结构升级
- ✅ 界面功能完善
- ✅ 向后兼容保证
- ✅ 数据安全保护
- ✅ 用户体验优化

该功能为ERPH系统提供了更灵活的课程管理能力，满足了现代教育环境中多师协作的需求。
