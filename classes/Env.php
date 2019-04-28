<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo;

/**
 * Class Env
 *
 * @package HereYouGo
 */
class Env {
    /**
     * Tells wether client is cli
     *
     * @return bool
     */
    public static function isCli() {
        return php_sapi_name() === 'cli';
    }

    /**
     * Fails if not cli
     */
    public static function requireCli() {
        if(!self::isCli())
            die("restricted to command line interface.\n");
    }
}