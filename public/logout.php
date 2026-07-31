<?php
require_once __DIR__ . '/inc/session_config.php';
// 登出不需要语言配置，直接销毁会话并重定向
session_destroy();
header('Location: login_roles.php');
exit;
