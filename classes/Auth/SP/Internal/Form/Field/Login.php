<?php


namespace HereYouGo\Auth\SP\Internal\Form\Field;


use HereYouGo\Form\Constraint\Required;
use HereYouGo\Form\ConstraintCollection;
use HereYouGo\Form\Field\Text;
use HereYouGo\UI\Locale;

/**
 * Class Login
 *
 * @package HereYouGo\Auth\SP\Internal\Form\Field
 */
class Login extends Text {
    /**
     * Login constructor.
     */
    public function __construct() {
        parent::__construct('login', Locale::translate('auth.internal-login.login'), new ConstraintCollection([
            new Required()
        ]));
    }
}