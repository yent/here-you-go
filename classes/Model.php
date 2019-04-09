<?php


namespace HereYouGo;


use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Model\Constant\Relation;
use HereYouGo\Model\Entity;
use HereYouGo\Model\Exception\Broken;
use HereYouGo\Model\Property;
use ReflectionClass;
use ReflectionException;

/**
 * Class Model
 *
 * @package HereYouGo
 *
 * @property string $class
 * @property string[] $has
 * @property string[] $relations
 * @property string $table
 * @property Property[] $data_map
 * @property Property[] $columns
 */
class Model {
    /** @var string */
    private $class = '';

    /** @var array */
    private $has = [];

    /** @var Property[][] */
    private $properties = ['own' => [], 'extension' => null, 'relation' => null];

    /** @var string */
    private $table = '';

    /**
     * Model constructor.
     *
     * @param string $class
     *
     * @throws Broken
     */
    public function __construct($class) {
        if(!is_subclass_of($class, 'Entity', true))
            throw new Broken($class, 'not a child class of Entity');

        $this->class = $class;
        $this->table = $this->class.'s';

        try {
            $reflexion = new ReflectionClass($class);

            foreach(explode("\n", $reflexion->getDocComment()) as $line) {
                if(!preg_match('`^\s+(?:\*\s+)?@([^\s]+)\s+(.+)$`', $line, $match)) continue;

                if($match[1] === 'has') {
                    if(!preg_match('`^(one|many)\s+(.+)$`', $match[2], $has))
                        throw new Broken($class, 'malformed @has');

                    if(!class_exists($has[2]))
                        throw new Broken($class, "related class {$has[2]} does not exist");

                    $this->has[$has[2]] = $has[1];

                } else if($match[1] === 'table') {
                    $this->table = $match[2];
                }
            }

            $defaults = $reflexion->getDefaultProperties();
            foreach($reflexion->getProperties() as $property) {
                $name = $property->getName();

                $dfn = preg_grep('`@var\s`', explode("\n", $property->getDocComment()));
                $dfn = preg_replace('`^.*@var\s+(.+)(?:\s+\*+/|$)$`', '$1', reset($dfn));

                $default = array_key_exists($name, $defaults) ? $defaults[$name] : null;
                $this->properties['own'][$name] = new Property($class, $name, $dfn, $default);
            }

        } catch (ReflectionException $e) {
            throw new Broken($class, 'could not analyse class', $e);
        }
    }

    /**
     * Get data map
     *
     * @param bool $with_relations
     *
     * @return Property[]
     *
     * @throws Broken
     */
    public function getDataMap($with_relations = true) {
        $check = false;
        $property_names = array_keys($this->properties['own']);

        if($with_relations && is_null($this->properties['relation'])) {
            foreach($this->getRelations() as $other => $relation) {
                try {
                    if(!Relation::isValue($relation))
                        throw new Broken($this->class, "unknown relation type with $other");

                } catch(ReflectionException $e) {
                    throw new Broken($this->class, 'failed to check relation');
                }

                if($relation === Relation::MANY) continue;

                /** @var Entity $other */
                foreach($other::model()->getDataMap(false) as $property) {
                    if(!$property->primary) continue;

                    $property = $property->getRelationProperty();
                    if(in_array($property->name, $property_names))
                        throw new Broken($this->class, "relation key name {$property->name} is already reserved for another property");

                    $this->properties['relation'][$property->name] = $property;
                    $property_names[] = $property->name;

                    $check = true;
                }
            }
        }

        if(is_null($this->properties['extension'])) {
            $properties = (new Event('datamap', $this->class))->trigger(function() {
                return [];
            });

            if(!is_array($properties) ||array_filter($properties, function($data) {
                    return !($data instanceof Property);
                })) throw new Broken($this->class, 'event returned properties is not an array of Property');

            /** @var Property[] $properties */
            foreach($properties as $property) {
                if(in_array($property->name, $property_names))
                    throw new Broken($this->class, "relation key name {$property->name} is already reserved for another property");

                $this->properties['extension'][$property->name] = $property;
                $property_names[] = $property->name;

                $check = true;
            }
        }

        if($check) {
            $auto_inc = 0;
            $indexes = [];
            foreach(['own', 'extension', 'relation'] as $type) {
                foreach($this->properties[$type] as $property) {
                    foreach($property->indexes as $index => $unique) {
                        if(array_key_exists($index, $indexes)) {
                            if($unique !== $indexes[$index])
                                throw new Broken($this->class, 'index cannot be unique and not unique at the same time');

                        } else {
                            $indexes[$index] = $unique;
                        }
                    }

                    if($property->auto_increment)
                        $auto_inc++;
                }
            }

            if($auto_inc > 1)
                throw new Broken($this->class, 'cannot have more than one auto increment column');
        }

        $properties = array_merge($this->properties['own'], $this->properties['extension']);
        if($with_relations)
            $properties = array_merge($properties, $this->properties['relation']);

        return $properties;
    }

    /**
     * Get relations with other entities
     *
     * @return string[]
     */
    public function getRelations() {
        return $this->has;
    }

    /**
     * Get table name
     *
     * @return string
     */
    public function getTable() {
        return $this->table;
    }

    /**
     * Get properties by database column name
     *
     * @return Property[]
     *
     * @throws Broken
     */
    public function getColumns() {
        $columns = [];
        foreach($this->getDataMap(true) as $property)
            $columns[$property->column] = $property;

        return $columns;
    }

    /**
     * Getter
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws Broken
     * @throws UnknownProperty
     */
    public function __get($name) {
        if(in_array($name, ['class', 'has', 'table']))
            return $this->$name;

        if($name === 'data_map') return $this->getDataMap(true);

        if($name === 'columns') return $this->getColumns();

        if($name === 'relations') return $this->has;

        throw new UnknownProperty($this, $name);
    }
}