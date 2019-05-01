<?php


namespace HereYouGo\Auth\Backend\Embedded\Field;


use HereYouGo\Form\Constraint\Required;
use HereYouGo\Form\ConstraintCollection;
use HereYouGo\Form\Field\Password as PasswordField;
use HereYouGo\UI\Locale;

/**
 * Class Password
 *
 * @package HereYouGo\Backend\Backend\Embedded\Field
 */
class Password extends PasswordField {
    /**
     * Password constructor.
     */
    public function __construct() {
        parent::__construct('password', Locale::translate('auth.embedded.password'), new ConstraintCollection([
            new Required()
        ]));
    }
}