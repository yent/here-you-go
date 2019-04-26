<?php

namespace HereYouGo\Auth\SP;

use HereYouGo\Auth;
use HereYouGo\Exception\BadType;
use HereYouGo\Model\Entity\User;
use HereYouGo\Model\Exception\Broken;
use HereYouGo\Model\Exception\NotFound;
use HereYouGo\UI;
use ReflectionException;
use HereYouGo\Auth\SP\Internal\States;

/**
 * Class Internal
 *
 * @package HereYouGo\Auth\SP
 */
class Internal extends Auth {
    /**
     * Check if any user logged-in
     *
     * @return bool
     */
    public static function hasUser(): bool {
        return array_key_exists('user', $_SESSION);
    }

    /**
     * Get current user attributes
     *
     * @return array
     */
    public static function getAttributes(): array {
        return $_SESSION['user'];
    }

    /**
     * Trigger login process (if any)
     *
     * @throws BadType
     * @throws Broken
     * @throws ReflectionException
     */
    public static function doLogin() {
        $target = array_key_exists('target', $_REQUEST) ? base64_decode($_REQUEST['target']) : '';

        if(array_key_exists('user', $_SESSION))
            UI::redirect($target);

        if(array_key_exists('login', $_POST)) { // Got credentials
            $login = $_POST['login'];
            $password = array_key_exists('password', $_POST) ? $_POST['password'] : '';

            if($login && $password) {
                try {
                    /** @var User $user */
                    $user = User::fromPk($login);

                    if(Auth\Password::verify($password, $user->auth_args)) {
                        $_SESSION['user'] = ['id' => $user->id, 'email' => $user->email, 'name' => $user->name];

                        // got user, goto target
                        UI::redirect($target);
                    }
                } catch(NotFound $e) {}

                // still here ? then display login template with unknown login/password message
                return new UI\Page('internal-login', ['target' => $target, 'state' => States::UNKNOWN_USER]);

            } else {
                // display login template with missing credentials message
                return new UI\Page('internal-login', ['target' => $target, 'state' => States::MISSING_CREDENTIAL]);
            }
        }

        // display login template
        return new UI\Page('internal-login', ['target' => $target, 'state' => States::NONE]);
    }

    /**
     * Trigger logout process (if any)
     *
     * @return string
     *
     * @throws UI\Exception\TemplateNotFound
     */
    public static function doLogout() {
        unset($_SESSION['user']);

        return new UI\Page('logged-out');
    }
}