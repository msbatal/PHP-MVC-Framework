<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Namespace for controller
 * Use App/Controllers/Admin directory
 */
namespace App\Controllers\Admin;

/**
 * Inherit from the main controller
 * Don't change parent controller path and name
 *
 * Admin panel logout: GET shows a confirmation page (App/Views/Admin/Logout.php),
 * POST performs the actual logout - CSRF protected by Core\Controller's
 * blanket POST check, same pattern as App/Controllers/Auth.php::logout().
 */
class Logout extends \Core\Controller
{

    /**
     * Construct method of the inherited controller
     * Don't change the parameters if not needed
     *
     * @param string $view
     * @param object $model
     * @param array $params
     */
    public function __construct($view = null, $model = null, $params = null) {
        $this->view = $view; // view file's address (in views directory)
        $this->model = $model; // model object (created by parent class)
        $this->params = $params; // parameters (if needed for model)
    }

    /**
     * Show the logout confirmation (GET) or log the current session out (POST)
     */
    public function show() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $GLOBALS['auth']->logout();
            $_SESSION['flash_notify'] = ['type' => 'success', 'message' => _tr('admin.flash_logged_out')];
            header('Location: ' . SYS_BASEURL . '/' . strtolower($GLOBALS['sunApp']->routes[0]) . '/Admin/Login');
            exit;
        }
        require_once ($this->view); // include view file
    }

}

?>
