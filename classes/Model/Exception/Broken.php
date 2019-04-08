<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Model\Exception;

use HereYouGo\Exception\Detailed;

class Broken extends Detailed {
    public function __construct($what, $reason, \Exception $previous = null) {
        if(is_object($what) && method_exists($what, '__toString'))
            $what = (string)$what;

        parent::__construct('broken_model', ['what' => $what, 'reason' => $reason], [], 500, $previous);
    }
}