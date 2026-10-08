<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Namespace: use Core directory
 */
namespace Core;

/**
 * Main application class
 * Don't change the class name
 */
class App
{

    /**
     * URL parameters
     * @var array
     */
    public $routes = [];


    public function __construct() {
        // Single, authoritative handler from this point in the boot sequence
        // onward: any exception thrown after this line (including ones thrown
        // deep inside System/Sun* classes, which have no local try/catch of
        // their own) ends up here and gets the same branded error page as the
        // classified catchError() calls below, instead of a raw dump. Does NOT
        // cover anything thrown earlier in init.php (SunFunc::loadEnv(), a
        // missing .env being the concrete case) - nothing does, since no
        // handler exists yet at that point. See the root README.md's boot
        // sequence and Core/README.md for that boundary.
        set_exception_handler(function ($exception) {
            $this->catchError($exception->getMessage(), 500);
        });
    }

    /**
     * Show the branded error page in place (same URL, correct HTTP status)
     * and stop execution - used for both classified application errors
     * (401/403/404/500 from Controller/Model) and any otherwise-uncaught
     * exception (see the handler installed in the constructor above).
     * The raw $message is only ever shown when SYS_SYSERR is true (see
     * App/Views/Error.php) - visitors always get the friendly version.
     *
     * @param string $message
     * @param int $type one of 401, 403, 404, 500
     */
    public function catchError($message = null, $type = 500) {
        if (!in_array($type, [401, 403, 404, 500], true)) {
            $type = 500;
        }
        $statusLines = [
            401 => 'HTTP/1.1 401 Unauthorized',
            403 => 'HTTP/1.1 403 Forbidden',
            404 => 'HTTP/1.1 404 Not Found',
            500 => 'HTTP/1.1 500 Internal Server Error',
        ];
        header($statusLines[$type]);
        $errorType = $type;
        $errorDebugMessage = $message;
        require SYS_BASEPATH . '/App/Views/Error.php';
        exit;
    }

    /**
     * Parse url
     *
     * @param string $url
     * @return array
     */
    public function parseUrl($url = null) {
        if (empty($url)) {$url = SYS_DFLTLANG . '/' . SYS_HOMEPAGE;} // set url as home page
        $url = explode('/', str_replace('.php', '', rtrim($url, '/'))); // parse url
        foreach ($url as $key => $value) {
            $this->routes[$key] = ucfirst($value); // get parameters from url
        }
        // if not exists in accepted languages
        $hadValidLanguage = in_array(strtolower($this->routes[0]), SYS_LANGUAGES, true);
        if (!$hadValidLanguage) {
            $this->routes[0] = SYS_DFLTLANG; // default language
        }
        // if page name is empty
        if (!isset($this->routes[1]) || is_null($this->routes[1]) || $this->routes[1] == 'Index') {
            if (!$hadValidLanguage && count($url) === 1) {
                $this->catchError('Page not found.', 404);
            }
            $this->routes[1] = ucfirst(SYS_HOMEPAGE); // home page
        }
        // if page name includes php extension
        if (strstr($this->routes[1], '.php')) {
            $this->routes[1] = ucfirst(SYS_ERRPAGE); // error page
        }
        return $this->routes;
    }

    /**
     * Filter sanitize the inputs
     *
     * @param string $input
     * @param string $content
     * @param string $type
     * @return boolean
     */
    public function secureInput($input = null, $content = null, $type = null) {
        $result = null;
        $content = trim($content);
        $check = filter_has_var(INPUT_POST | INPUT_GET, $content);
        if (!in_array($input, ['get', 'post']) || !in_array($type, ['string', 'integer', 'float', 'email', 'url']) || $check === false) {
            return $result;
        }
        switch ($type) {
            case 'string' :
                $result = filter_input(INPUT_POST | INPUT_GET, $content, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            break;
            case 'integer' :
                $result = filter_input(INPUT_POST | INPUT_GET, $content, FILTER_SANITIZE_NUMBER_INT);
            break;
            case 'float' :
                $result = filter_input(INPUT_POST | INPUT_GET, $content, FILTER_SANITIZE_NUMBER_FLOAT);
            break;
            case 'email' :
                $result = filter_input(INPUT_POST | INPUT_GET, $content, FILTER_SANITIZE_EMAIL);
            break;
            case 'url' :
                $result = filter_input(INPUT_POST | INPUT_GET, $content, FILTER_SANITIZE_URL);
            break;
        }
        return $result;
    }

    /**
     * Filter sanitize the variables
     *
     * @param string $content
     * @param string $type
     * @return boolean
     */
    public function secureVar($content = null, $type = null) {
        $result = null;
        $content = strip_tags(trim($content));
        if (!in_array($type, ['string', 'integer', 'float', 'email', 'url'])) {
            return $result;
        }
        switch ($type) {
            case 'string' :
                $result = filter_var($content, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            break;
            case 'integer' :
                $result = filter_var($content, FILTER_SANITIZE_NUMBER_INT);
            break;
            case 'float' :
                $result = filter_var($content, FILTER_SANITIZE_NUMBER_FLOAT);
            break;
            case 'email' :
                $result = filter_var($content, FILTER_SANITIZE_EMAIL);
            break;
            case 'url' :
                $result = filter_var($content, FILTER_SANITIZE_URL);
            break;
        }
        return $result;
    }

}

?>