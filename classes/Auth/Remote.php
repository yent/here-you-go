<?php


namespace HereYouGo\Auth;


use HereYouGo\Auth;

/**
 * Class Remote
 *
 * @package HereYouGo\Auth
 */
class Remote extends Auth {
    /**
     * Check if any user logged-in
     *
     * @return bool
     */
    public static function hasUser(): bool {
        // TODO: Implement hasUser() method.
        return false;
    }

    /**
     * Get current user attributes
     *
     * @return array
     */
    public static function getAttributes(): array {
        // TODO: Implement getAttributes() method.
        return [];
    }
}