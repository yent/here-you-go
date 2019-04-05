<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Exception;


class UnknownProperty extends Detailed {
    public function __construct($object, $name) {
        parent::__construct('unknown_property', ['object' => (string)$object, 'name' => $name]);
    }
}