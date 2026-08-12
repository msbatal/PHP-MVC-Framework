# App/Views/ - one file per controller, not per screen

Every file here is `App/Views/{Name}.php`, matching a controller of the
same name. **One view file handles every method/screen of that
controller** - there's no separate view-per-action. The controller sets
a local variable (conventionally `$mode` or `$section`) before its
`require_once`, and the view branches on it:

```php
// In the controller:
$mode = 'login';
require_once ($this->view);

// In the view (App/Views/Auth.php):
<?php if ($mode === 'login'): ?>
  ...login form...
<?php elseif ($mode === 'register'): ?>
  ...register form...
<?php endif; ?>
```

See `App/Views/Auth.php` for the full pattern across six modes
(`login`/`register`/`verifyemail`/`verify2fa`/`forgotpassword`/`resetpassword`).
`App/Views/Home.php` is the trivial case - one screen, no `$mode` needed
at all.

Admin panel views follow the same rule one level deeper, under
`App/Views/Admin/` - see `App/Views/Admin/README.md`.

## Every view is self-contained - no shared Header.php/Footer.php

There's no shared include for the page shell. Each view (`Home.php`,
`Auth.php`) opens its own `<html>`/`<head>`/`<body>`, renders its own
nav (home link, login-aware login/register-or-logout, language
switcher), and closes `</body>`/`</html>` at the bottom - the same
handful of lines duplicated at the top and bottom of each file rather
than factored into a shared require. For a two-view template that's a
deliberate simplicity trade-off: one file to read top-to-bottom per
page, no jumping between three files to see what actually renders. If
your project grows past a few views and the duplication starts hurting,
that's the point where pulling the shell back out into a shared include
(or a tiny render-a-partial helper) earns its keep - nothing in the
framework assumes one way or the other.

**Deliberately unstyled** - no CSS framework, no design opinion. That's
intentional for a blank template: add your own stylesheet `<link>` in
`<head>` and script `<script>` tag before `</body>` (both views have a
commented-out example in each spot) once you've picked how the project
should look, rather than fighting inherited framework styling.

## The flash-message block

Both `Home.php` and `Auth.php` have an identical block right after
`</nav>`:

```php
<?php
if (!empty($_SESSION['flash_notify'])) {
    $flash = $_SESSION['flash_notify'];
    unset($_SESSION['flash_notify']);
    ?>
<div class="flash flash-<?php _e($flash['type']); ?>"><?php _e($flash['message']); ?></div>
    <?php
}
?>
```

A controller sets `$_SESSION['flash_notify'] = ['type' => 'success',
'message' => _tr('some.key')]` before a `header('Location: ...')`
redirect (see every branch in `App/Controllers/Auth.php` that redirects
after a state change); the next page load reads it, clears it
immediately (one-shot - a refresh never re-shows it), and prints it as
plain unstyled HTML. Swap the `<div>` for your own toast/alert call once
you've picked a library - the read/clear logic doesn't need to change.
Always set the message via `_tr()` against `App/Lang/*.json`, not a
hardcoded string, so it stays translated like everything else (see
`App/Lang/README.md`).

## `_t()` / `_e()` / `_c()` in views

```php
<h1><?php _t('home.title'); ?></h1>                <!-- translated string, echoes -->
<td><?php _e($row['user_supplied_value']); ?></td> <!-- HTML-escaped dynamic value -->
<input type="hidden" name="csrf" value="<?php _c(); ?>"> <!-- every POST form needs this -->
```

Full helper reference in `System/README.md`. Translation keys come from
`App/Lang/*.json` - see `App/Lang/README.md` for the naming convention
and how to add a new language.

## Error.php - the one view that breaks the rules above

`App/Views/Error.php` doesn't render a nav or a flash-message block like
`Home.php`/`Auth.php` do, and has its own inline `<style>` instead of a
linked stylesheet. This is deliberate, not an oversight:
`Core\App::catchError()` (`Core/README.md`) can fire from any point
between `$sunApp`'s own construction and the rest of `init.php`
finishing - which covers `$filter`/`$captcha`/`$license`/`$authDb`/
`$auth`/`$local` all still being constructed, i.e. `$GLOBALS['auth']`
and `$GLOBALS['local']` may not exist yet. Every other view calls
`$GLOBALS['auth']->isLoggedIn()` unconditionally in its nav, which
assumes a fully-booted app. An error page must not depend on the very
systems that might be *why* it's showing - so it defensively constructs
its own minimal `SunFilter`/`SunLocal` instances if the globals aren't
there yet, and never touches `$GLOBALS['auth']` at all.

(There's an even earlier window - before `$sunApp` itself exists, e.g.
`SunFunc::loadEnv()` failing because `.env` is missing - that this
*doesn't* cover at all; nothing does, since no handler is installed yet.
See `Core/README.md`'s error-handling section for that boundary.)

If you want the error page to visually match the rest of your site,
link your real stylesheet in its `<head>` too - just keep it
self-contained, without a dependency on `$GLOBALS['auth']`.

## Reading GET/POST directly in a view

You'll see this in `App/Views/Auth.php` (`isset($_GET['error'])`) - it's
fine for simple "was there an error" flags on the *current* request, but
anything crossing a redirect should go through the flash-message pattern
in `App/Controllers/README.md` instead, not a query string you have to
thread through every link.
