<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Model;


class Cache {
    /** @var array */
    private static $entities = [];

    /** @var array */
    private static $relations = [];

    /**
     * Get entity/entities
     *
     * @param string|null $class
     * @param mixed|null $key
     *
     * @return Entity[]|Entity
     */
    public static function getEntity($class = null, $key = null) {
        if(!$class)
            return self::$entities;

        if(!array_key_exists($class, self::$entities))
            return $key ? null : [];

        if(!$key)
            return self::$entities[$class];

        if(!array_key_exists($key, self::$entities[$class]))
            return null;

        return self::$entities[$class][$key];
    }

    /**
     * Cache entity
     *
     * @param Entity $entity
     */
    public static function setEntity(Entity $entity) {
        $class = get_class($entity);
        if(!array_key_exists($class, self::$entities))
            self::$entities[$class] = [];

        self::$entities[$class][$entity->getCacheKey()] = $entity;
    }

    /**
     * Drop entity / class from cache
     *
     * @param Entity|string|null $class
     * @param string|null $key
     */
    public static function dropEntity($class = null, $key = null) {
        if($class instanceof Entity) {
            $key = $class->getCacheKey();
            $class = get_class($class);
        }

        if($class && $key) {
            if(array_key_exists($class, self::$entities) && array_key_exists($key, self::$entities[$class]))
                unset(self::$entities[$class][$key]);

        } else if($class) {
            if(array_key_exists($class, self::$entities))
                unset(self::$entities[$class]);

        } else {
            self::$entities = [];
        }
    }
}