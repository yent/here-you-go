<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Converter\JSON\Exception;

use HereYouGo\Exception\Detailed;

class UnableToEncode extends Detailed {
    public function __construct($error) {
        parent::__construct('unable_to_encode_to_json', ['error' => $error]);
    }
}