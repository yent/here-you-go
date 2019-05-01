<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Form\Control;


use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Form\Control;

/**
 * Class Submit
 *
 * @package HereYouGo\Form\Control
 *
 * @property-read string $action
 */
class Custom extends Control {
    /** @var string */
    protected $action = '';

    /**
     * Custom action constructor.
     *
     * @param string $action
     * @param string $label
     * @param string $prompt
     * @param bool $disabled
     */
    public function __construct($action, $label, $prompt = '', $disabled = false) {
        parent::__construct('', '', $label, $prompt, $disabled);

        $this->action = $action;
    }

    /**
     * Getter
     *
     * @param string $name
     *
     * @return mixed|string
     *
     * @throws UnknownProperty
     */
    public function __get($name) {
        if($name === 'action')
            return $this->action;

        return parent::__get($name);
    }
}