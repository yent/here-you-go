<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Logger;


use HereYouGo\Logger\Base;

/**
 * Syslog based logger
 *
 * @package HereYouGo\Logger
 */
class Syslog extends Base {
    const LEVELS = [LOG_ERR, LOG_WARNING, LOG_INFO, LOG_DEBUG];

    /** @var bool */
    private $failure_notified = false;

    /**
     * Constructor
     *
     * @param array $conf
     */
    public function __construct(array $conf) {
        $ident = array_key_exists('ident', $conf) ? $conf['ident'] : 'HYG';
        $facility = array_key_exists('facility', $conf) ? $conf['facility'] : LOG_LOCAL0;

        openlog($ident, 0, $facility);
    }

    /**
     * Close handle when done
     */
    public function __destruct() {
        closelog();
    }

    /**
     * Log message
     *
     * @param int $level
     * @param string $message
     */
    public function log($level, $message) {
        if(!syslog(self::LEVELS[$level], $message)) {
            if(!$this->failure_notified) {
                $this->failure_notified = true;

                error_log('HYG::Logger::Syslog could not log');
            }

            error_log($message);
        }
    }
}