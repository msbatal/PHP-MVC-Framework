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
    private $routes = [];


    public function __construct() {
        if (SYS_SYSERR === true) { // if system error printing set true
            set_exception_handler(function($exception) {
                echo '<b>[Sunhill] Exception:</b> '.$exception->getMessage();
            });
        }
    }

    /**
     * Catch system errors
     *
     * @param string $message
     * @throws exception
     */
    public function catchError($message = null) {
        if (SYS_SYSERR === true) { // if system error printing set true
            throw new \Exception($message);
        } else { // if set false
            if (!empty(SYS_ERRPAGE) && file_exists(SYS_BASEPATH . '/App/Controllers/' . ucfirst(SYS_ERRPAGE) . '.php')) {
                header('Location: ' . SYS_BASEURL . '/' . SYS_ERRPAGE); // redirect to error page
            } else {
                header('Location: ' . SYS_BASEURL); // redirect to home page
            }
        }
    }

    /**
     * Parse url
     *
     * @param string $url
     * @return array
     */
    public function parseUrl($url = null) {
        if (empty($url)) {$url = ucfirst(SYS_HOMEPAGE);} // set url as home page
        $url = explode('/', str_replace('.php', '', rtrim($url, '/'))); // parse url
        foreach ($url as $key => $value) {
            $this->routes[$key] = ucfirst($value); // get parameters from url
        }
        // if page name is empty
        if (!isset($this->routes[0]) || is_null($this->routes[0]) || $this->routes[0] == 'Index') {
            $this->routes[0] = ucfirst(SYS_HOMEPAGE); // home page
        }
        // if page name includes php extension
        if (strstr($this->routes[0], '.php')) {
            $this->routes[0] = ucfirst(SYS_ERRPAGE); // error page
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
