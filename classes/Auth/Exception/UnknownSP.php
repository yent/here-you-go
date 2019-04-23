<?php


namespace HereYouGo\Auth;


use HereYouGo\Exception\Detailed;

/**
 * Class UnknownSP
 *
 * @package HereYouGo\Auth
 */
class UnknownSP extends Detailed {
    /**
     * UnknownSP constructor.
     *
     * @param string $type
     */
    public function __construct($type) {
        parent::__construct('unknown_sp_type', ['type' => $type]);
    }
}