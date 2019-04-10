<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Model;

use HereYouGo\DBI;
use HereYouGo\Model;
use HereYouGo\Model\Exception\Broken;
use HereYouGo\Model\Exception\NotFound;

abstract class Entity {
    /** @var Model|null */
    protected static $model = null;
    
    /**
     * Get model
     *
     * @return Model
     *
     * @throws Broken
     */
    final public static function model() {
        if(is_null(static::$model))
            static::$model = new Model(static::class);

        return static::$model;
    }

    /**
     * Check and format primary key
     *
     * @param array|string $pk
     *
     * @throws Broken
     */
    final protected static function checkPk(&$pk) {
        $parts = array_filter(array_map(function(Property $property) {
            return $property->primary ? $property->name : null;
        }, static::model()->data_map));

        sort($parts);

        if(!is_array($pk)) {
            if(count($parts) > 1)
                throw new Broken(static::class, 'entity has multi-properties primary key but single part was provided');

            $pk[$parts[0]] = $pk;
        }

        foreach($parts as $part)
            if(!array_key_exists($part, $pk))
                throw new Broken(static::class, "primary key part $pk is missing");

        sort($pk);
    }

    /**
     * Get cache key
     *
     * @return string
     *
     * @throws Broken
     */
    final public function getCacheKey() {
        $parts = array_filter(array_map(function(Property $property) {
            return $property->primary ? $property->name : null;
        }, static::model()->data_map));

        $pk = [];
        foreach($parts as $name)
            $pk[$name] = $this->$name;

        return static::buildCacheKey($pk);
    }

    /**
     * Build cache key from primary key parts
     *
     * @param array|string $pk
     *
     * @return string
     *
     * @throws Broken
     */
    final public static function buildCacheKey($pk) {
        static::checkPk($pk);

        return base64_encode(serialize($pk));
    }

    /**
     * Get collection matching query
     *
     * @param Query|string $query
     * @param array $placeholders
     *
     * @return array|Entity[]|null
     *
     * @throws Broken
     */
    public static function all($query, $placeholders = []) {
        if($query instanceof Query) {
            $class = static::class;
            if($query->class !== static::class)
                throw new Broken($query, "asking $class to fetch instances of $query->class");

        } else {
            $query = new Query(static::class, $query, $placeholders);
        }

        $cached = Cache::getCollection($query);
        if($cached)
            return $cached;

        $statement = DBI::prepare($query->getSql());
        $statement->execute($query->getAggregatedPlaceholders());

        $joined = (bool)count($query->joins);

        $entities = [];
        while($row = $statement->fetch()) {
            $entry = $query->categorizeJoinedData($row);

            if($joined) {
                // TODO
            } else {
                // TODO
            }
        }

        Cache::setCollection($query, $entities);

        return $entities;
    }

    /**
     * Get from primary key
     *
     * @param array|string $pk
     * @param bool $fatal
     *
     * @return self
     *
     * @throws Broken
     * @throws NotFound
     */
    public static function fromPk($pk, $fatal = true) {
        static::checkPk($pk);

        $key = static::buildCacheKey($pk);
        $entity = Cache::getEntity(static::class, $key);
        if($entity)
            return $entity;

        $criteria = [];
        $placeholders = [];
        foreach($pk as $k => $v) {
            $criteria[] = "$k = :$k";
            $placeholders[":$k"] = $v;
        }

        $entities = static::all(implode(' AND ', $criteria), $placeholders);
        if(!$entities) {
            if($fatal)
                throw new NotFound(static::class, $pk);

            return null;
        }

        if(count($entities) > 1)
            throw new Broken(static::class, "more than one entity matching primary key");

        $entity = array_shift($entities);
        Cache::setEntity($entity);

        return $entity;
    }

    /**
     * Create or get and update from raw data
     *
     * @param array $data
     *
     * @return self
     */
    public static function fromData(array $data) {
        $entity = static::fromPk($data, false); // ensures that primary key columns are included

        if($entity) {

        } else {

        }
    }

    protected function __construct(array $data) {

    }

    final protected function setProperties(array $data) {

    }

    final protected function get
}