<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo;


use HereYouGo\Logger\Base;

/**
 * Main logger
 *
 * @package HereYouGo\Utils
 */
class Logger {
    const ERROR = 0;
    const WARN  = 1;
    const INFO  = 2;
    const DEBUG = 3;

    const LEVELS = ['error', 'warn', 'info', 'debug'];

    /** @var Base */
    private static $facility = null;

    /** @var int */
    private static $level = self::INFO;

    /**
     * Setup logger
     *
     * @param bool $force_reload
     */
    public static function setup($force_reload = false) {
        if(self::$facility && !$force_reload)
            return;

        $default = ['type' => 'server'];

        $conf = Config::get('logger');
        if(!$conf || !is_array($conf)) $conf = $default;

        $class = '\\HereYouGo\\Logger\\'.ucfirst($conf['type']);
        if(!class_exists($class)) $conf = $default;

        self::$facility = new $class($conf);

        if(array_key_exists('level', $conf) && is_int($conf['level']))
        self::$level = $conf['level'];
    }

    /**
     * Log things
     *
     * @param int $level
     * @param array $things
     */
    private static function log($level, array $things) {
        if($level > self::$level)
            return;

        foreach($things as $thing) {
            if(is_scalar($thing)) {
                self::$facility->log($level, $thing);
            } else {
                foreach(explode("\n", print_r($thing, true)) as $line)
                    self::$facility->log($level, $line);
            }
        }
    }

    /**
     * Log error
     *
     * @param mixed ...$things
     */
    public static function error(...$things) {
        self::log(self::ERROR, $things);
    }

    /**
     * Log warning
     *
     * @param mixed ...$things
     */
    public static function warn(...$things) {
        self::log(self::WARN, $things);
    }

    /**
     * Log info
     *
     * @param mixed ...$things
     */
    public static function info(...$things) {
        self::log(self::INFO, $things);
    }

    /**
     * Log debug
     *
     * @param mixed ...$things
     */
    public static function debug(...$things) {
        self::log(self::DEBUG, $things);
    }
}