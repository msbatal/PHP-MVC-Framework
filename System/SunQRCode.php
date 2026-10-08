<?php

/**
 * SunQRCode Class
 *
 * @category  QR Code Generator
 * @package   SunQRCode
 * @author    Mehmet Selcuk Batal <batalms@gmail.com>
 * @copyright Copyright (c) 2025, Sunhill Technology <www.sunhillint.com>
 * @license   https://opensource.org/licenses/lgpl-3.0.html The GNU Lesser General Public License, version 3.0
 * @link      https://github.com/msbatal/PHP-QRCode-Generator
 * @version   1.2.0
 */

class SunQRCode 
{
    
    private string $data; // The URL or text payload to be encoded into the QR code
    private int $size = 300; // Output image dimension in pixels (width and height)
    private string $format = 'svg'; // Output image file format ('svg' or 'png')
    private string $foregroundColor = '000000'; // QR code modules color in HEX format
    private string $backgroundColor = 'ffffff'; // Image background color in HEX format
    private string $errorCorrection = 'L'; // Reed-Solomon error correction level ('L', 'M', 'Q', 'H')
    private int $timeout = 10; // cURL timeout in seconds

    /**
     * Initializes the QR Code Generator with an optional initial data payload.
     *
     * @param string $data
     */
    public function __construct(string $data = '') {
        $this->data = trim($data);
    }

    /**
     * Sets the URL or text payload to be encoded.
     * 
     * @param string $data
     * @return string
     */
    public function setData(string $data): self {
        $this->data = trim($data);
        return $this;
    }

    /**
     * Sets the square dimension of the QR code in pixels (between 50 and 1000).
     * 
     * @param integer $size
     * @return object
     * @throws Exception
     */
    public function setSize(int $size): self {
        if ($size < 50 || $size > 1000) {
            throw new Exception("QR code size must be between 50px and 1000px. Given: {$size}");
        }
        $this->size = $size;
        return $this;
    }

    /**
     * Sets the output format: 'svg' or 'png'.
     * 
     * @param string $format
     * @return object
     * @throws Exception
     */
    public function setFormat(string $format): self {
        $format = strtolower($format);
        if (!in_array($format, ['svg', 'png'])) {
            throw new Exception("Unsupported format: '{$format}'. Allowed formats are 'svg' and 'png'.");
        }
        $this->format = $format;
        return $this;
    }

    /**
     * Sets the foreground (modules) color in HEX format (e.g., "000000" or "#000000").
     * 
     * @param string $hexColor
     * @return object
     * @throws Exception
     */
    public function setForegroundColor(string $hexColor): self {
        $cleanHex = ltrim($hexColor, '#');
        if (!ctype_xdigit($cleanHex) || (strlen($cleanHex) !== 3 && strlen($cleanHex) !== 6)) {
            throw new Exception("Invalid foreground HEX color code: '{$hexColor}'");
        }
        $this->foregroundColor = $cleanHex;
        return $this;
    }

    /**
     * Sets the background color in HEX format (e.g., "FFFFFF" or "#FFFFFF").
     * 
     * @param string $hexColor
     * @return object
     * @throws Exception
     */
    public function setBackgroundColor(string $hexColor): self {
        $cleanHex = ltrim($hexColor, '#');
        if (!ctype_xdigit($cleanHex) || (strlen($cleanHex) !== 3 && strlen($cleanHex) !== 6)) {
            throw new Exception("Invalid background HEX color code: '{$hexColor}'");
        }
        $this->backgroundColor = $cleanHex;
        return $this;
    }

    /**
     * Sets the Error Correction Level ('L', 'M', 'Q', 'H').
     * 
     * @param string $level
     * @return object
     * @throws Exception
     */
    public function setErrorCorrection(string $level): self {
        $level = strtoupper($level);
        if (!in_array($level, ['L', 'M', 'Q', 'H'])) {
            throw new Exception("Invalid error correction level: '{$level}'. Allowed values: L, M, Q, H.");
        }
        $this->errorCorrection = $level;
        return $this;
    }

    /**
     * Sets the cURL connection timeout limit in seconds.
     * 
     * @param integer $seconds
     * @return object
     */
    public function setTimeout(int $seconds): self {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Builds the remote API endpoint URL with query parameters.
     */
    private function buildApiUrl(): string {
        $encodedData = urlencode($this->data);
        $dimension = "{$this->size}x{$this->size}";
        return sprintf(
            "https://api.qrserver.com/v1/create-qr-code/?size=%s&data=%s&format=%s&color=%s&bgcolor=%s&ecc=%s",
            $dimension,
            $encodedData,
            $this->format,
            $this->foregroundColor,
            $this->backgroundColor,
            $this->errorCorrection
        );
    }

    /**
     * Executes the cURL request and fetches raw QR binary/string data.
     *
     * @return string|image
     * @throws Exception
     */
    public function generate(): string {
        if (empty($this->data)) {
            throw new Exception("The data payload to encode cannot be empty.");
        }
        $apiUrl = $this->buildApiUrl();
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'PHP-QrCodeGenerator/2.0'
        ]);
        $result = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // Check for network-level or cURL errors
        if ($curlErrno !== CURLE_OK) {
            throw new Exception("Failed to connect to QR service (cURL Error {$curlErrno}): {$curlError}");
        }
        // Check HTTP status code
        if ($httpCode !== 200) {
            throw new Exception("QR service returned an unsuccessful HTTP status: {$httpCode}", $httpCode);
        }
        // Check for empty body response
        if (empty($result)) {
            throw new Exception("QR service returned an empty response body.", $httpCode);
        }
        return $result;
    }

    /**
     * Outputs the generated QR code directly to the browser header stream.
     */
    public function render(): void {
        $content = $this->generate();
        $mimeType = $this->format === 'svg' ? 'image/svg+xml' : 'image/png';
        header("Content-Type: {$mimeType}");
        echo $content;
        exit;
    }

    /**
     * Saves the generated QR code to a specified file path.
     *
     * @param string $filePath
     * @return boolean
     * @throws Exception
     */
    public function saveToFile(string $filePath): bool {
        $content = $this->generate();
        $saved = file_put_contents($filePath, $content);
        if ($saved === false) {
            throw new Exception("Failed to save QR code image. Permission denied or invalid path: {$filePath}");
        }
        return true;
    }

}

?>