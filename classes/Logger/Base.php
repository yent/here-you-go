<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Logger;

/**
 * Logger base
 *
 * @package HereYouGo\Logger
 */
abstract class Base {
    /**
     * Constructor
     *
     * @param array $conf
     */
    abstract public function __construct(array $conf);

    /**
     * Log message
     *
     * @param int $level
     * @param string $message
     */
    abstract public function log($level, $message);
}