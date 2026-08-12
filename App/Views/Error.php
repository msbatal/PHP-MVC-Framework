<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Site-wide error page - reached two ways:
 *  1. Core\App::catchError() requires this file directly (no redirect, same
 *     URL, correct HTTP status already sent) with $errorType/$errorDebugMessage
 *     already set as local vars. This is the normal path, covering both
 *     classified errors (401/403/404/500 from Controller/Model) and any
 *     otherwise-uncaught exception thrown after Core\App is constructed
 *     (see the handler its constructor installs - Core/README.md explains
 *     the exact boundary, including what's NOT covered).
 *  2. The normal router dispatch (Core\App::parseUrl()'s ".php in URL"
 *     fallback) - $errorType isn't set, defaults to 404 below.
 *
 * Deliberately self-contained (own <head>/<body>, inline CSS instead of a
 * linked stylesheet, and - unlike every other view - no assumption that
 * $GLOBALS['auth']/$GLOBALS['local'] exist): catchError() can be triggered
 * by a failure as early as the DB connection during bootstrap, before those
 * globals are constructed. An error page must not have a dependency on the
 * very systems that might be *why* it's showing, so it builds its own
 * fallback $GLOBALS['filter']/$GLOBALS['local'] below instead of trusting
 * them to already exist. Keep it this way even as you build out your real
 * design - link your stylesheet here too if you want matching branding.
 */
if (!isset($GLOBALS['filter']) || !($GLOBALS['filter'] instanceof SunFilter)) {
    $GLOBALS['filter'] = new SunFilter();
}
$fallbackLang = SYS_DFLTLANG;
if (isset($GLOBALS['sunApp']) && isset($GLOBALS['sunApp']->routes[0]) && in_array(strtolower($GLOBALS['sunApp']->routes[0]), SYS_LANGUAGES, true)) {
    $fallbackLang = strtolower($GLOBALS['sunApp']->routes[0]);
}
if (!isset($GLOBALS['local']) || !($GLOBALS['local'] instanceof SunLocal)) {
    $GLOBALS['local'] = new SunLocal($fallbackLang);
}
$currentLang = $fallbackLang;
$errorType = isset($errorType) && in_array($errorType, [401, 403, 404, 500], true) ? $errorType : 404;
$errorDebugMessage = isset($errorDebugMessage) ? $errorDebugMessage : null;
$homeUrl = SYS_BASEURL . '/' . $currentLang . '/home';
$loginUrl = SYS_BASEURL . '/' . $currentLang . '/Auth/login';
// 401 sends the visitor to log in (the actual fix for that error); every
// other type sends them home. If your app has a members-only area whose
// "home" differs from the public site's, add that branching back here -
// check routes[1] against your own controller name(s) and point $ctaUrl
// at your own landing page.
$ctaUrl = $errorType === 401 ? $loginUrl : $homeUrl;
$ctaKey = $errorType === 401 ? 'nav.login' : 'error.home_button';
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php _t('error.' . $errorType . '.title'); ?></title>
<style>
  body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; text-align: center; color: #1a1a1a; background: #fff; }
  .wrap { max-width: 480px; padding: 2rem; }
  .code { font-size: 3rem; font-weight: bold; opacity: .35; margin-bottom: .5rem; }
  h1 { margin: 0 0 .75rem; font-size: 1.5rem; }
  p { color: #555; margin: 0 0 1.5rem; }
  a.cta { display: inline-block; padding: .6rem 1.5rem; background: #1a1a1a; color: #fff; text-decoration: none; border-radius: .375rem; }
  .debug { margin-top: 2rem; text-align: left; font-size: .85rem; background: #fff3cd; border: 1px solid #ffe69c; border-radius: .375rem; padding: .75rem 1rem; }
</style>
</head>
<body>
<div class="wrap">
  <div class="code"><?php echo (int) $errorType; ?></div>
  <h1><?php _t('error.' . $errorType . '.title'); ?></h1>
  <p><?php _t('error.' . $errorType . '.message'); ?></p>
  <a class="cta" href="<?php echo $ctaUrl; ?>"><?php _t($ctaKey); ?></a>
  <?php if (SYS_SYSERR === true && !empty($errorDebugMessage)): ?>
  <div class="debug">
    <strong><?php _t('error.debug_label'); ?>:</strong> <?php _e($errorDebugMessage); ?>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
