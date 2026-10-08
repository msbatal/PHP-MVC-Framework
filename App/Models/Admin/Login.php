<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Namespace for model
 * Use App/Models/Admin directory
 */
namespace App\Models\Admin;

/**
 * Inherit from the main model
 * Don't change parent model path and name
 */
class Login extends \Core\Model
{

    /**
     * Main method of the model
     * Don't change the method's name
     */
    public function show() {
        // no direct db work here - Admin\Login controller uses $GLOBALS['auth'] (SunAuth) directly
    }

}

?>
