<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo;

use HereYouGo\Autoloader\Exception\ClassNotFound;

class Autoloader {
    /** @var string[] */
    private static $known = [];
    
    /**
     * @param string $class
     * @param bool $critical
     *
     * @return bool
     *
     * @throws ClassNotFound
     */
    public static function find($class, $critical = true) {
        if(array_key_exists($class, self::$known)) {
            if(!self::$known[$class] && $critical)
                throw new ClassNotFound($class);
            
            return self::$known[$class];
        }
        
        $class_path = explode('\\', $class);
        if($class_path[0] !== 'HereYouGo')
            return false;
    
        $file = HYG_ROOT.'classes/'.implode('/', array_slice($class_path, 1)).'.php';
        if(file_exists($file)) {
            require_once $file;
        
            if(class_exists($class, false)) {
                self::$known[$class] = true;
                return true;
            }
        }
    
        if($critical)
            throw new ClassNotFound($class);
    
        self::$known[$class] = false;
        return false;
    }
    
    /**
     * Check ifclass exists
     *
     * @param string $class
     * @return bool
     */
    public static function exists($class) {
        try {
            return self::find($class, false);
        } catch(ClassNotFound $e) {
            return false;
        }
    }
}

spl_autoload_register(Autoloader::class.'::find');