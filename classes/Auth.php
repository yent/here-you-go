<?php


namespace HereYouGo;


use Exception;
use HereYouGo\Auth\Backend;
use HereYouGo\Auth\Exception\FailedToLoadUser;
use HereYouGo\Auth\Exception\RegistrationDisabled;
use HereYouGo\Auth\Exception\UnknownBackend;
use HereYouGo\Auth\LocalUser;
use HereYouGo\Auth\Remote;
use HereYouGo\Auth\Backend\NewUserPolicy;
use HereYouGo\Auth\Backend\Triggerable;
use HereYouGo\Model\Entity\User;
use HereYouGo\UI\Page;
use HereYouGo\Auth\MissingAttribute;

/**
 * Class Backend
 *
 * @package HereYouGo
 */
class Auth {
    /** @var User|null */
    private static $user = null;

    /** @var Backend|null */
    private static $backends = null;

    /**
     * Get configured backends if any
     *
     * @return Backend[]
     *
     * @throws UnknownBackend
     */
    public static function getBackends() {
        if(is_null(self::$backends)) {
            self::$backends = [];

            $config = Config::get('auth.backend');
            if(array_key_exists('type', $config))
                $config = [$config];

            foreach($config as $backend) {
                if(array_key_exists('enabled', $backend) && !$backend['enabled'])
                    continue;

                if(!array_key_exists('type', $backend))
                    throw new UnknownBackend('no type');

                $class = $backend['type'];
                if(strrpos($class, '\\') === false) // default backends, plugins provide full class name
                    $class = 'HereYouGo\\Auth\\Backend\\'.ucfirst($class);

                if(!Autoloader::exists($class))
                    throw new UnknownBackend($backend['type']);

                self::$backends[] = new $class;
            }
        }

        return self::$backends;
    }

    /**
     * Get embeddable backends
     *
     * @return Backend\Embedded[]
     *
     * @throws UnknownBackend
     */
    public static function getEmbeddableBackends() {
        return array_filter(self::getBackends(), function(Backend $backend) {
            return $backend instanceof Backend\Embedded;
        });
    }

    /**
     * Get registrable backends
     *
     * @return Backend[]
     *
     * @throws UnknownBackend
     */
    public static function getRegistrableBackends() {
        return array_filter(self::getBackends(), function(Backend $backend) {
            return $backend->newUsers() === NewUserPolicy::REGISTER;
        });
    }

    /**
     * Get current user
     *
     * @return User|false
     *
     * @throws MissingAttribute
     * @throws UnknownBackend
     * @throws FailedToLoadUser
     */
    public static function getUser() {
        if(is_null(self::$user)) {
            self::$user = false;

            $backends = self::getBackends();

            // add special backends
            $remote = Config::get('auth.remote');
            if($remote && array_key_exists('enabled', $remote) && $remote['enabled'])
                array_unshift($backends, new Remote($remote));

            array_unshift($backends, new LocalUser());

            foreach($backends as $backend) {
                if(!$backend->hasIdentity()) continue;

                $attributes = $backend->getAttributes();

                foreach(['id', 'email'] as $attribute)
                    if(!array_key_exists($attribute, $attributes))
                        throw new MissingAttribute($attribute);

                try {
                    self::$user = User::fromAttributes($attributes);

                } catch(Exception $e) {
                    throw new FailedToLoadUser($attributes);
                }

                break;
            }
        }

        return self::$user;
    }

    /**
     * Trigger login process (if any)
     *
     * @return void|string|Page
     *
     * @throws UnknownBackend
     */
    public static function logIn() {
        $backends = self::getBackends();

        if(count($backends) === 1 && ($backends[0] instanceof Triggerable)) {
            /** @var Triggerable[] $backends */
            return $backends[0]->logIn();
        }

        return new Page('log-in');
    }

    /**
     * Trigger logout process (if any)
     *
     * @return void|string|Page
     *
     * @throws UnknownBackend
     */
    public static function logOut() {
        $backends = self::getBackends();

        if(count($backends) === 1 && ($backends[0] instanceof Triggerable)) {
            /** @var Triggerable[] $backends */
            return $backends[0]->logOut();
        }

        return new Page('log-out');
    }

    /**
     * Trigger registration process (if any)
     *
     * @return void|string|Page
     *
     * @throws UnknownBackend
     * @throws RegistrationDisabled
     */
    public static function register() {
        $registrable = self::getRegistrableBackends();
        if(!$registrable)
            throw new RegistrationDisabled();

        array_shift($registrable)->register();
    }
}