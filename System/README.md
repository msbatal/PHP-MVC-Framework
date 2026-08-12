# System/ - framework config + the Sun* class library

Two kinds of files here, treated very differently:

- **`Config.php`, `Functions.php`** - this framework's own settings and
  helper functions. Edit freely.
- **`Sun*.php` (10 files)** - general-purpose, framework-agnostic PHP
  classes maintained *outside* this project and dropped in as-is. **Never
  hand-edit these.** If you need different behavior, either configure it
  (most of them take a constructor/config array) or wrap it in your own
  `App/Models/` code. See "Why Sun*.php has no namespace" below before
  you're tempted to add one.

## Config.php

Defines every `SYS_*`/`DB_*` constant (from `.env`, see the root
`README.md`), starts the session, and registers the autoloader. The
settings worth knowing about day to day:

| Constant | Default | What it does |
|---|---|---|
| `SYS_PHPERR` | from `.env` | `true` → raw PHP warnings/notices print to the page. Dev only. |
| `SYS_SYSERR` | from `.env` | `true` → `App/Views/Error.php` shows a technical-details box under the friendly message. Dev only - see `App/Views/README.md`. |
| `SYS_PGCACHE` | `false` | Full-page caching via `SunCache` (see below). Off by default. |
| `SYS_DFLTLANG` | `en` | Fallback language when the URL's language segment isn't in `SYS_LANGUAGES`. |
| `SYS_LANGUAGES` | `['en']` | Every code here needs a matching `App/Lang/lang.{code}.json` **or `SunLocal`'s constructor (in `init.php`, not `parseUrl()`) will 500 the whole site for that language** - see `App/Lang/README.md`. |
| `SYS_HOMEPAGE` | `home` | Controller name used when the URL has no page segment. |
| `SYS_ERRPAGE` | `error` | Controller name for the "page looks like a `.php` probe" guard in `parseUrl()`. |

### The autoloader - read this before adding any new class anywhere

```php
spl_autoload_register(function ($class) {
    // 1. Namespaced class ("App\Controllers\Home", "Core\App", ...):
    //    map the namespace straight to a folder - App\Controllers\Home
    //    -> App/Controllers/Home.php. Works for anything under App/ or Core/.
    // 2. Namespace-less class (all 9 Sun* classes): no "\" to map to a
    //    folder, so it falls back to checking System/{ClassName}.php
    //    directly.
});
```

If you add a new namespaced class (say `App\Services\Billing`), just put
it at `App/Services/Billing.php` - no `require` needed anywhere, the
autoloader finds it the first time something does `new
\App\Services\Billing()`. If you add a new *namespace-less* utility
class of your own, either give it a namespace (then it needs to live
under a matching folder), or drop it directly in `System/` to piggyback
on the same fallback the `Sun*` classes use.

## Functions.php - global helpers

Loaded once in `init.php`, available everywhere without an import.

| Function | Does | Example |
|---|---|---|
| `_t($key, $params = null)` | Echoes a translated string from `App/Lang/*.json` (see `App/Lang/README.md`). **Echoes, doesn't return** - call bare, never `echo _t(...)`. | `<h1><?php _t('home.title'); ?></h1>` |
| `_tr($key, $params = null)` | Same lookup as `_t()`, but **returns** the string instead of echoing. Use this (not `_t()`) anywhere you need the value itself - inside a ternary, building a JSON payload for JS, etc. | `echo $cond ? _tr('a.key') : _tr('b.key');` |
| `_e($input)` | Sanitizes and echoes untrusted output (HTML-escapes via `SunFilter`). Use for any dynamic value going into HTML. | `<td><?php _e($row['name']); ?></td>` |
| `_c()` | Echoes a fresh CSRF token. Put in every POST form. | `<input type="hidden" name="csrf" value="<?php _c(); ?>">` |
| `_a($role = null)` | Returns `true`/`false` - logged in (and, if given, has that role). Handy in views for "show this only if logged in" without touching `$GLOBALS['auth']` directly. | `<?php if (_a()): ?>...<?php endif; ?>` |
| `_w()` | Echoes the current request URL (`SunFunc::getUrl()`). | |
| `_l()` | Echoes the current language code (`routes[0]`, lowercased). | |
| `_u($input)` | Echoes a SEF-formatted URL for the given path in the current language. | |
| `_s($input)` | **Returns** (doesn't echo) a SEF-formatted slug for the given text - the building block for id+slug SEO URLs, see "SunFunc - SEO-friendly id+slug URLs" below. | `$blogUrl . '/show/' . $row['id'] . '-' . _s($row['title'])` |

**The `_t()`-echoes-but-`_tr()`-returns split exists for a real reason,
not just taste:** `_t()` mirrors this framework's older `_e()`/`_c()`
idiom (bare call, prints immediately). But `_t()`'s underlying
`SunLocal::translate()` only ever `return`s on a *failed* lookup (see
`App/Lang/README.md`) - so `json_encode(_t('key'))` silently encodes
nothing on a successful lookup, not the translated string. `_tr()` wraps
the same call in `ob_start()`/`ob_get_clean()` to get the actual value
back. If you ever need a translated string as data (not print it
immediately), you want `_tr()`.

## The Sun* class library

All 9 are deliberately **namespace-less** (no `namespace` declaration),
matching how they're published/maintained on their own outside this
framework. That's a design constraint, not an oversight - see below.

| Class | What it's for |
|---|---|
| `SunAuth` | Full authentication: login/register/logout, sessions, remember-me, password reset, role checks, 2FA (TOTP). Powers `App/Controllers/Auth.php`. |
| `SunDB` | PDO query builder (`select`/`where`/`insert`/`update`/`delete`/`join`/`orderBy`/`paginate`/...). Every model's `$this->pdo` is one of these. |
| `SunFilter` | Input sanitization (`sanitize()`) and validation (`validate()`) - `_e()` uses this under the hood. |
| `SunLocal` | Loads and merges `App/Lang/*.{lang}.json`, powers `_t()`/`_tr()`. |
| `SunMailer` | Thin wrapper over the vendored PHPMailer (`System/Vendor/PHPMailer/`) - SMTP from `.env`. |
| `SunCache` | Opt-in full-page caching (`SYS_PGCACHE`). |
| `SunCaptcha` | Generates/validates an image CAPTCHA. |
| `SunSitemap` | Builds `sitemap.xml`/`sitemap-index.xml`/`robots.txt`. |
| `SunFunc` | CSRF tokens, encryption/decryption, IP lookup, `.env` loading, misc URL helpers. Instantiated in `init.php` as `$functions`. |
| `SunQRCode` | Generates QR codes as SVG or PNG images from a URL or text payload.|

### SunAuth - the one you'll use most

Already wired up as `$GLOBALS['auth']` in `init.php`
(`$auth = new SunAuth($authDb);` - no config override, so it uses its
own defaults: table `users`, identifier `email`, issuer `SunAuth`). Full
worked example of every method below in `App/Controllers/Auth.php`; the
short version:

```php
// Login
if ($GLOBALS['auth']->login($email, $password)) {
    if ($GLOBALS['auth']->isLoggedIn()) {
        // real login - no 2FA pending
    } else {
        // credentials were right, but 2FA is pending - see verify2fa()
        // in App/Controllers/Auth.php for the promotion flow
    }
} else {
    $GLOBALS['auth']->lastError(); // 'Invalid credentials.' / 'Account is not active.' / ...
}

// Register (status defaults to 1/active in the "users" table - pass
// status => 0 yourself if you want an email-verification gate first,
// exactly like App/Controllers/Auth.php::register() does)
$userId = $GLOBALS['auth']->register(['email' => $email, 'password' => $password]);

// Anywhere else in the app
$GLOBALS['auth']->isLoggedIn();      // bool
$GLOBALS['auth']->id();              // current user's id, or null
$GLOBALS['auth']->user();            // current user's full row, or null
$GLOBALS['auth']->hasRole('admin');  // bool
$GLOBALS['auth']->logout();

// Password reset (token goes in a query string - see Core/README.md's
// note on parseUrl() mangling case-sensitive path segments)
$token = $GLOBALS['auth']->createResetToken($email);
$GLOBALS['auth']->verifyResetToken($token);       // returns user id or false
$GLOBALS['auth']->resetPassword($token, $newPw);

// 2FA (TOTP, RFC 6238)
$secret = $GLOBALS['auth']->enableTwoFactor($userId);  // returns ['secret' => ..., 'uri' => 'otpauth://...']
$GLOBALS['auth']->verifyTwoFactorCode($userId, $code); // check a code without touching session state
$GLOBALS['auth']->disableTwoFactor($userId);
```

Override table/identifier/issuer/etc. by passing a config array:
`new SunAuth($authDb, ['table' => 'accounts', 'issuer' => 'MyApp'])`.
See `$config` at the top of `System/SunAuth.php` for every overridable
key (session lifetimes, lockout thresholds, cookie name, ...).

### SunDB - the query builder

`Core\Model::$pdo` is already one of these - call query methods
directly, end every chain with `->run()`:

```php
$this->pdo->select('posts')->where('status', 'published', '=')->orderBy('id', 'desc')->limit(10)->run(); // array of rows
$this->pdo->select('posts')->where('id', $id, '=')->first()->run(); // one row (flat array) or null
$this->pdo->insert('posts', ['title' => $title, 'body' => $body])->run(); // bool
$this->pdo->update('posts', ['title' => $newTitle])->where('id', $id, '=')->run();
$this->pdo->delete('posts')->where('id', $id, '=')->run();
$this->pdo->select('posts')->count(); // int
```

`first()` returns the row directly (not wrapped in an outer array) -
`$row['column']`, not `$row[0]['column']`.

### SunFilter - sanitize/validate

```php
$clean = $GLOBALS['filter']->sanitize($_POST['email'], 'email')->result();
$isValid = $GLOBALS['filter']->validate($input, 'email')->result(); // bool
```
`sanitize()` types: `string`, `float`, `integer`, `url`, `email`,
`special`. `validate()` types: `boolean`, `float`, `integer`, `email`,
`url`, `domain`, `ip`, `mac`.

### SunMailer

```php
$mailer = new \SunMailer();
$mailer->send(
    $toEmail,
    'Subject line',
    '<p>HTML body</p>',
    'Plain-text fallback body',
    ['fromName' => 'My App'] // optional: toName, from, fromName, replyTo, bcc[], attachments[]
);
```
Reads `SMTP_HOST`/`SMTP_USER`/`SMTP_PASS`/`SMTP_PORT`/`SMTP_SECURE`/
`SMTP_FROM_NAME` from `.env`. `send()` never throws - logs to
`php_error.log` and returns `false` on failure, so a missing/broken SMTP
config degrades to "email silently didn't send," not a crashed request.

### SunQRCode

```php
$qr = new SunQRCode();
$qr->setData('https://sunhillint.com') // URL or text payload to encode
   ->setSize(500) // output dimension in px, between 50 and 1000
   ->setFormat('png') // 'svg' or 'png'
   ->setForegroundColor('1a73e8') // HEX color, with or without '#'
   ->setBackgroundColor('#ffffff') // HEX color, with or without '#'
   ->setErrorCorrection('H') // 'L', 'M', 'Q' or 'H'
   ->setTimeout(15); // cURL timeout in seconds, default 10
```
`setSize()`, `setFormat()`, `setForegroundColor()`, `setBackgroundColor()` and `setErrorCorrection()` validate their input and throw an `Exception` if it's out of range or not one of the allowed values, so bad values never reach the request.

```php
$qr = new SunQRCode('https://sunhillint.com');
$data = $qr->generate();
//Returns the raw SVG markup (string) or PNG binary data, fetched from the QR service
```

Use this when you want to handle the raw output yourself, e.g. store it in a database or embed the SVG markup directly into a page.

```php
$qr = new SunQRCode('https://sunhillint.com');
$qr->setFormat('png')->render();
//Sends the correct Content-Type header (image/svg+xml or image/png), echoes the image, and exits
```

Call this from a script that is meant to output nothing but the image itself (e.g. an `<img src="qr.php?...">` endpoint).

```php
$qr = new SunQRCode('https://sunhillint.com');
$saved = $qr->setFormat('png')->saveToFile('qrcodes/sunhillint.png');

if ($saved) {
    echo 'QR code saved successfully!';
}
```

Returns `true` on success, or throws an `Exception` if the file path is invalid or not writable.

### SunCache - full-page caching

Off by default (`SYS_PGCACHE` in `System/Config.php`). When on,
`Core\Controller::cache()` wraps a request in `SunCache`, which buffers
the whole rendered page and writes it to `Public/cache/{controller}_{...}_{hash}.html`
(config in `Config.php`'s `$cacheConfig` - storage time, minification,
etc.). The next request for that same URL is served straight from that
file - no routing, no model queries, no view render at all.

**Turning it on:**

```php
define ('SYS_PGCACHE', true); // System/Config.php
define ('SYS_CHEXCLUDE', ['auth', 'error']); // controller names (lowercase) to never cache
```

`auth` is excluded by default because `App/Views/Auth.php` prints a
per-session CSRF token on every GET form (`_c()`, see `Functions.php`
above) - caching one of those pages would serve one visitor's token to
everyone else, breaking login/register for anyone but the visitor whose
request first wrote the cache file. `error` is excluded because a cached
failure page (a transient DB error, say) would otherwise keep being
served long after the real problem is fixed. Add your own controller
names here for the same reason if you build something similar.

**Cache only ever reflects a logged-out visitor**, automatically -
`Core\Controller::cache()` skips caching entirely when
`$GLOBALS['auth']->isLoggedIn()` is true, on *any* controller, not just
ones in `SYS_CHEXCLUDE`. This matters because `App/Views/Home.php`'s nav
renders differently by login state (Login/Register vs. a Logout button);
without this check, whichever visitor's login state happened to render
a page first would get baked into the cache file for everyone.

**Two bugs worth knowing about if you ever touch this area** (both
worked around in `Core\Controller::cache()`, not in `SunCache.php`
itself - see "Why Sun\*.php has no namespace" below for why):

1. The `SunCache` instance **must** be stored somewhere that outlives
   the `cache()` method call (`Core\Controller::$cache`, not a local
   variable) - its constructor opens an output buffer and its
   `__destruct()` is what writes the cache file, so if the object were a
   plain local variable, PHP would destroy it (and write an empty file)
   the instant `cache()` returns, before the page is even rendered.
2. `SunCache::readCache()`'s cache-HIT path calls `ob_end_flush()`
   without ever calling `ob_start()` on that path, which throws a "no
   buffer to flush" PHP notice. `Core\Controller::cache()` opens its own
   buffer with `ob_start()` right before constructing `SunCache`, purely
   so there's always one for that call to close.
   **If you ever pull a fresh `SunCache.php` from upstream, check
   whether this is still true** - if a future version fixes it upstream,
   the extra `ob_start()` here becomes a harmless no-op, safe to leave.

**Counters under caching (view counts, etc.):** if you build something
that needs to change on *every* real pageview - a blog post's view
count, for example - incrementing it inside the normal controller/model
flow stops working once caching is on: a cache HIT reads the saved file
and `exit()`s before your controller's `show()` ever runs again (see
above), so the value would freeze for the life of the cache entry.

The fix: don't increment it as part of the page render at all. Increment
it from a small, standalone endpoint that the page's own JS pings on
every load - the browser runs that JS regardless of whether the HTML
came from cache or a live render, so the counter keeps moving either
way, and the page render itself stays cacheable.

The endpoint (e.g. `counter.php` at the project root, alongside
`index.php`) deliberately bypasses `Core\App`/`Core\Controller`
entirely - it's a stateless machine endpoint, not a page, so there's no
reason to pay for routing, `SYS_PGCACHE` wrapping, or even
`System/Config.php`'s session start on every ping:

```php
<?php
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    die();
}

require_once (__DIR__ . '/System/SunFunc.php');
SunFunc::loadEnv(__DIR__ . '/.env');
// No autoloader here (that lives in System/Config.php, deliberately
// skipped) - require the two System classes this endpoint needs directly.
require_once (__DIR__ . '/System/SunFilter.php');
require_once (__DIR__ . '/System/SunDB.php');

define('SYS_BASEPATH', __DIR__);
define('DB_HOST', getenv('DB_HOST'));
define('DB_PORT', getenv('DB_PORT'));
define('DB_DBNAME', getenv('DB_DBNAME'));
define('DB_USERNAME', getenv('DB_USERNAME'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));

$filter = new SunFilter();
$db = new SunDB(null, DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DBNAME, DB_PORT);

$page = $filter->sanitize(isset($_GET['pg']) ? $_GET['pg'] : '', 'string')->result();
$id = $filter->sanitize(isset($_POST['id']) ? $_POST['id'] : '', 'integer')->result();

if ($id > 0) {
    if ($page === 'posts') {
        $db->rawQuery('update `posts` set `view_count` = `view_count` + 1 where `id` = ?', [$id])->run();
    }
    // Add more "if ($page === '...')" branches here as more counted
    // pages/tables show up - same shape, one line each.
}
?>
```

The `X-Requested-With` check is a cheap "only respond to an actual AJAX
ping, not a random GET" guard - it's not real security (any HTTP client
can set that header), and it doesn't need to be: the only thing a forged
request could do here is inflate a vanity metric by one, not touch
anything that needs real protection. No CSRF check either, for the same
reason.

The ping goes in the view that shows the counted row, after the value's
already been read for display - this only ever affects the *next*
visitor's count, not what's shown on the page right now:

```html
<script>
fetch('<?php echo SYS_BASEURL; ?>/counter.php?pg=posts', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/x-www-form-urlencoded',
    'X-Requested-With': 'XMLHttpRequest'
  },
  body: 'id=<?php echo (int) $row['id']; ?>'
});
</script>
```

Plain `fetch()`, not a library call - no script tag has to load first,
and the header needs to be set by hand either way (some JS libraries add
`X-Requested-With` to every request automatically, bare `fetch()`
doesn't - and the endpoint above checks for exactly that header, so it
has to be there regardless of what you end up calling it with).

### SunFunc - SEO-friendly id+slug URLs

`Core\App::parseUrl()` `ucfirst()`s every path segment (`Core/README.md`),
which is exactly why the framework's usual advice for a case-sensitive
value is "put it in a query string, not the path" (see the password-reset
token in `App/Controllers/Auth.php`). But a query string is a poor fit
for something you *want* search engines and visitors to see in the URL -
a blog post title, a product name. `SunFunc::sefUrl()` +
`SunFunc::slugId()` (added together, use both or neither) solve that
specific case instead: combine a row's numeric id with a slugified title
into one path segment, id first, so the segment always starts with a
digit and `ucfirst()` has nothing to mangle regardless of what the slug
half looks like.

```php
// Building a link (view or controller) - id + slug in the URL:
$url = $blogUrl . '/show/' . $post['id'] . '-' . _s($post['title']);
// -> ".../blog/show/42-hello-world"

// Reading it back (controller's show() method) - only the id matters:
$postId = isset($this->params[3]) ? $GLOBALS['functions']->slugId($this->params[3]) : false;
$post = $this->model->postById($postId); // slug half never touches the query
```

Because only the leading digits are ever matched against the database,
this is naturally forgiving: `.../blog/show/42` (no slug at all) and
`.../blog/show/42-anything-whatsoever` both resolve to the same row - so
a bookmarked or previously-indexed link never breaks just because a
title was edited later. No new database column is required; the slug is
generated from the title at render time, not stored.

### Why Sun\*.php has no namespace (read this before editing one)

These classes are maintained upstream, independent of this framework,
and dropped into `System/` verbatim. Two consequences:

1. **Don't add `namespace System;`, don't add a leading `\` before
   `Exception`/`PDO`/etc. inside these files.** If you ever pull a fresh
   copy from upstream over one of these files, it should be a clean
   drop-in with zero adaptation needed. The autoloader fallback
   (`Config.php`, above) exists specifically so this works.
2. **Don't add a `set_exception_handler()` call inside any of them.**
   An earlier version of some of these classes did this in their
   constructors - installing a *global* PHP exception handler as a side
   effect of being instantiated. With several such classes in the same
   request, whichever got constructed last silently won, overriding
   `Core\App`'s own handler unpredictably. That's fixed now (`Core\App`
   is the single source of truth - see `Core/README.md`) - keep it that
   way when updating these classes; their `throw new Exception(...)`
   calls are correct and unrelated, leave those exactly as they are.

If you need project-specific behavior from one of these classes, wrap it
in your own `App/Models/` code (see the `SunAuth` example in
`App/Models/Auth.php`) rather than editing the class itself.
