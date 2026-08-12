# App/Controllers/Admin/ - the admin panel's own controller group

Same rules as `App/Controllers/README.md`, plus one addition: everything
here is reached through the reserved `Admin` routing group instead of the
flat `{controller}/{method}` scheme.

```
URL:    /en/Admin/Dashboard
routes: [0]=en  [1]=Admin  [2]=Dashboard (page)  [3]=method (optional, default "show")
```

`Core\Controller` (see `Core/README.md`) detects `routes[1] === 'Admin'`
and, instead of mapping straight to `App/Controllers/{routes[1]}.php`, maps
`routes[2]` to `App/Controllers/Admin/{routes[2]}.php`,
`namespace App\Controllers\Admin;`, class `{routes[2]} extends \Core\Controller`.
`routes[3]` becomes the method (default `show`), and anything past that
(`routes[4]`, ...) is available the same way as any other controller's
`$this->params[4]`.

This is why the front end (`App/Controllers/README.md`) and the panel
share `Core/` and `System/` without a fork: the panel is just another
routing group inside the same dispatch engine, not a second framework
copy. Because `Admin` is reserved this way, `App/Controllers/Admin.php`
(a flat controller literally named "Admin") is not reachable - name a
real front-end controller something else.

## The required boilerplate

Identical to the front end's, just under the `App\Controllers\Admin`
namespace and folder:

```php
<?php
namespace App\Controllers\Admin;

class Users extends \Core\Controller
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

Needs a matching `App/Models/Admin/Users.php` and
`App/Views/Admin/Users.php` - same requirement `Core\Controller::check()`
enforces for the front end, see `App/Models/README.md` /
`App/Views/README.md`.

## Gating a page behind login

Same mechanism as the front end (`App/Controllers/README.md`):

```php
class Users extends \Core\Controller
{
    public $authRequired = ['show']; // method names, case-insensitive
    public $authRole = 'admin'; // optional - add once your users table has a role you trust
    ...
}
```

## The three pages in this template

| File | GET | POST |
|---|---|---|
| `Login.php` | login form | checks credentials via `$GLOBALS['auth']` (`System/SunAuth.php`); on success redirects to `Admin/Dashboard`, otherwise back to `Admin/Login?error=1` |
| `Logout.php` | confirmation page (`App/Views/Admin/Logout.php`) | logs out, redirects to `Admin/Login` |
| `Dashboard.php` | welcome message + logout link, gated behind `$authRequired = ['show']` | - |

`Login.php` and `Logout.php` don't declare `$authRequired` - a visitor
who isn't logged in still needs to reach the login form, and logging out
must work regardless of the current auth state. `Dashboard.php` does.
Add `$authRole = 'admin'` to it (or any other admin page you add) once
your `users` table actually distinguishes admins from regular visitors
(`database/README.md`) - it's left out here so this template keeps
working out of the box against a plain `users` table.

Text on all three pages comes from `App/Lang/admin.en.json` via `_t()`,
never a hardcoded string - see `App/Lang/README.md`.
