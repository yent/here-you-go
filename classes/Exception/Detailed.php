<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Exception;

class Detailed extends Base {
    /** @var array */
    private $details = [];

    /**
     * Constructor
     *
     * @param string $message
     * @param array $private
     * @param array $public
     * @param int $code
     * @param Base|null $previous
     */
    public function __construct($message, array $private = [], array $public = [], $code = 500, Base $previous = null) {
        $this->details = $public;

        parent::__construct($message, $code, $previous);

        $this->log($public + $private);
    }

    /**
     * get public details
     *
     * @return array
     */
    public function getDetails() {
        return $this->details;
    }
}