<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

class SunFunc
{

    public static function loadEnv($path = null) {
        if (!file_exists($path)) {
            throw new Exception('Environment file ".env" not found. Copy ".env.example" to ".env" and fill in the values.');
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue; // skip empty lines, comments and malformed lines
            }
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), "\"'");
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    public function csrfToken($token = null) {
        if (is_null($token)) {
            if (empty($_SESSION['sunCsrf'])) {
                $_SESSION['sunCsrf'] = bin2hex(random_bytes(32)); // generate a new token
            }
            return $_SESSION['sunCsrf'];
        } else {
            return !empty($_SESSION['sunCsrf']) && hash_equals($_SESSION['sunCsrf'], $token); // verify submitted token
        }
    }

    /**
     * AES-256-GCM with a fresh random IV per call (IV+tag are prepended to
     * the ciphertext, then the whole thing base64-encoded) - the previous
     * scheme derived a single fixed IV from SYS_SCRIV and reused it on
     * every call, which under CTR mode let two ciphertexts be XORed
     * together to recover the XOR of their plaintexts. GCM also adds a
     * tag, catching tampering that CTR alone never detected.
     */
    public function encryption($input = null) {
        if (is_null($input)) {
            return false;
        }
        $key = SYS_SCRKEY;
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($input, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($cipher === false) {
            return false;
        }
        return base64_encode($iv . $tag . $cipher);
    }

    /**
     * Decrypts values from encryption() above. Falls back to the retired
     * fixed-IV AES-256-CTR scheme (see migrate-encrypted-secrets.php) so
     * any row missed by that migration still reads correctly instead of
     * silently failing.
     */
    public function decryption($input = null) {
        if (is_null($input)) {
            return false;
        }
        $key = SYS_SCRKEY;
        $raw = base64_decode($input, true);
        if ($raw !== false && strlen($raw) > 28) {
            $iv = substr($raw, 0, 12);
            $tag = substr($raw, 12, 16);
            $cipher = substr($raw, 28);
            $result = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            if ($result !== false) {
                return $result;
            }
        }
        $legacyIv = substr(hash('sha256', SYS_SCRIV), 0, 16);
        return openssl_decrypt($input, 'AES-256-CTR', $key, 0, $legacyIv);
    }

    /**
     * Resolves $host to an IP and returns it only if that IP is public
     * (not loopback/private/link-local/reserved) - null otherwise. Shared
     * SSRF guard for any feature that fetches a customer-supplied URL
     * (WebhookIntegrations, AiBotCrawler): callers should pin curl to the
     * returned IP (CURLOPT_RESOLVE) rather than re-resolving the hostname,
     * or a DNS answer that changes between this check and the actual
     * connection (DNS rebinding) bypasses the check entirely.
     *
     * @param string $host
     * @return string|null
     */
    public static function resolveSafeIp($host) {
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return null;
        }
        return $ip;
    }

    /**
     * Rejects anything but a plain http(s) URL whose host resolves to a
     * public IP. See resolveSafeIp() above - callers that go on to make
     * the actual request should call resolveSafeIp() themselves and pin
     * to its result instead of relying on this boolean check alone.
     *
     * @param string $url
     * @return bool
     */
    public static function isSafeUrl($url) {
        $parts = parse_url($url);
        if (empty($parts['scheme']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true) || empty($parts['host'])) {
            return false;
        }
        return self::resolveSafeIp($parts['host']) !== null;
    }

    public function getIpAddress() {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    } else if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
                        return $ip;
                    } else {
                        return 0;
                    }
                }
            }
        }
    }

    public function clearExpiredCache($time = null) {
        if (is_null($time)) {$time = '-12 hours';}
        $cacheDir = SYS_BASEPATH.substr($GLOBALS['cacheConfig']['cacheDir'],3);
        $dir = opendir($cacheDir);
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && $file != '.htaccess') {
                if (filemtime($cacheDir . '/' . $file) <= strtotime($time)) {
                    unlink($cacheDir . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public function sefUrl($input = null) {
        if (!is_null($input)) {
            $input = transliterator_transliterate('Any-Latin; Latin-ASCII', $input);
            $input = mb_strtolower($input, 'utf-8');
            $input = trim(preg_replace('#[^-a-zA-Z0-9_ ]#', '', $input));
            $input = preg_replace('#[-_ ]+#', '-', $input);
            return $input;
        } else {
            return false;
        }
    }

    public function slugId($input = null) {
        if (!is_null($input) && preg_match('/^(\d+)/', $input, $matches)) {
            return (int) $matches[1];
        } else {
            return false;
        }
    }

    public function createPass($input = null) {
        if (!is_null($input)) {
            $password = password_hash($input, PASSWORD_DEFAULT);
            return $password;
        } else {
            return false;
        }
    }

    public function verifyPass($inputPass = null, $dbPass = null) {
        if (!is_null($inputPass) && !is_null($dbPass)) {
            $result = password_verify($inputPass, $dbPass);
            return $result;
        } else {
            return false;
        }
    }

    public function getUrl() {
      $result = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
      return $result;
    }

    public function isMobileDevice() {
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if (empty($agent)) {
            return false;
        }
        return preg_match('/(Mobile|Android|Tablet|GoBrowser|[0-9]x[0-9]*|uZardWeb\/|Mini|Doris\/|Skyfire\/|iPhone|iPad|iOS|Fennec\/|Maemo|Iris\/|CLDC\-|Mobi\/)/uis', $agent) === 1;
    }

    public static function ensureSitemapFiles() {
        $sitemapPath = SYS_BASEPATH . '/Public/sitemap.xml';
        $robotsPath = SYS_BASEPATH . '/Public/robots.txt';
        $sitemapMissing = !file_exists($sitemapPath);
        $robotsMissing = !file_exists($robotsPath);
        if (!$sitemapMissing && !$robotsMissing) {
            return; // both already present - the common case, nothing to do
        }
        $publicDir = SYS_BASEPATH . '/Public';
        if (!is_writable($publicDir)) {
            $procOwner = 'unknown';
            if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
                $pw = posix_getpwuid(posix_geteuid());
                $procOwner = isset($pw['name']) ? $pw['name'] : ('uid ' . posix_geteuid());
            }
            $dirOwner = 'unknown';
            $dirUid = @fileowner($publicDir);
            if ($dirUid !== false) {
                $dirOwner = function_exists('posix_getpwuid') && ($pw2 = posix_getpwuid($dirUid))
                    ? $pw2['name'] : ('uid ' . $dirUid);
            }
            $dirGroup = 'unknown';
            $dirGid = @filegroup($publicDir);
            if ($dirGid !== false) {
                $dirGroup = function_exists('posix_getgrgid') && ($gr = posix_getgrgid($dirGid))
                    ? $gr['name'] : ('gid ' . $dirGid);
            }
            $dirMode = @fileperms($publicDir);
            $dirMode = $dirMode !== false ? substr(sprintf('%o', $dirMode), -4) : 'unknown';
            error_log(
                '[SunFunc::ensureSitemapFiles] "' . $publicDir . '" (owner: ' . $dirOwner . ', group: ' .
                $dirGroup . ', mode: ' . $dirMode . ') is not writable by the web server process ' .
                '(running as: ' . $procOwner . '). sitemap.xml/robots.txt were NOT (re)created - ' .
                'align ownership (e.g. chgrp the directory to the web server\'s group and use 775, ' .
                'never 777) or create these two files by hand once and upload them directly.'
            );
            return;
        }
        $originalDocRoot = $_SERVER['DOCUMENT_ROOT'];
        $_SERVER['DOCUMENT_ROOT'] = SYS_BASEPATH . '/Public';
        try {
            $sitemap = new SunSitemap(SYS_BASEURL);
            if ($sitemapMissing) {
                foreach (SYS_LANGUAGES as $lang) {
                    $sitemap->addUrl($lang . '/' . SYS_HOMEPAGE, date('c'), 'weekly', '1.0');
                }
                $sitemap->createSitemap();
            }
            if ($robotsMissing) {
                $sitemap->updateRobots(['/App/', '/Core/', '/System/', '/index.php', '/init.php']);
            }
        } catch (\Throwable $e) {
            error_log(
                '[SunFunc::ensureSitemapFiles] ' . get_class($e) . ': ' . $e->getMessage() .
                ' in ' . $e->getFile() . ':' . $e->getLine()
            );
        }
        $_SERVER['DOCUMENT_ROOT'] = $originalDocRoot;
    }

}

?>
