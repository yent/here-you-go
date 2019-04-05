<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\REST\Exception;


use HereYouGo\Exception\Base;
use HereYouGo\REST\Exception;

class BadMethod extends Exception {
    public function __construct(string $method) {
        parent::__construct('bad_rest_method', [], ['method' => $method], 400);
    }
}