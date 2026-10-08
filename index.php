<?php

/**
 * Sunhill: PHP App Development Framework
 * Copyright (c) Sunhill Technology
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

// apache_get_modules() only exists when PHP runs as an Apache module (mod_php).
// Under php-cgi/FastCGI (MAMP's default - confirmed in the logs: php.fcgi)
// the function doesn't exist at all, so calling it unconditionally is a fatal
// error, not a "module missing" message. Skip the check on that SAPI; rewrite
// support is already required to reach this file via pretty URLs.
if (!function_exists('apache_get_modules') || in_array('mod_rewrite', apache_get_modules()) === true) {
    require_once (__DIR__ . '/init.php'); // include init file
} else { // if mod_rewrite module not enabled
    die ('<br><h4 align="center">Apache "mod_rewrite" module is not enabled!<br>You must enable this module on your web server to continue to use the framework.</h4>'); // print alert
}

?>