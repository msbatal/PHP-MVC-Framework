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
     * The active SunCache instance, when caching applies to this request
     * (see cache() below). Kept as a property, not a method-local
     * variable, specifically so it survives past cache()'s own return -
     * SunCache's constructor opens an output buffer and its __destruct()
     * is what closes it and writes the cache file, so it must stay alive
     * for the whole request (until this Controller object itself is
     * destroyed at script end), not just for the duration of cache().
     * @var object
     */
    public $cache;

    /**
     * @param array $params
     */
    public function __construct($params = null) {
        $this->controller = $params[1]; // define controller
        // "Admin" is a reserved routing group: App/Controllers/Admin, App/Models/Admin
        // and App/Views/Admin hold one controller/model/view trio per admin page
        // (Login, Dashboard, ...), same as the flat trios above them. When routes[1]
        // is "Admin", routes[2] names the page and routes[3] becomes the method
        // (default 'show') - see App/Controllers/Admin/README.md.
        if (strtolower($this->controller) === 'admin' && isset($params[2])) {
            $this->controller = 'Admin\\' . $params[2]; // define controller (admin group)
            $this->method = isset ($params[3]) ? $params[3] : 'show'; // define method (if not exists, set default)
        } else {
            $this->method = isset ($params[2]) ? $params[2] : 'show'; // define method (if not exists, set default)
        }
        $this->params = $params; // define parameters
        $this->contClass = '\App\Controllers\\' . $this->controller; // controller class
        $this->modelClass = '\App\Models\\' . $this->controller; // model class
        $this->contAddr = SYS_BASEPATH . '/App/Controllers/' . str_replace('\\', '/', $this->controller) . '.php'; // controller file
        $this->modelAddr = SYS_BASEPATH . '/App/Models/' . str_replace('\\', '/', $this->controller) . '.php'; // model file
        $this->viewAddr = SYS_BASEPATH . '/App/Views/' . str_replace('\\', '/', $this->controller) . '.php'; // view file
        $this->check(); // check existing of files, classes and methods
        $this->auth(); // check login/role requirement for the requested method
        $this->cache(); // cache the page (if set enabled)
        $this->csrf(); // validate csrf token for post requests
        $this->call(); // create model and call controller
    }

    /**
     * Create model and call controller
     *
     * @param array $params
     */
    private function call() {
        $model = new $this->modelClass; // create model object
        $controller = new $this->contClass($this->viewAddr, $model, $this->params); // create controller object
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
            $GLOBALS['sunApp']->catchError($msg, 404); // send error message
        }
    }

    /**
     * Check login/role requirement for the requested method
     * Controllers opt in by declaring public $authRequired = ['method', ...] and, optionally, public $authRole = 'role'
     */
    private function auth() {
        $vars = get_class_vars($this->contClass);
        $required = isset($vars['authRequired']) ? array_map('strtolower', $vars['authRequired']) : [];
        if (in_array(strtolower($this->method), $required, true)) { // method names from the url are ucfirst'd, compare case-insensitively
            $role = isset($vars['authRole']) ? $vars['authRole'] : null;
            try {
                $auth = $GLOBALS['auth'];
                $authorized = $auth->isLoggedIn() && (is_null($role) || $auth->hasRole($role));
            } catch (\Throwable $e) {
                $authorized = false; // fail closed: treat an unreachable auth backend as "not authorized"
            }
            if (!$authorized) {
                $GLOBALS['sunApp']->catchError('Authentication required.', 401); // send error message
            }
        }
    }

    /**
     * Validate csrf token on post requests
     */
    private function csrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $raw = isset($_POST['csrf']) ? $_POST['csrf'] : ''; // SunFilter::sanitize() expects a string, not null
            $token = $GLOBALS['filter']->sanitize($raw, 'string')->result(); // sanitize submitted token
            if (empty($token) || !$GLOBALS['functions']->csrfToken($token)) {
                $GLOBALS['sunApp']->catchError('CSRF token validation failed.', 403); // send error message
            }
        }
    }

    /**
     * Cache the page
     *
     * Skipped for a logged-in visitor even on a page that isn't in
     * SYS_CHEXCLUDE: every view's shared nav renders differently based on
     * $GLOBALS['auth']->isLoggedIn() (Login/Register links vs. a Logout
     * button, see App/Views/Home.php). SunCache has no concept of "vary
     * by session" - it caches one file per URL, full stop. Without this
     * check, whichever visitor's login state happened to render the page
     * first gets baked into the cache file and served to everyone else
     * until it expires. Net effect: caching only ever writes/reads the
     * logged-out render of a page, which is what the overwhelming
     * majority of traffic to a public/marketing page actually is.
     */
    private function cache() {
        // if page caching is enabled and allowed to cache the page
        if (SYS_PGCACHE === true && !in_array(strtolower($this->controller), SYS_CHEXCLUDE) && !$GLOBALS['auth']->isLoggedIn()) {
            // ob_start() here, before SunCache even exists, works around a
            // real bug in System/SunCache.php (not ours to hand-edit - see
            // System/README.md): on a cache HIT, readCache() calls
            // ob_end_flush() without ever having called ob_start() on that
            // code path, which throws a "no buffer to flush" notice
            // straight into the response. Pre-opening a buffer here means
            // there's always one for it to close, on both the hit and
            // miss paths.
            ob_start();
            // $this->cache, not a local $cache - see the property's doc
            // comment above for why (SunCache's __destruct() must not
            // fire until the whole request is done rendering).
            $this->cache = new \SunCache(true, $GLOBALS['cacheConfig']); // use SunCache class and create cache object
        }
    }

}

?>