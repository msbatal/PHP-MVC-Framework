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
  <p><a href="<?php echo $logoutUrl; ?>"><?php _t('admin.dashboard.logout_link'); ?></a></p>
</main>

<!-- <script src="<?php echo SYS_BASEURL; ?>/Public/Admin/js/admin.js"></script> -->
</body>
</html>
