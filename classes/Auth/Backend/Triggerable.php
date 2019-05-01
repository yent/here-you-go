<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Auth\Backend;


use HereYouGo\Auth\Backend;

abstract class Triggerable extends Backend {
    /**
     * Trigger login process (if any)
     */
    abstract public function logIn();

    /**
     * Trigger logout process (if any)
     */
    abstract public function logOut();
}