# Public/Admin/ - static files for the admin panel only

Same exception rule as `Public/README.md`: everything under `Public/` is
served directly, no PHP routing involved. This subfolder exists purely to
keep the panel's own CSS/JS/images/fonts out of the front end's
`Public/css`/`Public/js`/`Public/img` - nothing framework-level treats
`Admin/` specially here, it's just a naming convention `Public/README.md`'s
`.htaccess` rule already covers (`RewriteRule ^Public/ - [L]` matches any
path under `Public/`, at any depth).

```
Public/Admin/css/admin.css
Public/Admin/js/admin.js
Public/Admin/img/logo.png
Public/Admin/fonts/your-font.woff2
```

Reference them with `SYS_BASEURL`, same as the front end:

```php
<link rel="stylesheet" href="<?php echo SYS_BASEURL; ?>/Public/Admin/css/admin.css">
<script src="<?php echo SYS_BASEURL; ?>/Public/Admin/js/admin.js"></script>
```

Every view in `App/Views/Admin/` has a commented-out example of exactly
this, ready to uncomment once you add your first admin stylesheet/script.

This folder ships with just `css/`, `js/`, `img/`, `fonts/` - empty, no
framework-mandated CSS library or JS framework baked in, same as the
front end's `Public/css`/`Public/js`/`Public/img`. Add more subfolders
here the same way if the panel needs them (e.g. `Public/Admin/vendor/`)
- nothing beyond the `.htaccess` rule above needs to know about them.
