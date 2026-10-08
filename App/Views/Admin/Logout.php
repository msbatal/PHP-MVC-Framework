<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Admin panel logout confirmation - matches App/Controllers/Admin/Logout.php's
 * show() method (GET renders this, POST performs the logout and redirects
 * before this file is ever reached). Linked from App/Views/Admin/Dashboard.php.
 *
 * Self-contained like every other view in this template (no shared
 * Header.php/Footer.php) - see App/Views/README.md for why.
 */
$currentLang = strtolower($GLOBALS['sunApp']->routes[0]);
$dashboardUrl = SYS_BASEURL . '/' . $currentLang . '/Admin/Dashboard';
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
<main>

  <h1><?php _t('admin.logout.title'); ?></h1>
  <p><?php _t('admin.logout.subtitle'); ?></p>
  <form method="post" action="">
    <input type="hidden" name="csrf" value="<?php _c(); ?>">
    <button type="submit"><?php _t('admin.logout.submit'); ?></button>
  </form>
  <p><a href="<?php echo $dashboardUrl; ?>"><?php _t('admin.logout.cancel_link'); ?></a></p>

</main>

<!-- <script src="<?php echo SYS_BASEURL; ?>/Public/Admin/js/admin.js"></script> -->
</body>
</html>
