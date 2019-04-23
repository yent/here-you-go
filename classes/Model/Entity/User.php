<?php


namespace HereYouGo\Model\Entity;


use HereYouGo\Auth\Profile;
use HereYouGo\Exception\BadEmail;
use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Model\Entity;
use HereYouGo\Model\Exception\Broken;
use HereYouGo\Model\Exception\NotFound;
use ReflectionException;

/**
 * Class User
 *
 * @package HereYouGo\Model\Entity
 *
 * @property-read string $id
 * @property string $email
 * @property string $name
 * @property string $auth_args
 */
class User extends Entity {
    /** @var string size=64 primary */
    protected $id = '';

    /** @var string size=128 */
    protected $email = '';

    /** @var string size=128 */
    protected $name = '';

    /** @var string size=128 */
    protected $auth_args = '';

    /**
     * @param Profile $profile
     *
     * @return bool
     *
     * @throws ReflectionException
     */
    public function hasProfile($profile) {
        return $profile::isSatisfiedBy($this);
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

        $changed = false;
        foreach($attributes as $attribute => $value) {
            if($attribute === 'id') continue;

            if($value !== $user->$attribute) {
                $user->$attribute = $value;
                $changed = true;
            }
        }

        if($changed)
            $user->save();

        return $user;
    }

    /**
     * Getter
     *
     * @param string $name
     *
     * @return mixed|Entity|Entity[]
     *
     * @throws BadType
     * @throws Broken
     * @throws NotFound
     * @throws ReflectionException
     * @throws UnknownProperty
     */
    public function __get($name) {
        if(in_array($name, ['id', 'email', 'name', 'auth_args']))
            return $this->$name;

        return parent::__get($name);
    }

    /**
     * Setter
     *
     * @param string $name
     *
     * @param mixed $value
     *
     * @throws BadEmail
     * @throws BadType
     * @throws Broken
     * @throws NotFound
     * @throws ReflectionException
     * @throws UnknownProperty
     */
    public function __set($name, $value) {
        if($name === 'email') {
            if(!filter_var($value, FILTER_VALIDATE_EMAIL))
                throw new BadEmail($value);

            $this->email = (string)$value;

        } else if($name === 'name') {
            $this->name = (string)$value;

        } else if($name === 'auth_args') {
            $this->auth_args = (string)$value;

        } else {
            parent::__set($name, $value);
        }
    }
}