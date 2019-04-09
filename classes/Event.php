<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo;


use HereYouGo\Exception\UnknownProperty;

class Event {
    const BEFORE = 'before';
    const AFTER = 'after';

    /** @var array */
    private static $handlers = [];

    /** @var string */
    private $name = '';

    /** @var mixed|null */
    public $data = null;

    /** @var mixed|null */
    public $result = null;

    /** @var bool */
    private $propagation_stopped = false;

    /** @var bool */
    private $default_prevented = false;

    /**
     * Register handler
     *
     * @param string $position
     * @param string $name
     * @param callable $callback
     */
    public static function register($position, $name, callable $callback) {
        if(!array_key_exists($name, self::$handlers))
            self::$handlers[$name] = [];

        if(!array_key_exists($position, self::$handlers[$name]))
            self::$handlers[$name][$position] = [];

        self::$handlers[$name][$position][] = $callback;
    }

    /**
     * Get given event handlers
     *
     * @param string $position
     * @param string $name
     *
     * @return array
     */
    private static function getHandlers($position, $name) {
        if(!array_key_exists($name, self::$handlers) || !array_key_exists($position, self::$handlers[$name]))
            return [];

        return self::$handlers[$name][$position];
    }

    /**
     * Event constructor.
     *
     * @param string $name
     * @param mixed $data
     */
    public function __construct($name, $data = null) {
        $this->name = $name;
        $this->data = $data;
    }

    /**
     * Stop event propagation
     */
    public function stopPropagation() {
        $this->propagation_stopped = true;
    }

    /**
     * Prevent event default
     */
    public function preventDefault() {
        $this->default_prevented = true;
    }

    /**
     * Trigger event
     *
     * @param callable $default
     *
     * @return mixed|null
     */
    public function trigger(callable $default = null) {
        foreach(self::getHandlers(self::BEFORE, $this->name) as $handler) {
            call_user_func($handler, $this);
            if($this->propagation_stopped)
                break;
        }

        $this->result = null;
        if($default && !$this->default_prevented)
            $this->result = call_user_func_array($default, is_array($this->data) ? $this->data : [$this->data]);

        if(!$this->propagation_stopped) {
            foreach(self::getHandlers(self::AFTER, $this->name) as $handler) {
                call_user_func($handler, $this);
                if($this->propagation_stopped)
                    break;
            }
        }

        return $this->result;
    }

    /**
     * Getter
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws UnknownProperty
     */
    public function __get($name) {
        if(in_array($name, ['name']))
            return $this->data;

        throw new UnknownProperty($this, $name);
    }

    /**
     * Stringifier
     *
     * @return string
     */
    public function __toString() {
        return self::class.'#'.$this->name;
    }
}