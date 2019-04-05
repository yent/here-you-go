<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Loggers;

use HereYouGo\Logger\Base;
use HereYouGo\Logger;

/**
 * Http server based logger
 *
 * @package HereYouGo\Logger
 */
class Server extends Base {
    /**
     * Constructor
     *
     * @param array $conf
     */
    public function __construct(array $conf) {}

    /**
     * Log message
     *
     * @param int $level
     * @param string $message
     */
    public function log($level, $message) {
        error_log('['.Logger::LEVELS[$level].'] '.$message);
    }
}