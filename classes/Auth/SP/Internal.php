<?php

namespace HereYouGo\Auth\SP;

use HereYouGo\Auth;

/**
 * Class Internal
 *
 * @package HereYouGo\Auth\SP
 */
class Internal extends Auth {
    /**
     * Check if any user logged-in
     *
     * @return bool
     */
    public static function hasUser(): bool {
        return array_key_exists('user', $_SESSION);
    }

    /**
     * Get current user attributes
     *
     * @return array
     */
    public static function getAttributes(): array {
        return $_SESSION['user'];
    }

    /**
     * Trigger login process (if any)
     *
     * @param string $target
     */
    public static function doLogin($target) {
        // TODO redirect to /login?target=base64_encode($target)
    }

    /**
     * Trigger logout process (if any)
     *
     * @param string $target
     */
    public static function doLogout($target) {
        unset($_SESSION);

        // TODO redirect to $target
    }
}