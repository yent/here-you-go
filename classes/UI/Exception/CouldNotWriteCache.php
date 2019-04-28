<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\UI\Exception;

use HereYouGo\Exception\Detailed;
use HereYouGo\UI\Cache;

/**
 * Class CouldNotWriteCache
 *
 * @package HereYouGo\UI\Exception
 */
class CouldNotWriteCache extends Detailed {
    /**
     * CouldNotWriteCache constructor.
     *
     * @param Cache $cache
     * @param string $id
     */
    public function __construct(Cache $cache, $id) {
        parent::__construct('could_not_write_cache', ['base' => $cache->base, 'id' => $id]);
    }
}