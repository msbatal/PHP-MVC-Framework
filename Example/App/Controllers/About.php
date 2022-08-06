<?php

/**
 * Sample Controller File for Sunhill Framework
 *
 * (c) Sunhill Technology <www.sunhillint.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Namespace for controller
 * Use App/Controllers directory
 */
namespace App\Controllers;

/**
 * Inherit from the main controller
 * Change class name (use page's name always)
 * Don't change parent controller path and name
 */
class About extends \Core\Controller
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
     * If this page is called by a browser without method parameter, this will work first
     */
    public function show() {
        if (!empty($this->model)) { // if this page needs database
            $result = ($this->model)->show(); // call model class' show method
        }
        require_once ($this->view); // include view file (with $result content)
    }

    /**
     * Other optional methods used in URL's custom [Method Name] part
     * Structure: .....[URL]...../[Class Name]/[Method Name]/[Parameters]
     */

}

?>