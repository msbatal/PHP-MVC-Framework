# App/Controllers/ - one class per URL segment

Every file here is `App/Controllers/{Name}.php`, `namespace App\Controllers;`,
class `{Name} extends \Core\Controller`. The `{Name}` **is** the URL's
second segment (`/en/blog/...` → `Blog.php`), matched case-insensitively.

**Exception:** `Admin` is a reserved name that routes into
`App/Controllers/Admin/` instead - see `App/Controllers/Admin/README.md`
and `Core/README.md`'s "Routing: the reserved `Admin` group".

`Core\Controller` (see `Core/README.md`) requires a matching
`App/Models/{Name}.php` and `App/Views/{Name}.php` to exist too - even if
the model does nothing and the view is trivial. There's no way to have a
controller without both.

## The required boilerplate

Every controller starts identically - copy this, don't hand-roll it:

```php
<?php
namespace App\Controllers;

class Blog extends \Core\Controller
{
    public function __construct($view = null, $model = null, $params = null) {
        $this->view = $view;
        $this->model = $model;
        $this->params = $params;
    }

    public function show() {
        require_once ($this->view);
    }
}
```

`show()` is the default method - a bare `/en/blog` (no third URL
segment) calls it. Add more public methods for more URLs
(`/en/blog/archive` → `archive()`).

## Passing data to the view

There's no separate "view data" mechanism. A controller method's local
variables are directly visible in the view file, because `require_once`
runs in the *same* variable scope:

```php
public function show() {
    $posts = $this->model->published(); // now $posts exists inside the view too
    require_once ($this->view);
}
```

## Gating a method behind login

```php
class Members extends \Core\Controller
{
    public $authRequired = ['dashboard', 'settings']; // method names, case-insensitive
    public $authRole = 'subscriber'; // optional
    ...
}
```

See `Core/README.md`'s `auth()` section for exactly what happens when
this fires. Neither `Home.php` nor `Auth.php` in this template declares
`$authRequired` - add it to your own controllers once you have something
worth gating.

## Two worked examples in this template

### `Home.php` - the minimal case

One method (`show()`), no model logic, just proves the pipeline works.
Replace `App/Views/Home.php` with your real landing page and this
controller barely needs to change.

### `Auth.php` - the full case

A complete auth flow built on `SunAuth` (`System/README.md`). Eight
public methods (plus the required `__construct()` boilerplate), each
following the same GET-shows-form / POST-processes-it shape:

| Method | GET | POST |
|---|---|---|
| `login()` | login form | checks credentials; 2FA-pending → redirects to `verify2fa()`; inactive account → redirects to `verifyemail()` with a fresh code |
| `register()` | sign-up form | creates the account (`status = 0`, inactive), sends a verification code, redirects to `verifyemail()` |
| `verifyemail()` | code-entry form (redirects to `login()` if nothing pending) | checks the code, activates the account (`status = 1`), redirects to `login()` |
| `resendverification()` | - (POST-only) | re-sends a code to whoever's pending in session |
| `verify2fa()` | code-entry form (redirects to `login()` if nothing pending) | checks the TOTP code, promotes the session |
| `forgotpassword()` | email form | sends a reset link if the email exists (same message either way - never reveals whether an account exists) |
| `resetpassword()` | new-password form (validates the token up front) | performs the reset |
| `logout()` | redirects home (GET not allowed) | logs out, redirects home |

**Patterns worth reusing elsewhere in your app, not just for auth:**

- **Flash messages across a redirect:** `$_SESSION['flash_notify'] = ['type' => 'success', 'message' => _tr('some.key')];`
  before a `header('Location: ...')`. Every view (`App/Views/Home.php`,
  `App/Views/Auth.php`) has an identical block right after `</nav>` that
  reads + clears it on the next page load (one-shot, so a refresh never
  re-shows it) and prints it as plain HTML - wire it to whatever
  toast/alert approach you pick. See `App/Views/README.md` for the exact
  block. Always build the message via `_tr()`, not a hardcoded string.
- **A token in a query string, never a path segment:** see the comment
  in `resetpassword()`/`forgotpassword()` about `Core\App::parseUrl()`
  `ucfirst()`-ing every path segment. Any case-sensitive value (a hex
  token, a hash) *will* get silently corrupted if you put it in the URL
  path instead of `?token=...`. When you *want* a value visible in the
  path instead (SEO - a post title next to its id), that's a different
  tool: `SunFunc::sefUrl()`/`slugId()` (`_s()` in views), see
  `System/README.md`'s "SunFunc - SEO-friendly id+slug URLs".
- **Resuming instead of dead-ending:** `register()`'s duplicate-email
  handling - if the existing account was never verified, it resumes that
  flow (fresh code, no new row) instead of a "this email is taken"
  dead end. Worth the same treatment anywhere else you have a
  multi-step signup-like flow.
- **A private helper shared by two public actions:**
  `sendVerificationCode()` is `private`, called from both `register()`
  and `login()`'s inactive-account branch. Nothing framework-specific
  about this - just plain PHP - but it's the natural place to put
  logic two of your controller's own methods need identically.

### `Error.php` - the special case

Reached almost entirely through `Core\App::catchError()`
(`Core/README.md`), not normal navigation - see `App/Views/README.md`
for why its view can't assume `$GLOBALS['auth']`/`$GLOBALS['local']`
exist the way every other view does.
