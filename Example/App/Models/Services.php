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
class Services extends \Core\Model
{

    /**
     * Main method of the model
     * Don't change the method's name
     * If this page is called by the controller without method parameter, this will work first
     */
    public function show() {
        $result = ($this->pdo)->select('services')
                              ->orderBy('id', 'asc')
                              ->run(); // select all records from the table
        return $result; // return the result
    }
    
    /**
     * Optional method for this controller
     * If this page is called by the controller with 'detail' parameter, this method will work
     */
    public function detail($params = null) {
        $id = $GLOBALS['sunApp']->secureVar($params[2], 'integer'); // get parameter
        if (intval($id) > 0) { // if parameter exists
            $select = ($this->pdo)->select('services')
                                  ->where('id', $id, '=')
                                  ->limit(1)
                                  ->run(); // select one record from the table
            if (($this->pdo)->rowCount() > 0) { // if record exists (affected row count greater than zero)
                $update = ($this->pdo)->update('services', ['view' => $select[0]['view']+1])
                                      ->where('id', $id, '=')
                                      ->run(); // update this record in the table
                return $select; // return the select query result
            } else { // if record does not exist
                return false;
            }
        } else { // if parameter does not exist
            return false;
        }
    }

    /**
     * Other optional methods related with db called from the controller
     */

}

?>