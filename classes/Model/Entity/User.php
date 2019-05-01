<?php


namespace HereYouGo\Model\Entity;


use HereYouGo\Auth\MissingAttribute;
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
 * @property string $auth
 */
class User extends Entity {
    /** @var string @db size=64 primary */
    protected $id = '';

    /** @var string @db size=128 */
    protected $email = '';

    /** @var string @db size=128 */
    protected $name = '';

    /** @var mixed|array @db type=text convert=JSON */
    protected $auth = [];

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
     * @throws BadEmail
     * @throws BadType
     * @throws Broken
     * @throws NotFound
     * @throws ReflectionException
     * @throws UnknownProperty
     * @throws MissingAttribute
     */
    public static function fromAttributes($attributes) {
        if(!array_key_exists('id', $attributes))
            throw new MissingAttribute('id');

        /** @var self $user */
        $user = self::fromPrimaryKey(['id' => $attributes['id']]);

        $changed = false;
        foreach($attributes as $attribute => $value) {
            if($attribute === 'id') continue;

            if($value !== $user->__get($attribute)) {
                $user->__set($attribute, $value);
                $changed = true;
            }
        }

        if($changed)
            $user->save();

        return $user;
    }

    /**
     * User constructor.
     *
     * @param string $id
     * @param string $email
     * @param string $name
     *
     * @throws BadType
     * @throws Broken
     * @throws NotFound
     * @throws ReflectionException
     */
    public function __construct($id, $email, $name = '') {
        if(!$id)
            throw new BadType($id, 'user id');

        if(count(self::all('id = :id', [':id' => $id])))
            throw new BadType($id, 'user id already in use');

        $this->id = $id;

        if(!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
            throw new BadType($email, 'email');

        $this->email = $email;

        if(!$name)
            $name = substr($email, 0, strpos($email, '@'));

        $this->name = $name;

        $this->save();
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
        if(in_array($name, ['id', 'email', 'name', 'auth']))
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

        } else if($name === 'auth') {
            $this->auth_args = $value;

        } else {
            parent::__set($name, $value);
        }
    }
}