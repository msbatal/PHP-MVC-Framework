# Core/ - the framework's own machinery

Three files. You will almost never edit these once a project is running -
they're the dispatch/routing/error-handling engine that every request goes
through before your `App/` code ever runs. Read this once to understand
*why* your controllers behave the way they do; don't add app-specific logic
here.

## App.php - `\Core\App`

Created once, first thing, in `init.php` (`$sunApp = new \Core\App();`).
Two jobs: own the parsed route, and be the single place errors get handled.

### Routing: `parseUrl($rawPath)`

Called once in `init.php` with whatever's in the `?pg=` query string
(`.htaccess` rewrites every request to `index.php?pg=<path>`, see the root
README). Splits the path on `/` into `$this->routes[]`:

```
URL:    /en/blog/show/42
routes: [0]=en  [1]=blog  [2]=show  [3]=42
```

- `routes[0]` - language code. Must be in `SYS_LANGUAGES`
  (`System/Config.php`) or it silently falls back to `SYS_DFLTLANG`.
- `routes[1]` - controller name (case-insensitive at dispatch time, but
  `parseUrl()` `ucfirst()`s it - **this matters**: never put a
  case-sensitive value like a hex token or hash in a path segment, it will
  come out mangled. Query string params are untouched by this, e.g.
  `App/Controllers/Auth.php`'s password-reset token is passed as
  `?token=...`, not `/resetpassword/{token}`, specifically because of this.
  If you actually *want* the value visible in the path (a blog post
  title, for SEO), see `System/README.md`'s "SunFunc - SEO-friendly
  id+slug URLs" - a numeric id prefix sidesteps the mangling entirely,
  since `ucfirst()` only ever touches a leading letter.
- `routes[2]` - method name. Defaults to `show` if absent (a bare
  `/en/blog` calls `Blog::show()`).
- `routes[3+]` - whatever's left, positional params your controller reads
  itself (see `App/Controllers/README.md`'s Auth example - it doesn't use
  these, but `Panel::builder()` in the project this template came from
  read `$this->params[3]` for a widget id).
- Empty path, or the page segment literally being the string `Index` →
  routes to `SYS_HOMEPAGE`.
- A page segment containing `.php` → routes to `SYS_ERRPAGE` (`error`) -
  a defensive guard against someone probing for `.php` files by URL.

### Error handling: `catchError($message, $type = 500)`

The **only** way errors are surfaced to a visitor in this framework. Call
it instead of throwing directly whenever you hit a real "stop, this
request can't continue" condition:

```php
$GLOBALS['sunApp']->catchError('Something specific went wrong.', 404);
```

`$type` must be one of `401` (needs login), `403` (forbidden/CSRF),
`404` (not found), `500` (server error) - anything else silently becomes
`500`. It sends the matching real HTTP status header, then `require`s
`App/Views/Error.php` **in place** (same URL, no redirect) and `exit`s.
See `App/Views/README.md` for why that view is special (self-contained,
no dependency on `$GLOBALS['auth']`).

You don't have to call this yourself very often - `Core\Controller`
already calls it for you on the common cases (see below), and there's a
global exception handler wired up automatically:

```php
// installed once, in App's own constructor:
set_exception_handler(function ($exception) {
    $this->catchError($exception->getMessage(), 500);
});
```

This means **any uncaught exception thrown from this point in the boot
sequence onward** - a `SunDB` query that throws, a bug in your own
controller - ends up on the same branded error page instead of a blank
screen or a raw PHP dump. The raw `$message` is only ever shown to the
visitor if `SYS_SYSERR=true` (local dev - see `App/Views/Error.php`'s
debug box); production visitors always get the friendly title/message
only.

**This does *not* cover the whole request - there's a real gap before
this point, and it's not just theoretical.** `init.php` calls
`SunFunc::loadEnv()` and requires `Config.php`/`Functions.php` *before*
`$sunApp = new \Core\App()` runs (see the root `README.md`'s boot
sequence). Anything that throws in that window happens before any
handler is installed at all, so PHP's own default uncaught-exception
behavior takes over instead - no branding, and depending on your PHP
config, possibly not even visible output (a genuinely blank page, not
even a raw dump), with the real error only in `php_error.log`. The
concrete case: run this app with no `.env` file present and
`SunFunc::loadEnv()` throws "Environment file '.env' not found" - a
blank 500, not the branded error page. This isn't a bug to fix so much
as a boundary to know about: the branded error page is a feature of an
app that has already booted; get `.env` in place first (root
`README.md`'s setup steps) and it stops being a concern.

**Why the handler that *does* exist lives in App's constructor and not
scattered across every `System/Sun*.php` class:** it used to be
scattered - each `Sun*` class
installed its *own* `set_exception_handler()` in its constructor, and
whichever class was constructed last in `init.php` silently won,
overriding whatever `Core\App` had set up. That's exactly the kind of
bug that's invisible until it isn't. Now there's exactly one handler,
installed exactly once, and the `Sun*` classes (deliberately kept as
close to their original upstream form as possible - see
`System/README.md`) don't touch it at all.

### `secureInput()` / `secureVar()`

Thin wrappers around `filter_input()`/`filter_var()` for GET/POST/raw
values. In practice you'll use `System/SunFilter.php`'s
`sanitize()`/`validate()` chain instead (see `System/README.md`) - these
two exist mainly because `init.php` needs `secureInput()` once, before
`$filter` exists yet, to read the `pg` query param.

## Controller.php - `\Core\Controller`

Every controller in `App/Controllers/` extends this. Its constructor
(`__construct($params)`) runs on **every single request** and does, in
order: `check()` → `auth()` → `cache()` → `csrf()` → `call()`.

### Routing: the reserved `Admin` group

Before any of that, the constructor resolves `routes[1]` (the controller
name from `App::parseUrl()` above) into a controller/model/view trio. For
almost every controller that's a direct, flat mapping - `routes[1]=Blog`
→ `App/Controllers/Blog.php`. One name is special-cased: if `routes[1]`
is `Admin`, `routes[2]` is treated as the page name instead, mapped to
`App/Controllers/Admin/{routes[2]}.php` (namespace `App\Controllers\Admin`),
with matching files required in `App/Models/Admin/` and `App/Views/Admin/`.
`routes[3]` becomes the method in that case (still defaulting to `show`),
shifted one segment from the normal `routes[2]`.

```
Normal:  /en/blog/show/42   → routes[1]=blog (controller)  routes[2]=show (method)  routes[3]=42
Admin:   /en/Admin/Users/edit/42 → routes[1]=Admin (group)  routes[2]=Users (page)  routes[3]=edit (method)  routes[4]=42
```

This is the one piece of admin-panel-awareness that lives in `Core/` -
everything else about the panel (its controllers, models, views, language
file, static assets) is ordinary `App/`/`Public/` content following the
same rules as the front end, just under an `Admin` subfolder. See
`App/Controllers/Admin/README.md` for the full convention and the three
pages (`Login`, `Logout`, `Dashboard`) this template ships. Because
`Admin` is reserved this way, a flat `App/Controllers/Admin.php` (a
front-end controller literally named "Admin") is never reachable.

### `check()`

Confirms the controller file, controller class, model file, model class,
the requested method, and the view file all exist. Any single miss →
`catchError($msg, 404)`. This is why a typo'd URL segment gets a clean
404 instead of a fatal "class not found" - the framework checks before
it ever tries to instantiate anything.

### `auth()` - opt-in per-controller

A controller gates specific methods behind login by declaring a public
property:

```php
class Panel extends \Core\Controller
{
    public $authRequired = ['widgets', 'builder', 'profile']; // method names, case-insensitive
    public $authRole = 'admin'; // optional - omit to just require *any* logged-in user
    ...
}
```

If the requested method is in that array and the visitor isn't logged in
(`$GLOBALS['auth']->isLoggedIn()`, from `System/SunAuth.php`) - or is
logged in but lacks the role - `catchError('Authentication required.', 401)`.
`App/Controllers/Auth.php` and `App/Controllers/Home.php` in this
template don't declare `$authRequired` at all (nothing to gate yet); add
it to your own controllers as you build members-only areas.

### `csrf()` - automatic on every POST

Every POST request needs a `csrf` field matching the current token
(`System/SunFunc.php`'s `csrfToken()`) or `catchError('CSRF token validation failed.', 403)`
fires before your controller method ever runs. You don't call this
yourself - put a hidden field in every form:

```html
<input type="hidden" name="csrf" value="<?php _c(); ?>">
```

**Gotcha:** this only ever reads `$_POST`, never a JSON body. An
AJAX call sending `Content-Type: application/json` will always fail CSRF
here - use `application/x-www-form-urlencoded` (a plain `FormData` or
`URLSearchParams` body) instead.

### `cache()`

Only active if `SYS_PGCACHE === true` in `System/Config.php` (defaults
`false` in this template). When on, wraps the page in `System/SunCache.php`.
Off by default because full-page caching is a per-project decision, not
a framework default.

### `call()`

The actual dispatch: instantiates the model, instantiates the controller
(passing the view path + model + params), calls the requested method on
it. You never call this yourself.

## Model.php - `\Core\Model`

Every model in `App/Models/` extends this. Its constructor runs - like
`Controller`'s - on every request that reaches `call()`, **including
controllers whose model does nothing** (e.g. `App/Models/Error.php`'s
`show()` is empty).

If `DB_HOST`/`DB_USERNAME`/`DB_PASSWORD`/`DB_DBNAME`/`DB_PORT` are all set
in `.env`, the constructor connects (or reuses an existing connection -
see below). **If any of them is empty, `$this->pdo` is simply left
`null`** instead of 500ing the whole app - a model that never touches
`$this->pdo` (a page whose content comes from its own PHP array/JSON
file instead of the database, say) works fine with no database
configured at all. A model that *does* call `$this->pdo->...` with
nothing configured fails right there, at the point of actual use - not
eagerly in the constructor for a page that never needed it.

It exposes one thing: `$this->pdo`, a `System/SunDB.php` instance (or
`null` if no DB is configured - guard for that in any model that isn't
certain the project has one), reused across models in the same request
(`SunDB::getInstance()` - see `System/README.md`) rather than opening a
second connection. Query with it directly in your model methods:

```php
public function published() {
    return $this->pdo->select('posts')->where('status', 'published', '=')->orderBy('id', 'desc')->run();
}
```
