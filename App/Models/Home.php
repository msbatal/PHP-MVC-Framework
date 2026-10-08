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
 * Use App/Models directory
 */
namespace App\Models;

/**
 * Inherit from the main model
 * Don't change parent model path and name
 */
class Home extends \Core\Model
{

    /**
     * Main method of the model
     * Don't change the method's name
     * If this page is called by the controller without a method parameter,
     * this runs first - currently unused, no DB work needed for the
     * placeholder homepage. Add methods here as the real page grows, and
     * call them from Home::show() before the require_once() (see
     * App/Controllers/README.md).
     */
    public function show() {
    }

}

?>
