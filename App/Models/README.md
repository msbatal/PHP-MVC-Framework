# App/Models/ - data access, one per controller

Every file here is `App/Models/{Name}.php`, `namespace App\Models;`,
class `{Name} extends \Core\Model`. It **must** exist and be named to
match a controller of the same name (`App/Controllers/Blog.php` needs
`App/Models/Blog.php`) even if it does nothing - `Core\Controller::check()`
404s if it's missing (see `Core/README.md`).

Admin panel models follow the same rule one level deeper, under
`App/Models/Admin/` - see `App/Models/Admin/README.md`.

## The required boilerplate

```php
<?php
namespace App\Models;

class Blog extends \Core\Model
{
    public function show() {
        // optional: return data your controller's show() will use
    }
}
```

`show()` doesn't have to do anything (see `App/Models/Home.php` and
`App/Models/Error.php` in this template - both empty), but the class
and file must exist.

## `$this->pdo` - your database handle

`\Core\Model`'s constructor already set this up (a `System/SunDB.php`
instance, reused across models in the same request rather than opening a
second connection - see `Core/README.md`). Add your own methods that
query it:

```php
namespace App\Models;

class Blog extends \Core\Model
{
    public function show() {
    }

    public function published() {
        return $this->pdo->select('posts')->where('status', 'published', '=')->orderBy('id', 'desc')->run();
    }

    public function find($id) {
        return $this->pdo->select('posts')->where('id', $id, '=')->first()->run();
    }
}
```

Call these from your controller (`$this->model->published()`), not
directly from the view - the view only ever sees whatever local
variables the controller set before its `require_once` (see
`App/Controllers/README.md`).

See `System/README.md`'s SunDB section for the full query builder method
list (`join`, `groupBy`, `paginate`, `insertMany`, ...).

## Worked example: `Auth.php`

Backs `App/Controllers/Auth.php`'s sign-up/email-verification flow. Four
methods, all plain `$this->pdo` calls against the `users` and
`email_verifications` tables (`database/schema.sql`):

```php
public function userByEmail($email = null) {
    return $this->pdo->select('users')->where('email', $email, '=')->first()->run();
}

public function createVerificationCode($userId = null) {
    // invalidate any still-unused codes first, so only the latest is ever valid
    $this->pdo->update('email_verifications', ['used' => 1])->where('user_id', $userId, '=')->where('used', 0, '=')->run();
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $this->pdo->insert('email_verifications', [
        'user_id' => $userId,
        'code_hash' => hash('sha256', $code),
        'expires_at' => date('Y-m-d H:i:s', time() + 1800),
        'used' => 0,
    ])->run();
    return $code; // the only place the plaintext code ever exists - only the hash is stored
}
```

Note it queries `users`/`email_verifications` directly rather than going
through `SunAuth` - `SunAuth`'s own user lookups are private to that
class (see `System/README.md`), so anything beyond its public API
(login/register/2FA/password-reset) means querying the table yourself,
exactly like any other model would.

## Worked example: reading analytics numbers

`SunAnalytics` (`System/README.md`) records visits on its own
(`Core\Controller::track()`) and does its own database work, so a model
doesn't query its tables through `$this->pdo` - it asks `$GLOBALS['analytics']`
for the finished report instead. `App/Models/Admin/Dashboard.php`'s
`stats()` is the template's example:

```php
public function stats($days = 30) {
    try {
        $analytics = $GLOBALS['analytics'];
        return [
            'summary' => $analytics->summary($days),  // visits, pageviews, visitors, bounce_rate, ...
            'sources' => $analytics->sources($days, 5), // top traffic sources (source + medium)
            'pages'   => $analytics->pages($days, 5),   // most viewed pages
            'online'  => $analytics->online()           // visitors seen in the last 5 minutes
        ];
    } catch (\Throwable $e) {
        return null; // the analytics tables don't exist yet (database/schema.sql)
    }
}
```

Keep the `try`/`catch`: a report on a database without the `sun_visits`
tables throws, and a statistics block should never take a whole page down
with it. Other reports (`daily()`, `breakdown('campaign' | 'referrer' |
'device' | ...)`) work the same way - the full list is in
`System/README.md`'s SunAnalytics section.
