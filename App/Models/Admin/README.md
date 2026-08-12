# App/Models/Admin/ - data access for the admin panel

Same rules as `App/Models/README.md`, under the `App\Models\Admin`
namespace and folder instead. Every file here is
`App/Models/Admin/{Name}.php`, `namespace App\Models\Admin;`, class
`{Name} extends \Core\Model`, matching a controller of the same name in
`App/Controllers/Admin/` (`Admin/Users.php` needs
`App/Models/Admin/Users.php`) even if it does nothing -
`Core\Controller::check()` 404s if it's missing, same as the front end.

## The required boilerplate

```php
<?php
namespace App\Models\Admin;

class Users extends \Core\Model
{
    public function show() {
        // optional: return data your controller's show() will use
    }
}
```

## `$this->pdo` - your database handle

Same `\Core\Model` every front-end model uses - one shared `System/SunDB.php`
connection per request, reused rather than opened twice (`Core/README.md`).
Query it directly:

```php
namespace App\Models\Admin;

class Users extends \Core\Model
{
    public function show() {
    }

    public function all() {
        return $this->pdo->select('users')->orderBy('id', 'desc')->run();
    }
}
```

See `System/README.md`'s SunDB section for the full query builder method
list.

## The three models in this template

`Login.php`, `Logout.php` and `Dashboard.php` are all empty `show()`
boilerplate, the same shape as `App/Models/Home.php` - the login/logout
flow reads and writes through `$GLOBALS['auth']` (`System/SunAuth.php`)
directly, not through a model, so there's no database work to do here yet.
Add your own methods to `Dashboard.php` (or a new model alongside it) once
the panel needs to read real data.
