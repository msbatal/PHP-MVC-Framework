# PHP MVC App Development Framework

Sunhill Framework is a simple, fast, and powerful PHP App Development Framework that enables you to develop more modern applications by using MVC (Model - View - Controller) pattern.

<hr>

This is a blank starting point for the Sunhill MVC framework ("SunMvc") -
routing, controllers/models/views, authentication, i18n, and error
handling all wired up and tested, with **no application-specific code**
in it. Every previous project built on this framework (routing fixes,
the `Sun*` class integration approach, the auth flow, the error page
mechanism) had its lessons folded back into this template. Build your
next project *on top of* this, not from scratch.

## If you're an AI agent picking this up cold

Read every `README.md` in this tree before writing any code -
`Core/README.md`, `System/README.md`, `App/Controllers/README.md`,
`App/Controllers/Admin/README.md`, `App/Models/README.md`,
`App/Models/Admin/README.md`, `App/Views/README.md`,
`App/Views/Admin/README.md`, `App/Lang/README.md`, `Public/README.md`,
`Public/Admin/README.md`, `database/README.md` - each one documents
exactly what's in its folder and how to extend it, with real examples
pulled from this template's own working code (not hypothetical).
Together they cover:

- **`Core/`** - the dispatch engine (`\Core\App`, `\Core\Controller`,
  `\Core\Model`). You will almost never edit these.
- **`System/`** - framework config (`Config.php`, `Functions.php`) plus
  11 general-purpose `Sun*.php` classes (auth, DB, mail, i18n, ...) that
  are maintained *outside* this project and must never be hand-edited -
  `System/README.md` explains why and what to do instead.
- **`App/`** - where your actual project lives: `Controllers/`,
  `Models/`, `Views/`, `Lang/`. This template ships two working
  controllers here (`Home`, a placeholder; `Auth`, a complete
  login/register/password-reset/2FA/email-verification flow) as
  worked examples of the pattern, not as content to keep - replace
  `Home` with your real landing page, keep or adapt `Auth` as needed.
  Each of `Controllers/`, `Models/`, `Views/` also has an `Admin/`
  subfolder - the admin panel (see "Routing" below and
  `App/Controllers/Admin/README.md`).
- **`Public/`** - the only directory served as static files. Its
  `Admin/` subfolder is the panel's own CSS/JS/images/fonts, kept apart
  from the front end's (`Public/Admin/README.md`).
- **`database/`** - `schema.sql` for the tables `SunAuth` and the auth
  flow expect.

**Stay inside this template's structure and conventions** - the whole
point of building on it is consistency across projects. If a task seems
to need a different pattern than what's documented, that's worth
flagging rather than quietly improvising a one-off.

## How a request flows, start to finish

```
Browser request
  → .htaccess rewrites to index.php?pg=<path>
  → index.php checks mod_rewrite is available, requires init.php
  → init.php: loads .env, requires Config.php (constants + autoloader)
              and Functions.php (_t/_e/_c/...), then constructs, in
              order: $sunApp, $functions, $filter, $captcha,
              $authDb, $auth, ($sunApp->parseUrl() - NOW routes[] exists),
              $local, $call (\Core\Controller - this is where your
              controller/model/view actually run)
  → \Core\Controller: check() controller/model/view files+classes+method
                       exist → auth() gate if $authRequired → cache()
                       if enabled → csrf() on POST → call() instantiate
                       + dispatch
  → Your controller method runs, requires its view
```

Full detail on each step in `Core/README.md`.

## Routing

```
URL:    /en/blog/show/42
routes: [0]=en (language)  [1]=blog (controller)  [2]=show (method)  [3]=42 (your own param)
```

- Language must be in `SYS_LANGUAGES` (`System/Config.php`) or it falls
  back to `SYS_DFLTLANG`.
- No page segment → `SYS_HOMEPAGE` controller.
- No method segment → `show()`.
- **`\Core\App::parseUrl()` `ucfirst()`s every path segment.** Never put
  a case-sensitive value (a token, a hash) in the URL path - use a query
  string param instead. `App/Controllers/Auth.php`'s password-reset flow
  is a real example of working around this correctly. If you *want* a
  value visible in the path for SEO (a post title next to its id, e.g.
  `/blog/show/42-hello-world`), use `SunFunc::sefUrl()`/`slugId()`
  (`_s()` in views) instead of fighting `ucfirst()` - see
  `System/README.md`'s "SunFunc - SEO-friendly id+slug URLs".

**One reserved controller name: `Admin`.** `/en/Admin/Dashboard` doesn't
map to a flat `App/Controllers/Admin.php` - `routes[1]=Admin` shifts
everything one segment over, so `routes[2]` (`Dashboard`) becomes the
page inside `App/Controllers/Admin/`, `App/Models/Admin/`,
`App/Views/Admin/`, and `routes[3]` becomes the method. This is the admin
panel this template ships (`Login`/`Logout`/`Dashboard`) - full detail in
`Core/README.md`'s "Routing: the reserved `Admin` group" and
`App/Controllers/Admin/README.md`.

## The `$GLOBALS` every request has available

Set up once in `init.php`, available everywhere without importing
anything:

| Global | Class | Typical use |
|---|---|---|
| `$GLOBALS['sunApp']` | `\Core\App` | `->routes`, `->catchError(...)` |
| `$GLOBALS['auth']` | `SunAuth` | `->isLoggedIn()`, `->login(...)`, `->user()`, ... |
| `$GLOBALS['filter']` | `SunFilter` | `->sanitize(...)->result()`, `->validate(...)->result()` |
| `$GLOBALS['functions']` | `SunFunc` | `->csrfToken()`, `->getIpAddress()`, ... |
| `$GLOBALS['local']` | `SunLocal` | backs `_t()`/`_tr()` - rarely used directly |
| `$GLOBALS['captcha']` | `SunCaptcha` | image CAPTCHA generation/validation |

Full method reference for each in `System/README.md`.

## Error handling

Every error a visitor can hit - a 401 (needs login), 403 (forbidden/CSRF
failure), 404 (not found), 500 (server error, including any uncaught
exception thrown *after* `\Core\App` is constructed - see below) - lands
on the same branded `App/Views/Error.php`, with the correct real HTTP
status code, in place (no redirect). You opt into it by calling
`$GLOBALS['sunApp']->catchError($message, $type)`; `Core\Controller`
already does this for the common cases automatically.

**Caveat worth knowing:** this only covers exceptions thrown *after*
`$sunApp = new \Core\App()` runs in `init.php` - i.e. after `.env` is
loaded and `Config.php`/`Functions.php` are required, per the boot
sequence above. A failure before that point (the concrete case: no
`.env` file present) is a raw, unbranded PHP fatal - blank page, error
only in `php_error.log`. Full explanation, including why this boundary
exists, in `Core/README.md`.

## Setting up a new project from this template

1. Copy `.env.example` to `.env`, fill in real `DB_*` values (and
   `SMTP_*` if you'll send email). Generate real `SYS_SCRKEY`/`SYS_SCRIV`
   values - don't ship the example placeholders.
2. Import `database/schema.sql` into your database. Extend the `users`
   table with your own columns as needed (`database/README.md`).
3. Decide `SYS_LANGUAGES` in `System/Config.php` - defaults to just
   `['en']`. Add a language only once you have a matching
   `App/Lang/lang.{code}.json` (`App/Lang/README.md` - skipping this
   breaks the whole site for that language, not just translations).
4. Replace `App/Views/Home.php` (and its controller/model, if your
   homepage needs real logic) with your actual landing page. Add your
   stylesheet/script links directly in the view - every view is
   self-contained (no shared Header.php/Footer.php) and ships unstyled
   on purpose (`App/Views/README.md`).
5. Decide whether to keep `App/Controllers/Auth.php` as-is, extend it,
   or strip parts you don't need. It's a complete, tested flow - reuse
   is the point - but check the `TODO` comment in `login()`/`verify2fa()`
   about the post-login redirect target, which currently points at
   `/home` as a safe default.
6. Build out your own controllers/models/views following the patterns
   in `App/Controllers/README.md`, `App/Models/README.md`,
   `App/Views/README.md`.
7. The admin panel (`/en/Admin/Login`, `/en/Admin/Dashboard`) works out
   of the box against the same `users` table - log in with any account
   from step 2. Add `public $authRole = 'admin';` to
   `App/Controllers/Admin/Dashboard.php` once your `users` table
   distinguishes admins from regular visitors, and add more admin pages
   following `App/Controllers/Admin/README.md`.

## Production deployment: file write permissions

**Warning:** a typical Apache vhost setup (`chown -R $USER:$USER` +
`chmod -R 755` on the whole site root - the common tutorial pattern)
leaves the web server process (`www-data` on Debian/Ubuntu) with *no*
write access anywhere, since it lands in the "other" permission bucket.
This framework writes to disk at runtime in more than one place -
`SunFunc::ensureSitemapFiles()` (auto-creates `robots.txt`/`sitemap.xml`
on boot if missing, `System/README.md`) and the page cache written to
`Public/twccache` (`Config.php`'s `$cacheConfig['cacheDir']`). If the
target directory isn't writable by the web server user, every request
that hits the write path can 500 *before* `\Core\App`'s exception
handler is even installed (raw uncaught fatal, blank page).

Do not fix this with `chmod 777` (world-writable), and never grant
`www-data` write access broadly, recursively, or to the codebase itself
(`App/`, `Core/`, `System/`, `.env`, `.htaccess`) - only the specific
directories the app actually writes to at runtime should ever be
writable by the web server process. Anything the same process serves
requests from staying writable by that same process turns any future
file-write bug in the app into a full webshell path; source code and
secrets (`.env`) must stay owner-only, read-only for `www-data`.

For every directory the running system writes to, apply exactly this -
never a blanket recursive change over the whole site:

```bash
sudo chown root:www-data /var/www/your_domain/directory_name
sudo chmod 775 /var/www/your_domain/directory_name
```

**At minimum**, even if no other write target exists yet, this must be
applied to:
- `Public/` (root-level auto-created files: `robots.txt`, `sitemap.xml`)
- `Public/cache` (`Public/twccache` in this template's default
  `$cacheConfig['cacheDir']` - page cache)

Confirmed on a real production deploy: `Public/` was `root:root` at
`755` (the direct result of the standard vhost setup above), `www-data`
couldn't write into it, and the site 500'd on every request until
`chown root:www-data` + `chmod 775` were applied to `Public/` (and its
cache subfolder) specifically.

## Security notes already baked in (don't remove these)

- `.htaccess` blocks direct access to `App/`, `Core/`, `System/` - a
  request for `/System/SunAuth.php` 403s instead of serving the source.
  Also blocks dotfiles (`.env`, `.git`, ...) and common sensitive
  extensions (`.sql`, `.log`, `.bak`, ...).
- CSRF is checked automatically on every POST (`Core/README.md`) - every
  form needs `<input type="hidden" name="csrf" value="<?php _c(); ?>">`.
- `index.php` guards against `apache_get_modules()` being undefined
  under PHP-FastCGI/PHP-CGI SAPIs (it only exists under `mod_php`) - a
  real bug on at least one MAMP setup, fatal without the guard.
- Set `SYS_PHPERR=false` and `SYS_SYSERR=false` in production `.env` -
  both default to whatever `.env` says, and both leak internal detail to
  visitors when true (raw PHP warnings, and a technical-details box on
  the error page, respectively).
