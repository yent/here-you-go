<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo;


use ReflectionException;

/**
 * Class Enum
 *
 * @package HereYouGo
 */
class Enum {
    /** @var array|null */
    protected static $values = null;
    
    /**
     * Get all values
     *
     * @return array
     *
     * @throws ReflectionException
     */
    public static function getValues() {
        if(is_null(static::$values)) {
            static::$values = (new \ReflectionClass(static::class))->getConstants();
        }
        
        return static::$values;
    }
    
    /**
     * Check if known value
     *
     * @param mixed $value
     *
     * @return bool
     *
     * @throws ReflectionException
     */
    public static function isValue($value) {
        return in_array($value, static::getValues());
    }
}