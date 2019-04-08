<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Model;


use HereYouGo\Logger;
use HereYouGo\Model\Exception\Broken;

class Updater {
    /**
     * Run updater
     *
     * @throws Broken
     */
    public static function run() {
        Logger::info('starting model updater');

        foreach(self::entities() as $entity)
            self::check($entity);
    }

    /**
     * List known entities
     *
     * @return array
     */
    private static function entities() {
        $dir = dirname(__FILE__).'/Entity/';
        $entities = [];
        foreach(scandir($dir) as $item) {
            if(substr($item, 0, 1) === '.') continue;
            if(!is_file($dir.$item)) continue;
            if(!preg_match('`^([A-Z][A-Za-z0-9]+)\.php$`', $item, $match)) continue;

            $class = '\\HereYouGo\\Model\\Entity\\'.$match[1];
            if(!class_exists($class)) continue;
            if(!is_subclass_of($class, Entity::class)) continue;

            $entities[] = $class;
        }

        return $entities;
    }

    /**
     * Get and check data map
     *
     * @param string $class
     *
     * @return array
     *
     * @throws Broken
     */
    private static function checkMap($class) {
        /** @var Entity $class */
        $map = $class::dataMap();

        if(
            !array_key_exists('fields', $map) ||
            !is_array($map['fields']) ||
            !count($map['fields'])
        ) throw new Broken($class, 'has no fields');

        if(
            !array_key_exists('primary', $map) ||
            !is_string($map['primary']) ||
            !$map['primary'] ||
            !array_key_exists($map['primary'], $map['fields'])
        ) throw new Broken($class, 'misses primary key or defined primary key refers to unknown field');

        return $map;
    }

    /**
     * Check database
     *
     * @param string $class
     *
     * @throws Broken
     */
    private static function check($class) {
        Logger::info("checking $class");

        /** @var Entity $class */
        $map = self::checkMap($class);

        foreach($class::relations() as $other => $relation) {
            if($relation !== Entity::HAS_ONE) continue;

            /** @var Entity $other */
            $other_map = self::checkMap($other);

            $pk = $other_map['fields'][$other_map['primary']];

            $map['fields'][$other.'_'.$other_map['primary']] = $pk;
        }

        if(self::tableExists($class::table())) {
            self::checktable($map);

        } else {
            self::createTable($map);
        }
    }
}