<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Admin panel login form - matches App/Controllers/Admin/Login.php's show()
 * method (GET renders this, POST is handled before this file is ever
 * reached). Static copy is translated via _t() against
 * App/Lang/admin.{lang}.json (merged into the same lookup table as
 * lang.{lang}.json by SunLocal, see App/Lang/README.md).
 *
 * Self-contained like every other view in this template (no shared
 * Header.php/Footer.php) - see App/Views/README.md for why. Deliberately
 * unstyled: add your own admin stylesheet/script under Public/Admin/ once
 * you've picked how the panel should look (see Public/Admin/README.md).
 */
$currentLang = strtolower($GLOBALS['sunApp']->routes[0]);
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php _t('admin.meta.title'); ?></title>
<link rel="icon" href="<?php echo SYS_BASEURL; ?>/Public/img/favicon.png">
<!-- Add your own admin stylesheet(s), e.g.: -->
<!-- <link rel="stylesheet" href="<?php echo SYS_BASEURL; ?>/Public/Admin/css/admin.css"> -->
</head>
<body>

<?php
/**
 * Flash-notify pattern (see App/Views/README.md) - Admin/Logout.php sets
 * this before redirecting back here on a successful logout.
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

  <h1><?php _t('admin.login.title'); ?></h1>
  <?php if (isset($_GET['error'])): ?>
  <p class="error"><?php _t('admin.login.error'); ?></p>
  <?php endif; ?>
  <form method="post" action="">
    <p>
      <label><?php _t('admin.email_label'); ?></label><br>
      <input type="email" name="email" required autofocus>
    </p>
    <p>
      <label><?php _t('admin.password_label'); ?></label><br>
      <input type="password" name="password" required>
    </p>
    <input type="hidden" name="csrf" value="<?php _c(); ?>">
    <button type="submit"><?php _t('admin.login.submit'); ?></button>
  </form>

</main>

<!-- Add your own admin script(s), e.g.: -->
<!-- <script src="<?php echo SYS_BASEURL; ?>/Public/Admin/js/admin.js"></script> -->
</body>
</html>
