<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\Exception;

/**
 * Class BadEmail
 *
 * @package HereYouGo\Exception
 */
class BadEmail extends Detailed {
    /**
     * BadEmail constructor.
     *
     * @param string $bad
     */
    public function __construct($bad) {
        parent::__construct('bad_email_address', ['bad' => $bad]);
    }
}