# database/ - schema, not application code

`schema.sql` is the only file here. It's not auto-run by anything -
import it yourself into whatever database `.env`'s `DB_*` values point
at:

```
mysql -u root -p your_database_name < database/schema.sql
```

## What's in it, and why

Every table matches what `System/SunAuth.php` needs *by default* - see
`System/README.md`'s SunAuth section and `System/SunAuth.php`'s
`$config['columns']`/`$config['prefix']`. `init.php` constructs
`SunAuth` with no config overrides (`new SunAuth($authDb)`), so it
expects exactly this table/column shape out of the box:

| Table | Backs |
|---|---|
| `users` | `SunAuth`'s canonical user table (default table name). `first_name`/`last_name`/`language` are extras used by `App/Controllers/Auth.php`'s sign-up form, not required by `SunAuth` itself - drop them (and the matching form fields) if your project doesn't want them. |
| `sun_sessions` | Database-backed sessions (multi-device aware, `SunAuth::sessions()`/`destroySession()`). |
| `sun_login_attempts` | Brute-force lockout (`SunAuth::isLocked()`). |
| `sun_remember_tokens` | "Remember me" cookies (selector+validator pattern). |
| `sun_password_resets` | `createResetToken()`/`resetPassword()`. |
| `sun_visits` / `sun_pageviews` / `sun_online` | `SunAnalytics`'s tables (visits with their traffic source, page views, online visitors). Filled automatically by `Core/Controller.php`'s `track()` and read by `App/Models/Admin/Dashboard.php`'s `stats()`. Skip them if you set `SYS_ANALYTICS` to `false`; they can also be created with `$GLOBALS['analytics']->install()`. |
| `email_verifications` | **Not** a `SunAuth` table - this one's `App/Models/Auth.php`'s own, backing the sign-up email-verification-code flow (`App/Controllers/Auth.php`'s `register()`/`verifyemail()`). Kept separate from `sun_password_resets` on purpose: account activation and password recovery are different concerns, and a token from one shouldn't be usable for the other. |

## If your project needs a different table name

```php
// init.php
$auth = new SunAuth($authDb, ['table' => 'accounts']);
```

...then `accounts` needs the same column shape `users` has above (SunAuth
doesn't care what you call the table, but it does need those specific
columns - see `System/SunAuth.php`'s `$config['columns']` if you also
want different *column* names, not just a different table name). Update
`App/Models/Auth.php`'s hardcoded `'users'` references to match, too.

## Extending the `users` table for your own app data

SunAuth only reads the columns it knows about - anything else you add
rides along for free:

```sql
ALTER TABLE `users`
    ADD COLUMN `company_name` VARCHAR(150) NOT NULL DEFAULT '',
    ADD COLUMN `avatar` VARCHAR(250) NOT NULL DEFAULT '';
```

`$GLOBALS['auth']->user()` returns the full row (`SELECT *`), so new
columns show up there automatically - no `SunAuth` code changes needed.

## The SunAnalytics tables

`sun_visits`, `sun_pageviews` and `sun_online` (see the table above) are
written by `Core/Controller.php`'s `track()` on every page load and read by
`System/SunAnalytics.php`'s reports (`System/README.md`). They need no
columns of yours and nothing in them is personal data: the visitor hash
rotates daily and the raw IP is only stored if you set
`$analyticsConfig['storeIp']` to `true` in `System/Config.php`. The
framework has no cron, so old rows are deleted now and then by the
requests themselves (1 in 500 calls `purge()`), after
`$analyticsConfig['retention']` days (365 by default, `0` keeps
everything). If you'd rather not import the tables by hand, call
`$GLOBALS['analytics']->install()` once - it creates the same three tables
(`IF NOT EXISTS`, safe to repeat) and honors the `prefix` setting; and if the
project must not record visits at all, set `SYS_ANALYTICS` to `false` and
leave the tables out.
