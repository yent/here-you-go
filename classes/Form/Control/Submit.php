<?php


namespace HereYouGo\Form\Control;


use HereYouGo\Form\Control;

/**
 * Class Submit
 *
 * @package HereYouGo\Form\Control
 */
class Submit extends Control {
    /**
     * Submit constructor.
     *
     * @param string $label
     * @param string $prompt
     * @param bool $disabled
     */
    public function __construct($label, $prompt = '', $disabled = false) {
        parent::__construct('', '', $label, $prompt, $disabled);
    }
}