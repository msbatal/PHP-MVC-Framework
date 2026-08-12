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
 * Placeholder admin landing page - proves the panel's routing/auth pipeline
 * works. Gated behind login via $authRequired below (see Core/README.md's
 * auth() section). Replace with your real admin content.
 */
class Dashboard extends \Core\Controller
{

    /**
     * Methods that require an authenticated visitor (see Core\Controller::auth())
     * @var array
     */
    public $authRequired = ['show'];

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
     * Main method of the controller
     * Don't change the method's name
     */
    public function show() {
        require_once ($this->view); // include view file
    }

    /**
     * Other optional methods used in URL's custom [Method Name] part
     * Structure: .....[URL]...../Admin/Dashboard/[Method Name]/[Parameters]
     */

}

?>
