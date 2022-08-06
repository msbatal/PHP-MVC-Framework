<?php

/**
 * Sunhill: PHP App Development Framework
 * Copyright (c) Sunhill Technology
 *
 * Licensed under The GNU Lesser General Public License, version 3.0
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      Mehmet Selcuk Batal <batalms@gmail.com>
 * @copyright   Copyright (c) 2021, Sunhill Technology <www.sunhillint.com>
 * @license     https://opensource.org/licenses/lgpl-3.0.html The GNU Lesser General Public License, version 3.0
 * @link        https://github.com/msbatal/PHP-MVC-App-Development-Framework
 * @version     1.2.0
 */

if (in_array('mod_rewrite', apache_get_modules()) === true) { // if mod_rewrite module enabled
    require_once (__DIR__ . '/init.php'); // include init file
} else { // if mod_rewrite module not enabled 
    die ('<br><h4 align="center">Apache "mod_rewrite" module is not enabled!<br>You must enable this module on your web server to continue to use the framework.</h4>'); // print alert
}

?>