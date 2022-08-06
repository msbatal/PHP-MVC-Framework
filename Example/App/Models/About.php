<?php

/**
 * Sample Model File for Sunhill Framework
 *
 * (c) Sunhill Technology <www.sunhillint.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Namespace for model
 * Use App/Models directory
 */
namespace App\Models;

/**
 * Inherit from the main model
 * Change class name (use page's name always)
 * Don't change parent model path and name
 */
class About extends \Core\Model
{

    /**
     * Main method of the model
     * Don't change the method's name
     * If this page is called by the controller without method parameter, this will work first
     */
    public function show() {
        $result = ($this->pdo)->select('about')
                              ->run(); // select all records from the table
        return $result; // return the result to the controller
    }

    /**
     * Other optional methods related with db called from the controller
     */
    
}

?>