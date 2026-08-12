# Public/ - the only directory served as static files

Everything else in this project (`App/`, `Core/`, `System/`, `database/`)
is explicitly blocked from direct HTTP access by `.htaccess` (see the
root `README.md`'s security section) - a request for
`/System/SunAuth.php` gets a 403, not the file's source. `Public/` is the
one exception: `.htaccess` has a dedicated rule serving anything that
exists under here directly, no PHP routing involved.

```
RewriteRule ^Public/ - [L]
RewriteCond %{DOCUMENT_ROOT}/Public/$1 -f
RewriteRule (.+) Public/$1 [L]
```

## What goes here

Put your CSS, JS, images, fonts, favicon - anything the browser fetches
directly by URL:

```
Public/css/site.css
Public/js/site.js
Public/img/favicon.png
```

Reference them with `SYS_BASEURL`, never a hardcoded path (so it keeps
working if the app moves subdirectories or domains):

```php
<link rel="stylesheet" href="<?php echo SYS_BASEURL; ?>/Public/css/site.css">
<script src="<?php echo SYS_BASEURL; ?>/Public/js/site.js"></script>
```

Every view (`App/Views/Home.php`, `App/Views/Auth.php`) has a
commented-out example of exactly this - a stylesheet `<link>` in
`<head>` and a script `<script>` tag before `</body>` - ready to
uncomment once you add your first stylesheet/script.

This folder ships empty (just `css/`, `js/`, `img/` subfolders) - there's
no framework-mandated CSS library or JS framework baked in. Pick your
own per project.
