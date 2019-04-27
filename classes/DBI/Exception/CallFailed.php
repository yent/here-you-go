<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\DBI\Exception;


use HereYouGo\DBI;
use HereYouGo\DBI\Exception;

class CallFailed extends Exception {
    public function __construct($method, array $arguments, $target, \Exception $previous) {
        parent::__construct('dbi_call_failed', ['method' => $method, 'arguments' => $arguments, 'error' => $previous->getMessage()], $target);
    }
}