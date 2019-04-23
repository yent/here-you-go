<?php


namespace HereYouGo\Auth\Profile;


use HereYouGo\Auth;
use HereYouGo\Auth\Profile;
use HereYouGo\Exception\BadType;
use HereYouGo\Model\Entity\User;
use HereYouGo\Model\Exception\Broken;
use HereYouGo\Model\Exception\NotFound;
use ReflectionException;

/**
 * Class Authenticated
 *
 * @package HereYouGo\Auth\Profile
 */
class Authenticated extends Profile {
    /**
     * Check user against profile
     *
     * @param User $user
     *
     * @return bool
     *
     * @throws Auth\MissingAttribute
     * @throws Auth\UnknownSP
     * @throws Broken
     * @throws BadType
     * @throws NotFound
     * @throws ReflectionException
     */
    protected static function check(User $user): bool {
        $authenticated = Auth::getUser();

        return $authenticated && $authenticated->is($user);
    }
}