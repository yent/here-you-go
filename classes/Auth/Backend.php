<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Auth;

use HereYouGo\Auth\SP\NewUserPolicy;
use HereYouGo\UI\Page;

/**
 * Interface Backend
 *
 * @package HereYouGo\Backend
 */
abstract class Backend {
    /**
     * Backend constructor.
     *
     * @param array $config
     */
    abstract function __construct(array $config = []);

    /**
     * Tells if new users returned should be automatically created
     *
     * @return string
     */
    public function newUsers(): string {
        return NewUserPolicy::REJECT;
    }

    /**
     * Check if any user logged-in
     *
     * @return bool
     */
    abstract public function hasIdentity(): bool;

    /**
     * Get current user attributes
     *
     * @return array
     */
    abstract public function getAttributes(): array;

    /**
     * Trigger registration process (if any)
     *
     * @return void|string|Page
     */
    public function register() {}
}