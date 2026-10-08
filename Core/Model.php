<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Namespace: use Core directory
 */
namespace Core;

/**
 * All custom model classes inherit from this
 * Don't change the class name
 */
class Model
{
    
    /**
     * PDO instance
     * @var object
     */
    public $pdo;

    public function __construct() {
        // db settings can be changed in System/Config.php file
        if (empty(DB_HOST) || empty(DB_USERNAME) || empty(DB_PASSWORD) || empty(DB_DBNAME) || empty(DB_PORT)) {
            // No DB configured - leave $pdo null instead of 500ing the whole
            // request. A model that never touches $this->pdo (e.g. one
            // reading static content from its own PHP file) works fine with
            // no database at all; a model that does try to query with
            // $pdo === null fails right there, at the point of actual use,
            // which is the honest place for that error to surface.
            $this->pdo = null;
        } else {
            // reuse an already open connection (e.g. the one init.php created for $auth) instead of opening a second one
            $existing = \SunDB::getInstance();
            $this->pdo = $existing instanceof \SunDB ? $existing : new \SunDB(null, DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DBNAME, DB_PORT);
        }
    }

}

?>