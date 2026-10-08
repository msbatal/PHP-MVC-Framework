<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Placeholder admin dashboard - proves the panel's routing/auth/i18n
 * pipeline works. Reached only when logged in (App/Controllers/Admin/Dashboard.php's
 * $authRequired, see Core/README.md's auth() section). Replace with your
 * real panel content once you've built it out - see
 * App/Controllers/Admin/README.md for how to add more admin pages
 * following this same controller/model/view trio pattern.
 *
 * Self-contained like every other view in this template (no shared
 * Header.php/Footer.php) - see App/Views/README.md for why.
 */
$currentLang = strtolower($GLOBALS['sunApp']->routes[0]);
$logoutUrl = SYS_BASEURL . '/' . $currentLang . '/Admin/Logout';
$adminUser = $GLOBALS['auth']->user();
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php _t('admin.meta.title'); ?></title>
<link rel="icon" href="<?php echo SYS_BASEURL; ?>/Public/img/favicon.png">
<!-- <link rel="stylesheet" href="<?php echo SYS_BASEURL; ?>/Public/Admin/css/admin.css"> -->
</head>
<body>

<?php
/**
 * Flash-notify pattern (see App/Views/README.md) - Admin/Login.php sets
 * this before redirecting here on a successful login.
 */
if (!empty($_SESSION['flash_notify'])) {
    $flash = $_SESSION['flash_notify'];
    unset($_SESSION['flash_notify']);
    ?>
<div class="flash flash-<?php _e($flash['type']); ?>"><?php _e($flash['message']); ?></div>
    <?php
}
?>

<main>
  <h1><?php _t('admin.dashboard.title'); ?></h1>
  <?php if (!empty($adminUser)): ?>
  <p><?php _t('admin.dashboard.welcome', [$adminUser['email']]); ?></p>
  <?php else: ?>
  <p><?php _t('admin.dashboard.welcome_generic'); ?></p>
  <?php endif; ?>
  <?php if (empty($stats)): ?>
  <p><?php _t('admin.dashboard.no_stats'); ?></p>
  <?php else: ?>
  <section>
    <h2><?php _t('admin.dashboard.traffic_title'); ?></h2>
    <ul>
      <li><?php _t('admin.dashboard.visits'); ?>: <strong><?php echo (int) $stats['summary']['visits']; ?></strong></li>
      <li><?php _t('admin.dashboard.pageviews'); ?>: <strong><?php echo (int) $stats['summary']['pageviews']; ?></strong></li>
      <li><?php _t('admin.dashboard.visitors'); ?>: <strong><?php echo (int) $stats['summary']['visitors']; ?></strong></li>
      <li><?php _t('admin.dashboard.bounce_rate'); ?>: <strong><?php echo (float) $stats['summary']['bounce_rate']; ?>%</strong></li>
      <li><?php _t('admin.dashboard.online'); ?>: <strong><?php echo (int) $stats['online']; ?></strong></li>
    </ul>
    <?php if (empty($stats['sources'])): ?>
    <p><?php _t('admin.dashboard.no_data'); ?></p>
    <?php else: ?>
    <h3><?php _t('admin.dashboard.sources_title'); ?></h3>
    <ul>
      <?php foreach ($stats['sources'] as $source): ?>
      <li><?php _e($source['name']); ?> / <?php _e($source['medium']); ?>: <strong><?php echo (int) $source['visits']; ?></strong></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <?php if (!empty($stats['pages'])): ?>
    <h3><?php _t('admin.dashboard.pages_title'); ?></h3>
    <ul>
      <?php foreach ($stats['pages'] as $page): ?>
      <li><?php _e($page['path']); ?>: <strong><?php echo (int) $page['views']; ?></strong></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
  <?php endif; ?>
  <p><a href="<?php echo $logoutUrl; ?>"><?php _t('admin.dashboard.logout_link'); ?></a></p>
</main>

<!-- <script src="<?php echo SYS_BASEURL; ?>/Public/Admin/js/admin.js"></script> -->
</body>
</html>
