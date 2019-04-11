<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\REST;

/**
 * Class Endpoint
 *
 * @package HereYouGo\REST
 */
abstract class Endpoint {
    /**
     * Initialize endpoint (register routes ...)
     */
    abstract public static function init();

    /**
     * Load all endpoints
     */
    final public static function loadAll() {
        $dir = dirname(__FILE__).'/Endpoint/';
        foreach(scandir($dir) as $item) {
            if(substr($item, 0, 1) === '.') continue;
            if(!is_file($dir.$item)) continue;
            if(!preg_match('`^([A-Z][A-Za-z]+)\.php$`', $item, $match)) continue;

            $class = '\\HereYouGo\\REST\\Endpoint\\'.$match[1];
            if(!class_exists($class)) continue;

            /** @var Endpoint $class */
            $class::init();
        }
    }
}