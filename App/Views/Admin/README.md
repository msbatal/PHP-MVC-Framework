# App/Views/Admin/ - one file per admin controller, not per screen

Same rules as `App/Views/README.md`: every file here is
`App/Views/Admin/{Name}.php`, matching a controller of the same name in
`App/Controllers/Admin/`. Unlike the front end's `Auth.php` (one view,
several `$mode` screens), each admin page in this template is small
enough to be its own single-screen view - `Login.php`, `Logout.php`,
`Dashboard.php` - but nothing stops a bigger admin controller from using
the same `$mode`-branching pattern the front end does; see
`App/Views/README.md` for it.

## Self-contained, unstyled - same as the front end

No shared `Header.php`/`Footer.php` here either, for the same reasoning
as the front end (`App/Views/README.md`): each view opens its own
`<html>`/`<head>`/`<body>` and closes them at the bottom. Deliberately
unstyled - add your own admin stylesheet `<link>`/`<script>` (both views
have a commented-out example pointing at `Public/Admin/css`/`Public/Admin/js`,
see `Public/Admin/README.md`) once you've picked how the panel should look.

## The flash-message block

Same pattern as the front end's (`App/Views/README.md`): `Login.php` sets
`$_SESSION['flash_notify']` on a successful login before redirecting to
`Dashboard.php`, and `Logout.php` sets it before redirecting back to
`Login.php`. Both destination views read + clear it right after `<body>`:

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

Always build the message via `_tr()` against `App/Lang/admin.{lang}.json`,
not a hardcoded string - see `App/Lang/README.md`.

## Translation keys

Everything visible in this folder is namespaced under the `admin.` prefix
in `App/Lang/admin.{lang}.json`, kept separate from the front end's
`lang.*`/`auth.*` keys even where the concept overlaps (`admin.email_label`
vs. `auth.email_label`) - the panel's copy can diverge from the front
end's without touching either file.
