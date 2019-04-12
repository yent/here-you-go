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
use HereYouGo\Exception\UnknownProperty;
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

        if(!is_array($pk)) {
            if(count($parts) > 1)
                throw new Broken(static::class, 'entity has multi-properties primary key but single part was provided');

            $pk[reset($parts)] = $pk;
        }

        foreach($parts as $part) {
            if(!array_key_exists($part, $pk))
                throw new Broken(static::class, "primary key part $pk is missing");

            if(!$pk[$part])
                throw new Broken(static::class, "primary key part $pk is empty");
        }

        // keep only pk parts
        $pk = array_intersect_key($pk, array_fill_keys($parts, true));

        ksort($pk);
    }

    /**
     * Get primary key components
     *
     * @param bool $as_string
     *
     * @return array|string
     *
     * @throws Broken
     */
    final public function getPk($as_string = false) {
        $parts = array_filter(array_map(function(Property $property) {
            return $property->primary ? $property->name : null;
        }, static::model()->data_map));

        $pk = [];
        foreach($parts as $name)
            $pk[$name] = $this->$name;

        static::checkPk($pk);

        return $as_string ? implode(',', array_map(function($k, $v) {
            return "$k=$v";
        }, array_keys($pk), array_values($pk))) : $pk;
    }

    /**
     * Get cache key
     *
     * @return string
     *
     * @throws Broken
     */
    final public function getCacheKey() {
        return $this->getPk(true);
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

        return implode(',', array_map(function($k, $v) {
            return "$k=$v";
        }, array_keys($pk), array_values($pk)));
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
    public static function all($query = '', $placeholders = []) {
        if($query instanceof Query) {
            $class = static::class;
            if($query->class !== static::class)
                throw new Broken($query, "asking $class to fetch instances of $query->class");

        } else {
            $query = new Query(static::class, (string)$query, $placeholders);
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
        $entity = static::fromPk($data, false); // also ensures that primary key columns are included
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
            if(!array_key_exists($class, $this->relation_keys)) continue;

            // if there is a related class keys should be set

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
        /** @var Entity|string $other */
        $relation = static::model()->getRelationWith($other);
        if(!$relation)
            throw new Broken($this, "has no relation with $other");

        $cache = Cache::getRelation($this, $other);
        if($cache)
            return in_array($relation, [Relation::MANY_TO_ONE, Relation::MANY_TO_MANY]) ? $cache : reset($cache);

        if($relation === Relation::ONE_TO_MANY) {
            // this has one other, other has many this => this has the relation keys

            if(!array_key_exists($other, $this->relation_keys))
                return null;

            $other = $other::fromPk($this->relation_keys[$other]);
            Cache::setRelation($this, $other);

            return $other;
        }

        if($relation === Relation::MANY_TO_ONE) {
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

    /**
     * Set related entities
     *
     * @param Entity|Entity[] $other
     *
     * @throws BadType
     * @throws Broken
     * @throws NotFound
     * @throws ReflectionException
     */
    public function setRelated($other) {
        if(!is_array($other)) $other = [$other];

        $other_by_class = [];
        foreach($other as $o)
            $other_by_class[get_class($o)][] = $o;

        if(count($other_by_class) > 1) {
            foreach($other_by_class as $others)
                $this->setRelated($others);

            return;
        }

        $others = reset($other_by_class);

        /** @var Entity|string $other_class */
        $other_class = key($other_by_class);

        $relation = static::model()->getRelationWith($other_class);
        if(!$relation)
            throw new Broken($this, "has no relation with $other_class");

        if($relation === Relation::ONE_TO_MANY) {
            // this has one other, other has many this => this has the relation keys

            if(count($others) > 1)
                throw new Broken($this, "cannot be related to more than one $other_class");

            $other = array_shift($others);
            foreach($other_class::model()->primary_keys as $property) {
                $value = $other->{$property->name};
                $key = $property->getRelationProperty(static::class)->name;
                $this->relation_keys[$other_class][$key] = $value;
            }

            Cache::dropRelation($this, $other_class);
            Cache::setRelation($this, $other);

            return;
        }

        if($relation === Relation::MANY_TO_ONE) {
            // this has many others, other has one this => other has the relation keys

            foreach($others as $other)
                $other->setRelated($this);

            return;

        } else {
            // this has many others, other has many this => there is a relation table
            $exists = array_map(function(Entity $other) {
                return $other->getPk(true);
            }, $this->getRelated($other_class));

            // ignore already related others
            $others = array_filter($others, function(Entity $other) use($exists) {
                return !in_array($other->getPk(true), $exists);
            });

            $relation_table = static::model()->getRelationTableWith($other);

            $this_keys = [];
            $this_placeholders = [];

            foreach(static::model()->primary_keys as $property) {
                $value = $this->{$property->name};
                $property = $property->getRelationProperty($other_class);
                $this_keys[$property->column] = ":$property->name";
                $this_placeholders[":$property->name"] = $value;
            }

            foreach($others as $other) {
                $other_keys = [];
                $other_placeholders = [];

                foreach($other_class::model()->primary_keys as $property) {
                    $value = $other->{$property->name};
                    $property = $property->getRelationProperty(static::class);
                    $other_keys[$property->column] = ":$property->name";
                    $other_placeholders[":$property->name"] = $value;
                }

                $keys = array_merge($this_keys, $other_keys);
                $placeholders = array_merge($this_placeholders, $other_placeholders);

                $statement = DBI::prepare("INSERT INTO $relation_table (`".implode('`, `', array_keys($keys))."`) VALUES(".implode(', ', array_values($keys)).")");
                $statement->execute($placeholders);

                Cache::setRelation($this, $other);
            }
        }
    }

    /**
     * Drop relation
     *
     * @param Entity[]|Entity|string $other
     *
     * @throws BadType
     * @throws Broken
     * @throws NotFound
     * @throws ReflectionException
     */
    public function dropRelated($other) {
        if(!is_array($other)) $other = [$other];

        $other_by_class = [];
        foreach($other as $o) {
            if($o instanceof Entity) {
                $other_by_class[get_class($o)][] = $o;

            } else {
                $other_by_class[$o] = $o;
            }
        }

        if(count($other_by_class) > 1) {
            foreach($other_by_class as $others)
                $this->dropRelated($others);

            return;
        }

        $others = reset($other_by_class);

        /** @var Entity|string $other_class */
        $other_class = key($other_by_class);

        $relation = static::model()->getRelationWith($other_class);
        if(!$relation)
            throw new Broken($this, "has no relation with $other_class");

        if($relation === Relation::ONE_TO_MANY) {
            // this has one other, other has many this => this has the relation keys

            if(is_array($others) && count($others) > 1)
                throw new Broken($this, "cannot be related to more than one $other_class");

            unset($this->relation_keys[$other_class]);

            Cache::dropRelation($this, $other_class);

            return;
        }

        if($relation === Relation::MANY_TO_ONE) {
            // this has many others, other has one this => other has the relation keys

            if(!is_array($others))
                $others = $other_class::all();

            foreach($others as $other)
                    $other->dropRelated($this);

            return;

        } else {
            // this has many others, other has many this => there is a relation table

            $relation_table = static::model()->getRelationTableWith($other);

            $this_where = [];
            $this_placeholders = [];

            foreach(static::model()->primary_keys as $property) {
                $value = $this->{$property->name};
                $property = $property->getRelationProperty($other_class);
                $this_where[] = "`$property->column` = :$property->name";
                $this_placeholders[":$property->name"] = $value;
            }

            if(!is_array($others)) {
                $statement = DBI::prepare("DELETE FROM $relation_table WHERE ".implode(' AND ', $this_where));
                $statement->execute($this_placeholders);

                Cache::dropRelation($this, $other_class);

                return;
            }

            foreach($others as $other) {
                $other_where = [];
                $other_placeholders = [];

                foreach($other_class::model()->primary_keys as $property) {
                    $value = $other->{$property->name};
                    $property = $property->getRelationProperty(static::class);
                    $other_where[] = "`$property->column` = :$property->name";
                    $other_placeholders[":$property->name"] = $value;
                }

                $where = array_merge($this_where, $other_where);
                $placeholders = array_merge($this_placeholders, $other_placeholders);

                $statement = DBI::prepare("DELETE FROM $relation_table WHERE ".implode(' AND ', $where));
                $statement->execute($placeholders);

                Cache::dropRelation($this, $other);
            }
        }
    }

    /**
     * Getter
     *
     * @param string $name
     *
     * @return Entity|Entity[]
     *
     * @throws BadType
     * @throws Broken
     * @throws NotFound
     * @throws ReflectionException
     * @throws UnknownProperty
     */
    public function __get($name) {
        if(preg_match('`^[A-Z]`', $name))
            return $this->getRelated($name);

        throw new UnknownProperty($this, $name);
    }

    /**
     * Setter
     *
     * @param string $name
     * @param mixed $value
     *
     * @throws BadType
     * @throws Broken
     * @throws NotFound
     * @throws ReflectionException
     * @throws UnknownProperty
     */
    public function __set($name, $value) {
        if(preg_match('`^[A-Z]`', $name)) {
            if($value) {
                if(!is_array($value)) $value = [$value];

                foreach($value as $other)
                    if(!($other instanceof $name))
                        throw new Broken($this, "$other is not an instance of $name");

                $this->setRelated($value);

            } else {
                $this->dropRelated($name);
            }

            return;
        }

        throw new UnknownProperty($this, $name);
    }

    /**
     * Stringifier
     *
     * @return string
     *
     * @throws Broken
     */
    public function __toString() {
        return get_class($this).'#'.$this->getPk(true);
    }
}