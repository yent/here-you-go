<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Model;


class Cache {
    const ENTITIY = 'entity';
    const RELATION = 'relation';

    /** @var array */
    private static $items = [];

    /**
     * Find item from path
     *
     * @param array $path
     * @param bool $create
     *
     * @return array|bool|mixed
     */
    protected static function find(array $path, $create = false) {
        $item = &self::$items;

        while($path) {
            $p = array_shift($path);

            if(!array_key_exists($p, $item)) {
                if($create) {
                    $item[$p] = $path ? [] : null;
                } else {
                    return false;
                }
            }

            $item = &$item[$p];
        }

        return $item;
    }

    /**
     * Get item from path
     *
     * @param array $path
     * @param null $default
     *
     * @return array|bool|mixed|null
     */
    protected static function get(array $path, $default = null) {
        $item = self::find($path);

        return $item ? $item : $default;
    }

    /**
     * Set item in path
     *
     * @param array $path
     * @param $value
     */
    protected static function set(array $path, $value) {
        $item = &self::find($path, true);

        $item = $value;
    }

    /**
     * Drop item / tree from path
     *
     * @param array $path
     */
    protected static function drop(array $path) {
        while(end($path) === '*')
            array_pop($path);

        reset($path);

        $wildcard = array_search('*', $path);
        if($wildcard !== false) {
            $sub = array_slice($path, $wildcard + 1);
            $path = array_slice($path, 0, $wildcard);

            $base = &self::find($path);
            if(!$base || !is_array($base)) return;

            foreach(array_keys($base) as $k)
                self::drop($path + [$k] + $sub);

            return;
        }

        $item = self::find($path);

        if($item) unset($item); // TODO check if that works
    }

    /**
     * Get entity/entities
     *
     * @param string|null $class
     * @param mixed|null $key
     *
     * @return Entity[]|Entity
     */
    public static function getEntity($class = null, $key = null) {
        $path = [self::ENTITIY];
        if($class) {
            $path[] = $class;

            if($key)
                $path[] = $key;
        }

        $item = self::get($path);
        if($item)
            return $item;

        return ($class && $key) ? null : [];
    }

    /**
     * Cache entity
     *
     * @param Entity $entity
     */
    public static function setEntity(Entity $entity) {
        self::set([self::ENTITIY, get_class($entity), $entity->getCacheKey()], $entity);
    }

    /**
     * Drop entity / class from cache
     *
     * @param Entity|string $entity
     */
    public static function dropEntity($entity) {
        if($entity === '*') {
            self::drop([self::ENTITIY]);
            self::drop([self::RELATION]);

            return;
        }

        $path = [self::ENTITIY];

        if($entity instanceof Entity) {
            $path[] = get_class($entity);
            $path[] = $entity->getCacheKey();

        } else {
            $path[] = $entity;
        }

        self::dropRelation($entity);

        self::drop($path);
    }

    /**
     * Get relation between given entity and other
     *
     * @param Entity $entity
     * @param Entity|string $other
     *
     * @return string[]|bool
     */
    public static function getRelation(Entity $entity, $other) {
        $path = [self::RELATION, get_class($entity), $entity->getCacheKey()];

        if($other instanceof Entity) {
            $path[] = get_class($other);
            $path[] = $other->getCacheKey();

        } else {
            $path[] = $other;
        }

        $relation = self::get($path);
        if(!$relation)
            return ($other instanceof Entity) ? false : [];

        if($other instanceof Entity)
            return true;

        return array_keys($relation); // entities cache keys
    }

    /**
     * Build reciprocating cache paths
     *
     * @param Entity|string $thing
     * @param Entity|string $other
     *
     * @return array[]
     */
    protected static function relationPaths($thing, $other) {
        $paths = [];

        if($thing && $thing !== '*') {
            $path = [self::RELATION];

            if($thing instanceof Entity) {
                $path[] = get_class($thing);
                $path[] = $thing->getCacheKey();

                if($other) {
                    if($other instanceof Entity) {
                        $path[] = get_class($other);
                        $path[] = $other->getCacheKey();

                    } else {
                        $path[] = $other;
                    }
                }

            } else {
                $path[] = $thing; // class
            }

            $paths[] = $path;
        }

        if($other && $other !== '*') {
            $path = [self::RELATION];

            if($other instanceof Entity) {
                $path[] = get_class($other);
                $path[] = $other->getCacheKey();

                if($thing) {
                    if($thing instanceof Entity) {
                        $path[] = get_class($thing);
                        $path[] = $thing->getCacheKey();

                    } else {
                        $path[] = $thing;
                    }
                }

            } else {
                $path[] = $other; // class
            }

            $paths[] = $path;
        }

        return $paths;
    }

    /**
     * Set relation between entities
     *
     * @param Entity $entity
     * @param Entity $other
     */
    public static function setRelation(Entity $entity, Entity $other) {
        foreach(self::relationPaths($entity, $other) as $path)
            self::set($path, true);
    }

    /**
     * Drop relation
     *
     * @param $entity
     * @param null $other
     */
    public static function dropRelation($entity, $other = null) {
        if($entity === '*') {
            self::drop([self::RELATION]);
            return;
        }

        foreach(self::relationPaths($entity, $other) as $path)
            self::drop($path);

        if($other) return;

        $path = [self::RELATION, '*', '*'];

        if($entity instanceof Entity) {
            $path[] = get_class($entity);
            $path[] = $entity->getCacheKey();

        } else {
            $path[] = $entity;
        }

        self::drop($path);
    }
}