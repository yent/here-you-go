<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Model;

use HereyouGo\Event;
use HereYouGo\Model\Constant\Relation;
use HereYouGo\Model\Exception\Broken;
use ReflectionException;
use ReflectionClass;

abstract class Entity {
    /** @var array|null */
    protected static $entity_analysis = null;
    
    /** @var array|null */
    protected static $entity_relations = null;
    
    /** @var array|null */
    protected static $entity_data_map = null;
    
    /**
     * Analyse class
     *
     * @return array
     *
     * @throws Broken
     */
    final protected static function getAnalysis() {
        if(is_null(static::$entity_analysis)) {
            static::$entity_analysis = ['class' => ['has' => [], 'table' => static::class.'s'], 'properties' => []];
            
            try {
                $reflexion = new ReflectionClass(static::class);
    
                foreach(explode("\n", $reflexion->getDocComment()) as $line) {
                    if(!preg_match('`^\s+(?:\*\s+)?@([^\s]+)\s+(.+)$`', $line, $match)) continue;
                    static::$entity_analysis['class'][$match[1]][] = $match[2];
                }
                
                $defaults = $reflexion->getDefaultProperties();
                foreach($reflexion->getProperties() as $property) {
                    $name = $property->getName();
                    
                    $dfn = preg_grep('`@var\s`', explode("\n", $property->getDocComment()));
                    $dfn = preg_replace('`^.*@var\s+(.+)(?:\s+\*+/|$)$`', '$1', reset($dfn));
    
                    $default = array_key_exists($name, $defaults) ? $defaults[$name] : null;
                    static::$entity_analysis['properties'][$name] = new Property(static::class, $name, $dfn, $default);
                }
                
            } catch (ReflectionException $e) {
                throw new Broken(static::class, 'could not analyse class', $e);
            }
        }
        
        return static::$entity_analysis;
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
    public static function getDataMap($with_relations = true) {
        if(is_null(static::$entity_data_map)) {
            static::$entity_data_map = [];
            
            $analysis = static::getAnalysis();

            /** @var Property[] $additional_data */
            $properties = (new Event('datamap', static::class))->trigger(function() {
                return [];
            });

            if(!is_array($properties) ||array_filter($properties, function($data) {
                return !($data instanceof Property);
            })) throw new Broken(static::class, 'event returned properties is not an array of Property');

            static::$entity_data_map = array_merge($properties, $analysis['properties']);

            if($with_relations) {
                foreach(static::getRelations() as $other => $relation) {
                    try {
                        if(!Relation::isValue($relation))
                            throw new Broken(static::class, 'unknown relation type with ' . $other);
                        
                    } catch(ReflectionException $e) {
                        throw new Broken(static::class, 'failed to check relation');
                    }
                    
                    if($relation === Relation::MANY) continue;
                    
                    /** @var self $other */
                    foreach($other::getDataMap(false) as $property => $definition) {
                        if(!$definition->primary) continue;
                        
                        $definition = $definition->getRelationProperty();
                        if(array_key_exists($definition->name, static::$entity_data_map))
                            throw new Broken(static::class, "relation key name {$definition->name} is already reserved for another property");
                        
                        static::$entity_data_map[$definition->name] = $definition;
                    }
                }
            }

            $auto_inc = 0;
            $indexes = [];
            foreach(static::$entity_data_map as $definition) {
                foreach($definition->indexes as $index => $unique) {
                    if(array_key_exists($index, $indexes)) {
                        if($unique !== $indexes[$index])
                            throw new Broken(static::class, 'index cannot be unique and not unique at the same time');

                    } else {
                        $indexes[$index] = $unique;
                    }
                }

                if($definition->auto_increment)
                    $auto_inc++;
            }

            if($auto_inc > 1) throw new Broken(static::class, 'cannot have more than one auto increment column');
        }
        
        return static::$entity_data_map;
    }

    /**
     * Get relations with other entities
     *
     * @return string[]
     *
     * @throws Broken
     */
    public static function getRelations() {
        if(is_null(static::$entity_relations)) {
            static::$entity_relations = [];
            
            foreach(static::getAnalysis()['class']['has'] as $has) {
                if(!preg_match('`^(one|many)\s+(.+)$`', $has, $match))
                    throw new Broken(static::class, 'malformed @has');
                
                if(!class_exists($match[2]))
                    throw new Broken(static::class, "related class {$match[2]} does not exist");
                
                static::$entity_relations[$match[2]] = $match[1];
            }
        }
        
        return static::$entity_relations;
    }

    /**
     * Get table name
     *
     * @return string
     *
     * @throws Broken
     */
    public static function getTable() {
        return static::getAnalysis()['class']['table'];
    }

    /**
     * Get cache key
     *
     * @return string
     */
    abstract public function getCacheKey(): string;
}