<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\DBI;

use HereYouGo\DBI;
use HereYouGo\Exception\Detailed;

class Exception extends Detailed {
    public function __construct($message, array $details, $target) {
        if($target instanceof Statement)
            $details['target'] = 'statement';

        if($target instanceof DBI)
            $details['target'] = 'dbi#'.$target->getId();

        parent::__construct($message, $details);
    }
}