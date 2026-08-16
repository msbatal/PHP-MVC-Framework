<?php

/**
 * SunCache Class
 *
 * @category  Page Caching
 * @package   SunCache
 * @author    Mehmet Selcuk Batal <batalms@gmail.com>
 * @copyright Copyright (c) 2020, Sunhill Technology <www.sunhillint.com>
 * @license   https://opensource.org/licenses/lgpl-3.0.html The GNU Lesser General Public License, version 3.0
 * @link      https://github.com/msbatal/PHP-Cache-Class
 * @version   4.2.7
 */

class SunCache
{

    /**
     * Cache credentials
     * @var array
     */
    private $params = [];

    /**
     * Cache system (enabled or disabled)
     * @var boolean
     */
    private $cacheSystem = true;

    /**
     * Cache folder path
     * @var string
     */
    private $cacheDir = 'suncache';

    /**
     * Cache file name
     * @var string
     */
    private $cacheFile = null;

    /**
     * Cache file extension
     * @var string
     */
    private $fileExtension = 'scf';

    /**
     * Cache storage time (seconds)
     * @var integer
     */
    private $storageTime = 24 * 60 * 60;

    /**
     * Exclude files from caching (file_name.ext)
     * @var array
     */
    private $excludeFiles = [];

    /**
     * Cache status (will cache or not)
     * @var boolean
     */
    private $willCache = true;

    /**
     * Cache status (cached or not)
     * @var boolean
     */
    private $cacheStatus = false;

    /**
     * Start time (miliseconds)
     * @var integer
     */
    private $startTime = null;

    /**
     * Cache content minification
     * @var boolean
     */
    private $contentMinify = true;

    /**
     * Show page load time
     * @var boolean
     */
    private $showTime = true;

    /**
     * Website sef url status (uses or not)
     * @var boolean
     */
    private $sefUrl = false;

    /**
     * @param boolean $cacheSystem
     * @param array $defaultParams
     * @param array $customParams
     */
    public function __construct($cacheSystem = true, $defaultParams = [], $customParams = []) {
        $this->cacheSystem = $cacheSystem;
        $params = array_merge($defaultParams, $customParams);
        if ($this->cacheSystem == true) { // if cache system enabled
            if (is_array($params)) { // cache files using parameters in the array
                foreach ($params as $key => $value) {
                    $this->params[$key] = $value;
                    if (isset($key) && !is_null($value)) {
                        $this->$key = $value;
                    } else {
                        $this->$key = $this->params[$key];
                    }
                }
                $this->htaccess(); // create htaccess file
            }
            if (is_array($this->excludeFiles) && count($this->excludeFiles) > 0) {
                $activePage = explode('/', $_SERVER['SCRIPT_FILENAME']); // get the active page
                foreach ($this->excludeFiles as $key => $value) {
                    if (in_array(end($activePage), $this->excludeFiles)) {
                        $this->willCache = false; // exclude the active page if in the array
                        break;
                    }
                }
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->willCache = false; // disable caching if post request is made
            }
            if (php_sapi_name() === 'cli') {
                $this->willCache = false; // disable caching in cli (terminal) environment
            }
            if (!in_array(strtolower(pathinfo($_SERVER['SCRIPT_NAME'])['extension'] ?? ''), ['php', 'html'])) {
                $this->willCache = false; // disable caching if file is not php or html
            }
            if ($this->willCache == true) { // if the active page will cache
                if (!file_exists($_SERVER['SCRIPT_FILENAME'])) {
                    $this->willCache = false; // disable caching if file not exists
                }
                if (!file_exists(dirname(__FILE__) . '/' . $this->cacheDir)) {
                    mkdir(dirname(__FILE__) . '/' . $this->cacheDir, 0777); // create directory if not exists
                }
                if ($this->showTime) { // if load time will show on the bottom of the page (hidden)
                    list($time[1], $time[0]) = explode(' ', microtime());
                    $this->startTime = $time[1] + $time[0];
                }
                list($file, $normalizedUri) = $this->normalizeUri(); // normalize the uri
                $hash = substr(md5($normalizedUri), 0, 6); // create hash
                $this->cacheFile = dirname(__FILE__) . '/' . $this->cacheDir . '/' . $file . '_' . $hash . '.' . $this->fileExtension; // define the cache file
                $this->readCache(); // read cached file
            }
        } else {
            $this->cacheDir = $params['cacheDir'];
            $this->fileExtension = $params['fileExtension'];
            $this->htaccess(); // create htaccess file
        }
    }

    /**
     * Finish Caching
     */
    public function __destruct() {
        if ($this->cacheSystem == true && $this->willCache == true) { // if cache system enabled and page will cache
            if ($this->cacheStatus == false) { // if page not cached before
                $content = ob_get_contents();
                if (!empty(trim($content))) { // if content not empty
                    $this->writeCache($this->contentMinify ? $this->minify($content) : $content); // write content into the cache file
                }
            }
            if ($this->showTime) { // if request to show time (bottom of the cached file, hidden)
                list($time[1], $time[0]) = explode(' ', microtime());
                $finish = $time[1] + $time[0];
                $duration = number_format(($finish - $this->startTime), 6);
                echo "<!-- Load Duration: {$duration} s. -->"; // add description
            }
            ob_end_flush(); // finish caching
        }
    }

    /**
     * Read cached file
     */
    private function readCache() {
        if (time() - $this->storageTime < @filemtime($this->cacheFile)) { // if the storage time has not expired
            if (filesize($this->cacheFile) > 0) { // if not cache file empty
                $this->browserCaching(); // call browser caching method
                readfile($this->cacheFile); // read cached file
                $this->cacheStatus = true; // page cached
                ob_end_flush(); // clear output buffer
                exit();
            } else {
                unlink($this->cacheFile); // delete cached file
            }
        } else { // if the storage time has expired
            if (file_exists($this->cacheFile)) { // if cache file exists
                unlink($this->cacheFile); // delete cached file
            }
            ob_start(); // start caching
        }
    }

    /**
     * Write cached content
     *
     * @param string $content
     */
    private function writeCache($content) {
        if (empty(trim($content))) {
            return; // prevent writing empty content
        }
        $cacheFile = fopen($this->cacheFile, 'w'); // open cache file with 'write' mode
        if ($cacheFile == false) {
            throw new Exception('Cache file "'.$this->cacheFile.'" cannot be opened.');
        }
        $content .= "<!-- Cache Expiration: {$this->storageTime} s. -->"; // add storage time
        fwrite($cacheFile, $content); // write content into the cache file
        fclose($cacheFile); // close cache file
    }

    /**
     * Delete cached file(s)
     *
     * @param string|array $files
     */
    public function deleteCache($files = null) {
        if (is_array($files) && count($files) > 0) {
            $fileArray = $files;
        } else {
            $fileArray = array($files);
        }
        foreach ($fileArray as $file) {
            if (!empty($file)) {
                $list = glob(dirname(__FILE__) . '/' . $this->cacheDir . '/*' . $file . '*.' . $this->fileExtension);
                foreach ($list as $item) {
                    unlink($item); // delete cached file
                }
            }
        }
    }

    /**
     * Delete all cached files
     * 
     * @throws exception
     */
    public function emptyCache() {
        $cacheDirPath = dirname(__FILE__) . '/' . $this->cacheDir; // same base path htaccess()/deleteCache() use
        if (!file_exists($cacheDirPath)){
            throw new Exception('Cache directory "'.$this->cacheDir.'" does not exist.');
        } else {
            $cacheDir = opendir($cacheDirPath); // open cache directory
            while (($cacheFile = readdir($cacheDir)) !== false) { // read cache directory
                if (!is_dir($cacheFile) && $cacheFile != '.htaccess') { // if content is a file
                    unlink($cacheDirPath . '/' . $cacheFile); // delete cached file
                }
            }
            closedir($cacheDir); // close cache directory
        }
    }

    /**
     * Minify the page
     *
     * @param string $content
     * @return string
     */
    private function minify($content = null): string {
        preg_match_all('#<script\b[^>]*>.*?</script>#is', $content, $scripts); // temporarily replace <script> blocks
        preg_match_all('#<style\b[^>]*>.*?</style>#is', $content, $styles); // temporarily replace <style> blocks
        $placeholders = [];
        if (!empty($scripts[0])) {
            foreach ($scripts[0] as $i => $script) {
                $placeholder = "__SCRIPT_PLACEHOLDER_{$i}__";
                $placeholders[$placeholder] = $script; // placeholders for <script> blocks
                $content = str_replace($script, $placeholder, $content);
            }
        }
        if (!empty($styles[0])) {
            foreach ($styles[0] as $i => $style) {
                $placeholder = "__STYLE_PLACEHOLDER_{$i}__";
                $placeholders[$placeholder] = $style; // placeholders for <style> blocks
                $content = str_replace($style, $placeholder, $content);
            }
        }
        $replace = [
            '/\>\s+(?=\<)/s' => '>',
            '/\>\s+(?=[^\<])/s' => '> ',
            '/([^\>])\s+\</s' => '$1 <',
            '/\s{2,}/s' => ' ',
            '/\t+/s' => '',
            '/\n{2,}/s' => "\n",
            '/^\s+|\s+$/m' => '',
            '/<!--(.|\s)*?-->/' => '',
            '/\>[\r\n\t ]+\</s' => '><',
            '/}[\r\n\t ]+/s' => '}',
            '/}[\r\n\t ]+,[\r\n\t ]+/s' => '},',
            '/\)[\r\n\t ]?{[\r\n\t ]+/s' => '){',
            '/,[\r\n\t ]?{[\r\n\t ]+/s' => ',{',
            '/\),[\r\n\t ]+/s' => '), ',
            '~([\r\n\t ])?([a-zA-Z0-9]+)="([a-zA-Z0-9_/\\-]+)"([\r\n\t ])?~s' => '$1$2=$3$4'
        ];
        $content = preg_replace(array_keys($replace), array_values($replace), $content);
        foreach ($placeholders as $ph => $original) {
            $content = str_replace($ph, $original, $content); // replace placeholders with old content
        }
        return $content;
    }

    /**
     * Normalize the uri
     *
     * @return array
     */
    private function normalizeUri(): array {
        $extension = $this->sefUrl ? '.html' : '.php';
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        if (preg_match("#/(index\.(html|php))$#", $requestPath)) { // if index.html or index.php exists
            $requestPath = dirname($requestPath); // drop to base path
        }
        if (substr($requestPath, -strlen($extension)) === $extension) {
            $requestPath = substr($requestPath, 0, -strlen($extension)); // remove extension
        }
        $segments = array_filter(explode('/', trim($requestPath, '/'))); // prepare segments
        $file = implode('_', $segments); // add underscore
        $file = $file ?: 'index'; // fallback for main page
        $normalizedUri = $requestPath; // generate normalized URI
        if ($queryString) {
            $normalizedUri .= '?' . $queryString; // add query strings
        }
        return [$file, $normalizedUri];
    }

    /**
     * Activate browser caching
     */
    private function browserCaching() {
        header("Cache-Control: public, max-age=".$this->storageTime.", must-revalidate"); // send cache-control header
        header("Pragma: cache"); // send pragma header
        $etag = md5_file($this->cacheFile); // create and hash etag value
        $lastModified = filemtime($this->cacheFile); // get last modified time
        header("ETag: \"$etag\""); // send etag header
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT"); // send last-modified header
        if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === "\"$etag\"") || (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $lastModified)) { // if content hasn't changed
            header("HTTP/1.1 304 Not Modified");
            exit;
        }
    }

    /**
     * Create htaccess file
     */
    private function htaccess() {
        $cacheDirPath = dirname(__FILE__) . '/' . $this->cacheDir;
        if (!file_exists($cacheDirPath)) {
            mkdir($cacheDirPath, 0777, true); // ensure the directory exists before writing into it
        }
        $htaccessFile = $cacheDirPath . '/.htaccess';
        if (!file_exists($htaccessFile)) { // if htaccess file not exists
            file_put_contents($htaccessFile, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\nOptions All -Indexes"); // create htaccess file
        }
    }

}

?>
