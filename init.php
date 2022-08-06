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

session_start(); // start session

/**
 * System Files
 */
require_once (__DIR__ . '/System/Config.php'); // config file (settings)

/**
 * PHP Error Reporting
 */
if (SYS_PHPERR === true) { // change from config file (if needed)
    error_reporting(E_ALL);
    ini_set('display_errors', TRUE);
}

/**
 * Creating Main Objects
 */
$sunApp = new \Core\App(); // create application object
$url = $sunApp->parseUrl($sunApp->secureInput('get', 'pg', 'string')); // get and check url
$call = new \Core\Controller($url); // create main controller object (with url parameter)

?>