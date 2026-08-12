<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Database Settings
 * Values are loaded from the ".env" file (see ".env.example"), never hardcode secrets here
 */
define ('DB_HOST', getenv('DB_HOST')); // database host
define ('DB_PORT', getenv('DB_PORT')); // database port
define ('DB_DBNAME', getenv('DB_DBNAME')); // database name
define ('DB_USERNAME', getenv('DB_USERNAME')); // database username
define ('DB_PASSWORD', getenv('DB_PASSWORD')); // database password

/**
 * Cache Settings
 */
$cacheConfig = [
    'cacheDir'      => '/../Public/cache', // cache folder path
    'fileExtension' => 'html', // cache file extension
    'storageTime'   => 24*60*60, // storage time (seconds)
    'contentMinify' => true, // content minification
    'showTime'      => true, // show page load time
    'sefUrl'        => true // website sef url status
];

/**
 * System Settings
 */
define ('SYS_PHPERR', getenv('SYS_PHPERR') === 'true'); // php errors (show or hide), from ".env" - keep false in production
define ('SYS_SYSERR', getenv('SYS_SYSERR') === 'true'); // system errors (show or hide), from ".env" - keep false in production
define ('SYS_PGCACHE', false); // page caching (true / false)
define ('SYS_CHEXCLUDE', ['auth', 'error']); // excluded pages for page caching (array)
define ('SYS_DFLTLANG', 'en'); // default language
define ('SYS_LANGUAGES', ['en']); // accepted languages
define ('SYS_HOMEPAGE', 'home'); // home page (index, home, main, etc.)
define ('SYS_ERRPAGE', 'error'); // error page (if requested page does not exist and SYS_SYSERR is set 'false', redirect to this page)
define ('SYS_BASEURL', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/')); // system base url (don't need to change)
define ('SYS_BASEPATH', dirname(__DIR__)); // system base path (don't need to change)
define ('SYS_COOKIEPATH', '/');  // cookie base path (available in the whole domain)
define ('SYS_COOKIETIME', 60*60*24*30); // cookie life time (30 days by default)
define ('SYS_COOKIESCR', true); // receive the cookie over https only
define ('SYS_COOKIEHTTP', true); // prevent javascript access to session cookie
define ('SYS_COOKIESMST', 'None'); // cookie samesite value (none, lax, strict)
define ('SYS_SCRKEY', getenv('SYS_SCRKEY')); // secret key for encryption/decryption, from ".env" (don't change later)
define ('SYS_SCRIV', getenv('SYS_SCRIV')); // iv string for encryption/decryption, from ".env" (don't change later)

/**
 * Runtime Settings
 */
ini_set ('session.name', 'SUNMVC');
ini_set ('session.gc_maxlifetime', SYS_COOKIETIME);
ini_set ('allow_url_fopen', 'on');

/**
 * Session Settings
 */
if (session_status() === PHP_SESSION_NONE) { // cookie params must be set before the session starts
    if (PHP_VERSION_ID < 70300) {
        session_set_cookie_params(
            SYS_COOKIETIME,
            SYS_COOKIEPATH.';
            samesite='.SYS_COOKIESMST,
            $_SERVER['HTTP_HOST'],
            SYS_COOKIESCR,
            SYS_COOKIEHTTP
        );
    } else {
        session_set_cookie_params([
            'lifetime' => SYS_COOKIETIME,
            'path' => SYS_COOKIEPATH,
            'secure' => SYS_COOKIESCR,
            'httponly' => SYS_COOKIEHTTP,
            'samesite' => SYS_COOKIESMST
        ]);
    }
    session_start();
}

/**
 * Autoload (Classes)
 */
spl_autoload_register(
    function ($class) {
        $class = ltrim($class, '\\');
        $file = '';
        $name = '';
        if ($pos = strrpos($class, '\\')) {
            $name = substr($class, 0, $pos);
            $class = substr($class, $pos + 1);
            $file = str_replace('\\', '/', $name) . '/';
        }
        $file .= $class . '.php';
        $file = dirname(__DIR__) . '/' . $file;
        if (file_exists($file)) {
            require_once ($file);
            return;
        }
        if ($name === '') {
            $fallback = dirname(__DIR__) . '/System/' . $class . '.php';
            if (file_exists($fallback)) {
                require_once ($fallback);
            }
        }
    }
);

?>