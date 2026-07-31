-- 为users表添加avatar字段
-- 如果字段不存在则添加

ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL COMMENT '用户头像路径';

-- 创建头像上传目录的说明
-- 请确保在public/uploads/avatars/目录存在并可写
