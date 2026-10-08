<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Namespace for model
 * Use App/Models/Admin directory
 */
namespace App\Models\Admin;

/**
 * Inherit from the main model
 * Don't change parent model path and name
 */
class Dashboard extends \Core\Model
{

    /**
     * Main method of the model
     * Don't change the method's name
     */
    public function show() {
        // no db work yet - add your own methods here as the panel grows
    }

    /**
     * Visit statistics for the dashboard (SunAnalytics reports, see System/README.md)
     *
     * @param integer $days
     * @return array|null null when the analytics tables are missing (import database/schema.sql)
     */
    public function stats($days = 30) {
        try {
            $analytics = $GLOBALS['analytics'];
            return [
                'summary' => $analytics->summary($days),
                'sources' => $analytics->sources($days, 5),
                'pages'   => $analytics->pages($days, 5),
                'online'  => $analytics->online()
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

}

?>
