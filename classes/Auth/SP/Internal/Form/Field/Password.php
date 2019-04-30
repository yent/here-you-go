<?php


namespace HereYouGo\Auth\SP\Internal\Form\Field;


use HereYouGo\Form\Constraint\Required;
use HereYouGo\Form\ConstraintCollection;
use HereYouGo\Form\Field\Password as PasswordField;
use HereYouGo\UI\Locale;

/**
 * Class Password
 *
 * @package HereYouGo\Auth\SP\Internal\Form\Field
 */
class Password extends PasswordField {
    /**
     * Password constructor.
     */
    public function __construct() {
        parent::__construct('password', Locale::translate('auth.internal-login.password'), new ConstraintCollection([
            new Required()
        ]));
    }
}