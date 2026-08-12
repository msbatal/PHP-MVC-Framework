<?php

/**
 * SunFilter Class
 *
 * @category  Filtering (Sanitization & Validation)
 * @package   SunFilter
 * @author    Mehmet Selcuk Batal <batalms@gmail.com>
 * @copyright Copyright (c) 2021, Sunhill Technology <www.sunhillint.com>
 * @license   https://opensource.org/licenses/lgpl-3.0.html The GNU Lesser General Public License, version 3.0
 * @link      https://github.com/msbatal/PHP-Data-Security-Class
 * @version   1.4.2
 */

class SunFilter
{
    /**
     * String that holds Sanitized/Validated value
     * @var string
     */
    private $result;

    /**
     * Sanitize data by type
     *
     * @param string $data
     * @param string $type
     * @throws exception
     * @return object
     */
    public function sanitize($data = null, $type = null) {
        /*
        if (empty($type) || empty($data)) {
            throw new Exception('Missing parameter for sanitization.');
        }
        */
        $this->result = null;
        switch ($type) {
            case 'string':
                if (version_compare(phpversion(), '8.1.0', '<')) {
                    $this->result = filter_var($data, FILTER_SANITIZE_STRING); //deprecated as of PHP 8.1.0
                } else {
                    $this->result = htmlspecialchars(strip_tags($data)); //use htmlspecialchars() and strip_tags instead
                }
            break;
            case 'float':
                $this->result = (float) filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT);
            break;
            case 'integer':
                $this->result = (int) filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            break;
            case 'url':
                $this->result = filter_var($data, FILTER_SANITIZE_URL);
            break;
            case 'email':
                $this->result = filter_var($data, FILTER_SANITIZE_EMAIL);
            break;
            case 'special':
                $this->result = filter_var($data, FILTER_SANITIZE_SPECIAL_CHARS);
            break;
            default:
                $this->result = null;
            break;
        }
        return $this;
    }

    /**
     * Validate data by type
     *
     * @param string $data
     * @param string $type
     * @throws exception
     * @return object
     */
    public function validate($data = null, $type = null) {
        /*
        if (empty($type) || empty($data)) {
            throw new Exception('Missing parameter for validation.');
        }
        */
        $this->result = false;
        switch ($type) {
            case 'boolean':
                $this->result = filter_var($data, FILTER_VALIDATE_BOOLEAN) == true ? true : false;
            break;
            case 'float':
                $this->result = filter_var($data, FILTER_VALIDATE_FLOAT) == true ? true : false;
            break;
            case 'integer':
                $this->result = filter_var($data, FILTER_VALIDATE_INT) == true ? true : false;
            break;
            case 'email':
                $this->result = filter_var($data, FILTER_VALIDATE_EMAIL) == true ? true : false;
            break;
            case 'url':
                $this->result = filter_var($data, FILTER_VALIDATE_URL) == true ? true : false;
            break;
            case 'domain':
                $this->result = filter_var($data, FILTER_VALIDATE_DOMAIN) == true ? true : false;
            break;
            case 'ip':
                $this->result = filter_var($data, FILTER_VALIDATE_IP) == true ? true : false;
            break;
            case 'mac':
                $this->result = filter_var($data, FILTER_VALIDATE_MAC) == true ? true : false;
            break;
            default:
                $this->result = false;
            break;
        }
        return $this;
    }

    /**
     * Return the Sanitized/Validated data
     *
     * @return string
     */
    public function result() {
        return $this->result;
    }

}

?>
