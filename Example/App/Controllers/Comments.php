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
class Comments extends \Core\Controller
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
     * Optional method for this controller
     * If this page is called by a browser with 'send' parameter, this method will work
     */
    public function send() {
        if (is_array($_POST) && count($_POST) > 0) { // if posted values exist
            $this->params = $_POST; // assign posted values to the variable
        }
        if (count($this->params) > 0) { // if posted values exist
            if (!empty($this->model)) { // if this page needs database
                $result = ($this->model)->send($this->params); // call model class' send method with posted values
            }
            if (is_bool($result) && $result === true) { // if model sends the result 
                $_SESSION["sunalert"] = "<div class='alert alert-success'>Successful! Your comment added to the list.</div>";
            } else {
                $_SESSION["sunalert"] = "<div class='alert alert-danger'>Error! Please try again later.</div>";
            }
        } else {
            header('Location: '.SYS_BASEURL.'/comments'); // if posted values does not exist
        }
        $result = ($this->model)->show(); // call model class' show method
        require_once ($this->view); // include view file (with $result content)
    }

    /**
     * Other optional methods used in URL's custom [Method Name] part
     * Structure: .....[URL]...../[Class Name]/[Method Name]/[Parameters]
     */

}

?>