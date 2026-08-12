<?php

/**
 * Sunhill: PHP App Development Framework
 * Copyright (c) Sunhill Technology
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * System Files
 */
require_once (__DIR__ . '/System/SunFunc.php'); // functions file (needed early for the static loadEnv() bootstrap call)
SunFunc::loadEnv(__DIR__ . '/.env'); // load environment variables (see .env.example)
require_once (__DIR__ . '/System/Config.php'); // config file (settings)
require_once (__DIR__ . '/System/Functions.php'); // functions file
SunFunc::ensureSitemapFiles(); // check sitemap.xml and robots.txt files

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
$functions = new SunFunc(); // create functions object
$filter = new SunFilter(); // create sanitize/validate filter object
$captcha = new SunCaptcha(); // create captcha generator object
$authDb = new SunDB(null, DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DBNAME, DB_PORT); // db connection for SunAuth (lazy, connects on first query)
$auth = new SunAuth($authDb); // create authentication object
$url = $sunApp->parseUrl($sunApp->secureInput('get', 'pg', 'string')); // get and check url
$local = new SunLocal(strtolower($sunApp->routes[0])); // create localization object
$call = new \Core\Controller($url); // create main controller object (with url parameter)

?>
