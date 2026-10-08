<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Placeholder homepage - proves the routing/DB/i18n pipeline works.
 * Replace with your real landing page. See App/Views/README.md for the
 * one-view-per-controller convention this file follows.
 *
 * Every view in this template is self-contained (no shared Header.php/
 * Footer.php) - see App/Views/README.md for why. All visible text goes
 * through _t() against App/Lang/lang.{code}.json (App/Lang/README.md) -
 * _t() ECHOES internally, so it's called bare, never wrapped in echo.
 */
$currentLang = strtolower($GLOBALS['sunApp']->routes[0]);
$currentPage = isset($GLOBALS['sunApp']->routes[1]) ? strtolower($GLOBALS['sunApp']->routes[1]) : 'home';
// Full remainder of the route (controller/method/extra params), lowercased -
// used to rebuild the URL when switching language so it lands on the same
// page instead of just "/{lang}/{page}" (which drops the method segment and
// 404s if the controller has no default show() method).
$currentPath = implode('/', array_map('strtolower', array_slice($GLOBALS['sunApp']->routes, 1))) ?: 'home';
$homeUrl = SYS_BASEURL . '/' . $currentLang . '/home';
$loginUrl = SYS_BASEURL . '/' . $currentLang . '/Auth/login';
$registerUrl = SYS_BASEURL . '/' . $currentLang . '/Auth/register';
$isLoggedIn = $GLOBALS['auth']->isLoggedIn();
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php _t('meta.title'); ?></title>
<link rel="icon" href="<?php echo SYS_BASEURL; ?>/Public/img/favicon.png">
<!-- Add your own stylesheet(s), e.g.: -->
<!-- <link rel="stylesheet" href="<?php echo SYS_BASEURL; ?>/Public/css/site.css"> -->
</head>
<body>
<nav>
  <a href="<?php echo $homeUrl; ?>"><?php _t('nav.home'); ?></a>

  <?php if ($isLoggedIn): ?>
  <form method="post" action="<?php echo SYS_BASEURL . '/' . $currentLang . '/Auth/logout'; ?>" id="logoutForm" style="display:inline;">
    <input type="hidden" name="csrf" value="<?php _c(); ?>">
    <button type="submit"><?php _t('nav.logout'); ?></button>
  </form>
  <?php else: ?>
  <a href="<?php echo $loginUrl; ?>"><?php _t('nav.login'); ?></a>
  <a href="<?php echo $registerUrl; ?>"><?php _t('nav.register'); ?></a>
  <?php endif; ?>

  <span>
    <?php foreach (SYS_LANGUAGES as $code): ?>
    <a href="<?php echo SYS_BASEURL . '/' . $code . '/' . $currentPath; ?>"<?php echo $code === $currentLang ? ' aria-current="true"' : ''; ?>><?php echo strtoupper($code); ?></a>
    <?php endforeach; ?>
  </span>
</nav>

<?php
/**
 * Flash-notify pattern: a controller sets $_SESSION['flash_notify']
 * before a redirect (e.g. after a successful login - see
 * App/Controllers/Auth.php) and the very next page load pops it here -
 * one-shot, cleared immediately so a page refresh never re-shows it.
 * Message text comes from App/Lang/*.json via _tr() at the point the
 * controller sets it, so it's already translated by the time it lands
 * here. Plain/unstyled on purpose - wire up your own toast/alert lib by
 * replacing this block's markup once you've picked one.
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
  <h1><?php _t('home.title'); ?></h1>
  <p><?php _t('home.subtitle'); ?></p>
</main>

<!-- Add your own script(s), e.g.: -->
<!-- <script src="<?php echo SYS_BASEURL; ?>/Public/js/site.js"></script> -->
</body>
</html>
