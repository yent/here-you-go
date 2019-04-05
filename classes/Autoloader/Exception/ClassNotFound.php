<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Autoloader\Exception;

use HereYouGo\Exception\Detailed;

/**
 * Class not found exception
 *
 * @package HereYouGo
 */
class ClassNotFound extends Detailed {
    /**
     * ClassNotFound constructor.
     *
     * @param string $class
     */
    public function __construct($class) {
        parent::__construct('class_not_found', ['class' => $class]);
    }
}