<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\Exception;


class BadType extends Detailed {
    public function __construct($what, $expected) {
        if(is_object($what) && method_exists($what, '__toString'))
            $what = (string)$what;
        
        parent::__construct('bad_type', ['what' => $what, 'expected' => $expected]);
    }
}