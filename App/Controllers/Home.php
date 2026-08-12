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
 * Use App/Controllers directory
 */
namespace App\Controllers;

/**
 * Inherit from the main controller
 * Don't change parent controller path and name
 */
class Home extends \Core\Controller
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
     * Main method of the controller
     * Don't change the method's name
     * If this page is called by a browser without a method parameter, this
     * runs first - SYS_HOMEPAGE ('home') + Core\App::parseUrl()'s empty-page
     * fallback both route here.
     */
    public function show() {
        require_once ($this->view); // include view file
    }

    /**
     * Other optional methods used in URL's custom [Method Name] part
     * Structure: .....[URL]...../[Class Name]/[Method Name]/[Parameters]
     */

}

?>
