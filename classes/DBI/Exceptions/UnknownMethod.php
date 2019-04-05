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

class UnknownMethod extends Exception {
    public function __construct($method, $target) {
        parent::__construct('unknown_dbi_method', ['method' => $method], $target);
    }
}