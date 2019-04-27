<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Model;


use HereYouGo\Config;
use HereYouGo\DBI;
use HereYouGo\Logger;
use HereYouGo\Model\Constant\Relation;
use HereYouGo\Model\Exception\Broken;

/**
 * Class Updater
 *
 * @package HereYouGo\Model
 */
abstract class Updater {
    /** @var self|null */
    private static $backend = null;
    
    /**
     * Run updater
     *
     * @throws Broken
     */
    public static function run() {
        Logger::info('starting model updater');
        
        $type = explode(':', DBI::getDsn(Config::get('db.*')))[0];
        self::$backend = __NAMESPACE__.'\\Backend\\'.ucfirst($type);
        
        if(!class_exists(self::$backend))
            throw new Broken(self::$backend, 'unknown backend type');

        $many_to_many = [];

        foreach(self::entities() as $class) {
            self::check($class);

            /** @var Entity $class */
            foreach($class::model()->relations as $other => $relation) {
                /** @var Entity $other */
                if($relation !== Relation::MANY) continue;
                if($other::model()->relations[$class] !== Relation::MANY) continue;

                $id = [$class, $other];
                sort($id);

                $many_to_many[implode('', $id)] = $id;
            }
        }

        foreach($many_to_many as $classes)
            self::checkrelationTable($classes);
    }

    /**
     * List known entities
     *
     * @return string[]
     */
    private static function entities() {
        $dir = dirname(__FILE__).'/Entity/';
        $entities = [];
        foreach(scandir($dir) as $item) {
            if($item{0} === '.') continue;
            if(!is_file($dir.$item)) continue;
            if(!preg_match('`^([A-Z][A-Za-z0-9]+)\.php$`', $item, $match)) continue;

            $class = 'HereYouGo\\Model\\Entity\\'.$match[1];
            if(!class_exists($class)) continue;
            if(!is_subclass_of($class, Entity::class)) continue;

            $entities[] = $class;
        }

        return $entities;
    }
    
    /**
     * Check database
     *
     * @param string $class
     *
     * @throws Broken
     */
    private static function check($class) {
        Logger::info("checking $class class");
    
        /** @var Entity $class */
        $table = $class::model()->table;
        $map = $class::model()->data_map;

        self::checkTable($table, $map);
    }

    /**
     * check relation table
     *
     * @param Entity[] $classes
     *
     * @throws Broken
     */
    private static function checkRelationTable(array $classes) {
        $classes = array_values($classes);

        Logger::info("checking relation table for $classes[0] and $classes[1]");

        $table = $classes[0]::model()->getRelationTableWith($classes[1]);

        $map = [];
        foreach($classes[0]::model()->primary_keys as $property) {
            $property = $property->getRelationProperty($classes[1]);
            $map[$property->name] = $property;
        }
        foreach($classes[1]::model()->primary_keys as $property) {
            $property = $property->getRelationProperty($classes[0]);
            $map[$property->name] = $property;
        }

        self::checkTable($table, $map);
    }

    /**
     * Check table against map
     *
     * @param string $table
     * @param Property[] $map
     */
    private static function checkTable($table, $map) {
        if(self::$backend::tableExists($table)) {
            foreach($map as $property => $definition) {
                Logger::info("checking {$definition->column} column in $table table");
                self::$backend::checkColumn($table, $definition);
            }

            Logger::info("checking constraints in $table table");
            self::$backend::checkConstraints($table, $map);

        } else {
            Logger::info("$table table is missing, creating it");
            self::$backend::createTable($table, $map);
        }
    }
    
    /**
     * Check wether table exists
     *
     * @param string $table
     *
     * @return bool
     */
    abstract protected static function tableExists($table): bool;
    
    /**
     * Check if column matches definition and updates it if it doesn't
     *
     * @param string $table
     * @param Property $definition
     */
    abstract protected static function checkColumn($table, Property $definition);

    /**
     * Check table constraints
     *
     * @param string $table
     * @param Property[] $map
     */
    abstract protected static function checkConstraints($table, array $map);
    
    /**
     * Create table
     *
     * @param string $table
     * @param Property[] $map
     */
    abstract protected static function createTable($table, array $map);
}