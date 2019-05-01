<?php


namespace HereYouGo\Auth\Backend\Embedded\Field;


use HereYouGo\Form\Constraint\Required;
use HereYouGo\Form\ConstraintCollection;
use HereYouGo\Form\Field\Text;
use HereYouGo\UI\Locale;

/**
 * Class Login
 *
 * @package HereYouGo\Backend\Backend\Embedded\Field
 */
class Login extends Text {
    /**
     * Login constructor.
     */
    public function __construct() {
        parent::__construct('login', Locale::translate('auth.embedded.login'), new ConstraintCollection([
            new Required()
        ]));
    }
}