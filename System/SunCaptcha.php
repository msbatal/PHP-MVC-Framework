<?php

/**
 * SunCaptcha Class
 *
 * @category  Captcha Creator
 * @package   SunCaptcha
 * @author    Mehmet Selcuk Batal <batalms@gmail.com>
 * @copyright Copyright (c) 2021, Sunhill Technology <www.sunhillint.com>
 * @license   https://opensource.org/licenses/lgpl-3.0.html The GNU Lesser General Public License, version 3.0
 * @link      https://github.com/msbatal/PHP-Cache-Class
 * @version   1.4.3
 */

class SunCaptcha
{

  /**
   * Image width (min. 90px)
   * @var integer
   */
  private $width  = 120;

  /**
   * Image height (min. 30px)
   * @var integer
   */
  private $height =  40;

  /**
   * Font size (between 1 and 5)
   * @var integer
   */
  private $fontSize =  5;

  /**
   * Text color
   * @var string
   */
  private $textColor = '#fff';

  /**
   * Line color
   * @var string
   */
  private $lineColor = '#fff';

  /**
   * Background color
   * @var string
   */
  private $fillColor = '#098';

  /**
   * @param integer $width, $height
   * @param string $textColor, $lineColor, $fillColor
   */
  public function __construct($width = null, $height = null, $textColor = null, $lineColor = null, $fillColor = null) {
    if (!empty($width))
      $width < 90 ? null : $this->width = $width; // check and define image width
    if (!empty($height))
      $height < 30 ? null : $this->height = $height; // check and define image height
    if (!empty($textColor))
      $this->textColor = $textColor;
    if (!empty($lineColor))
      $this->lineColor = $lineColor;
    if (!empty($fillColor))
      $this->fillColor = $fillColor;
  }

  /**
   * Create captcha
   *
   * @return string
   */
  public function create() {
    $_SESSION['suncptch'] = substr(md5(mt_rand()), -6); // create session for validation
    $locationLeft = (int) (($this->width - 50) / 2);
    $locationTop = (int) (($this->height - 15) / 2);
    $text = $this->hex2rgb($this->textColor); // call conversion method
    $line = $this->hex2rgb($this->lineColor);
    $fill = $this->hex2rgb($this->fillColor);
    $captcha = imagecreatetruecolor($this->width, $this->height);
    $textColor = imagecolorallocate($captcha, $text[0], $text[1], $text[2]); // text color
    $lineColor = imagecolorallocate($captcha, $line[0], $line[1], $line[2]); // line color
    $fillColor = imagecolorallocate($captcha, $fill[0], $fill[1], $fill[2]); // background color
    imagefill($captcha, 0, 0, $fillColor); // fill the background
    imagestring($captcha, $this->fontSize, $locationLeft, $locationTop, $_SESSION['suncptch'], $textColor); // write the string 
    imageline($captcha, $this->width, 0, 0, $this->height, $lineColor); // draw the line
    ob_start();
    imagejpeg($captcha, null, 100); // create jpeg image
    $result = ob_get_contents();
    ob_end_clean();
    imagedestroy($captcha);  // destroy jpeg image
    return 'data:image/jpeg;base64,' . base64_encode($result);  // return base-64 image
  }

  /**
   * Validate captcha
   *
   * @param string $val
   * @return boolean
   */
  public function validate($val = null) {
    $result = false;
    if ($_SESSION['suncptch'] === $val) { // if validated
      $result = true;
    }
    return $result;
  }

  /**
   * Convert hex to rgb
   *
   * @param string $hex
   * @return array
   */
  private function hex2rgb($hex = null) {
    $hex = str_replace('#', '', $hex);
    switch (strlen($hex)) {
      case 3: // short base-16 code (eg. #000)
        $red = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $green = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $blue = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
      break;
      case 6: // long base-16 code (eg. #000000)
        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));
      break;
    }
    $rgb = array($red, $green, $blue);
    return $rgb;
  }

}

?>
