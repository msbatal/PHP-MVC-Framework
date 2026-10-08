<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

function _c() {
    $result = $GLOBALS['functions']->csrfToken();
    echo $result;
}

function _e($input = null) {
    $result = $GLOBALS['filter']->sanitize($input, 'string')->result();
    echo $result;
}

function _a($role = null) {
    try {
        $auth = $GLOBALS['auth'];
        if (!$auth->isLoggedIn()) {
            return false;
        }
        return is_null($role) ? true : $auth->hasRole($role);
    } catch (\Throwable $e) {
        return false; // fail closed: treat an unreachable auth backend as "not authorized"
    }
}

function _t($input = null, $intpol = null) {
    $result = $GLOBALS['local']->translate($input, $intpol);
    return $result;
}

/**
 * Like _t(), but returns the translated string instead of echoing it.
 * SunLocal::translate() echoes directly and only returns on a missed
 * lookup, so _t() can't be used where the value itself is needed (e.g.
 * building a JS-facing i18n object with json_encode() - json_encode(_t(...))
 * would encode whatever translate() *returned*, which is nothing on a
 * successful lookup, not what it printed). Needed for exactly that case.
 */
function _tr($input = null, $intpol = null) {
    ob_start();
    $GLOBALS['local']->translate($input, $intpol);
    return ob_get_clean();
}

function _w() {
  $result = $GLOBALS['functions']->getUrl();
  echo $result;
}

function _l() {
    $result = strtolower($GLOBALS['sunApp']->routes[0]);
    echo $result;
}

function _u($input = null) {
    $lang = strtolower($GLOBALS['sunApp']->routes[0]);
    $url = $GLOBALS['functions']->sefUrl($input);
    $result = $lang . '/' . $url . '/';
    echo $result;
}

/**
 * Slug half of an "{id}-{slug}" SEO URL segment (SunFunc::sefUrl();
 * SunFunc::slugId() is the reverse - extracting the id back out on the
 * controller side, see System/README.md's SunFunc section). Returns
 * rather than echoes, like _tr() vs _t() - this is almost always
 * concatenated into a larger URL string ($row['id'] . '-' . _s($row['title'])),
 * not printed on its own.
 */
function _s($input = null) {
    return $GLOBALS['functions']->sefUrl($input);
}


?>