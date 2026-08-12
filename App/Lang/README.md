# App/Lang/ - translations, merged from any number of files

`_t()`/`_tr()` (see `System/README.md`) look keys up in a single flat
table built by `System/SunLocal.php`. That table isn't just one file -
`SunLocal` globs **every** file matching `App/Lang/*.{lang}.json` and
merges them together. This template ships three for English:

- `lang.en.json` - site-wide strings (nav, homepage placeholder). This
  one is **required**: `SunLocal`'s constructor checks
  `App/Lang/lang.{lang}.json` specifically exists for whatever language
  is requested, and throws if it's missing (see the next section).
- `auth.en.json` - every string `App/Views/Auth.php` uses.
- `error.en.json` - every string `App/Views/Error.php` uses.
- `admin.en.json` - every string `App/Views/Admin/*.php` uses, prefixed
  `admin.*` and kept separate from `auth.*`/`lang.*` on purpose - see
  `App/Views/Admin/README.md`.

**Adding a new namespace needs zero code changes** - just drop a new
`App/Lang/{whatever}.en.json` file in and its keys are immediately
available to `_t()`, merged in with everything else. This template's own
three-file split (`lang`/`auth`/`error`) is just an organizational
convention (group keys with the view that uses them), not something
`SunLocal` enforces or cares about.

## Key naming convention

Flat, dot-namespaced strings - not nested JSON objects:

```json
{
  "nav.home": "Home",
  "nav.login": "Log In",
  "login.title": "Log In",
  "login.submit": "Log In"
}
```

Reuse a key across screens when the text is identical (`auth.email_label`
is one key, used by the login, register, and forgot-password forms
alike) rather than repeating the same string under three different
names - see `auth.en.json` for the pattern.

## Adding a new language

1. Add the code to `SYS_LANGUAGES` in `System/Config.php`.
2. Create `App/Lang/lang.{code}.json` with the same keys as `lang.en.json`.
3. Create matching `auth.{code}.json`, `error.{code}.json` (or whatever
   other namespaced files you've added) with the same keys as their
   English counterparts.

**Skipping step 2 breaks the entire site for that language, not just
missing translations** - `SunLocal` only ever checks for
`lang.{code}.json` specifically (not the other namespaced files), and
throws an exception if it's missing when `SYS_SYSERR` is true, or
silently falls back to `SYS_DFLTLANG` when it's false. Keep every
language's `lang.*.json` in sync with `SYS_LANGUAGES` - don't add a code
to one without the other.

## Interpolation

```php
_t('welcome.message', [$userName]); // JSON: "welcome.message": "Hello, %s!"
```

`SunLocal::translate()` runs the params through `vsprintf()` against the
stored string - standard `%s`/`%d`/etc. placeholders. **Escape any
user-supplied value before passing it in** - the interpolated result is
echoed raw, not HTML-escaped (same reasoning as `_e()` existing
separately from `_t()` - see `System/README.md`).

## `_t()` vs `_tr()` - which one to use

`_t()` echoes; `_tr()` returns. Use `_tr()` any time you need the
translated *value* rather than to print it immediately - inside a
ternary, building a string that gets JSON-encoded for JavaScript, etc.
`App/Views/Auth.php`'s reset-password error line is a real example:

```php
<p class="error"><?php echo $_GET['error'] === 'mismatch' ? _tr('auth.password_mismatch') : _tr('resetpassword.error_generic'); ?></p>
```

Using `_t()` there would echo the message *and* leave `echo` printing
nothing (since `_t()`'s return value is empty on a successful lookup) -
a real bug that's easy to introduce by habit. If you're inside an
`echo`/ternary/string-concatenation context, reach for `_tr()`.
