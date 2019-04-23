<?php


namespace HereYouGo\Auth;


use HereYouGo\Exception\Detailed;

/**
 * Class MissingAttribute
 *
 * @package HereYouGo\Auth
 */
class MissingAttribute extends Detailed {
    /**
     * MissingAttribute constructor.
     *
     * @param string $attribute
     */
    public function __construct($attribute) {
        parent::__construct('missing_attribute', [], ['attribute' => $attribute], 400);
    }
}