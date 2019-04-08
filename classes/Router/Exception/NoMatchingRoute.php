<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Router\Exception;

use HereYouGo\Exception\Detailed;

class NoMatchingRoute extends Detailed {
    public function __construct($method, $path) {
        parent::__construct('no_matching_route', [], ['method' => $method, 'path' => $path], 404);
    }
}