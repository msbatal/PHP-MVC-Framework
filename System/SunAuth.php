<?php

/**
 * SunAuth Class
 *
 * @category  Authentication
 * @package   SunAuth
 * @author    Mehmet Selcuk Batal <batalms@gmail.com>
 * @copyright Copyright (c) 2025, Sunhill Technology <www.sunhillint.com>
 * @license   https://opensource.org/licenses/lgpl-3.0.html The GNU Lesser General Public License, version 3.0
 * @link      https://github.com/msbatal/PHP-Authentication-Class
 * @version   1.0.1
 */

class SunAuth
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
        'table'            => 'users',        // user table name
        'columns'          => [               // user table column mapping
            'id'       => 'id',
            'username' => 'username',
            'email'    => 'email',
            'password' => 'password',
            'role'     => 'role',
            'status'   => 'status',
            'twofa'    => 'twofa_secret'
        ],
        'identifier'       => 'email',        // column used to log in with
        'activeStatus'     => 1,              // value of "status" column for an active account
        'prefix'           => 'sun_',         // prefix for the support tables
        'sessionLifetime'  => 7200,           // active session lifetime (seconds)
        'rememberLifetime' => 1209600,        // remember-me lifetime (14 days)
        'resetLifetime'    => 3600,           // password reset token lifetime (seconds)
        'maxAttempts'      => 5,              // failed login attempts before lockout
        'lockoutTime'      => 900,            // lockout duration (seconds)
        'adminRole'        => 'admin',        // role value that identifies an administrator
        'cookieName'       => 'sun_remember', // remember-me cookie name
        'sessionKey'       => 'sun_auth',     // $_SESSION key holding the raw session token
        'issuer'           => 'SunAuth'       // issuer label for the 2FA otpauth uri
    ];

    /**
     * Cache of the user table column names (loaded once for feature detection)
     * @var array
     */
    private $tableColumns = null;

    /**
     * Cache for the current authenticated user record
     * @var array
     */
    private $userCache;

    /**
     * Last non-exception error message (for failed logins, etc.)
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
            if (isset($config['columns']) && is_array($config['columns'])) {
                $config['columns'] = array_merge($this->config['columns'], $config['columns']);
            }
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
     * Return the mapped user table column name
     *
     * @param string $key
     * @return string
     */
    private function col($key = null) {
        return isset($this->config['columns'][$key]) ? $this->config['columns'][$key] : $key;
    }

    /**
     * Return a prefixed support table name
     *
     * @param string $name
     * @return string
     */
    private function tbl($name = null) {
        return $this->config['prefix'] . $name;
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
     * Start the PHP session if it is not already active
     */
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Return the client IP address (falls back to 0.0.0.0 when unavailable, e.g. CLI/cron)
     *
     * @return string
     */
    private function ip() {
        return (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] !== '') ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
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
     * Validate a table/column identifier
     *
     * @param string $name
     * @throws exception
     * @return string
     */
    private function validateIdentifier($name = null) {
        if (!is_string($name) || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new Exception('Invalid table/column identifier: "' . $name . '".');
        }
        return $name;
    }

    /**
     * Check whether a column exists on the user table (cached, feature flags)
     *
     * @param string $key column map key (role, status, twofa, ...)
     * @return boolean
     */
    private function hasColumn($key = null) {
        $column = $this->col($key);
        if (empty($column)) {
            return false;
        }
        if ($this->tableColumns === null) { // load the column list once
            $table = $this->validateIdentifier($this->config['table']);
            $this->tableColumns = [];
            try {
                $rows = $this->db->rawQuery('SHOW COLUMNS FROM `' . $table . '`')->run();
                foreach ($rows as $row) {
                    $this->tableColumns[] = $row['Field'];
                }
            } catch (Exception $e) {
                $this->tableColumns = [];
            }
        }
        return in_array($column, $this->tableColumns, true);
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
     * Hash a plain password using the modern algorithm
     *
     * @param string $password
     * @return string
     */
    private function hashPassword($password = null) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a plain password against a stored hash
     *
     * @param string $password
     * @param string $hash
     * @return boolean
     */
    private function verifyHash($password = null, $hash = null) {
        return password_verify((string) $password, (string) $hash);
    }

    /**
     * Fetch a single user record by a given column/value
     *
     * @param string $column
     * @param string $value
     * @return array|null
     */
    private function getUserBy($column = null, $value = null) {
        $row = $this->db->select($this->config['table'])->where($column, $value, '=')->orderBy($this->col('id'), 'asc')->first()->run();
        return !empty($row) ? $row : null;
    }

    /**
     * Fetch a single user record by primary key
     *
     * @param integer $id
     * @return array|null
     */
    private function getUserById($id = null) {
        return $this->getUserBy($this->col('id'), $id);
    }

    /**
     * Check whether a user exists for the configured identifier column
     *
     * @param string $identifier
     * @return boolean
     */
    public function userExists($identifier = null) {
        return $this->getUserBy($this->config['identifier'], $identifier) !== null;
    }

    /**
     * Register a new user (hashes the password, prevents duplicates)
     *
     * @param array $data associative column => value data (must contain the password)
     * @throws exception
     * @return integer|boolean inserted id on success, false on duplicate
     */
    public function register($data = []) {
        if (!is_array($data) || count($data) <= 0) {
            throw new Exception('Register data must be a non-empty array.');
        }
        $pwCol = $this->col('password');
        if (empty($data[$pwCol])) {
            throw new Exception('Register data must contain the password field "' . $pwCol . '".');
        }
        $idCol = $this->config['identifier'];
        if (isset($data[$idCol]) && $this->userExists($data[$idCol])) {
            $this->lastError = 'A user with this identifier already exists.';
            return false;
        }
        $data[$pwCol] = $this->hashPassword($data[$pwCol]);
        $this->db->insert($this->config['table'], $data)->run();
        return $this->db->lastInsertId();
    }

    /**
     * Authenticate a user and open a session
     *
     * @param string $identifier
     * @param string $password
     * @param boolean $remember set a persistent remember-me cookie
     * @return boolean
     */
    public function login($identifier = null, $password = null, $remember = false) {
        $this->lastError = '';
        if ($this->isLocked($identifier)) {
            $this->lastError = 'Account temporarily locked due to too many failed attempts.';
            return false;
        }
        $user = $this->getUserBy($this->config['identifier'], $identifier);
        if (empty($user)) {
            $this->hashPassword('timing_safe_dummy'); // mitigate user enumeration via timing
            $this->recordAttempt($identifier);
            $this->lastError = 'Invalid credentials.';
            return false;
        }
        if ($this->hasColumn('status') && $user[$this->col('status')] != $this->config['activeStatus']) {
            $this->lastError = 'Account is not active.';
            return false;
        }
        if (!$this->verifyHash($password, $user[$this->col('password')])) {
            $this->recordAttempt($identifier);
            $this->lastError = 'Invalid credentials.';
            return false;
        }
        $userId = $user[$this->col('id')];
        if (password_needs_rehash($user[$this->col('password')], PASSWORD_DEFAULT)) { // silently strengthen
            $this->db->update($this->config['table'], [$this->col('password') => $this->hashPassword($password)])->where($this->col('id'), $userId, '=')->run();
        }
        $this->clearAttempts($identifier);
        $pending = ($this->hasColumn('twofa') && !empty($user[$this->col('twofa')])) ? 1 : 0;
        $this->createSession($userId, $pending);
        if ($remember) {
            $this->setRememberCookie($userId);
        }
        return true;
    }

    /**
     * Verify a user's credentials without opening a session
     *
     * @param string $identifier
     * @param string $password
     * @return boolean
     */
    public function verifyPassword($identifier = null, $password = null) {
        $user = $this->getUserBy($this->config['identifier'], $identifier);
        if (empty($user)) {
            return false;
        }
        return $this->verifyHash($password, $user[$this->col('password')]);
    }

    /**
     * Create a database backed session and store the raw token in $_SESSION
     *
     * @param integer $userId
     * @param integer $pending 1 while waiting for 2FA verification
     * @return string raw session token
     */
    private function createSession($userId = null, $pending = 0) {
        $this->startSession();
        $token = bin2hex(random_bytes(32));
        $this->db->insert($this->tbl('sessions'), [
            'user_id'       => $userId,
            'token_hash'    => hash('sha256', $token),
            'ip'            => $this->ip(),
            'user_agent'    => $this->agent(),
            'twofa_pending' => $pending,
            'created_at'    => $this->now(),
            'last_activity' => $this->now(),
            'expires_at'    => date('Y-m-d H:i:s', time() + (int) $this->config['sessionLifetime'])
        ])->run();
        $_SESSION[$this->config['sessionKey']] = $token;
        $this->userCache = null;
        return $token;
    }

    /**
     * Resolve and return the current session row from the token in $_SESSION
     *
     * @return array|null
     */
    private function currentSessionRow() {
        $this->startSession();
        if (empty($_SESSION[$this->config['sessionKey']])) {
            return null;
        }
        $hash = hash('sha256', $_SESSION[$this->config['sessionKey']]);
        $row = $this->db->select($this->tbl('sessions'))->where('token_hash', $hash, '=')->orderBy('id', 'asc')->first()->run();
        if (empty($row)) {
            return null;
        }
        if (strtotime($row['expires_at']) < time()) { // expired, destroy it
            $this->db->delete($this->tbl('sessions'))->where('token_hash', $hash, '=')->run();
            return null;
        }
        return $row;
    }

    /**
     * Return the current authenticated user id (resolves remember-me if needed)
     *
     * @return integer|null
     */
    public function id() {
        $row = $this->currentSessionRow();
        if (!empty($row)) {
            if ((int) $row['twofa_pending'] === 1) { // 2FA not completed yet
                return null;
            }
            $this->db->update($this->tbl('sessions'), ['last_activity' => $this->now()])->where('token_hash', $row['token_hash'], '=')->run();
            return (int) $row['user_id'];
        }
        return $this->validateRememberCookie(); // fall back to persistent login
    }

    /**
     * Return the current authenticated user record
     *
     * @return array|null
     */
    public function user() {
        if (!empty($this->userCache)) {
            return $this->userCache;
        }
        $userId = $this->id();
        if (empty($userId)) {
            return null;
        }
        $this->userCache = $this->getUserById($userId);
        return $this->userCache;
    }

    /**
     * Check whether a user is currently authenticated
     *
     * @return boolean
     */
    public function isLoggedIn() {
        return $this->id() !== null;
    }

    /**
     * Alias of isLoggedIn()
     *
     * @return boolean
     */
    public function check() {
        return $this->isLoggedIn();
    }

    /**
     * Log the current user out (this device or all devices)
     *
     * @param boolean $allDevices
     * @return boolean
     */
    public function logout($allDevices = false) {
        $this->startSession();
        $row = $this->currentSessionRow();
        if (!empty($row)) {
            if ($allDevices) {
                $this->db->delete($this->tbl('sessions'))->where('user_id', $row['user_id'], '=')->run();
            } else {
                $this->db->delete($this->tbl('sessions'))->where('token_hash', $row['token_hash'], '=')->run();
            }
        }
        $this->clearRememberCookie();
        unset($_SESSION[$this->config['sessionKey']]);
        $this->userCache = null;
        return true;
    }

    /**
     * List the active sessions for a user (defaults to the current user)
     *
     * @param integer $userId
     * @return array
     */
    public function sessions($userId = null) {
        if (empty($userId)) {
            $userId = $this->id();
        }
        if (empty($userId)) {
            return [];
        }
        return $this->db->select($this->tbl('sessions'), ['id', 'user_id', 'ip', 'user_agent', 'created_at', 'last_activity', 'expires_at'])->where('user_id', $userId, '=')->orderBy('last_activity', 'desc')->run();
    }

    /**
     * Destroy a specific session (device) belonging to the current user
     *
     * @param integer $sessionId
     * @return boolean
     */
    public function destroySession($sessionId = null) {
        $userId = $this->id();
        if (empty($userId) || empty($sessionId)) {
            return false;
        }
        $this->db->delete($this->tbl('sessions'))->where('id', $sessionId, '=')->where('user_id', $userId, '=')->run();
        return true;
    }

    /**
     * Regenerate the PHP session id to prevent session fixation
     *
     * @return boolean
     */
    public function regenerate() {
        $this->startSession();
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            return session_regenerate_id(true);
        }
        return false;
    }

    /**
     * Set a persistent remember-me cookie (selector + validator pattern)
     *
     * @param integer $userId
     * @return void
     */
    private function setRememberCookie($userId = null) {
        $selector  = bin2hex(random_bytes(8));
        $validator = bin2hex(random_bytes(32));
        $this->db->insert($this->tbl('remember_tokens'), [
            'user_id'        => $userId,
            'selector'       => $selector,
            'validator_hash' => hash('sha256', $validator),
            'expires_at'     => date('Y-m-d H:i:s', time() + (int) $this->config['rememberLifetime'])
        ])->run();
        $this->writeCookie($this->config['cookieName'], $selector . ':' . $validator, time() + (int) $this->config['rememberLifetime']);
    }

    /**
     * Validate the remember-me cookie and re-establish a session if valid
     *
     * @return integer|null
     */
    private function validateRememberCookie() {
        if (empty($_COOKIE[$this->config['cookieName']])) {
            return null;
        }
        $parts = explode(':', $_COOKIE[$this->config['cookieName']]);
        if (count($parts) !== 2) {
            $this->clearRememberCookie();
            return null;
        }
        list($selector, $validator) = $parts;
        $row = $this->db->select($this->tbl('remember_tokens'))->where('selector', $selector, '=')->orderBy('id', 'asc')->first()->run();
        if (empty($row)) {
            $this->clearRememberCookie();
            return null;
        }
        if (strtotime($row['expires_at']) < time() || !hash_equals($row['validator_hash'], hash('sha256', $validator))) {
            $this->clearRememberCookie();
            return null;
        }
        $userId = (int) $row['user_id'];
        // rotate the validator so a stolen cookie cannot be reused after this login
        $newValidator = bin2hex(random_bytes(32));
        $this->db->update($this->tbl('remember_tokens'), ['validator_hash' => hash('sha256', $newValidator)])->where('selector', $selector, '=')->run();
        $this->writeCookie($this->config['cookieName'], $selector . ':' . $newValidator, time() + (int) $this->config['rememberLifetime']);
        $this->createSession($userId, 0);
        return $userId;
    }

    /**
     * Delete the remember-me cookie and its database row
     *
     * @return void
     */
    private function clearRememberCookie() {
        if (!empty($_COOKIE[$this->config['cookieName']])) {
            $parts = explode(':', $_COOKIE[$this->config['cookieName']]);
            if (count($parts) === 2) {
                $this->db->delete($this->tbl('remember_tokens'))->where('selector', $parts[0], '=')->run();
            }
        }
        $this->writeCookie($this->config['cookieName'], '', time() - 3600);
    }

    /**
     * Write a cookie with secure defaults
     *
     * @param string $name
     * @param string $value
     * @param integer $expires
     * @return void
     */
    private function writeCookie($name = null, $value = null, $expires = 0) {
        if (headers_sent()) {
            return;
        }
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie($name, $value, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        if ($expires < time()) {
            unset($_COOKIE[$name]);
        } else {
            $_COOKIE[$name] = $value;
        }
    }

    /**
     * Record a failed login attempt and apply a lockout when the threshold is hit
     *
     * @param string $identifier
     * @return void
     */
    private function recordAttempt($identifier = null) {
        $ip = $this->ip();
        $row = $this->db->select($this->tbl('login_attempts'))->where('identifier', $identifier, '=')->where('ip', $ip, '=')->orderBy('id', 'asc')->first()->run();
        if (empty($row)) {
            $this->db->insert($this->tbl('login_attempts'), [
                'identifier'   => $identifier,
                'ip'           => $ip,
                'attempts'     => 1,
                'last_attempt' => $this->now(),
                'locked_until' => null
            ])->run();
            return;
        }
        $attempts = (int) $row['attempts'] + 1;
        $data = ['attempts' => $attempts, 'last_attempt' => $this->now()];
        if ($attempts >= (int) $this->config['maxAttempts']) {
            $data['locked_until'] = date('Y-m-d H:i:s', time() + (int) $this->config['lockoutTime']);
        }
        $this->db->update($this->tbl('login_attempts'), $data)->where('identifier', $identifier, '=')->where('ip', $ip, '=')->run();
    }

    /**
     * Check whether an identifier is currently locked out
     *
     * @param string $identifier
     * @return boolean
     */
    public function isLocked($identifier = null) {
        $row = $this->db->select($this->tbl('login_attempts'))->where('identifier', $identifier, '=')->where('ip', $this->ip(), '=')->orderBy('id', 'asc')->first()->run();
        if (empty($row) || empty($row['locked_until'])) {
            return false;
        }
        return strtotime($row['locked_until']) > time();
    }

    /**
     * Return the lockout status (locked flag, remaining seconds, attempt count)
     *
     * @param string $identifier
     * @return array
     */
    public function lockoutStatus($identifier = null) {
        $row = $this->db->select($this->tbl('login_attempts'))->where('identifier', $identifier, '=')->where('ip', $this->ip(), '=')->orderBy('id', 'asc')->first()->run();
        if (empty($row)) {
            return ['locked' => false, 'remaining' => 0, 'attempts' => 0];
        }
        $remaining = !empty($row['locked_until']) ? (strtotime($row['locked_until']) - time()) : 0;
        return [
            'locked'    => $remaining > 0,
            'remaining' => $remaining > 0 ? $remaining : 0,
            'attempts'  => (int) $row['attempts']
        ];
    }

    /**
     * Clear the failed attempt counter for an identifier
     *
     * @param string $identifier
     * @return void
     */
    private function clearAttempts($identifier = null) {
        $this->db->delete($this->tbl('login_attempts'))->where('identifier', $identifier, '=')->where('ip', $this->ip(), '=')->run();
    }

    /**
     * Return the role of the current user (or null when unavailable)
     *
     * @return string|null
     */
    public function role() {
        if (!$this->hasColumn('role')) {
            return null;
        }
        $user = $this->user();
        return !empty($user) ? $user[$this->col('role')] : null;
    }

    /**
     * Check whether the current user has the given role
     *
     * @param string $role
     * @return boolean
     */
    public function hasRole($role = null) {
        return $this->role() === $role;
    }

    /**
     * Check whether the current user is an administrator
     *
     * @return boolean
     */
    public function isAdmin() {
        return $this->hasRole($this->config['adminRole']);
    }

    /**
     * Require a given role, throwing when the current user does not have it
     *
     * @param string $role
     * @throws exception
     * @return boolean
     */
    public function requireRole($role = null) {
        if (!$this->hasRole($role)) {
            throw new Exception('Access denied. Role "' . $role . '" is required.');
        }
        return true;
    }

    /**
     * Create a password reset token for an identifier
     *
     * @param string $identifier
     * @return string|boolean raw token on success, false when the user is unknown
     */
    public function createResetToken($identifier = null) {
        $user = $this->getUserBy($this->config['identifier'], $identifier);
        if (empty($user)) {
            return false;
        }
        $token = bin2hex(random_bytes(32));
        $this->db->insert($this->tbl('password_resets'), [
            'user_id'    => $user[$this->col('id')],
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + (int) $this->config['resetLifetime']),
            'used'       => 0
        ])->run();
        return $token;
    }

    /**
     * Verify a password reset token and return the owning user id
     *
     * @param string $token
     * @return integer|boolean
     */
    public function verifyResetToken($token = null) {
        if (empty($token)) {
            return false;
        }
        $row = $this->db->select($this->tbl('password_resets'))->where('token_hash', hash('sha256', $token), '=')->where('used', 0, '=')->orderBy('id', 'asc')->first()->run();
        if (empty($row) || strtotime($row['expires_at']) < time()) {
            return false;
        }
        return (int) $row['user_id'];
    }

    /**
     * Reset a password using a valid token (invalidates the token and all sessions)
     *
     * @param string $token
     * @param string $newPassword
     * @return boolean
     */
    public function resetPassword($token = null, $newPassword = null) {
        $userId = $this->verifyResetToken($token);
        if (empty($userId)) {
            $this->lastError = 'Invalid or expired reset token.';
            return false;
        }
        $this->db->update($this->config['table'], [$this->col('password') => $this->hashPassword($newPassword)])->where($this->col('id'), $userId, '=')->run();
        $this->db->update($this->tbl('password_resets'), ['used' => 1])->where('token_hash', hash('sha256', $token), '=')->run();
        $this->db->delete($this->tbl('sessions'))->where('user_id', $userId, '=')->run(); // force re-login everywhere
        return true;
    }

    /**
     * Change a user's password after verifying the old one
     *
     * @param integer $userId
     * @param string $oldPassword
     * @param string $newPassword
     * @return boolean
     */
    public function changePassword($userId = null, $oldPassword = null, $newPassword = null) {
        $user = $this->getUserById($userId);
        if (empty($user) || !$this->verifyHash($oldPassword, $user[$this->col('password')])) {
            $this->lastError = 'Current password does not match.';
            return false;
        }
        $this->db->update($this->config['table'], [$this->col('password') => $this->hashPassword($newPassword)])->where($this->col('id'), $userId, '=')->run();
        return true;
    }

    /**
     * Enable two factor authentication for a user (returns secret and otpauth uri)
     *
     * @param integer $userId
     * @throws exception
     * @return array
     */
    public function enableTwoFactor($userId = null) {
        if (!$this->hasColumn('twofa')) {
            throw new Exception('Two factor column "' . $this->col('twofa') . '" does not exist on the user table.');
        }
        $secret = $this->generateSecret();
        $this->db->update($this->config['table'], [$this->col('twofa') => $secret])->where($this->col('id'), $userId, '=')->run();
        $user = $this->getUserById($userId);
        $label = rawurlencode($this->config['issuer'] . ':' . $user[$this->config['identifier']]);
        $uri = 'otpauth://totp/' . $label . '?secret=' . $secret . '&issuer=' . rawurlencode($this->config['issuer']);
        return ['secret' => $secret, 'uri' => $uri];
    }

    /**
     * Disable two factor authentication for a user
     *
     * @param integer $userId
     * @return boolean
     */
    public function disableTwoFactor($userId = null) {
        if (!$this->hasColumn('twofa')) {
            return false;
        }
        $this->db->update($this->config['table'], [$this->col('twofa') => null])->where($this->col('id'), $userId, '=')->run();
        return true;
    }

    /**
     * Validate a TOTP code against a user's stored secret (+/- one time step)
     *
     * @param integer $userId
     * @param string $code
     * @return boolean
     */
    public function verifyTwoFactorCode($userId = null, $code = null) {
        if (!$this->hasColumn('twofa')) {
            return false;
        }
        $user = $this->getUserById($userId);
        if (empty($user) || empty($user[$this->col('twofa')])) {
            return false;
        }
        $secret = $user[$this->col('twofa')];
        $slice = floor(time() / 30);
        for ($i = -1; $i <= 1; $i++) { // small window for clock drift
            if (hash_equals($this->totp($secret, $slice + $i), str_pad((string) $code, 6, '0', STR_PAD_LEFT))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Complete a pending 2FA login by verifying the code and promoting the session
     *
     * @param string $code
     * @return boolean
     */
    public function verifyTwoFactor($code = null) {
        $userId = $this->pendingTwoFactor();
        if (empty($userId)) {
            return false;
        }
        if (!$this->verifyTwoFactorCode($userId, $code)) {
            return false;
        }
        $hash = hash('sha256', $_SESSION[$this->config['sessionKey']]);
        $this->db->update($this->tbl('sessions'), ['twofa_pending' => 0])->where('token_hash', $hash, '=')->run();
        $this->userCache = null;
        return true;
    }

    /**
     * Return the user id of a session that is waiting for 2FA verification
     *
     * @return integer|boolean
     */
    public function pendingTwoFactor() {
        $row = $this->currentSessionRow();
        if (!empty($row) && (int) $row['twofa_pending'] === 1) {
            return (int) $row['user_id'];
        }
        return false;
    }

    /**
     * Generate a random base32 secret for TOTP
     *
     * @param integer $length
     * @return string
     */
    private function generateSecret($length = 16) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Compute a 6 digit TOTP code for a secret and time step
     *
     * @param string $secret
     * @param integer $timeSlice
     * @return string
     */
    private function totp($secret = null, $timeSlice = null) {
        if (is_null($timeSlice)) {
            $timeSlice = floor(time() / 30);
        }
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice); // 8 byte big-endian counter
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $part = substr($hash, $offset, 4);
        $value = unpack('N', $part)[1] & 0x7FFFFFFF;
        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a base32 string into raw bytes
     *
     * @param string $secret
     * @return string
     */
    private function base32Decode($secret = null) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(rtrim($secret, '='));
        $buffer = '';
        foreach (str_split($secret) as $char) {
            $position = strpos($alphabet, $char);
            if ($position === false) {
                continue;
            }
            $buffer .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($buffer, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }
        return $bytes;
    }

    /**
     * Return a static instance of SunAuth
     *
     * @return object
     */
    public static function getInstance() {
        return self::$instance;
    }

}

?>