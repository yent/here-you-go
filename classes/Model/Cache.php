<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Model;

/**
 * Class Cache
 *
 * @package HereYouGo\Model
 */
class Cache {
    const ENTITIY = 'entity';
    const RELATION = 'relation';
    const COLLECTIONS = 'collections';

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

        $entity = self::get($path);
        if($entity)
            return $entity;

        return ($class && $key) ? null : [];
    }

    /**
     * Cache entity
     *
     * @param Entity $entity
     *
     * @throws Exception\Broken
     */
    public static function setEntity(Entity $entity) {
        self::set([self::ENTITIY, get_class($entity), $entity->getCacheKey()], $entity);
    }

    /**
     * Drop entity / class from cache
     *
     * @param Entity|string $entity
     *
     * @throws Exception\Broken
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

        self::dropCollection($entity);

        self::drop($path);
    }

    /**
     * Get relation between given entity and other
     *
     * @param Entity $entity
     * @param Entity|string $other
     *
     * @return Entity[]|bool|null
     *
     * @throws Exception\Broken
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
        if(is_null($relation))
            return null;

        if($other instanceof Entity)
            return true;

        return array_filter(array_map(function($key) use($other) {
            return self::getEntity($other, $key);
        }, array_keys($relation)));
    }

    /**
     * Build reciprocating cache paths
     *
     * @param Entity|string $thing
     * @param Entity|string $other
     *
     * @return array[]
     *
     * @throws Exception\Broken
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
     *
     * @throws Exception\Broken
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
     *
     * @throws Exception\Broken
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

    /**
     * Get cached collection
     *
     * @param Query $query
     *
     * @return Entity[]|null
     */
    public static function getCollection(Query $query) {
        $entities = self::get([self::COLLECTIONS, $query->cache_key]);
        if(is_null($entities))
            return null;

        return array_filter(array_map(function($cache) {
            return self::getEntity($cache['class'], $cache['key']);
        }, $entities));
    }

    /**
     * Set cached collection entities
     *
     * @param Query $query
     * @param Entity[] $entities
     */
    public static function setCollection(Query $query, array $entities) {
        self::set([self::COLLECTIONS, $query->cache_key], array_values(array_map(function(Entity $entity) {
            return ['class' => get_class($entity), 'key' => $entity->getCacheKey()];
        }, $entities)));
    }

    /**
     * Drop cache collection
     *
     * @param Query|Entity|string $thing
     */
    public static function dropCollection($thing) {
        if($thing === '*') {
            self::drop([self::COLLECTIONS]);

        } else if(is_string($thing)) {
            foreach(self::find([self::COLLECTIONS]) as &$queries) {
                foreach($queries as $key => &$entities) {
                    $entities = array_filter($entities, function($cache) use($thing) {
                        return $cache['class'] !== $thing;
                    });

                    if(!$entities)
                        unset($queries[$key]);
                }

                if(!$queries)
                    unset($queries);
            }

        } else if($thing instanceof Query) {
            self::drop([self::COLLECTIONS, $thing->cache_key]);

        } else if($thing instanceof Entity) {
            foreach(self::find([self::COLLECTIONS]) as &$queries) {
                foreach($queries as $key => &$entities) {
                    $entities = array_filter($entities, function($cache) use($thing) {
                        return ($cache['class'] !== get_class($thing)) || ($cache['key'] !== $thing->getCacheKey());
                    });

                    if(!$entities)
                        unset($queries[$key]);
                }

                if(!$queries)
                    unset($queries);
            }
        }
    }
}