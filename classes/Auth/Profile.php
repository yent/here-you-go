<?php


namespace HereYouGo\Auth;

use HereYouGo\Model\Entity\User;
use ReflectionException;

/**
 * Class Profile
 *
 * @package HereYouGo\Auth
 */
abstract class Profile {
    /** @var Profile[] */
    protected static $dependencies = null;

    /**
     * Get profile dependencies
     *
     * @return Profile[]
     *
     * @throws ReflectionException
     */
    final protected static function getDependencies() {
        if(is_null(static::$dependencies)) {
            static::$dependencies = [];

            foreach(explode("\n", (new \ReflectionClass(static::class))->getDocComment()) as $line)
                if(preg_match('`@depends\s+([^\s]+)`', $line, $depends))
                    static::$dependencies[] = $depends[1];
        }

        return static::$dependencies;
    }

    /**
     * Check if user satisfies profile
     *
     * @param User $user
     *
     * @return bool
     *
     * @throws ReflectionException
     */
    public static function isSatisfiedBy(User $user) {
        foreach(static::getDependencies() as $dependency)
            if(!$dependency::isSatisfiedBy($user))
                return false;

        return static::check($user);
    }

    /**
     * Check user against profile
     *
     * @param User $user
     *
     * @return bool
     */
    abstract protected static function check(User $user): bool;
}