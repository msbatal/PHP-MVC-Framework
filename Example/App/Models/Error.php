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
 * Namespace for model
 * Use App/Models directory
 */
namespace App\Models;

/**
 * Inherit from the main model
 * Don't change parent model path and name
 */
class Error extends \Core\Model
{

    /**
     * Main method of the model
     * Don't change the method's name
     * If this page is called by the controller without method parameter, this will work first
     */
    public function show() {
        // There's nothing here, because this 404 page does not need db
    }
    
}

?>