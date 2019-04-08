<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Exception;

use Exception;
use HereYouGo\Logger;

/**
 * Exception ancestor
 *
 * @package HereYouGo
 */
abstract class Base extends Exception {
    /** @var string */
    private $id = '';

    /**
     * Base constructor.
     *
     * @param string $message
     * @param int $code
     * @param Base|null $previous
     */
    public function __construct($message, $code = 500, Base $previous = null) {
        $this->id = uniqid();

        parent::__construct($message, $code, $previous);

        $this->log($message);

        foreach(explode("\n", $this->getTraceAsString()) as $line)
            $this->log($line);
    }

    protected function log($thing) {
        if(is_array($thing)) {
            foreach(explode("\n", print_r($thing, true)) as $line)
                $this->log($line);

        } else {
            $id = static::class.':'.$this->id;
            Logger::error("[exception:$id] ".$thing);
        }
    }

    /**
     * Get exception id
     *
     * @return string
     */
    public function getId() {
        return $this->id;
    }
}