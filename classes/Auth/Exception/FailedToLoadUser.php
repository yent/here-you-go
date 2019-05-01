<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Auth\Exception;


use HereYouGo\Exception\Detailed;

/**
 * Class FailedToLoadUser
 *
 * @package HereYouGo\Auth\Exception
 */
class FailedToLoadUser extends Detailed {
    /**
     * FailedToLoadUser constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes) {
        parent::__construct('failed_to_load_user', $attributes);
    }
}