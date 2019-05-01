<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Auth\Backend\Embedded;

use HereYouGo\Auth;
use HereYouGo\Exception\BadType;
use HereYouGo\Form as FormBase;
use HereYouGo\UI\Locale;

/**
 * Class Form
 *
 * @package HereYouGo\Backend\Backend\Embedded\Form
 */
class Form extends FormBase {
    /**
     * Form constructor.
     *
     * @param string $target
     *
     * @throws BadType
     * @throws Auth\Exception\UnknownBackend
     */
    public function __construct($target) {
        $fieldsets = [];

        foreach(Auth::getEmbeddableBackends() as $backend)
            $fieldsets[] = $backend->getFields();

        $controls = [new FormBase\Control\Custom('log-in', Locale::translate('auth.log-in'))];

        if(Auth::getRegistrableBackends())
            $controls[] = new FormBase\Control\Custom('register', Locale::translate('auth.register'));

        parent::__construct('log-in', $fieldsets, $controls);

        $this->addAttributes([
            'class' => 'col-md-2 offset-md-5',
            'data-target' => $target
        ]);
    }
}