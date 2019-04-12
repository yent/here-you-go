<?php


namespace HereYouGo;


use HereYouGo\Auth\LocalUser;
use HereYouGo\Model\Entity\User;
use ReflectionException;

/**
 * Class Auth
 *
 * @package HereYouGo
 */
abstract class Auth {
    /** @var static|null */
    private static $backend = null;

    /** @var User|null */
    private static $user = null;

    /**
     * Get current user
     *
     * @return User|false
     *
     * @throws Exception\BadType
     * @throws Model\Exception\Broken
     * @throws Model\Exception\NotFound
     * @throws ReflectionException
     */
    public static function getUser() {
        if(is_null(self::$user)) {
            self::$user = false;

            /** @var Auth[] $backends */
            $backends = ['LocalUser', 'Remote'];

            $sp_type = Config::get('auth.sp.type');
            if($sp_type) {
                $class = '\\HereYouGo\\Auth\\SP\\' . ucfirst($sp_type);

                if(Autoloader::exists($class)) {
                    $backends[] = $class;

                } else {
                    // TODO throw
                }
            }

            $attributes = [];
            foreach($backends as $backend) {
                if(!$backend::hasUser()) continue;

                $attributes = $backend::getAttributes();
                break;
            }

            if($attributes) {
                foreach(Config::get('auth.attributes') as $attribute)
                    if(!array_key_exists($attribute, $attributes))
                        throw new MissingAttribute($attribute); // TODO

                self::$user = User::fromAuthAttributes($attributes);
            }
        }

        return self::$user;
    }

    /**
     * Check if any user logged-in
     *
     * @return bool
     */
    abstract public static function hasUser(): bool;

    /**
     * Get current user attributes
     *
     * @return array
     */
    abstract public static function getAttributes(): array;

    /**
     * Trigger login process (if any)
     *
     * @param string $target
     */
    public static function doLogin($target) {}

    /**
     * Trigger logout process (if any)
     *
     * @param string $target
     */
    public static function doLogout($target) {}
}