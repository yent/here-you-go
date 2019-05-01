<?php


namespace HereYouGo\Auth;


use HereYouGo\Auth\Exception\PasswordHashingFailed;

/**
 * Class Password
 *
 * @package HereYouGo\Backend
 */
class Password {
    /**
     * Hash password
     *
     * @param string $password
     *
     * @return string
     *
     * @throws PasswordHashingFailed
     */
    public static function hash($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        if(!$hash)
            throw new PasswordHashingFailed();

        return $hash;
    }

    /**
     * Verify password
     *
     * @param string $password
     * @param string $hash
     *
     * @return bool
     */
    public static function verify($password, $hash) {
        return password_verify($password, $hash);
    }
}