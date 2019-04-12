<?php


namespace HereYouGo\Model\Entity;


use HereYouGo\Auth\Profile;
use HereYouGo\Exception\BadType;
use HereYouGo\Model\Entity;
use HereYouGo\Model\Exception\Broken;
use HereYouGo\Model\Exception\NotFound;
use ReflectionException;

/**
 * Class User
 *
 * @package HereYouGo\Model\Entity
 */
class User extends Entity {
    /**
     * @param Profile $profile
     *
     * @return bool
     *
     * @throws ReflectionException
     */
    public function hasProfile($profile) {
        return $profile::isSatifiedBy($this);
    }

    /**
     * Get user from authentication attributes
     *
     * @param array $attributes
     *
     * @return self
     *
     * @throws Broken
     * @throws ReflectionException
     * @throws BadType
     * @throws NotFound
     */
    public static function fromAuthAttributes($attributes) {
        if(!array_key_exists('id', $attributes))
            throw new Broken(static::class, "missing id key");

        /** @var self $user */
        $user = self::fromPk(['id' => $attributes['id']]);

        unset($attributes['id']);

        // TODO update attributes

        return $user;
    }
}