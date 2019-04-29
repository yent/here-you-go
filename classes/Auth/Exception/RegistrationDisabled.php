<?php


namespace HereYouGo\Auth\Exception;


use HereYouGo\Exception\Detailed;

/**
 * Class RegistrationDisabled
 *
 * @package HereYouGo\Auth\Exception
 */
class RegistrationDisabled extends Detailed {
    /**
     * RegistrationDisabled constructor.
     */
    public function __construct() {
        parent::__construct('registration_disabled', [], [], 403);
    }
}