<?php

/**
 * SunAnalytics Class
 *
 * @category  Web Analytics (Visits, Traffic Sources & Page Views)
 * @package   SunAnalytics
 * @author    Mehmet Selcuk Batal <batalms@gmail.com>
 * @copyright Copyright (c) 2026, Sunhill Technology <www.sunhillint.com>
 * @license   https://opensource.org/licenses/lgpl-3.0.html The GNU Lesser General Public License, version 3.0
 * @link      https://github.com/msbatal/PHP-Analytics-Class
 * @version   1.0.0
 */

class SunAnalytics
{
    /**
     * SunDB instance used for all database access
     * @var object
     */
    private $db;

    /**
     * Static instance of self
     * @var object
     */
    private static $instance;

    /**
     * Configuration values (merged with user supplied array)
     * @var array
     */
    private $config = [
        'prefix'         => 'sun_',          // prefix for the analytics tables
        'tables'         => [],              // table name overrides (visits, pageviews, online)
        'charset'        => 'utf8mb4',       // table charset used by install()
        'collation'      => '',              // table collation used by install() (empty = server default)
        'sessionKey'     => 'sun_analytics', // $_SESSION key holding the current visit
        'sessionTimeout' => 1800,            // inactivity time that ends a visit (seconds)
        'salt'           => '',              // secret used to hash the visitor identity (set your own)
        'ip'             => null,            // client ip override (string or callable, e.g. behind a proxy)
        'storeIp'        => false,           // store the raw ip address (false = only the daily visitor hash is kept)
        'storePages'     => true,            // store every page view (false = only count them on the visit)
        'respectDnt'     => false,           // skip visitors that send Do Not Track / Global Privacy Control
        'hosts'          => [],              // own host names (referrers from them are not traffic sources)
        'basePath'       => '',              // sub folder prefix removed from the stored page paths (e.g. /shop)
        'excludeIps'     => [],              // ip addresses that are never tracked
        'excludePaths'   => [],              // path prefixes that are never tracked (e.g. /admin)
        'bots'           => [],              // extra bot user agent patterns (regex fragments)
        'sources'        => [],              // extra referrer rules ['host regex' => ['name', 'medium']]
        'onlineWindow'   => 300,             // visitor is "online" for this long after the last request (seconds)
        'retention'      => 0,               // default age limit of purge() in days (0 = keep everything)
        'throwErrors'    => false            // throw exceptions from track() instead of returning false
    ];

    /**
     * Default table names (before prefix)
     * @var array
     */
    private $tableNames = ['visits' => 'visits', 'pageviews' => 'pageviews', 'online' => 'online'];

    /**
     * Report dimensions and the visit columns behind them
     * @var array
     */
    private $dimensions = [
        'source'   => 'source',
        'medium'   => 'medium',
        'campaign' => 'campaign',
        'term'     => 'term',
        'content'  => 'content',
        'referrer' => 'referrer_host',
        'landing'  => 'landing',
        'device'   => 'device',
        'browser'  => 'browser',
        'os'       => 'os',
        'language' => 'language'
    ];

    /**
     * Dimensions that are empty for most visits (empty values are left out of the reports)
     * @var array
     */
    private $optionalDimensions = ['campaign', 'term', 'content', 'referrer', 'landing', 'language'];

    /**
     * Click id parameters that mark a paid visit (parameter => [source, medium])
     * @var array
     */
    private $paidClickIds = [
        'gclid'   => ['google', 'cpc'],
        'gbraid'  => ['google', 'cpc'],
        'wbraid'  => ['google', 'cpc'],
        'gad_source' => ['google', 'cpc'],
        'msclkid' => ['bing', 'cpc'],
        'ttclid'  => ['tiktok', 'cpc'],
        'yclid'   => ['yandex', 'cpc']
    ];

    /**
     * Click id parameters that only reveal the social network when no referrer is sent
     * @var array
     */
    private $softClickIds = [
        'fbclid'  => ['facebook', 'social'],
        'igshid'  => ['instagram', 'social'],
        'twclid'  => ['twitter', 'social']
    ];

    /**
     * Built-in referrer rules (host regex => [source, medium]), checked in order
     * @var array
     */
    private $referrerRules = [
        '/^(mail\.google\.com|outlook\.(live|office|office365)\.com|mail\.yahoo\.com|mail\.yandex\.[a-z.]+|mail\.proton\.me|webmail\.[a-z0-9.-]+|mail\.[a-z0-9-]+\.[a-z.]+)$/' => ['email', 'email'],
        '/^(google\.[a-z.]+|(images|news|scholar|maps)\.google\.[a-z.]+)$/' => ['google', 'organic'],
        '/^com\.google\.android\.googlequicksearchbox$/'    => ['google', 'organic'],
        '/(^|\.)bing\.com$/'                                => ['bing', 'organic'],
        '/(^|\.)(search\.)?yahoo\.[a-z.]+$/'                => ['yahoo', 'organic'],
        '/(^|\.)duckduckgo\.com$/'                          => ['duckduckgo', 'organic'],
        '/(^|\.)yandex\.[a-z.]+$/'                          => ['yandex', 'organic'],
        '/(^|\.)baidu\.com$/'                               => ['baidu', 'organic'],
        '/(^|\.)ecosia\.org$/'                              => ['ecosia', 'organic'],
        '/(^|\.)search\.brave\.com$/'                       => ['brave', 'organic'],
        '/(^|\.)(startpage|qwant|seznam|naver|ask)\.[a-z.]+$/' => ['search', 'organic'],
        '/(^|\.)(facebook|fb)\.com$/'                       => ['facebook', 'social'],
        '/^com\.facebook\.[a-z.]+$/'                        => ['facebook', 'social'],
        '/(^|\.)instagram\.com$/'                           => ['instagram', 'social'],
        '/^com\.instagram\.[a-z.]+$/'                       => ['instagram', 'social'],
        '/^(t\.co|(.*\.)?twitter\.com|(.*\.)?x\.com)$/'     => ['twitter', 'social'],
        '/(^|\.)(linkedin\.com|lnkd\.in)$/'                 => ['linkedin', 'social'],
        '/(^|\.)(youtube\.com|youtu\.be)$/'                 => ['youtube', 'social'],
        '/(^|\.)pinterest\.[a-z.]+$/'                       => ['pinterest', 'social'],
        '/(^|\.)tiktok\.com$/'                              => ['tiktok', 'social'],
        '/(^|\.)reddit\.com$/'                              => ['reddit', 'social'],
        '/(^|\.)(whatsapp\.com|wa\.me)$/'                   => ['whatsapp', 'social'],
        '/(^|\.)(telegram\.org|t\.me)$/'                    => ['telegram', 'social'],
        '/(^|\.)threads\.net$/'                             => ['threads', 'social'],
        '/(^|\.)(snapchat\.com|tumblr\.com|vk\.com|ok\.ru)$/' => ['social', 'social']
    ];

    /**
     * Built-in bot user agent pattern (regex fragment)
     * @var string
     */
    private $botPattern = 'bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|uptime|curl|wget|python|httpclient|headless|lighthouse|scrapy|go-http-client|libwww|axios|node-fetch|postman|phantom|pingdom|gtmetrix|semrush|ahrefs|mj12';

    /**
     * Id of the visit of the current request (set by track())
     * @var integer
     */
    private $visitId = 0;

    /**
     * Last non-exception error message
     * @var string
     */
    private $lastError = '';

    /**
     * @param object|array|null $db     SunDB object, PDO object, or connection params array
     * @param array $config             configuration overrides
     * @throws exception
     */
    public function __construct($db = null, $config = []) {
        if ($db instanceof SunDB) { // already a SunDB instance
            $this->db = $db;
        } else if ($db instanceof PDO) { // wrap a raw PDO connection with SunDB
            $this->db = new SunDB($db);
        } else if (is_array($db)) { // build a new SunDB from connection params
            $this->db = new SunDB($db);
        } else {
            throw new Exception('A SunDB instance, a PDO object, or a connection params array is required.');
        }
        if (is_array($config) && count($config) > 0) {
            $this->config = array_merge($this->config, $config);
        }
        self::$instance = $this;
    }

    /**
     * Get/Set a configuration value
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function config($key = null, $value = null) {
        if (is_null($value)) {
            return isset($this->config[$key]) ? $this->config[$key] : null;
        }
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * Return the SunDB instance
     *
     * @return object
     */
    public function db() {
        return $this->db;
    }

    /**
     * Return the last non-exception error message
     *
     * @return string
     */
    public function lastError() {
        return $this->lastError;
    }

    /**
     * Return the id of the current visit (0 when the request was not tracked)
     *
     * @return integer
     */
    public function visitId() {
        return (int) $this->visitId;
    }

    /**
     * Return a prefixed table name
     *
     * @param string $name visits, pageviews or online
     * @throws exception
     * @return string
     */
    public function table($name = null) {
        if (!isset($this->tableNames[$name])) {
            throw new Exception('Unknown analytics table: "' . $name . '".');
        }
        $table = (!empty($this->config['tables'][$name])) ? $this->config['tables'][$name] : $this->config['prefix'] . $this->tableNames[$name];
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new Exception('Invalid table identifier: "' . $table . '".');
        }
        return $table;
    }

    /**
     * Return the current datetime string
     *
     * @return string
     */
    private function now() {
        return date('Y-m-d H:i:s');
    }

    /**
     * Return the client IP address (falls back to 0.0.0.0 when unavailable, e.g. CLI/cron)
     *
     * @return string
     */
    private function ip() {
        $ip = $this->config['ip'];
        if (is_callable($ip)) {
            $ip = call_user_func($ip);
        }
        if (is_string($ip) && $ip !== '') {
            return substr($ip, 0, 45);
        }
        return (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] !== '') ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : '0.0.0.0';
    }

    /**
     * Return the client user agent string (trimmed)
     *
     * @return string
     */
    private function agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
    }

    /**
     * Start the PHP session if it is not already active
     *
     * @return boolean
     */
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Return the daily rotating, non reversible visitor hash (no cookie, no raw ip)
     *
     * @return string
     */
    private function visitorHash() {
        $salt = $this->config['salt'] !== '' ? $this->config['salt'] : hash('sha256', __DIR__ . php_uname('n'));
        return substr(hash_hmac('sha256', $this->ip() . '|' . $this->agent() . '|' . date('Y-m-d'), $salt), 0, 32);
    }

    /**
     * Cut a value to a maximum length (null when empty)
     *
     * @param string $value
     * @param integer $length
     * @return string|null
     */
    private function clip($value = null, $length = 100) {
        $value = trim((string) $value);
        return $value === '' ? null : mb_substr($value, 0, $length, 'UTF-8');
    }

    /**
     * Create the analytics tables if they do not exist (safe to run repeatedly)
     *
     * @throws exception
     * @return boolean
     */
    public function install() {
        $suffix = ' ENGINE=InnoDB DEFAULT CHARSET=' . preg_replace('/[^a-z0-9_]/i', '', $this->config['charset']);
        if ($this->config['collation'] !== '') {
            $suffix .= ' COLLATE=' . preg_replace('/[^a-z0-9_]/i', '', $this->config['collation']);
        }
        $visits = $this->table('visits');
        $this->db->rawQuery('CREATE TABLE IF NOT EXISTS `' . $visits . '` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `visitor_hash` char(32) NOT NULL,
            `source` varchar(100) NOT NULL DEFAULT \'direct\',
            `medium` varchar(50) NOT NULL DEFAULT \'direct\',
            `campaign` varchar(150) DEFAULT NULL,
            `term` varchar(150) DEFAULT NULL,
            `content` varchar(150) DEFAULT NULL,
            `referrer_host` varchar(190) DEFAULT NULL,
            `landing` varchar(255) DEFAULT NULL,
            `device` varchar(20) DEFAULT NULL,
            `browser` varchar(40) DEFAULT NULL,
            `os` varchar(40) DEFAULT NULL,
            `language` varchar(10) DEFAULT NULL,
            `ip` varchar(45) DEFAULT NULL,
            `pages` int unsigned NOT NULL DEFAULT 0,
            `created_at` datetime NOT NULL,
            `updated_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `ix_' . $visits . '_created` (`created_at`),
            KEY `ix_' . $visits . '_visitor` (`visitor_hash`, `created_at`)
        )' . $suffix)->run();
        $pageviews = $this->table('pageviews');
        $this->db->rawQuery('CREATE TABLE IF NOT EXISTS `' . $pageviews . '` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `visit_id` bigint unsigned NOT NULL,
            `path` varchar(255) NOT NULL,
            `title` varchar(190) DEFAULT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `ix_' . $pageviews . '_visit` (`visit_id`),
            KEY `ix_' . $pageviews . '_created` (`created_at`)
        )' . $suffix)->run();
        $online = $this->table('online');
        $this->db->rawQuery('CREATE TABLE IF NOT EXISTS `' . $online . '` (
            `visitor_hash` char(32) NOT NULL,
            `last_seen` datetime NOT NULL,
            PRIMARY KEY (`visitor_hash`),
            KEY `ix_' . $online . '_seen` (`last_seen`)
        )' . $suffix)->run();
        return true;
    }

    /**
     * Check whether a user agent belongs to a bot (empty user agents count as bots)
     *
     * @param string $agent user agent (null = current request)
     * @return boolean
     */
    public function isBot($agent = null) {
        $agent = strtolower(is_null($agent) ? $this->agent() : (string) $agent);
        if ($agent === '') {
            return true;
        }
        $pattern = $this->botPattern;
        foreach ((array) $this->config['bots'] as $extra) {
            $pattern .= '|' . $extra;
        }
        return (bool) preg_match('/' . str_replace('/', '\/', $pattern) . '/', $agent);
    }

    /**
     * Detect the device type, browser and operating system from a user agent
     *
     * @param string $agent user agent (null = current request)
     * @return array device, browser, os
     */
    public function parseAgent($agent = null) {
        $agent = strtolower(is_null($agent) ? $this->agent() : (string) $agent);
        $device = 'desktop';
        if (preg_match('/ipad|tablet|kindle|silk|playbook/', $agent) || (strpos($agent, 'android') !== false && strpos($agent, 'mobile') === false)) {
            $device = 'tablet';
        } else if (preg_match('/mobi|iphone|ipod|windows phone|blackberry|opera mini/', $agent)) {
            $device = 'mobile';
        }
        $browsers = [
            '/instagram/' => 'Instagram', '/fban|fbav|fb_iab/' => 'Facebook', '/edg(e|a|ios)?\//' => 'Edge', '/opr\/|opera/' => 'Opera',
            '/samsungbrowser/' => 'Samsung Internet', '/firefox|fxios/' => 'Firefox', '/chrome|crios/' => 'Chrome',
            '/safari/' => 'Safari', '/msie|trident/' => 'Internet Explorer'
        ];
        $browser = 'Other';
        foreach ($browsers as $pattern => $name) {
            if (preg_match($pattern, $agent)) {
                $browser = $name;
                break;
            }
        }
        $systems = [
            '/windows phone/' => 'Windows Phone', '/windows/' => 'Windows', '/android/' => 'Android', '/iphone|ipad|ipod/' => 'iOS',
            '/mac os x|macintosh/' => 'macOS', '/cros/' => 'ChromeOS', '/linux|x11/' => 'Linux'
        ];
        $os = 'Other';
        foreach ($systems as $pattern => $name) {
            if (preg_match($pattern, $agent)) {
                $os = $name;
                break;
            }
        }
        return ['device' => $device, 'browser' => $browser, 'os' => $os];
    }

    /**
     * Normalize a utm_medium value to the common medium names
     *
     * @param string $medium
     * @return string
     */
    private function normalizeMedium($medium = null) {
        $medium = strtolower(trim((string) $medium));
        if ($medium === '') {
            return '';
        }
        if (in_array($medium, ['cpc', 'ppc', 'paid', 'paidsearch', 'paid-search', 'paid_search', 'adwords', 'sem'], true)) {
            return 'cpc';
        }
        if (in_array($medium, ['social', 'social-network', 'social_media', 'social-media', 'sm', 'paidsocial', 'paid-social', 'paid_social'], true)) {
            return 'social';
        }
        if (in_array($medium, ['email', 'e-mail', 'mail', 'newsletter', 'edm'], true)) {
            return 'email';
        }
        if (in_array($medium, ['display', 'cpm', 'banner', 'cpv', 'cpa'], true)) {
            return 'display';
        }
        return mb_substr($medium, 0, 50, 'UTF-8');
    }

    /**
     * Check whether a host is one of the own hosts
     *
     * @param string $host
     * @return boolean
     */
    private function isOwnHost($host = null) {
        $own = array_map('strtolower', (array) $this->config['hosts']);
        if (!empty($_SERVER['HTTP_HOST'])) {
            $own[] = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']));
        }
        foreach ($own as $item) {
            $item = preg_replace('/^www\./', '', $item);
            if ($item !== '' && ($host === $item || substr($host, -strlen($item) - 1) === '.' . $item)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detect the traffic source of a visit (utm tags > paid click ids > referrer > social click ids > direct)
     *
     * @param string $referrer referrer url (null = HTTP_REFERER)
     * @param string $url      landing url with its query string (null = current request uri)
     * @return array source, medium, campaign, term, content, referrer_host
     */
    public function detectSource($referrer = null, $url = null) {
        $referrer = is_null($referrer) ? (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') : (string) $referrer;
        $url = is_null($url) ? (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '') : (string) $url;
        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $query = array_change_key_case(array_filter($query, 'is_string'), CASE_LOWER);

        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);
        if ($host !== '' && $this->isOwnHost($host)) { // internal navigation is not a traffic source
            $host = '';
        }
        $result = [
            'source'        => 'direct',
            'medium'        => 'direct',
            'campaign'      => $this->clip(isset($query['utm_campaign']) ? $query['utm_campaign'] : '', 150),
            'term'          => $this->clip(isset($query['utm_term']) ? $query['utm_term'] : '', 150),
            'content'       => $this->clip(isset($query['utm_content']) ? $query['utm_content'] : '', 150),
            'referrer_host' => $this->clip($host, 190)
        ];

        if (!empty($query['utm_source'])) { // 1. explicit campaign tags
            $result['source'] = strtolower(mb_substr(trim($query['utm_source']), 0, 100, 'UTF-8'));
            $medium = $this->normalizeMedium(isset($query['utm_medium']) ? $query['utm_medium'] : '');
            $result['medium'] = $medium !== '' ? $medium : 'campaign';
            return $result;
        }
        foreach ($this->paidClickIds as $param => $info) { // 2. paid click ids (ad clicks also carry a search/social referrer)
            if (!empty($query[$param])) {
                $result['source'] = $info[0];
                $result['medium'] = $info[1];
                return $result;
            }
        }
        if ($host !== '') { // 3. referrer
            $rules = array_merge((array) $this->config['sources'], $this->referrerRules);
            foreach ($rules as $pattern => $info) {
                if (@preg_match($pattern, $host)) {
                    $result['source'] = $info[0];
                    $result['medium'] = $info[1];
                    return $result;
                }
            }
            $result['source'] = mb_substr($host, 0, 100, 'UTF-8');
            $result['medium'] = 'referral';
            return $result;
        }
        foreach ($this->softClickIds as $param => $info) { // 4. social click ids without a referrer (in-app browsers)
            if (!empty($query[$param])) {
                $result['source'] = $info[0];
                $result['medium'] = $info[1];
                return $result;
            }
        }
        return $result;
    }

    /**
     * Check whether the current request should be tracked
     *
     * @param string $path
     * @return boolean
     */
    private function shouldTrack($path = null) {
        if (PHP_SAPI === 'cli' || $this->isBot()) {
            return false;
        }
        if ($this->config['respectDnt'] && ((isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] === '1') || (isset($_SERVER['HTTP_SEC_GPC']) && $_SERVER['HTTP_SEC_GPC'] === '1'))) {
            return false;
        }
        if (in_array($this->ip(), (array) $this->config['excludeIps'], true)) {
            return false;
        }
        foreach ((array) $this->config['excludePaths'] as $prefix) {
            if ($prefix !== '' && strpos((string) $path, $prefix) === 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Normalize a request path (query string removed, base path stripped, max 255 chars)
     *
     * @param string $path path or full request uri (null = current request)
     * @return string
     */
    private function normalizePath($path = null) {
        $path = is_null($path) ? (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/') : (string) $path;
        $path = (string) parse_url($path, PHP_URL_PATH);
        $base = rtrim((string) $this->config['basePath'], '/');
        if ($base !== '' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        return mb_substr($path === '' ? '/' : $path, 0, 255, 'UTF-8');
    }

    /**
     * Track the current request: starts a visit when needed (with source detection), records the page view
     * and refreshes the online visitor. Never breaks the page: failures return false (see lastError())
     *
     * Options: path, url (landing url with query string), referrer, title. Use them when tracking from an
     * ajax beacon (cached pages) where the request itself is not the page that was viewed.
     *
     * @param array $options
     * @throws exception when "throwErrors" is enabled
     * @return integer|boolean visit id or false when not tracked
     */
    public function track($options = []) {
        try {
            $path = $this->normalizePath(isset($options['path']) ? $options['path'] : null);
            if (!$this->shouldTrack($path)) {
                return false;
            }
            if (!$this->startSession()) {
                $this->lastError = 'A PHP session is required to follow visits.';
                return false;
            }
            $now = $this->now();
            $key = $this->config['sessionKey'];
            $current = (isset($_SESSION[$key]) && is_array($_SESSION[$key])) ? $_SESSION[$key] : null;
            $hash = $this->visitorHash();
            if (!$current || empty($current['id']) || (time() - (int) $current['time']) > (int) $this->config['sessionTimeout']) {
                $agent = $this->parseAgent();
                $source = $this->detectSource(isset($options['referrer']) ? $options['referrer'] : null, isset($options['url']) ? $options['url'] : (isset($options['path']) ? $options['path'] : null));
                $language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? strtolower(substr(preg_replace('/[^a-zA-Z-].*$/', '', $_SERVER['HTTP_ACCEPT_LANGUAGE']), 0, 10)) : '';
                $this->db->insert($this->table('visits'), [
                    'visitor_hash'  => $hash,
                    'source'        => $source['source'],
                    'medium'        => $source['medium'],
                    'campaign'      => $source['campaign'],
                    'term'          => $source['term'],
                    'content'       => $source['content'],
                    'referrer_host' => $source['referrer_host'],
                    'landing'       => $path,
                    'device'        => $agent['device'],
                    'browser'       => $agent['browser'],
                    'os'            => $agent['os'],
                    'language'      => $this->clip($language, 10),
                    'ip'            => $this->config['storeIp'] ? $this->ip() : null,
                    'pages'         => 0,
                    'created_at'    => $now,
                    'updated_at'    => $now
                ])->run();
                $current = ['id' => (int) $this->db->lastInsertId()];
            }
            $current['time'] = time();
            $_SESSION[$key] = $current;
            $this->visitId = (int) $current['id'];
            $this->db->rawQuery('update `' . $this->table('visits') . '` set `pages` = `pages` + 1, `updated_at` = ? where `id` = ?', [$now, $this->visitId])->run();
            if ($this->config['storePages']) {
                $this->db->insert($this->table('pageviews'), [
                    'visit_id'   => $this->visitId,
                    'path'       => $path,
                    'title'      => $this->clip(isset($options['title']) ? $options['title'] : '', 190),
                    'created_at' => $now
                ])->run();
            }
            $this->db->rawQuery('insert into `' . $this->table('online') . '` (`visitor_hash`, `last_seen`) values (?, ?) on duplicate key update `last_seen` = ?', [$hash, $now, $now])->run();
            return $this->visitId;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            if ($this->config['throwErrors']) {
                throw $e;
            }
            return false;
        }
    }

    /**
     * Resolve a report period to a [from, to] datetime pair
     *
     * @param integer|string|array $range days (last N days incl. today), 'today', 'yesterday' or [from, to] dates (null = 30)
     * @return array
     */
    private function period($range = null) {
        if (is_array($range) && count($range) === 2) {
            $from = strtotime((string) $range[0]);
            $to = strtotime((string) $range[1]);
            if ($from !== false && $to !== false && $from <= $to) {
                return [date('Y-m-d 00:00:00', $from), date('Y-m-d 23:59:59', $to)];
            }
        }
        if ($range === 'today') {
            return [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')];
        }
        if ($range === 'yesterday') {
            return [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 23:59:59', strtotime('-1 day'))];
        }
        $days = (is_numeric($range) && (int) $range > 0) ? min((int) $range, 3660) : 30;
        return [date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days')), date('Y-m-d 23:59:59')];
    }

    /**
     * Return the visit, page view and bounce totals of a period
     * (unique visitors are counted per day: the visitor hash rotates every midnight)
     *
     * @param integer|string|array $range
     * @return array visits, pageviews, visitors, bounces, bounce_rate, pages_per_visit
     */
    public function summary($range = null) {
        list($from, $to) = $this->period($range);
        $rows = $this->db->rawQuery('select count(*) as visits, coalesce(sum(`pages`), 0) as pageviews, count(distinct `visitor_hash`) as visitors, coalesce(sum(case when `pages` <= 1 then 1 else 0 end), 0) as bounces from `' . $this->table('visits') . '` where `created_at` >= ? and `created_at` <= ?', [$from, $to])->run();
        $row = !empty($rows) ? $rows[0] : [];
        $visits = isset($row['visits']) ? (int) $row['visits'] : 0;
        $bounces = isset($row['bounces']) ? (int) $row['bounces'] : 0;
        $pageviews = isset($row['pageviews']) ? (int) $row['pageviews'] : 0;
        return [
            'visits'          => $visits,
            'pageviews'       => $pageviews,
            'visitors'        => isset($row['visitors']) ? (int) $row['visitors'] : 0,
            'bounces'         => $bounces,
            'bounce_rate'     => $visits > 0 ? round($bounces / $visits * 100, 1) : 0.0,
            'pages_per_visit' => $visits > 0 ? round($pageviews / $visits, 2) : 0.0
        ];
    }

    /**
     * Return the visits per day of a period (days without a visit are included with zeros)
     *
     * @param integer|string|array $range
     * @return array date => [visits, pageviews, visitors]
     */
    public function daily($range = null) {
        list($from, $to) = $this->period($range);
        $days = [];
        $day = new DateTime(substr($from, 0, 10));
        $last = substr($to, 0, 10);
        while ($day->format('Y-m-d') <= $last) {
            $days[$day->format('Y-m-d')] = ['visits' => 0, 'pageviews' => 0, 'visitors' => 0];
            $day->modify('+1 day');
        }
        $rows = $this->db->rawQuery('select date(`created_at`) as day, count(*) as visits, coalesce(sum(`pages`), 0) as pageviews, count(distinct `visitor_hash`) as visitors from `' . $this->table('visits') . '` where `created_at` >= ? and `created_at` <= ? group by date(`created_at`)', [$from, $to])->run();
        foreach ((array) $rows as $row) {
            if (isset($days[$row['day']])) {
                $days[$row['day']] = ['visits' => (int) $row['visits'], 'pageviews' => (int) $row['pageviews'], 'visitors' => (int) $row['visitors']];
            }
        }
        return $days;
    }

    /**
     * Group the visits of a period by a dimension
     *
     * @param string $dimension source, medium, campaign, term, content, referrer, landing, device, browser, os or language
     * @param integer|string|array $range
     * @param integer $limit
     * @throws exception
     * @return array list of [name, visits, pageviews, bounce_rate, share]
     */
    public function breakdown($dimension = null, $range = null, $limit = 10) {
        if (!isset($this->dimensions[$dimension])) {
            throw new Exception('Unknown report dimension: "' . $dimension . '".');
        }
        $column = $this->dimensions[$dimension];
        return $this->group([$column], $range, $limit, in_array($dimension, $this->optionalDimensions, true));
    }

    /**
     * Return the traffic sources of a period (source and medium together, e.g. google / organic)
     *
     * @param integer|string|array $range
     * @param integer $limit
     * @return array list of [name, medium, visits, pageviews, bounce_rate, share]
     */
    public function sources($range = null, $limit = 10) {
        return $this->group(['source', 'medium'], $range, $limit, false);
    }

    /**
     * Return the most viewed pages of a period
     *
     * @param integer|string|array $range
     * @param integer $limit
     * @return array list of [path, views, visits]
     */
    public function pages($range = null, $limit = 10) {
        list($from, $to) = $this->period($range);
        $rows = $this->db->rawQuery('select `path`, count(*) as views, count(distinct `visit_id`) as visits from `' . $this->table('pageviews') . '` where `created_at` >= ? and `created_at` <= ? group by `path` order by views desc, `path` asc limit ' . max(1, (int) $limit), [$from, $to])->run();
        $list = [];
        foreach ((array) $rows as $row) {
            $list[] = ['path' => $row['path'], 'views' => (int) $row['views'], 'visits' => (int) $row['visits']];
        }
        return $list;
    }

    /**
     * Group the visits table by one or two columns
     *
     * @param array $columns validated visit columns
     * @param integer|string|array $range
     * @param integer $limit
     * @param boolean $skipEmpty leave out visits without a value
     * @return array
     */
    private function group($columns = [], $range = null, $limit = 10, $skipEmpty = false) {
        list($from, $to) = $this->period($range);
        $fields = '`' . implode('`, `', $columns) . '`';
        $where = '`created_at` >= ? and `created_at` <= ?' . ($skipEmpty ? ' and `' . $columns[0] . '` is not null' : '');
        $total = $this->db->rawQuery('select count(*) as total from `' . $this->table('visits') . '` where ' . $where, [$from, $to])->run();
        $total = !empty($total) ? (int) $total[0]['total'] : 0;
        $rows = $this->db->rawQuery('select ' . $fields . ', count(*) as visits, coalesce(sum(`pages`), 0) as pageviews, coalesce(sum(case when `pages` <= 1 then 1 else 0 end), 0) as bounces from `' . $this->table('visits') . '` where ' . $where . ' group by ' . $fields . ' order by visits desc, ' . $fields . ' asc limit ' . max(1, (int) $limit), [$from, $to])->run();
        $list = [];
        foreach ((array) $rows as $row) {
            $visits = (int) $row['visits'];
            $item = ['name' => $row[$columns[0]]];
            if (isset($columns[1])) {
                $item['medium'] = $row[$columns[1]];
            }
            $item['visits'] = $visits;
            $item['pageviews'] = (int) $row['pageviews'];
            $item['bounce_rate'] = $visits > 0 ? round((int) $row['bounces'] / $visits * 100, 1) : 0.0;
            $item['share'] = $total > 0 ? round($visits / $total * 100, 1) : 0.0;
            $list[] = $item;
        }
        return $list;
    }

    /**
     * Return the number of visitors seen recently
     *
     * @param integer $seconds look back time (null = "onlineWindow" setting)
     * @return integer
     */
    public function online($seconds = null) {
        $seconds = (is_numeric($seconds) && (int) $seconds > 0) ? (int) $seconds : (int) $this->config['onlineWindow'];
        $rows = $this->db->rawQuery('select count(*) as total from `' . $this->table('online') . '` where `last_seen` >= ?', [date('Y-m-d H:i:s', time() - $seconds)])->run();
        return !empty($rows) ? (int) $rows[0]['total'] : 0;
    }

    /**
     * Delete old records (visits and page views older than the given days, stale online rows)
     *
     * @param integer $days age limit in days (null = "retention" setting, 0 = only the online rows are cleaned)
     * @return array visits, pageviews, online (deleted row counts)
     */
    public function purge($days = null) {
        $days = is_null($days) ? (int) $this->config['retention'] : (int) $days;
        $deleted = ['visits' => 0, 'pageviews' => 0, 'online' => 0];
        $this->db->rawQuery('delete from `' . $this->table('online') . '` where `last_seen` < ?', [date('Y-m-d H:i:s', time() - 86400)])->run();
        $deleted['online'] = (int) $this->db->rowCount();
        if ($days > 0) {
            $limit = date('Y-m-d H:i:s', time() - $days * 86400);
            $this->db->rawQuery('delete from `' . $this->table('visits') . '` where `created_at` < ?', [$limit])->run();
            $deleted['visits'] = (int) $this->db->rowCount();
            $this->db->rawQuery('delete from `' . $this->table('pageviews') . '` where `created_at` < ?', [$limit])->run();
            $deleted['pageviews'] = (int) $this->db->rowCount();
        }
        return $deleted;
    }

    /**
     * Return a static instance of SunAnalytics
     *
     * @return object
     */
    public static function getInstance() {
        return self::$instance;
    }

}
