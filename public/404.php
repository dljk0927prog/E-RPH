<?php
require_once __DIR__ . '/inc/language_config.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= t('errors.page_not_found') ?> - ERPH</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="error-page">
    <div class="error-content">
      <h1>404</h1>
      <h2><?= t('errors.page_not_found') ?></h2>
      <p><?= t('errors.page_not_found_message', '抱歉，您访问的页面不存在。') ?></p>
      <a href="../index.php" class="btn"><?= t('common.back') ?><?= t('common.home') ?></a>
    </div>
  </div>
</body>
</html>
