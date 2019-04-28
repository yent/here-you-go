<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\UI\Exception;

use HereYouGo\Exception\Detailed;

/**
 * Class CouldNotCreateCache
 *
 * @package HereYouGo\UI\Exception
 */
class CouldNotCreateCache extends Detailed {
    /**
     * CouldNotCreateCache constructor.
     *
     * @param string $path
     */
    public function __construct($path) {
        parent::__construct('could_not_create_cache_dir', ['path' => $path]);
    }
}