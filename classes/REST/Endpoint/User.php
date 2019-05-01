<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\REST\Endpoint;


use HereYouGo\Auth;
use HereYouGo\Exception\BadType;
use HereYouGo\Model\Exception\Broken;
use HereYouGo\Model\Exception\NotFound;
use HereYouGo\REST\Endpoint;
use HereYouGo\REST\Request;
use HereYouGo\Router;
use ReflectionException;

/**
 * Class User
 *
 * @package HereYouGo\REST\Endpoint
 */
class User extends Endpoint {
    /**
     * Initialize endpoint (register routes ...)
     */
    public static function init() {
        Router::addRoute('get', '/user/@me', null, self::class.'::getCurrent');
        Router::addRoute('post', '/user/@me', null, self::class.'::authenticate');
        Router::addRoute('post', '/user', null, self::class.'::register');
    }

    /**
     * Cast user to output-able data
     *
     * @param \HereYouGo\Model\Entity\User $user
     *
     * @return array
     */
    public static function cast(\HereYouGo\Model\Entity\User $user) {
        return [
            'id'    => $user->id,
            'email' => $user->email,
            'name'  => $user->name,
        ];
    }

    /**
     * Get currently logged-in user
     *
     * @return array
     *
     * @throws Auth\Exception\UnknownBackend
     * @throws Auth\MissingAttribute
     * @throws Auth\Exception\FailedToLoadUser
     */
    public static function getCurrent() {
        $user = Auth::getUser();
        return $user ? self::cast($user) : null;
    }

    public static function authenticate() {

    }

    public static function register() {
        $sp = Auth::getSP();
        if(!$sp || !$sp::canRegister())
            throw new Auth\Exception\RegistrationDisabled();

        $data = Request::getBody();
        if(!is_array($data))
            throw new BadType('provided data', 'array of user properties');

        if(!array_key_exists('id', $data))
            throw new BadType('id', 'missing user id');


    }
}