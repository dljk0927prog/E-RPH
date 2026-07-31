# 课程数据问题修复指南

## 问题描述
课程管理页面显示"没有找到符合条件的课程"，这是因为系统升级到多老师支持功能后，数据库结构发生了变化，但数据迁移可能没有完成。

## 问题原因
1. **缺少 `course_teachers` 表** - 新的多老师功能需要这个关系表
2. **数据未迁移** - 原有的课程-老师关系数据可能还在旧的 `teacher_id` 字段中
3. **查询不兼容** - 页面查询语句依赖新的表结构

## 解决步骤

### 方法1：自动修复（推荐）

1. **访问修复工具**
   ```
   在浏览器中访问：http://your-domain/fix_courses_issue.php
   ```

2. **运行修复脚本**
   - 脚本会自动检测数据库状态
   - 创建缺失的表
   - 迁移现有数据
   - 验证修复结果

3. **查看结果**
   - 修复完成后返回课程管理页面
   - 应该能看到所有课程数据

### 方法2：手动修复

如果自动修复失败，可以手动执行以下SQL：

```sql
-- 1. 创建course_teachers表
CREATE TABLE IF NOT EXISTS course_teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_course_teacher (course_id, teacher_id)
);

-- 2. 迁移现有数据（如果courses表有teacher_id字段）
INSERT IGNORE INTO course_teachers (course_id, teacher_id)
SELECT id, teacher_id FROM courses WHERE teacher_id IS NOT NULL;

-- 3. 备份原teacher_id字段
ALTER TABLE courses CHANGE COLUMN teacher_id old_teacher_id INT;
```

### 方法3：使用诊断工具

1. **运行诊断**
   ```
   访问：http://your-domain/diagnose_courses.php
   ```

2. **查看详细信息**
   - 检查表结构
   - 验证数据完整性
   - 测试查询语句

3. **使用简化查看工具**
   ```
   访问：http://your-domain/public/simple_course_view.php
   ```

## 验证修复

修复完成后，请验证以下功能：

### ✅ 课程管理页面
- [ ] 能显示所有课程
- [ ] 能看到分配的老师
- [ ] 搜索功能正常
- [ ] 筛选功能正常

### ✅ 添加课程页面
- [ ] 能选择多个老师
- [ ] 能成功创建课程
- [ ] 新课程能正常显示

### ✅ 编辑课程页面
- [ ] 能修改老师分配
- [ ] 能保存更改
- [ ] 更改能正确显示

### ✅ 老师相关页面
- [ ] 老师仪表板统计正确
- [ ] 我的课程页面正常
- [ ] 教案页面能选择课程

## 常见问题

### Q1: 修复后仍然看不到课程
**解决方案：**
1. 检查数据库中是否真的有课程数据
2. 确认用户权限正确
3. 查看浏览器控制台是否有JS错误
4. 检查PHP错误日志

### Q2: 老师选择功能不工作
**解决方案：**
1. 确认有老师用户（role='teacher'）
2. 检查course_teachers表是否有数据
3. 验证外键约束正常

### Q3: 统计数据不正确
**解决方案：**
1. 重新运行修复脚本
2. 检查所有相关页面的查询语句
3. 确认数据迁移完整

## 预防措施

1. **定期备份数据库**
2. **在测试环境先验证更改**
3. **保留旧字段作为备份**
4. **记录所有数据库变更**

## 技术细节

### 新表结构
```sql
course_teachers (
    id - 主键
    course_id - 课程ID（外键）
    teacher_id - 老师ID（外键）
    created_at - 创建时间
)
```

### 查询变更
```sql
-- 旧查询
SELECT c.*, u.name as teacher_name 
FROM courses c 
LEFT JOIN users u ON c.teacher_id = u.id

-- 新查询
SELECT c.*, GROUP_CONCAT(u.name) as teacher_names
FROM courses c 
LEFT JOIN course_teachers ct ON c.id = ct.course_id
LEFT JOIN users u ON ct.teacher_id = u.id 
GROUP BY c.id
```

## 联系支持

如果以上方法都无法解决问题，请：

1. 保存错误截图
2. 导出相关数据库表结构
3. 记录具体的错误信息
4. 联系技术支持

---

**重要提醒：** 在执行任何数据库操作前，请务必备份数据库！
