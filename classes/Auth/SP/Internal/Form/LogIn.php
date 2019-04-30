<?php


namespace HereYouGo\Auth\SP\Internal\Form;

use HereYouGo\Auth\SP\Internal\Form\Field\Login as LoginField;
use HereYouGo\Auth\SP\Internal\Form\Field\Password as PasswordField;
use HereYouGo\Exception\BadType;
use HereYouGo\Form;
use HereYouGo\UI\Locale;

/**
 * Class LogIn
 *
 * @package HereYouGo\Auth\SP\Internal\Form
 */
class LogIn extends Form {
    /**
     * LogIn constructor.
     *
     * @param string $target
     * @param string $state
     *
     * @throws BadType
     */
    public function __construct($target, $state) {
        $fields = [new Form\Field\Hidden('target', $target)];

        if($state) // TODO move to JS
            $fields[] = (new Form\Fragment('div', ['class' => 'alert alert-danger']))->wrap(Locale::translate("internal-login.$state"));

        $fields[] = new LoginField();
        $fields[] = new PasswordField();

        parent::__construct('internal-log-in', $fields, [new Form\Control\Submit(Locale::translate('auth.log-in'))]);

        $this->addAttributes(['class' => 'col-md-2 offset-md-5']);
    }
}