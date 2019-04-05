<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

use HereYouGo\Autoloader\Exception\ClassNotFound;

spl_autoload_register(function($class) {
    $class_path = explode('\\', $class);
    if($class_path[0] !== 'HereYouGo')
        return;

    $file = HYG_ROOT.'/classes/'.implode('/', array_slice($class_path, 1)).'.php';
    if(file_exists($file)) {
        require_once $file;

        if(!class_exists($class, false))
            throw new ClassNotFound($class);
    }
});