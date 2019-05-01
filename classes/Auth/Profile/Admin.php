<?php


namespace HereYouGo\Auth\Profile;


use HereYouGo\Auth\Profile;
use HereYouGo\Config;
use HereYouGo\Model\Entity\User;
use HereYouGo\Model\Exception\Broken;

/**
 * Class Admin
 *
 * @package HereYouGo\Backend\Profile
 *
 * @depends Authenticated
 */
class Admin extends Profile {
    /**
     * Check user against profile
     *
     * @param User $user
     *
     * @return bool
     *
     * @throws Broken
     */
    protected static function check(User $user): bool {
        $admins = (array)Config::get('admin');

        return in_array($user->getPk(true), $admins);
    }
}