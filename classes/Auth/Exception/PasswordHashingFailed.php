<?php


namespace HereYouGo\Auth\Exception;


use HereYouGo\Exception\Detailed;

/**
 * Class PasswordHashingFailed
 *
 * @package HereYouGo\Backend\Exception
 */
class PasswordHashingFailed extends Detailed {
    /**
     * PasswordHashingFailed constructor.
     */
    public function __construct() {
        parent::__construct('password_hashing_failed');
    }
}