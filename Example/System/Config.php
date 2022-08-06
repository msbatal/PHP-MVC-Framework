<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Mehmet Selcuk Batal, Sunhill Technology <batalms@gmail.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0
 * Redistributions of files must retain the above copyright notice.
 */

/**
 * Database Settings
 */
define ('DB_HOST', 'localhost'); // database host
define ('DB_PORT', '3306'); // database port
define ('DB_DBNAME', 'sunmvc'); // database name
define ('DB_USERNAME', 'sunmvc_user'); // database username
define ('DB_PASSWORD', 'sunhill.4927'); // database password

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
define ('SYS_PHPERR', true); // php errors (show or hide, true / false)
define ('SYS_SYSERR', false); // system errors (shor or hide, true / false)
define ('SYS_PGCACHE', false); // page caching (true / false)
define ('SYS_CHEXCLUDE', ['comments']); // excluded pages for page caching (array)
define ('SYS_HOMEPAGE', 'home'); // home page (index, home, main, etc.)
define ('SYS_ERRPAGE', 'error'); // error page (if requested page does not exist and SYS_SYSERR is set 'false', redirect to this page)
define ('SYS_BASEURL', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'])); // base url (don't need to change)
define ('SYS_BASEPATH', dirname(__DIR__)); // base path (don't need to change)

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
        }
    }
);

?>