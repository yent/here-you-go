<?php


namespace HereYouGo\Auth;

/**
 * Class LocalUser
 *
 * @package HereYouGo\Backend
 */
class LocalUser extends Backend {
    /** @var string[]|null */
    private static $attributes = null;

    /**
     * Check if any user logged-in
     *
     * @return bool
     */
    public static function hasUser(): bool {
        return !is_null(self::$attributes);
    }

    /**
     * Get current user attributes
     *
     * @return array
     */
    public static function getAttributes(): array {
        return self::hasUser() ? self::$attributes : [];
    }

    /**
     * Set current local user attributes
     *
     * @param array|null $attributes
     */
    public static function setAttributes($attributes) {
        if(!is_array($attributes) && !is_null($attributes))
            return;

        self::$attributes = $attributes;
    }
}