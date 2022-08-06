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
            $GLOBALS['sunApp']->catchError('Database settings can not be empty.'); // send error message
        } else {
            // use SunDB class for database connection
            $this->pdo = new \System\SunDB(null, DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DBNAME, DB_PORT); // create pdo object
        }
    }

    public function query($query = null) {
        $result = ($this->pdo)->rawQuery($query)->run(); // execute query
        return $result; // return the result
    }

    public function showQuery() {
        $result = ($this->pdo)->showQuery();  // get last executed query
        return $result; // return the result
    }

    public function rowCount() {
        $result = ($this->pdo)->rowCount();  // get total row count
        return $result; // return the result
    }

}

?>