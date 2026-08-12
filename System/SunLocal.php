<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

class SunLocal
{

    private $content = null;

    public function __construct($lang = null) {
        if (is_null($lang) || !in_array($lang, SYS_LANGUAGES)) {
            $lang = SYS_DFLTLANG; // set default lang
        }
        if (!file_exists(SYS_BASEPATH . '/App/Lang/lang.' . $lang . '.json')) { // required base file missing
            if (SYS_SYSERR === true) { // if system error printing set true
                throw new Exception('Language file "lang.'.$lang.'.json" not found.'); // print error message
            } else { // if set false
                $lang = SYS_DFLTLANG;
            }
        }
        $this->getFile($lang);
    }

    /**
     * Merge every namespaced language file for this lang code
     * (lang.{lang}.json, panel.{lang}.json, ...) into one flat lookup
     * table, so translate() works the same regardless of which file a
     * key lives in - new namespaces just need a matching file dropped
     * into App/Lang/, no code change here.
     */
    private function getFile($lang = null) {
        $content = [];
        foreach (glob(SYS_BASEPATH . '/App/Lang/*.' . $lang . '.json') as $file) {
            $decoded = json_decode(file_get_contents($file), true);
            if (is_array($decoded)) {
                $content = array_merge($content, $decoded);
            }
        }
        $this->content = $content;
    }

    public function translate() {
        $parameters = func_get_args(); // get parameters
        if (array_key_exists($parameters[0], $this->content)) { // if translation found
            if (!is_null($parameters[1])) {
                echo vsprintf($this->content[$parameters[0]], $parameters[1]); // print translated complex content
            } else {
                echo $this->content[$parameters[0]]; // print translated content
            }
        } else {
            return 'Non_Translated_Word'; // if translation not found
        }
    }

}

?>