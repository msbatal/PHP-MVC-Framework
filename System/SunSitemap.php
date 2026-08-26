<?php

/**
 * SunSitemap Class
 *
 * @category  Sitemap Generator
 * @package   SunSitemap
 * @author    Mehmet Selcuk Batal <batalms@gmail.com>
 * @copyright Copyright (c) 2020, Sunhill Technology <www.sunhillint.com>
 * @license   https://opensource.org/licenses/lgpl-3.0.html The GNU Lesser General Public License, version 3.0
 * @link      https://github.com/msbatal/PHP-Sitemap-Generator
 * @version   2.6.0
 */

class SunSitemap
{

    /**
     * Sitemap file name
     * @var string
     */
    private $sitemapFile = "sitemap.xml";

    /**
     * Sitemap index file name
     * @var string
     */
    private $sitemapIndexFile = "sitemap-index.xml";

    /**
     * Robots file name
     * @var string
     */
    private $robotsFile = "robots.txt";

    /**
     * Llms file name
     * @var string
     */
    private $llmsFile = "llms.txt";

    /**
     * Sitemap base url
     * @var string
     */
    public $baseUrl;

    /**
     * Sitemap relative path
     * @var string
     */
    public $relPath;

    /**
     * Sitemap absolute path
     * @var string
     */
    private $absPath;

    /**
     * Maximum Urls Per Sitemap (max 50000)
     * @var integer
     */
    public $maxUrl = 50000;

    /**
     * Create zipped copy of sitemap
     * @var boolean
     */
    public $createZip = false;

    /**
     * Update/create robots.txt
     * @var boolean
     */
    public $robots = false;

    /**
     * Create llms.txt
     * @var boolean
     */
    public $llms = false;

    /**
     * Urls to add sitemap
     * @var array
     */
    private $urls = [];

    /**
     * Sitemaps
     * @var array
     */
    private $sitemaps = [];

    /**
     * Sitemap index
     * @var array
     */
    private $sitemapIndex = [];

    /**
     * Sitemap full url
     * @var string
     */
    private $sitemapUrl;

    /**
     * Calculated start time
     * @var integer
     */
    private $startTime = 0;

    /**
     * Calculated finish time
     * @var integer
     */
    private $finishTime = 0;

    /**
     * @param string $baseUrl
     * @param string $relPath
     * @param integer $maxUrl
     * @param boolean $createZip
     * @param boolean $robots
     * @param boolean $llms
     */
    public function __construct($baseUrl = null, $relPath = null, $maxUrl = null, $createZip = null, $robots = null, $llms = null) {
        $this->startTime = microtime(true); // get process start time
        if (!empty($baseUrl)) {
            $this->baseUrl = $baseUrl . '/'; // set base url
        } else {
            $this->baseUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/'; // set default base url
        }
        if (!empty($relPath)) {
            if (!file_exists($relPath)){
                throw new Exception('Sitemap path "'.$relPath.'" does not valid.');
            }
            $this->relPath = str_replace(['../','..','./'], '', $relPath) . '/'; // set relative path
            $this->absPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->relPath; // set absolute path
        } else {
            $this->relPath = null; // set default relative path
            $this->absPath = $_SERVER['DOCUMENT_ROOT'] . '/'; // set default absolute path
        }
        if (is_int($maxUrl)) {
            $this->maxUrl = $maxUrl; // set maximum urls per sitemap
        }
        if (is_bool($createZip)) {
            $this->createZip = $createZip; // create zipped copy of sitemap
        }
        if (is_bool($robots)) {
            $this->robots = $robots; // update/create robots.txt
        }
        if (is_bool($llms)) {
            $this->llms = $llms; // create llms.txt
        }
    }

    /**
     * Add URL(s) into the sitemap file
     *
     * @param array|string $urls
     * @param string $lastmod
     * @param string $changefreq
     * @param string $priority
     * @throws exception
     */
    public function addUrl($urls = null, $lastmod = null, $changefreq = null, $priority = null) {
        if (is_array($urls)) { // if urls sent in an array
            foreach ($urls as $url) {
                $this->addUrl(
                    isset ($url[0]) ? $url[0] : null,
                    isset ($url[1]) ? $url[1] : null,
                    isset ($url[2]) ? $url[2] : null,
                    isset ($url[3]) ? $url[3] : null
                );
            }
        } else { // if urls sent separately
            if ($urls == null) {
                throw new Exception('URL parameter is required. At least this parameter should be sent.');
            }
            if (strlen($urls) > 2048) {
                throw new Exception('URL length cannot exceed 2048 characters.');
            }
            $urlArray = [];
            $urlArray['loc'] = rtrim($this->baseUrl, '/') . '/' . ltrim($this->relPath . $urls, '/'); // add page url attribute
            if (!empty($lastmod)) {
                $urlArray['lastmod'] = $lastmod; // add page last modification attribute
            }
            if (!empty($changefreq)) {
                $urlArray['changefreq'] = $changefreq; // add change frequency attribute
            }
            if (!empty($priority)) {
                $urlArray['priority'] = $priority; // add page priority attribute
            }
            $this->urls[] = $urlArray;
        }
    }

    /**
     * Create sitemap file(s) and sitemap index file
     *
     * @throws exception
     * @return object
     */
    public function createSitemap() {
        if (!isset($this->urls)) {
            throw new Exception('To create a sitemap, first call the "addUrl" method.');
        }
        if ($this->maxUrl > 50000) {
            throw new Exception('Each sitemap file can contain a maximum of 50,000 URLs.');
        }
        foreach (array_chunk($this->urls, $this->maxUrl) as $sitemap) { // create xml for sitemap
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>'); // add xml header
            foreach ($sitemap as $url) {
                $row = $xml->addChild('url');
                $row->addChild('loc', htmlspecialchars($url['loc'], ENT_QUOTES, 'utf-8')); // add page url
                if (isset($url['lastmod'])) {
                    $row->addChild('lastmod', $url['lastmod']); // add page last modification
                }
                if (isset($url['changefreq'])) {
                    $row->addChild('changefreq',$url['changefreq']); // add change frequency
                }
                if (isset($url['priority'])) {
                    $row->addChild('priority',$url['priority']); // add page priority
                }
            }
            if (strlen($xml->asXML()) > 1024*1024*50) {
                throw new Exception('The size of the sitemap file is more than 50 MB. Please update your sitemap settings to reduce the number of pages.');
            }
            $this->sitemaps[] = $xml->asXML();
        }
        if (sizeof($this->sitemaps) > 1000) {
            throw new Exception("The sitemap directory (index) can contain a maximum of 1,000 sitemap files.");
        }
        if (sizeof($this->sitemaps) > 1) { // create xml for sitemap index
            for ($i=0; $i < sizeof($this->sitemaps); $i++) {
                $this->sitemaps[$i] = array(str_replace('.xml', ($i+1).'.xml.gz', $this->sitemapFile), $this->sitemaps[$i]);
            }
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>'); // add xml header
            foreach ($this->sitemaps as $sitemap) {
                $row = $xml->addChild('sitemap');
                $row->addChild('loc', rtrim($this->baseUrl, '/') . '/' . ltrim($this->relPath . htmlentities($sitemap[0]), '/')); // add page url
                $row->addChild('lastmod', date('c')); // add page last modification
            }
            $this->sitemapUrl = rtrim($this->baseUrl, '/') . '/' . ltrim($this->relPath . $this->sitemapIndexFile, '/');
            $this->sitemapIndex = array($this->sitemapIndexFile, $xml->asXML());
        } else {
            if ($this->createZip) { // if will create gzip file
                $this->sitemapUrl = rtrim($this->baseUrl, '/') . '/' . ltrim($this->relPath . $this->sitemapFile, '/') . '.gz';
            } else {
                $this->sitemapUrl = rtrim($this->baseUrl, '/') . '/' . ltrim($this->relPath . $this->sitemapFile, '/');
            }
            $this->sitemaps[0] = array($this->sitemapFile, $this->sitemaps[0]);
        }
        if (!isset($this->sitemaps)) {
            throw new Exception('To create/update the sitemap file, first call the "createSitemap" method.');
        }
        if (count($this->sitemapIndex) > 0) { // if will create sitemap index
            $file = fopen($this->absPath . $this->sitemapIndex[0], 'w');
            fwrite($file, $this->sitemapIndex[1]);
            fclose($file);
            if (function_exists('ob_gzhandler') && ini_get('zlib.output_compression')) { // check if gzip extension enabled
                foreach ($this->sitemaps as $sitemap) {
                    $file = gzopen($this->absPath . $sitemap[0], 'w');
                    gzwrite($file, $sitemap[1]);
                    gzclose($file);
                }
            } else {
                throw new Exception('The GZip file compression module is not enabled.');
            }
        } else {  // if will create sitemap
            $file = fopen($this->absPath . $this->sitemaps[0][0], 'w');
            fwrite($file, $this->sitemaps[0][1]);
            fclose($file);
            if ($this->createZip) {
                if (function_exists('ob_gzhandler') && ini_get('zlib.output_compression')) { // check if gzip extension enabled
                    $file = gzopen($this->absPath . $this->sitemaps[0][0] . '.gz', 'w');
                    gzwrite($file, $this->sitemaps[0][1]);
                    gzclose($file);
                } else {
                    throw new Exception('The GZip file compression module is not enabled.');
                }
            }
        }
        return $this;
    }

    /**
     * Create/update Robots.txt file
     *
     * @param array $disallow Paths to disallow
     * @throws exception
     * @return object
     */
    public function updateRobots($disallow = []) {
        if (!isset($this->sitemaps)) {
            throw new Exception('To create/update the robots.txt file, first call the "createSitemap" method.');
        }
        if (!$this->robots) {
            return $this; // robots.txt update disabled via constructor
        }
        $hasDisallow = is_array($disallow) && count($disallow) > 0;
        $robotsContent = $hasDisallow ? "User-agent: *\nAllow: /\n" : "User-agent: *\nDisallow:\n";
        if ($hasDisallow) {
            foreach ($disallow as $path) {
                $robotsContent .= "Disallow: " . $path . "\n";
            }
        }
        $aiCrawlers = [
            'GPTBot', 'ChatGPT-User', 'Google-Extended', 'CCBot', 'anthropic-ai',
            'ClaudeBot', 'PerplexityBot', 'Applebot-Extended', 'Bytespider', 'meta-externalagent', 'Perplexity-User', 'Claude-Web', 'OAI-SearchBot'
        ];
        foreach ($aiCrawlers as $crawler) {
            $robotsContent .= "\nUser-agent: " . $crawler . "\nAllow: /\n";
        }
        if ($this->llms) {
            if (!file_exists($this->absPath . $this->llmsFile)) {
                file_put_contents($this->absPath . $this->llmsFile, ''); // create empty llms.txt if missing
            }
            $robotsContent .= "\n# AI context file: " . $this->baseUrl . $this->relPath . $this->llmsFile . "\n";
        }
        if (count($this->sitemapIndex) == 0) {
            $robotsContent .= "\nSitemap: " . $this->baseUrl . $this->relPath . $this->sitemapFile;
        } else {
            $robotsContent .= "\nSitemap: " . $this->baseUrl . $this->relPath . $this->sitemapIndexFile;
        }
        if ($this->createZip && count($this->sitemapIndex) == 0) {
            $robotsContent .= "\nSitemap: " . $this->baseUrl . $this->relPath . $this->sitemapFile . '.gz';
        }
        if (file_exists($this->absPath . $this->robotsFile)) {
            unlink($this->absPath . $this->robotsFile); // always start clean - never preserve/merge an existing file
        }
        file_put_contents($this->absPath . $this->robotsFile, $robotsContent); // create robots.txt file
        return $this;
    }

    /**
     * Show total memory usage
     *
     * @return float
     */
    public function memoryUsage() {
        return number_format(memory_get_peak_usage() / (1024 * 1024), 2); // calculate memory usage
    }

    /**
     * Show total process duration
     *
     * @return float
     */
    public function showDuration() {
        $this->finishTime = microtime(true); // get process finish time
        $total = $this->finishTime - $this->startTime; // calculate total time
        return number_format($total, 4);
    }

}

?>
