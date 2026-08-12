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
 * Admin panel login form, built on the same SunAuth instance as the front
 * end (System/SunAuth.php) - one shared session/user table, no separate
 * admin login system. See App/Controllers/Admin/README.md for the request
 * flow and App/Views/Admin/Login.php for the matching form.
 */
class Login extends \Core\Controller
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
     * Show the login form (GET) or process the submitted credentials (POST)
     * Csrf validation on the POST branch already runs in Core\Controller before this is called
     */
    public function show() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawEmail = isset($_POST['email']) ? $_POST['email'] : '';
            $email = $GLOBALS['filter']->sanitize($rawEmail, 'email')->result();
            $password = isset($_POST['password']) ? (string) $_POST['password'] : ''; // never HTML-sanitize a password, only compared via hash

            $currentLang = strtolower($GLOBALS['sunApp']->routes[0]);
            if ($GLOBALS['auth']->login($email, $password) && $GLOBALS['auth']->isLoggedIn()) {
                $_SESSION['flash_notify'] = ['type' => 'success', 'message' => _tr('admin.flash_welcome_back')];
                header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Admin/Dashboard');
                exit;
            }
            header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Admin/Login?error=1');
            exit;
        }
        require_once ($this->view); // include view file
    }

}

?>
