<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Router\Exception;

use HereYouGo\Exception\Detailed;

class MissingArgument extends Detailed {
    public function __construct($method, $path, $arg) {
        parent::__construct('missing_route_argument', [], ['method' => $method, 'path' => $path, 'arg' => $arg], 400);
    }
}