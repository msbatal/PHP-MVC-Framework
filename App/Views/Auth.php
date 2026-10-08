<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Single view for the Auth controller - $mode (set by the controller
 * before require_once'ing this file) picks 'login', 'register',
 * 'verifyemail', 'verify2fa', 'forgotpassword', or 'resetpassword' - one
 * view file handles every screen for this controller (see App/Views/README.md
 * for the pattern). Static copy is translated via _t() against
 * App/Lang/auth.{lang}.json (merged into the same lookup table as
 * lang.{lang}.json by SunLocal, see App/Lang/README.md).
 *
 * Every view in this template is self-contained (no shared Header.php/
 * Footer.php) - see App/Views/README.md for why. Deliberately unstyled
 * (no CSS framework classes): this is a blank starting point. Add your
 * own classes/design once you've picked how this project should look.
 */
$currentLang = strtolower($GLOBALS['sunApp']->routes[0]);
$currentPage = isset($GLOBALS['sunApp']->routes[1]) ? strtolower($GLOBALS['sunApp']->routes[1]) : 'home';
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
 * before a redirect (e.g. after a successful login, see login()/
 * verify2fa() below) and the very next page load pops it here -
 * one-shot, cleared immediately so a page refresh never re-shows it.
 * Message text comes from App/Lang/auth.{lang}.json via _tr() at the
 * point the controller sets it, so it's already translated here.
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

  <?php if ($mode === 'login'): ?>

  <h1><?php _t('login.title'); ?></h1>
  <?php if (isset($_GET['error'])): ?>
  <p class="error"><?php _t('login.error'); ?></p>
  <?php endif; ?>
  <form method="post" action="">
    <p>
      <label><?php _t('auth.email_label'); ?></label><br>
      <input type="email" name="email" required autofocus>
    </p>
    <p>
      <label><?php _t('auth.password_label'); ?></label><br>
      <input type="password" name="password" required>
    </p>
    <input type="hidden" name="csrf" value="<?php _c(); ?>">
    <button type="submit"><?php _t('login.submit'); ?></button>
  </form>
  <p><a href="<?php echo SYS_BASEURL . '/' . $currentLang . '/Auth/forgotpassword'; ?>"><?php _t('login.forgot_link'); ?></a></p>
  <p><?php _t('login.no_account'); ?> <a href="<?php echo SYS_BASEURL . '/' . $currentLang . '/Auth/register'; ?>"><?php _t('login.signup_link'); ?></a></p>

  <?php elseif ($mode === 'register'): ?>

  <h1><?php _t('register.title'); ?></h1>
  <?php if (isset($_GET['error'])): ?>
  <p class="error">
    <?php
    if ($_GET['error'] === 'exists') {
      _t('register.error_exists');
    } elseif ($_GET['error'] === 'mismatch') {
      _t('auth.password_mismatch');
    } else {
      _t('register.error_generic');
    }
    ?>
  </p>
  <?php endif; ?>
  <form method="post" action="">
    <p>
      <label><?php _t('register.first_name_label'); ?></label><br>
      <input type="text" name="first_name" required autofocus>
    </p>
    <p>
      <label><?php _t('register.last_name_label'); ?></label><br>
      <input type="text" name="last_name" required>
    </p>
    <p>
      <label><?php _t('auth.email_label'); ?></label><br>
      <input type="email" name="email" required>
    </p>
    <p>
      <label><?php _t('auth.password_label'); ?></label><br>
      <input type="password" name="password" minlength="8" required>
    </p>
    <p>
      <label><?php _t('auth.confirm_password_label'); ?></label><br>
      <input type="password" name="confirm_password" minlength="8" required>
    </p>
    <input type="hidden" name="csrf" value="<?php _c(); ?>">
    <button type="submit"><?php _t('register.submit'); ?></button>
  </form>
  <p><?php _t('register.have_account'); ?> <a href="<?php echo SYS_BASEURL . '/' . $currentLang . '/Auth/login'; ?>"><?php _t('register.login_link'); ?></a></p>

  <?php elseif ($mode === 'verifyemail'): ?>

  <h1><?php _t('verifyemail.title'); ?></h1>
  <p><?php _t('verifyemail.subtitle'); ?></p>
  <?php if (isset($_GET['error'])): ?>
  <p class="error"><?php _t('verifyemail.error'); ?></p>
  <?php endif; ?>
  <?php if (isset($_GET['resent'])): ?>
  <p class="success"><?php _t('verifyemail.resent'); ?></p>
  <?php endif; ?>
  <form method="post" action="">
    <p>
      <label><?php _t('auth.verification_code_label'); ?></label><br>
      <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus>
    </p>
    <input type="hidden" name="csrf" value="<?php _c(); ?>">
    <button type="submit"><?php _t('verifyemail.submit'); ?></button>
  </form>
  <form method="post" action="<?php echo SYS_BASEURL . '/' . $currentLang . '/Auth/resendverification'; ?>">
    <input type="hidden" name="csrf" value="<?php _c(); ?>">
    <button type="submit"><?php _t('verifyemail.resend_button'); ?></button>
  </form>

  <?php elseif ($mode === 'forgotpassword'): ?>

  <h1><?php _t('forgotpassword.title'); ?></h1>
  <p><?php _t('forgotpassword.subtitle'); ?></p>
  <form method="post" action="">
    <p>
      <label><?php _t('auth.email_label'); ?></label><br>
      <input type="email" name="email" required autofocus>
    </p>
    <input type="hidden" name="csrf" value="<?php _c(); ?>">
    <button type="submit"><?php _t('forgotpassword.submit'); ?></button>
  </form>
  <p><a href="<?php echo SYS_BASEURL . '/' . $currentLang . '/Auth/login'; ?>"><?php _t('forgotpassword.back_link'); ?></a></p>

  <?php elseif ($mode === 'resetpassword'): ?>

  <h1><?php _t('resetpassword.title'); ?></h1>
  <?php if (!$resetTokenValid): ?>
  <p class="error"><?php _t('resetpassword.invalid'); ?></p>
  <p><a href="<?php echo SYS_BASEURL . '/' . $currentLang . '/Auth/forgotpassword'; ?>"><?php _t('resetpassword.request_new_link'); ?></a></p>
  <?php else: ?>
  <p><?php _t('resetpassword.subtitle'); ?></p>
  <?php if (isset($_GET['error'])): ?>
  <p class="error"><?php echo $_GET['error'] === 'mismatch' ? _tr('auth.password_mismatch') : _tr('resetpassword.error_generic'); ?></p>
  <?php endif; ?>
  <form method="post" action="">
    <p>
      <label><?php _t('resetpassword.new_password_label'); ?></label><br>
      <input type="password" name="new_password" required autofocus>
    </p>
    <p>
      <label><?php _t('auth.confirm_password_label'); ?></label><br>
      <input type="password" name="confirm_password" required>
    </p>
    <input type="hidden" name="token" value="<?php _e($resetToken); ?>">
    <input type="hidden" name="csrf" value="<?php _c(); ?>">
    <button type="submit"><?php _t('resetpassword.submit'); ?></button>
  </form>
  <?php endif; ?>

  <?php elseif ($mode === 'verify2fa'): ?>

  <h1><?php _t('verify2fa.title'); ?></h1>
  <p><?php _t('verify2fa.subtitle'); ?></p>
  <?php if (isset($_GET['error'])): ?>
  <p class="error"><?php _t('verify2fa.error'); ?></p>
  <?php endif; ?>
  <form method="post" action="">
    <p>
      <label><?php _t('auth.verification_code_label'); ?></label><br>
      <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus>
    </p>
    <input type="hidden" name="csrf" value="<?php _c(); ?>">
    <button type="submit"><?php _t('verify2fa.submit'); ?></button>
  </form>

  <?php endif; ?>

</main>

<!-- Add your own script(s), e.g.: -->
<!-- <script src="<?php echo SYS_BASEURL; ?>/Public/js/site.js"></script> -->
</body>
</html>
