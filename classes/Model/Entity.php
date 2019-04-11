<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Model;

use HereYouGo\DBI;
use HereYouGo\Exception\BadType;
use HereYouGo\Model;
use HereYouGo\Model\Exception\Broken;
use HereYouGo\Model\Exception\NotFound;
use HereYouGo\Model\Constant\Relation;
use ReflectionClass;
use ReflectionException;

/**
 * Class Entity
 *
 * @package HereYouGo\Model
 */
abstract class Entity {
    /** @var Model|null */
    protected static $model = null;

    /** @var array */
    protected $relation_keys = [];

    /** @var bool */
    protected $stored_in_database = false;
    
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
        /** @var string[] $parts */
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
     * @return Entity[]|JoinCollection|null
     *
     * @throws BadType
     * @throws Broken
     * @throws NotFound
     * @throws ReflectionException
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

        $results = [];
        while($row = $statement->fetch())
            $results[] = $query->categorizeData($row);

        if($joined)
            return new JoinCollection($query, $results);

        $entities = array_map(function(ResultSet $result_set) use($query) {
            return static::fromData($result_set->{$query->scope}->data);
        }, $results);

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
     * @throws BadType
     * @throws Broken
     * @throws NotFound
     * @throws ReflectionException
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
     * @return static
     *
     * @throws Broken
     * @throws NotFound
     * @throws BadType
     * @throws ReflectionException
     */
    public static function fromData(array $data) {
        $entity = static::fromPk($data, false); // ensures that primary key columns are included
        $properties = static::model()->data_map;

        if($entity) {
            $properties = array_filter($properties, function(Property $property) {
                return !$property->primary;
            });

        } else {
            $entity = (new ReflectionClass(static::class))->newInstanceWithoutConstructor();
        }

        foreach($properties as $property) {
            if($property->primary) continue;

            $col = array_key_exists($property->column, $data) ? $property->column : $property->name;

            if(array_key_exists($col, $data)) {
                $value = $property->castToEntity($data[$col]);

            } else {
                if(!$property->null)
                    throw new Broken(static::class, '');

                $value = null;
            }

            if($property->related_class) {
                if(!array_key_exists($property->related_class, $entity->relation_keys))
                    $entity->relation_keys[$property->related_class] = [];

                $entity->relation_keys[$property->related_class][$property->name] = $value;

            } else {
                $entity->{$property->name} = $value;
            }
        }

        return $entity;
    }

    /**
     * Save entity in database
     *
     * @throws BadType
     * @throws Broken
     */
    public function save() {
        // Check primary keys
        foreach(static::model()->primary_keys as $property) {
            if($property->auto_increment) continue;
            if($this->{$property->name}) continue;

            throw new Broken($this, "missing primary key $property->name value");
        }

        // Check relations
        foreach(static::model()->relations as $class => $has) {
            if($has !== Model\Constant\Relation::ONE) continue;

            if(!array_key_exists($class, $this->relation_keys))
                throw new Broken($this, "needs relation to a $class but has none");

            /** @var Entity $class */
            foreach($class::model()->primary_keys as $property)
                if(!array_key_exists($property->name, $this->relation_keys[$class]))
                    throw new Broken($this, "relation with $class is missing $property->name key");
        }

        $pks = [];
        $columns = [];
        $placeholders = [];
        foreach(static::model()->data_map as $property) {
            $name = $property->related_class ? "{$property->related_class}___$property->name" : $property->name;
            $value = $property->related_class ? $this->relation_keys[$property->related_class][$property->name] : $this->{$property->name};

            if($this->stored_in_database && $property->primary) {
                $pks[$property->column] = ":$name";

            } else {
                $columns[$property->column] = ":$name";
            }

            $placeholders[":$name"] = $property->castToEntity($value);
        }

        $table = static::model()->table;

        if($this->stored_in_database) {
            $query = "UPDATE $table SET ".implode(', ', array_map(function($col, $ph) {
                return "`$col` = $ph";
            }, array_keys($columns), array_values($columns)))." WHERE ".implode(' AND ', array_map(function($col, $ph) {
                return "`$col` = $ph";
            }, array_keys($pks), array_values($pks)));

            $statement = DBI::prepare($query);
            $statement->execute($placeholders);

        } else {
            $query = "INSERT INTO $table (`".implode('`, `', array_keys($columns))."`) VALUES(".implode(', ', array_values($columns)).")";

            $statement = DBI::prepare($query);
            $statement->execute($placeholders);

            // update autoinc pk if any
            foreach(static::model()->primary_keys as $property)
                if($property->primary && $property->auto_increment)
                    $this->{$property->name} = DBI::lastInsertId("{$table}_{$property->column}_seq");

            Cache::dropCollection(static::class);

            $this->stored_in_database = true;
        }

        Cache::setEntity($this);
    }

    /**
     * Delete entity from database
     *
     * @throws BadType
     * @throws Broken
     */
    public function delete() {
        if(!$this->stored_in_database)
            return;

        $pks = [];
        $placeholders = [];
        foreach(static::model()->primary_keys as $property) {
            $pks[$property->column] = ":$property->name";
            $placeholders[":$property->name"] = $property->castToEntity($this->{$property->name});
        }

        $table = static::model()->table;

        $statement = DBI::prepare("DELETE FROM $table WHERE ".implode(' AND ', array_map(function($col, $ph) {
            return "`$col` = $ph";
        }, array_keys($pks), array_values($pks))));

        $statement->execute($placeholders);

        $this->stored_in_database = false;

        Cache::dropEntity($this);
    }

    /**
     * Get related entity/entities
     *
     * @param string $other
     *
     * @return Entity|Entity[]
     *
     * @throws BadType
     * @throws Broken
     * @throws NotFound
     * @throws ReflectionException
     */
    public function getRelated($other) {
        /** @var Entity $other */
        if(!array_key_exists($other, static::model()->relations))
            throw new Broken($this, "has no relation with $other");

        if(!array_key_exists(static::class, $other::model()->relations))
            throw new Broken($this, "has no relation with $other");

        $this_to_other_relation = static::model()->relations[$other];
        $other_to_this_relation = $other::model()->relations[static::class];

        if($this_to_other_relation === Relation::ONE && $other_to_this_relation === Relation::ONE)
            throw new Broken($this, "cannot have one to one relation with $other");

        $cache = Cache::getRelation($this, $other);
        if($cache)
            return ($this_to_other_relation === Relation::MANY) ? $cache : reset($cache);

        if($this_to_other_relation === Relation::ONE) {
            // this has one other, other has many this => this has the relation keys

            $other = $other::fromPk($this->relation_keys[$other]);
            Cache::setRelation($this, $other);

            return $other;
        }

        if($other_to_this_relation === Relation::ONE) {
            // this has many others, other has one this => other has the relation keys

            $criteria = [];
            $placeholders = [];
            foreach(static::model()->primary_keys as $property) {
                $value = $this->{$property->name}; // do this before name changes
                $property = $property->getRelationProperty($other);
                $criteria[] = "$property->name = :$property->name";
                $placeholders[":$property->name"] = $value;
            }

            $criteria = implode(' AND ', $criteria);

            $others = $other::all(new Query($other, $criteria, $placeholders));

        } else {
            // this has many others, other has many this => there is a relation table
            $relation_table = static::model()->getRelationTableWith($other);
            $other_table = $other::model()->table;

            $criteria = [];
            $placeholders = [];
            foreach(static::model()->primary_keys as $property) {
                $value = $this->{$property->name}; // do this before name changes
                $property = $property->getRelationProperty($other);
                $criteria[] = "$relation_table.$property->column = :$property->name";
                $placeholders[":$property->name"] = $value;
            }

            $on = [];
            foreach($other::model()->primary_keys as $property) {
                $relation_property = $property->getRelationProperty(static::class);
                $on[] = "$other_table.$property->column = $relation_table.$relation_property->column";
            }

            $on = implode(' AND ', $on);

            $statement = DBI::prepare("SELECT $other_table.* FROM $relation_table JOIN $other_table ON ($on) WHERE $criteria");
            $statement->execute($placeholders);

            $others = [];
            foreach($statement->fetchAll() as $row)
                $others[] = $other::fromData($row);
        }

        foreach($others as $other)
            Cache::setRelation($this, $other);

        return $others;
    }
}