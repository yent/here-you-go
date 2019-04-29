<?php


namespace HereYouGo;


use HereYouGo\Auth\Exception\UnknownSP;
use HereYouGo\Auth\LocalUser;
use HereYouGo\Auth\Remote;
use HereYouGo\Model\Entity\User;
use HereYouGo\UI\Page;
use ReflectionException;
use HereYouGo\Auth\MissingAttribute;

/**
 * Class Auth
 *
 * @package HereYouGo
 */
abstract class Auth {
    /** @var User|null */
    private static $user = null;

    /** @var self|string|null */
    private static $sp = null;

    /**
     * Get configured SP if any
     *
     * @return Auth|string|null
     *
     * @throws UnknownSP
     */
    public static function getSP() {
        if(is_null(self::$sp)) {
            self::$sp = '';

            $type = Config::get('auth.sp.type');
            if($type) {
                $class = 'HereYouGo\\Auth\\SP\\'.ucfirst($type);

                if(!Autoloader::exists($class))
                    throw new UnknownSP($type);

                /** @var self $class */
                $class::init();

                self::$sp = $class;
            }
        }

        return self::$sp;
    }

    /**
     * Get current user
     *
     * @return User|false
     *
     * @throws Exception\BadType
     * @throws Model\Exception\Broken
     * @throws Model\Exception\NotFound
     * @throws ReflectionException
     * @throws MissingAttribute
     * @throws UnknownSP
     */
    public static function getUser() {
        if(is_null(self::$user)) {
            self::$user = false;

            /** @var Auth[] $candidates */
            $candidates = [LocalUser::class, Remote::class, self::getSP()];

            $attributes = [];
            foreach($candidates as $candidate) {
                if(!$candidate || !$candidate::hasUser()) continue;

                $attributes = $candidate::getAttributes();
                break;
            }

            if($attributes) {
                foreach(Config::get('auth.attributes') as $attribute)
                    if(!array_key_exists($attribute, $attributes))
                        throw new MissingAttribute($attribute);

                self::$user = User::fromAuthAttributes($attributes);
            }
        }

        return self::$user;
    }

    /**
     * Init authentication backend (for SPs)
     */
    public static function init() {}

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
     */
    public static function doLogin() {}

    /**
     * Trigger logout process (if any)
     */
    public static function doLogout() {}

    /**
     * Check if auth backend allows new user registration
     *
     * @return bool
     */
    public static function canRegister() {
        return false;
    }

    /**
     * Trigger registration process (if any)
     *
     * @return void|string|Page
     */
    public static function doRegister() {}
}