<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Logger;


use HereYouGo\Logger;

/**
 * File based logger
 *
 * @package HereYouGo\Logger
 */
class File extends Base {
    /** @var resource */
    private $fh = '';

    /** @var bool */
    private $failure_notified = false;

    /**
     * Constructor
     *
     * @param array $conf
     */
    public function __construct(array $conf) {
        $path = array_key_exists('path', $conf) ? $conf['path'] : HYG_ROOT.'logs/';

        if(substr($path, -1) === '/')
            $path .= 'hyg';

        if(array_key_exists('rotate', $conf)) {
            $suffix = '';
            switch($conf['rotate']) {
                case 'yearly': $suffix = date('Y'); break;
                case 'monthly': $suffix = date('Y-m'); break;
                case 'weekly': $suffix = date('Y-\wW'); break;
                case 'daily': $suffix = date('Y-m-d'); break;
            }

            if($suffix)
                $path = preg_replace('`^(.+)/([^/])(\.[^\.]|$)$`', '$1/$2_'.$suffix.'$3', $path);
        }

        $this->fh = fopen($path, 'a');
    }

    /**
     * Close handle when done
     */
    public function __destruct() {
        fclose($this->fh);
    }

    /**
     * Log message
     *
     * @param int $level
     * @param string $message
     */
    public function log($level, $message) {
        $message = '['.Logger::LEVELS[$level].'] '.$message;

        if(
            !$this->fh ||
            !flock($this->fh, LOCK_EX) ||
            !fwrite($this->fh, $message) ||
            !fflush($this->fh) ||
            !flock($this->fh, LOCK_UN)
        ) {
            if(!$this->failure_notified) {
                $this->failure_notified = true;

                error_log('HYG::Logger::File could not log to file ' . stream_get_meta_data($this->fh)['uri']);
            }

            error_log($message);
        }
    }
}