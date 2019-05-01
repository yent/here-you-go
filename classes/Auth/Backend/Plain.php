<?php

namespace HereYouGo\Auth\SP;

use HereYouGo\Auth;
use HereYouGo\Exception\BadType;
use HereYouGo\Form\FieldSet;
use HereYouGo\Model\Entity\User;
use HereYouGo\Model\Exception\Broken;
use HereYouGo\Model\Exception\NotFound;
use HereYouGo\UI;
use HereYouGo\UI\Page;
use ReflectionException;
use HereYouGo\Auth\SP\Internal\States;

/**
 * Class Plain
 *
 * @package HereYouGo\Backend\Backend
 */
class Plain extends Embedded {
    /**
     * Backend constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = []) {}

    /**
     * Check if any user logged-in
     *
     * @return bool
     */
    public function hasIdentity(): bool {
        return array_key_exists('user', $_SESSION);
    }

    /**
     * Get current user attributes
     *
     * @return array
     */
    public function getAttributes(): array {
        return $_SESSION['user'];
    }

    /**
     * Trigger login process (if any)
     *
     * @throws BadType
     * @throws Broken
     * @throws ReflectionException
     */
    public function triggerLogin() {
        $target = array_key_exists('target', $_REQUEST) ? base64_decode($_REQUEST['target']) : '';

        if(array_key_exists('user', $_SESSION))
            UI::redirect($target);

        if(array_key_exists('login', $_POST)) { // Got credentials
            $login = $_POST['login'];
            $password = array_key_exists('password', $_POST) ? $_POST['password'] : '';

            if($login && $password) {
                try {
                    /** @var User $user */
                    $user = User::fromPrimaryKey($login);

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
     */
    public function triggerLogout() {
        unset($_SESSION['user']);

        return new UI\Page('logged-out');
    }

    /**
     * Trigger registration process (if any)
     *
     * @return void|string|Page
     */
    public function register() {
        // TODO
    }

    /**
     * Get login form fragment
     *
     * @return FieldSet
     *
     * @throws BadType
     */
    public function getFields(): FieldSet {
        return new FieldSet([
            new Auth\Backend\Embedded\Field\Login(),
            new Auth\Backend\Embedded\Field\Password()
        ]);
    }
}