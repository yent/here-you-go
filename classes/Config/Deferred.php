<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Config;

/**
 * Class Deferred
 *
 * @package HereYouGo\Config
 */
class Deferred {
    /** @var callable */
    private $callback = null;

    /**
     * Deferred constructor.
     *
     * @param callable $callback
     */
    public function __construct(callable $callback) {
        $this->callback = $callback;
    }

    /**
     * Run callback and get value
     *
     * @return mixed
     */
    public function evaluate() {
        return ($this->callback)();
    }

    /**
     * value shortcut
     *
     * @return mixed
     */
    public function __invoke() {
        return $this->evaluate();
    }
}