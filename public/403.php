<?php
require_once __DIR__ . '/inc/language_config.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= t('errors.access_denied') ?> - ERPH</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="error-page">
    <div class="error-content">
      <h1>403</h1>
      <h2><?= t('errors.access_denied') ?></h2>
      <p><?= t('errors.access_denied_message', '抱歉，您没有权限访问此页面。') ?></p>
      <a href="../index.php" class="btn"><?= t('common.back') ?><?= t('common.home') ?></a>
    </div>
  </div>
</body>
</html>
