<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Model;

abstract class Entity {
    const HAS_ONE = 1;
    const HAS_MANY = 2;

    /**
     * Get data map
     *
     * @return array
     */
    abstract public static function dataMap(): array;

    /**
     * Get relations with other entities
     *
     * @return int[]
     */
    public static function relations() {
        return [];
    }

    /**
     * Get table name
     *
     * @return string
     */
    public static function table() {
        return static::class.'s';
    }
}