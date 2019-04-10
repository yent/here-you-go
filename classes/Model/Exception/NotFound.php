<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Model\Exception;

use HereYouGo\Exception\Detailed;
use HereYouGo\UI\Locale;

class NotFound extends Detailed {
    public function __construct($class, $selector) {
        $message = "{$class}_not_found";
        if(!Locale::isTranslatable($message))
            $message = 'entity_not_found';

        parent::__construct($message, ['class' => $class, 'selector' => $selector]);
    }
}