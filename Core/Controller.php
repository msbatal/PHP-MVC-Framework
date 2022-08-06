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
 * All custom controller classes inherit from this
 * Don't change the class name
 */
class Controller
{
    /**
     * Controller
     * @var string
     */
    private $controller = null;

    /**
     * Method
     * @var string
     */
    private $method = null;

    /**
     * View
     * @var string
     */
    public $view = null;

    /**
     * Model
     * @var object
     */
    public $model;

    /**
     * Parameters
     * @var array
     */
    public $params = [];

    /**
     * Controller file
     * @var string
     */
    private $contAddr = null;

    /**
     * Controller class
     * @var string
     */
    private $contClass = null;

    /**
     * Model file
     * @var string
     */
    private $modelAddr = null;

    /**
     * Model class
     * @var string
     */
    private $modelClass = null;

    /**
     * View file
     * @var string
     */
    private $viewAddr = null;

    /**
     * @param array $params
     */
    public function __construct($params = null) {
        $this->controller = $params[0]; // define controller
        $this->method = isset ($params[1]) ? $params[1] : 'show'; // define method (if not exists, set default)
        $this->params = array_diff_key($params, array_flip([0, 1])); // rebuild parameters
        $this->contClass = '\App\Controllers\\' . $this->controller; // controller class
        $this->modelClass = '\App\Models\\' . $this->controller; // model class
        $this->contAddr = SYS_BASEPATH . '/App/Controllers/' . $this->controller . '.php'; // controller file
        $this->modelAddr = SYS_BASEPATH . '/App/Models/' . $this->controller . '.php'; // model file
        $this->viewAddr = SYS_BASEPATH . '/App/Views/' . $this->controller . '.php'; // view file
        $this->check(); // check existing of files, classes and methods
        $this->cache(); // cache the page (if set enabled)
        $this->call($params); // create model and call controller
    }

    /**
     * Create model and call controller
     *
     * @param array $params
     */
    private function call($params = null) {
        $model = new $this->modelClass; // create model object
        $controller = new $this->contClass($this->viewAddr, $model, $params); // create controller object
        $controller->{$this->method}(); // call controller method
    }

    /**
     * Check existing of files, classes and methods
     */
    public function check() {
        if (!file_exists($this->contAddr)) { // if controller file does not exist
            $msg = 'Controller file "'.$this->contClass.'" not found.';
        }
        else if (!class_exists($this->contClass)) { // if controller class does not exist
            $msg = 'Controller class "'.$this->contClass.'" does not exist.';
        }
        else if (!file_exists($this->modelAddr)) { // if model file does not exist
            $msg = 'Model file "'.$this->contClass.'" not found.';
        }
        else if (!class_exists($this->modelClass)) { // if model class does not exist
            $msg = 'Model class "'.$this->contClass.'" does not exist.';
        }
        else if (!method_exists($this->contClass, $this->method)) { // if method does not exist
            $msg = 'Method "'.$this->method.'" does not exist in "'.$this->contClass.'" class.';
        }
        else if (!file_exists($this->viewAddr)) { // if view file does not exist
            $msg = 'View file "'.$this->contClass.'" not found.';        
        } else {
            $msg = null;
        }
        // if an error occurred
        if (!empty($msg)) {
            $GLOBALS['sunApp']->catchError($msg); // send error message
        }
    }

    /**
     * Cache the page
     */
    private function cache() {
        // if page caching is enabled and allowed to cache the page
        if (SYS_PGCACHE === true && !in_array(strtolower($this->controller), SYS_CHEXCLUDE)) {
            $cache = new \System\SunCache(true, $GLOBALS['cacheConfig']); // use SunCache class and create cache object
        }
    }

}

?>